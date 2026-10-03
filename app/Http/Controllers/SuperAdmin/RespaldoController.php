<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BackupConfiguracion;
use App\Models\BackupRun;
use App\Services\BackupDestinos;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Respaldo automático de la PLATAFORMA (base compartida de todos los colegios): hora elegida, carpeta local de sincronización y Google Drive.
 * Solo el superadministrador: el volcado contiene los datos de todos los centros, así que ningún administrador de colegio puede verlo.
 */
class RespaldoController extends Controller
{
    public const ZONAS = [
        'America/Santo_Domingo' => 'República Dominicana (AST, UTC−4)',
        'America/New_York'      => 'Nueva York / Miami',
        'America/Mexico_City'   => 'Ciudad de México',
        'America/Bogota'        => 'Bogotá / Lima',
        'America/Caracas'       => 'Caracas',
        'America/Argentina/Buenos_Aires' => 'Buenos Aires',
        'Europe/Madrid'         => 'Madrid',
        'UTC'                   => 'UTC',
    ];

    public function index()
    {
        $cfg = BackupConfiguracion::actual();

        $disco = Storage::disk('local');
        $backups = collect($disco->files('backups'))->map(fn ($f) => [
            'name' => basename($f),
            'size' => $this->bytes($disco->size($f)),
            'date' => \Carbon\Carbon::createFromTimestamp($disco->lastModified($f))->timezone($cfg->zona_horaria ?: 'UTC')->format('d/m/Y H:i'),
            'ts'   => $disco->lastModified($f),
        ])->sortByDesc('ts')->values();

        // El respaldo depende de que el programador de tareas (cron / Programador de tareas de Windows) llame cada minuto a schedule:run
        $latido = Cache::get('scheduler_latido');
        $programadorActivo = $latido && (now()->getTimestamp() - (int) $latido) < 300;

        return view('superadmin.respaldos.index', [
            'cfg'               => $cfg,
            'existe'            => $cfg->exists,
            'backups'           => $backups,
            'historial'         => BackupRun::latest('iniciado_en')->limit(12)->get(),
            'ultimoExitoso'     => BackupRun::ultimoExitoso(),
            'proxima'           => $cfg->proximaEjecucion(),
            'zonas'             => self::ZONAS,
            'dias'              => BackupConfiguracion::DIAS,
            'driveRedirect'     => route('superadmin.respaldos.drive.callback'),
            'programadorActivo' => $programadorActivo,
            'rutaProyecto'      => base_path(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'activo'               => 'sometimes|boolean',
            'frecuencia'           => 'required|in:diaria,semanal',
            'dia_semana'           => 'required|integer|between:0,6',
            'hora'                 => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'zona_horaria'         => 'required|timezone',
            'retencion_dias'       => 'required|integer|between:1,365',
            'incluir_archivos'     => 'sometimes|boolean',
            'carpeta_local_activa' => 'sometimes|boolean',
            'carpeta_local_ruta'   => 'nullable|string|max:500',
            'drive_activo'         => 'sometimes|boolean',
            'drive_client_id'      => 'nullable|string|max:300',
            'drive_client_secret'  => 'nullable|string|max:300',
            'drive_carpeta_nombre' => 'required|string|max:120',
        ]);

        $cfg = BackupConfiguracion::query()->firstOrNew([]);

        $cfg->fill([
            'activo'               => $request->boolean('activo'),
            'frecuencia'           => $data['frecuencia'],
            'dia_semana'           => (int) $data['dia_semana'],
            'hora'                 => $data['hora'],
            'zona_horaria'         => $data['zona_horaria'],
            'retencion_dias'       => (int) $data['retencion_dias'],
            'incluir_archivos'     => $request->boolean('incluir_archivos'),
            'carpeta_local_activa' => $request->boolean('carpeta_local_activa'),
            'carpeta_local_ruta'   => filled($data['carpeta_local_ruta'] ?? null) ? trim($data['carpeta_local_ruta']) : null,
            'drive_carpeta_nombre' => trim($data['drive_carpeta_nombre']),
        ]);

        // Carpeta local: se valida y se CREA aquí, para que el error salga al guardar y no a las 2:30 de la madrugada
        if ($cfg->carpeta_local_activa) {
            if ($error = BackupDestinos::validarCarpeta($cfg->carpeta_local_ruta)) {
                return back()->withInput()->with('error', 'Carpeta de sincronización: ' . $error);
            }
        }

        // Credenciales de Google: el secreto solo se reemplaza si se escribe uno nuevo; si cambia el cliente, el permiso anterior ya no vale
        $clienteCambio = filled($data['drive_client_id'] ?? null) && $data['drive_client_id'] !== $cfg->drive_client_id;
        if (array_key_exists('drive_client_id', $data) && $data['drive_client_id'] !== null) {
            $cfg->drive_client_id = trim($data['drive_client_id']);
        }
        if (filled($data['drive_client_secret'] ?? null)) {
            $cfg->drive_client_secret = trim($data['drive_client_secret']);
        }
        if ($clienteCambio && $cfg->exists && $cfg->driveConectado()) {
            $cfg->drive_refresh_token = null;
            $cfg->drive_cuenta = null;
            $cfg->drive_carpeta_id = null;
            $cfg->drive_conectado_en = null;
        }

        $cfg->drive_activo = $request->boolean('drive_activo');
        if ($cfg->drive_activo && ! $cfg->driveConectado()) {
            $cfg->drive_activo = false;
            $cfg->save();

            return back()->with('error', 'Guardado, pero Google Drive quedó desactivado: primero conecta la cuenta con el botón «Conectar con Google».');
        }

        $cfg->save();

        $proxima = $cfg->proximaEjecucion();

        return back()->with('success', 'Configuración guardada.' . ($proxima ? ' Próximo respaldo: ' . $proxima->format('d/m/Y H:i') . ' (' . $cfg->zona_horaria . ').' : ' El respaldo automático está desactivado.'));
    }

    /** Ejecuta el respaldo completo ahora (el mismo comando que el programador: BD + archivos + destinos + retención). */
    public function ejecutar(): RedirectResponse
    {
        @set_time_limit(0);
        $codigo = Artisan::call('sge:backup');
        $run = BackupRun::latest('id')->first();

        if ($codigo !== 0 || ! $run || $run->estado !== 'exitoso') {
            return back()->with('error', 'El respaldo falló' . ($run?->error_mensaje ? ': ' . $run->error_mensaje : '. Revisa storage/logs/backup.log.'));
        }

        $fallos = collect($run->destinos ?? [])->except('retencion')->flatMap(fn ($archivos) => collect($archivos)->filter(fn ($r) => is_array($r) && ($r['ok'] ?? true) === false)->pluck('error'))->unique()->values();

        return back()->with($fallos->isEmpty() ? 'success' : 'error', $fallos->isEmpty()
            ? "Respaldo completado: {$run->bd_archivo}" . ($run->archivos_archivo ? " y {$run->archivos_archivo}." : '.')
            : 'El respaldo se creó en el servidor, pero un destino falló: ' . $fallos->implode(' · '));
    }

    public function probarCarpeta(Request $request): RedirectResponse
    {
        $ruta = $request->input('carpeta_local_ruta') ?: BackupConfiguracion::actual()->carpeta_local_ruta;
        $error = BackupDestinos::validarCarpeta($ruta);

        return back()->withInput()->with($error ? 'error' : 'success', $error ? 'Carpeta de sincronización: ' . $error : 'La carpeta está lista (creada y con permiso de escritura): ' . $ruta);
    }

    // ── Google Drive ──────────────────────────────────────────────────────────

    public function driveConectar(Request $request): RedirectResponse
    {
        $cfg = BackupConfiguracion::query()->first();
        if (! $cfg || blank($cfg->drive_client_id) || blank($cfg->drive_client_secret)) {
            return back()->with('error', 'Guarda primero el «ID de cliente» y el «Secreto de cliente» de Google.');
        }

        $state = Str::random(40);
        $request->session()->put('drive_oauth_state', $state);

        return redirect()->away((new GoogleDriveService($cfg))->urlAutorizacion(route('superadmin.respaldos.drive.callback'), $state));
    }

    public function driveCallback(Request $request): RedirectResponse
    {
        $volver = redirect()->route('superadmin.respaldos.index');
        $esperado = $request->session()->pull('drive_oauth_state');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return $volver->with('error', 'La conexión con Google no se pudo verificar (estado inválido). Inténtalo de nuevo.');
        }
        if ($request->query('error')) {
            return $volver->with('error', 'Google canceló la conexión: ' . $request->query('error'));
        }

        $cfg = BackupConfiguracion::query()->first();
        if (! $cfg) {
            return $volver->with('error', 'Falta la configuración de Google Drive.');
        }

        try {
            (new GoogleDriveService($cfg))->conectarConCodigo((string) $request->query('code'), route('superadmin.respaldos.drive.callback'));
        } catch (\Throwable $e) {
            return $volver->with('error', $e->getMessage());
        }

        return $volver->with('success', 'Google Drive conectado como ' . ($cfg->fresh()->drive_cuenta ?: 'tu cuenta') . '. Pulsa «Probar» y luego activa el respaldo a Drive.');
    }

    public function driveProbar(): RedirectResponse
    {
        $cfg = BackupConfiguracion::query()->first();
        if (! $cfg || ! (new GoogleDriveService($cfg))->conectado()) {
            return back()->with('error', 'Google Drive no está conectado.');
        }
        $r = (new GoogleDriveService($cfg))->probar();

        return back()->with($r['ok'] ? 'success' : 'error', $r['ok']
            ? 'Prueba correcta: se subió y se borró un archivo de prueba en la carpeta «' . $cfg->drive_carpeta_nombre . '» de ' . $r['cuenta'] . '.'
            : 'La prueba con Google Drive falló: ' . $r['error']);
    }

    public function driveDesconectar(): RedirectResponse
    {
        if ($cfg = BackupConfiguracion::query()->first()) {
            (new GoogleDriveService($cfg))->desconectar();
        }

        return back()->with('success', 'Google Drive desconectado. Los respaldos ya subidos siguen en tu Drive.');
    }

    private function bytes(int $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : ($b >= 1024 ? round($b / 1024, 1) . ' KB' : $b . ' B');
    }
}
