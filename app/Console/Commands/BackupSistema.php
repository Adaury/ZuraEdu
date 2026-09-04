<?php

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backup automático diario de ZuraEdu: base de datos completa (todos los
 * tenants, es una sola BD compartida) + archivos persistentes en
 * storage/app/public (carnets/fotos, entregas de Classroom, boletines,
 * branding, planes de clase, comprobantes). Programado en Kernel::schedule().
 *
 * Ver docs/BACKUP_ZURAEDU.md.
 */
class BackupSistema extends Command
{
    protected $signature = 'sge:backup {--sin-archivos : Respaldar solo la base de datos, omitir archivos}';

    protected $description = 'Genera un backup de la base de datos y los archivos persistentes, aplica retención y registra el resultado';

    public function handle(BackupService $service): int
    {
        $inicio = now();
        $etapa  = 'inicio';

        Log::channel('backup')->info('BACKUP STARTED', ['fecha' => $inicio->toDateTimeString()]);
        $this->info('Iniciando backup...');

        try {
            $etapa = 'verificacion_configuracion';
            $this->verificarConfiguracion();

            $etapa = 'backup_bd';
            $bd = $service->respaldarBaseDatos();

            if (! $bd['ok']) {
                return $this->fallar($inicio, $etapa, $bd['error']);
            }

            Log::channel('backup')->info('BACKUP DATABASE SUCCESS', [
                'archivo' => $bd['filename'], 'tamano_bytes' => $bd['size'],
            ]);
            $this->info("Backup de BD: {$bd['filename']} ({$this->formatBytes($bd['size'])})");

            $archivos = ['ok' => true, 'filename' => null, 'size' => null];
            $incluirArchivos = ! $this->option('sin-archivos') && config('backup.incluir_archivos', true);

            if ($incluirArchivos) {
                $etapa    = 'backup_archivos';
                $archivos = $service->respaldarArchivos();

                if (! $archivos['ok']) {
                    return $this->fallar($inicio, $etapa, $archivos['error'], [
                        'bd_archivo' => $bd['filename'], 'bd_tamano_bytes' => $bd['size'],
                    ]);
                }

                Log::channel('backup')->info('BACKUP FILES SUCCESS', [
                    'archivo' => $archivos['filename'], 'tamano_bytes' => $archivos['size'],
                ]);
                $this->info("Backup de archivos: {$archivos['filename']} ({$this->formatBytes($archivos['size'])})");
            }

            $etapa      = 'retencion';
            $dias       = (int) config('backup.retencion_dias', 7);
            $eliminados = $service->aplicarRetencion($dias);

            Log::channel('backup')->info('RETENTION SUCCESS', ['eliminados' => $eliminados, 'retencion_dias' => $dias]);

            $finalizado = now();

            BackupRun::create([
                'iniciado_en'           => $inicio,
                'finalizado_en'         => $finalizado,
                'duracion_segundos'     => $this->segundosEntre($inicio, $finalizado),
                'estado'                => 'exitoso',
                'bd_archivo'            => $bd['filename'],
                'bd_tamano_bytes'       => $bd['size'],
                'archivos_archivo'      => $archivos['filename'],
                'archivos_tamano_bytes' => $archivos['size'],
                'eliminados_retencion'  => $eliminados,
            ]);

            Log::channel('backup')->info('BACKUP COMPLETE', [
                'duracion_segundos' => $this->segundosEntre($inicio, $finalizado),
            ]);
            $this->info('Backup completado correctamente.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            return $this->fallar($inicio, $etapa, $e->getMessage());
        }
    }

    private function verificarConfiguracion(): void
    {
        if (! config('database.connections.mysql.database')) {
            throw new \RuntimeException('Configuración de base de datos incompleta (database.connections.mysql).');
        }
    }

    private function fallar(\Illuminate\Support\Carbon $inicio, string $etapa, ?string $mensaje, array $extra = []): int
    {
        $finalizado = now();
        $mensajeSeguro = $mensaje ?: 'Error desconocido';

        Log::channel('backup')->error('BACKUP FAILED', [
            'etapa'   => $etapa,
            'mensaje' => $mensajeSeguro,
            'fecha'   => $finalizado->toDateTimeString(),
        ]);
        $this->error("Backup falló en la etapa '{$etapa}': {$mensajeSeguro}");

        BackupRun::create(array_merge([
            'iniciado_en'       => $inicio,
            'finalizado_en'     => $finalizado,
            'duracion_segundos' => $this->segundosEntre($inicio, $finalizado),
            'estado'            => 'fallido',
            'etapa_fallo'       => $etapa,
            'error_mensaje'     => $mensajeSeguro,
        ], $extra));

        return self::FAILURE;
    }

    /**
     * Duración en segundos entera y no-negativa. diffInSeconds() de Carbon 3
     * puede devolver un float con signo (precisión de microsegundos), lo
     * cual no cabe en la columna unsignedInteger de backup_runs.
     */
    private function segundosEntre(\Illuminate\Support\Carbon $inicio, \Illuminate\Support\Carbon $fin): int
    {
        return max(0, $fin->getTimestamp() - $inicio->getTimestamp());
    }

    private function formatBytes(?int $bytes): string
    {
        if ($bytes === null) return '0 B';
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
