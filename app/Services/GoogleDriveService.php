<?php

namespace App\Services;

use App\Models\BackupConfiguracion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Subida de respaldos a Google Drive con OAuth 2.0 (sin librerías externas: solo la API REST de Drive v3).
 *
 * Alcance «drive.file»: la aplicación solo ve y modifica los archivos que ELLA misma crea (la carpeta «ZuraEdu Respaldos» y lo que sube),
 * nunca el resto del Drive del usuario. El token de actualización y el secreto del cliente se guardan cifrados (BackupConfiguracion).
 * Los archivos se suben con el protocolo «reanudable» por tramos, porque el .zip de archivos puede pesar cientos de MB.
 */
class GoogleDriveService
{
    public const URL_AUTORIZACION = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const URL_TOKEN        = 'https://oauth2.googleapis.com/token';
    public const URL_REVOCAR      = 'https://oauth2.googleapis.com/revoke';
    public const URL_USUARIO      = 'https://openidconnect.googleapis.com/v1/userinfo';
    public const API              = 'https://www.googleapis.com/drive/v3';
    public const SUBIDA           = 'https://www.googleapis.com/upload/drive/v3/files';
    public const SCOPES           = 'https://www.googleapis.com/auth/drive.file openid email';

    /** Tamaño de cada tramo de subida: Drive exige múltiplos de 256 KiB (salvo el último). */
    public int $tramo = 8 * 1024 * 1024;

    public function __construct(private BackupConfiguracion $cfg)
    {
    }

    public function conectado(): bool
    {
        return $this->cfg->driveConectado() && filled($this->cfg->drive_client_id) && filled($this->cfg->drive_client_secret);
    }

    public function urlAutorizacion(string $redirectUri, string $state): string
    {
        return self::URL_AUTORIZACION . '?' . http_build_query([
            'client_id'     => $this->cfg->drive_client_id,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'access_type'   => 'offline',   // para recibir el token de actualización
            'prompt'        => 'consent',   // Google solo lo entrega si se pide el consentimiento
            'state'         => $state,
        ]);
    }

    /**
     * Cambia el código de autorización por el token de actualización y guarda la cuenta conectada.
     *
     * @throws \RuntimeException si Google rechaza el código o no devuelve token de actualización
     */
    public function conectarConCodigo(string $codigo, string $redirectUri): void
    {
        $r = Http::asForm()->timeout(30)->post(self::URL_TOKEN, [
            'code'          => $codigo,
            'client_id'     => $this->cfg->drive_client_id,
            'client_secret' => $this->cfg->drive_client_secret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (! $r->successful()) {
            throw new \RuntimeException('Google rechazó la autorización: ' . ($r->json('error_description') ?? $r->json('error') ?? 'HTTP ' . $r->status()));
        }
        if (! $r->json('refresh_token')) {
            throw new \RuntimeException('Google no entregó el permiso permanente. Revoca el acceso de ZuraEdu en tu cuenta de Google y vuelve a conectar.');
        }

        $cuenta = null;
        if ($r->json('access_token')) {
            $cuenta = Http::withToken($r->json('access_token'))->timeout(15)->get(self::URL_USUARIO)->json('email');
        }

        $this->cfg->forceFill([
            'drive_refresh_token' => $r->json('refresh_token'),
            'drive_cuenta'        => $cuenta,
            'drive_conectado_en'  => now(),
            'drive_carpeta_id'    => null,   // cuenta nueva: la carpeta se vuelve a buscar o crear
        ])->save();

        Cache::forget($this->claveToken());
    }

    /** Token de acceso vigente (se renueva con el token de actualización y se guarda en caché hasta poco antes de vencer). */
    public function accessToken(): string
    {
        if (! $this->conectado()) {
            throw new \RuntimeException('Google Drive no está conectado.');
        }

        return Cache::remember($this->claveToken(), 3000, function () {
            $r = Http::asForm()->timeout(30)->post(self::URL_TOKEN, [
                'client_id'     => $this->cfg->drive_client_id,
                'client_secret' => $this->cfg->drive_client_secret,
                'refresh_token' => $this->cfg->drive_refresh_token,
                'grant_type'    => 'refresh_token',
            ]);

            if (! $r->successful() || ! $r->json('access_token')) {
                $motivo = $r->json('error') ?? 'HTTP ' . $r->status();
                throw new \RuntimeException($motivo === 'invalid_grant'
                    ? 'El permiso de Google Drive venció o fue revocado: vuelve a conectar la cuenta.'
                    : "No se pudo renovar el acceso a Google Drive ({$motivo}).");
            }

            return $r->json('access_token');
        });
    }

    /** Devuelve el id de la carpeta de respaldos; la busca y, si no existe (o está en la papelera), la crea. */
    public function asegurarCarpeta(): string
    {
        $token = $this->accessToken();
        $nombre = trim($this->cfg->drive_carpeta_nombre ?: 'ZuraEdu Respaldos');

        if ($this->cfg->drive_carpeta_id) {
            $r = Http::withToken($token)->timeout(20)->get(self::API . '/files/' . $this->cfg->drive_carpeta_id, ['fields' => 'id,trashed']);
            if ($r->successful() && ! $r->json('trashed')) {
                return $this->cfg->drive_carpeta_id;
            }
        }

        $q = "name='" . str_replace("'", "\\'", $nombre) . "' and mimeType='application/vnd.google-apps.folder' and trashed=false";
        $buscar = Http::withToken($token)->timeout(20)->get(self::API . '/files', ['q' => $q, 'fields' => 'files(id,name)', 'pageSize' => 1]);
        $id = $buscar->json('files.0.id');

        if (! $id) {
            $crear = Http::withToken($token)->timeout(20)->post(self::API . '/files?fields=id', ['name' => $nombre, 'mimeType' => 'application/vnd.google-apps.folder']);
            if (! $crear->successful() || ! $crear->json('id')) {
                throw new \RuntimeException('No se pudo crear la carpeta en Google Drive: ' . ($crear->json('error.message') ?? 'HTTP ' . $crear->status()));
            }
            $id = $crear->json('id');
        }

        $this->cfg->forceFill(['drive_carpeta_id' => $id])->save();

        return $id;
    }

    /**
     * Sube un archivo a la carpeta de respaldos con subida reanudable por tramos.
     *
     * @return array{id: string, name: string, size: int}
     */
    public function subir(string $ruta, ?string $nombre = null): array
    {
        $tam = filesize($ruta);
        if ($tam === false || $tam === 0) {
            throw new \RuntimeException('El archivo a subir está vacío o no existe.');
        }
        $nombre ??= basename($ruta);
        $carpeta = $this->asegurarCarpeta();
        $token = $this->accessToken();

        $inicio = Http::withToken($token)->timeout(30)
            ->withHeaders(['X-Upload-Content-Type' => 'application/octet-stream', 'X-Upload-Content-Length' => (string) $tam])
            ->post(self::SUBIDA . '?uploadType=resumable&fields=id,name,size', ['name' => $nombre, 'parents' => [$carpeta]]);

        $sesion = $inicio->header('Location');
        if (! $inicio->successful() || ! $sesion) {
            throw new \RuntimeException('Google Drive no abrió la sesión de subida: ' . ($inicio->json('error.message') ?? 'HTTP ' . $inicio->status()));
        }

        $fh = fopen($ruta, 'rb');
        try {
            $desplazamiento = 0;
            while ($desplazamiento < $tam) {
                fseek($fh, $desplazamiento);
                $datos = fread($fh, $this->tramo);
                $largo = strlen($datos);
                $fin = $desplazamiento + $largo - 1;

                $r = Http::timeout(600)
                    ->withHeaders(['Content-Range' => "bytes {$desplazamiento}-{$fin}/{$tam}"])
                    ->withBody($datos, 'application/octet-stream')
                    ->put($sesion);

                if ($r->status() === 308) {   // tramo recibido, falta el resto
                    $rango = $r->header('Range');
                    $desplazamiento = $rango && preg_match('/bytes=0-(\d+)/', $rango, $m) ? ((int) $m[1]) + 1 : $desplazamiento + $largo;
                    continue;
                }
                if ($r->successful()) {
                    return ['id' => (string) $r->json('id'), 'name' => (string) $r->json('name', $nombre), 'size' => (int) $r->json('size', $tam)];
                }

                throw new \RuntimeException('Google Drive rechazó la subida: ' . ($r->json('error.message') ?? 'HTTP ' . $r->status()));
            }
        } finally {
            fclose($fh);
        }

        throw new \RuntimeException('La subida a Google Drive terminó sin confirmación.');
    }

    /** @return list<array{id: string, name: string, createdTime: string}> */
    public function listar(): array
    {
        $r = Http::withToken($this->accessToken())->timeout(30)->get(self::API . '/files', [
            'q'        => "'" . $this->asegurarCarpeta() . "' in parents and trashed=false",
            'fields'   => 'files(id,name,createdTime,size)',
            'pageSize' => 200,
            'orderBy'  => 'createdTime desc',
        ]);

        return $r->successful() ? ($r->json('files') ?? []) : [];
    }

    public function eliminar(string $id): bool
    {
        return Http::withToken($this->accessToken())->timeout(30)->delete(self::API . '/files/' . $id)->successful();
    }

    /** Retención: borra de la carpeta de respaldos lo más viejo que $dias días (solo nombres backup_*.sql / files_*.zip). */
    public function aplicarRetencion(int $dias): int
    {
        $limite = now()->subDays($dias);
        $borrados = 0;
        foreach ($this->listar() as $f) {
            if (! preg_match('/^(backup_.*\.sql|files_.*\.zip)$/', $f['name'] ?? '')) {
                continue;
            }
            if (isset($f['createdTime']) && \Carbon\Carbon::parse($f['createdTime'])->lt($limite) && $this->eliminar($f['id'])) {
                $borrados++;
            }
        }

        return $borrados;
    }

    /** Prueba de extremo a extremo: sube un archivo pequeño y lo borra. @return array{ok: bool, error: ?string, cuenta: ?string} */
    public function probar(): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zura');
        file_put_contents($tmp, 'ZuraEdu: prueba de conexión ' . now()->toDateTimeString());
        try {
            $subido = $this->subir($tmp, 'prueba_conexion_zuraedu.txt');
            $this->eliminar($subido['id']);

            return ['ok' => true, 'error' => null, 'cuenta' => $this->cfg->drive_cuenta];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'cuenta' => $this->cfg->drive_cuenta];
        } finally {
            @unlink($tmp);
        }
    }

    /** Revoca el permiso en Google (mejor esfuerzo) y borra el token guardado. */
    public function desconectar(): void
    {
        if ($this->cfg->drive_refresh_token) {
            try {
                Http::asForm()->timeout(15)->post(self::URL_REVOCAR, ['token' => $this->cfg->drive_refresh_token]);
            } catch (\Throwable) {
            }
        }
        $this->cfg->forceFill(['drive_refresh_token' => null, 'drive_cuenta' => null, 'drive_carpeta_id' => null, 'drive_conectado_en' => null, 'drive_activo' => false])->save();
        Cache::forget($this->claveToken());
    }

    private function claveToken(): string
    {
        return 'backup_drive_token_' . md5((string) $this->cfg->drive_client_id . $this->cfg->id);
    }
}
