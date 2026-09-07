<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Lógica central de respaldo (BD + archivos), usada tanto por el botón
 * manual del panel (BackupController) como por el comando programado
 * (sge:backup). Nunca registra la contraseña de BD en logs ni la pasa por
 * argumentos de shell (va por variable de entorno del subproceso).
 */
class BackupService
{
    public function directorioDestino(): string
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * @return array{ok: bool, path: ?string, filename: ?string, size: ?int, error: ?string}
     */
    public function respaldarBaseDatos(): array
    {
        $db   = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path     = $this->directorioDestino() . DIRECTORY_SEPARATOR . $filename;

        $cmd = sprintf(
            '%s --host=%s --port=%s -u%s --single-transaction --routines --triggers %s',
            escapeshellarg(config('backup.mysqldump_path', 'mysqldump')),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($db)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $path, 'w'],
            2 => ['pipe', 'w'],
        ];

        // $_ENV puede estar vacío según variables_order de php.ini (pasa en
        // este servidor: "GPCS", sin la "E") — sin las variables del sistema
        // (SystemRoot, PATH, etc.) mysqldump falla en Windows al no poder
        // inicializar Winsock. getenv() sin argumentos siempre trae el
        // entorno real del proceso, sin depender de esa configuración.
        $env = array_merge(getenv(), ['MYSQL_PWD' => (string) $pass]);

        $process = @proc_open($cmd, $descriptors, $pipes, null, $env);

        if (! is_resource($process)) {
            return ['ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => 'No se pudo iniciar mysqldump. Verifica que esté disponible en el PATH del servidor.'];
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($process);

        $minimo = (int) config('backup.tamano_minimo_bytes', 100);

        if ($code !== 0 || ! file_exists($path) || filesize($path) < $minimo) {
            @unlink($path);

            return ['ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => 'mysqldump terminó con error: ' . ($stderr ?: 'archivo resultante vacío o inválido.')];
        }

        return ['ok' => true, 'path' => $path, 'filename' => $filename, 'size' => filesize($path), 'error' => null];
    }

    /**
     * Comprime storage/app/public (carnets/fotos, entregas de Classroom,
     * boletines generados, branding, planes de clase, etc.) en un .zip.
     *
     * @return array{ok: bool, path: ?string, filename: ?string, size: ?int, error: ?string}
     */
    public function respaldarArchivos(?string $origen = null): array
    {
        $origen = $origen ?? config('backup.archivos_origen', storage_path('app/public'));

        if (! is_dir($origen)) {
            return ['ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => "El directorio de archivos a respaldar no existe: {$origen}"];
        }

        $filename = 'files_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $path     = $this->directorioDestino() . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => "No se pudo crear el archivo zip en {$path}"];
        }

        $origenReal = rtrim(realpath($origen), DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($origenReal, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $localName = ltrim(str_replace($origenReal, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), str_replace(DIRECTORY_SEPARATOR, '/', $localName));
        }

        $zip->close();

        $minimo = (int) config('backup.tamano_minimo_bytes', 100);

        if (! file_exists($path) || filesize($path) < $minimo) {
            @unlink($path);

            return ['ok' => false, 'path' => null, 'filename' => null, 'size' => null,
                'error' => 'El zip de archivos resultó vacío o inválido.'];
        }

        return ['ok' => true, 'path' => $path, 'filename' => $filename, 'size' => filesize($path), 'error' => null];
    }

    /**
     * Sube una copia del backup local a un disco remoto (típicamente 's3'),
     * para no depender únicamente del mismo disco físico donde vive la app.
     * El backup local YA se generó con éxito antes de llamar esto — una
     * falla aquí es un problema de durabilidad extra, no del backup en sí,
     * así que se reporta pero nunca hace fallar la corrida completa.
     *
     * @return array{ok: bool, error: ?string}
     */
    public function subirDestinoRemoto(string $path, string $filename): array
    {
        $disco = config('backup.disco', 'local');

        if ($disco === 'local' || ! $disco) {
            return ['ok' => true, 'error' => null];
        }

        try {
            $subido = Storage::disk($disco)->putFileAs('backups', new \Illuminate\Http\File($path), $filename);

            if ($subido === false) {
                return ['ok' => false, 'error' => "No se pudo subir {$filename} al disco remoto '{$disco}'."];
            }

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => "Error subiendo {$filename} al disco remoto '{$disco}': " . $e->getMessage()];
        }
    }

    /**
     * Elimina backups (BD y archivos) con más de $dias días de antigüedad.
     * Solo toca archivos con el patrón backup_*.sql / files_*.zip dentro del
     * directorio de backups — nunca otros archivos.
     */
    public function aplicarRetencion(int $dias): int
    {
        $dir = $this->directorioDestino();
        $limite = Carbon::now()->subDays($dias)->getTimestamp();
        $eliminados = 0;

        foreach (glob($dir . DIRECTORY_SEPARATOR . 'backup_*.sql') ?: [] as $file) {
            if (filemtime($file) < $limite && @unlink($file)) {
                $eliminados++;
            }
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . 'files_*.zip') ?: [] as $file) {
            if (filemtime($file) < $limite && @unlink($file)) {
                $eliminados++;
            }
        }

        return $eliminados;
    }
}
