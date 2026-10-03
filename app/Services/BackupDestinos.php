<?php

namespace App\Services;

use App\Models\BackupConfiguracion;
use Carbon\Carbon;

/**
 * Reparte cada archivo de respaldo (ya generado en storage/app/backups) a los destinos extra elegidos en la pantalla del
 * superadministrador: una carpeta local de sincronización y Google Drive. El respaldo local siempre se genera primero; una falla en un
 * destino se registra por destino pero nunca invalida el respaldo ya creado.
 */
class BackupDestinos
{
    public function __construct(private BackupConfiguracion $cfg)
    {
    }

    public function drive(): GoogleDriveService
    {
        return new GoogleDriveService($this->cfg);
    }

    public function hayDestinosActivos(): bool
    {
        return $this->cfg->carpeta_local_activa || $this->cfg->drive_activo;
    }

    /**
     * Valida y prepara una carpeta local de destino: ruta absoluta, sin «..», fuera de las carpetas públicas del sitio
     * (un respaldo nunca debe quedar accesible por URL) y escribible. La crea si no existe.
     *
     * @return string|null mensaje de error, o null si es válida
     */
    public static function validarCarpeta(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);
        if ($ruta === '') {
            return 'Indica la ruta de la carpeta.';
        }
        if (! preg_match('~^([A-Za-z]:[\\\\/]|\\\\\\\\|/)~', $ruta)) {
            return 'La ruta debe ser absoluta (por ejemplo D:\\Respaldos\\ZuraEdu o /var/respaldos/zuraedu).';
        }
        if (preg_match('~(^|[\\\\/])\.\.([\\\\/]|$)~', $ruta)) {
            return 'La ruta no puede contener «..».';
        }

        $norm = fn (string $p) => rtrim(strtolower(str_replace('\\', '/', $p)), '/');
        $candidata = $norm($ruta);
        foreach ([public_path(), storage_path('app/public')] as $prohibida) {
            $p = $norm((string) (realpath($prohibida) ?: $prohibida));
            if ($candidata === $p || str_starts_with($candidata . '/', $p . '/')) {
                return 'Esa carpeta es pública (accesible por URL). Elige una carpeta fuera del sitio web.';
            }
        }

        if (! is_dir($ruta) && ! @mkdir($ruta, 0755, true) && ! is_dir($ruta)) {
            return 'No se pudo crear la carpeta. Revisa la ruta y los permisos.';
        }
        if (! is_writable($ruta)) {
            return 'La carpeta existe pero el servidor no puede escribir en ella.';
        }

        return null;
    }

    /**
     * Copia el archivo a cada destino activo.
     *
     * @return array<string, array{ok: bool, error: ?string, detalle?: string}>
     */
    public function distribuir(string $path, string $filename): array
    {
        $resultado = [];

        if ($this->cfg->carpeta_local_activa) {
            $resultado['carpeta_local'] = $this->copiarACarpetaLocal($path, $filename);
        }

        if ($this->cfg->drive_activo) {
            try {
                $subido = $this->drive()->subir($path, $filename);
                $resultado['drive'] = ['ok' => true, 'error' => null, 'detalle' => $subido['name']];
            } catch (\Throwable $e) {
                $resultado['drive'] = ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        return $resultado;
    }

    /** @return array{ok: bool, error: ?string, detalle?: string} */
    public function copiarACarpetaLocal(string $path, string $filename): array
    {
        $error = self::validarCarpeta($this->cfg->carpeta_local_ruta);
        if ($error) {
            return ['ok' => false, 'error' => $error];
        }

        $destino = rtrim($this->cfg->carpeta_local_ruta, '\\/') . DIRECTORY_SEPARATOR . basename($filename);
        if (! @copy($path, $destino) || filesize($destino) !== filesize($path)) {
            @unlink($destino);

            return ['ok' => false, 'error' => "No se pudo copiar {$filename} a la carpeta de sincronización."];
        }

        return ['ok' => true, 'error' => null, 'detalle' => $destino];
    }

    /**
     * Retención en los destinos activos (mismos patrones que la local: solo backup_*.sql y files_*.zip).
     *
     * @return array<string, int|string> eliminados por destino (o el mensaje de error)
     */
    public function aplicarRetencion(int $dias): array
    {
        $out = [];

        if ($this->cfg->carpeta_local_activa && self::validarCarpeta($this->cfg->carpeta_local_ruta) === null) {
            $limite = Carbon::now()->subDays($dias)->getTimestamp();
            $n = 0;
            $carpeta = rtrim($this->cfg->carpeta_local_ruta, '\\/');
            foreach (array_merge(glob($carpeta . DIRECTORY_SEPARATOR . 'backup_*.sql') ?: [], glob($carpeta . DIRECTORY_SEPARATOR . 'files_*.zip') ?: []) as $f) {
                if (filemtime($f) < $limite && @unlink($f)) {
                    $n++;
                }
            }
            $out['carpeta_local'] = $n;
        }

        if ($this->cfg->drive_activo) {
            try {
                $out['drive'] = $this->drive()->aplicarRetencion($dias);
            } catch (\Throwable $e) {
                $out['drive'] = 'error: ' . $e->getMessage();
            }
        }

        return $out;
    }
}
