<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $backups = collect(Storage::disk('local')->files('backups'))
            ->map(function ($file) {
                return [
                    'name'    => basename($file),
                    'path'    => $file,
                    'size'    => $this->formatBytes(Storage::disk('local')->size($file)),
                    'date'    => \Carbon\Carbon::createFromTimestamp(
                        Storage::disk('local')->lastModified($file)
                    )->format('d/m/Y H:i'),
                    'ts'      => Storage::disk('local')->lastModified($file),
                ];
            })
            ->sortByDesc('ts')
            ->values();

        return view('admin.sistema.backup', compact('backups'));
    }

    public function crear()
    {
        $db   = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');

        $filename  = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $backupDir = storage_path('app/backups');
        $localPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Pasamos la contraseña via variable de entorno para que no aparezca
        // en la lista de procesos del sistema operativo ni en los logs de shell.
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s -u%s --single-transaction --routines --triggers %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($db)
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $localPath, 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = array_merge($_ENV, ['MYSQL_PWD' => (string) $pass]);

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (! is_resource($process)) {
            return back()->with('error', 'No se pudo iniciar mysqldump. Verifica que esté disponible en el PATH.');
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0 || ! file_exists($localPath) || filesize($localPath) < 100) {
            return back()->with('error', 'Error al generar el backup: ' . ($stderr ?: 'resultado vacío.'));
        }

        return back()->with('success', "Backup creado: $filename (" . $this->formatBytes(filesize($localPath)) . ')');
    }

    public function descargar(Request $request)
    {
        $path = $this->resolverRutaBackup($request->file);

        if ($path === null) {
            return back()->with('error', 'Archivo no encontrado.');
        }

        return response()->download($path, basename($path));
    }

    public function eliminar(Request $request)
    {
        $path = $this->resolverRutaBackup($request->file);

        if ($path !== null) {
            unlink($path);
        }

        return back()->with('success', 'Backup eliminado.');
    }

    /**
     * Resuelve y valida que el nombre de archivo pertenezca al directorio
     * de backups, evitando path traversal (ej: ../../etc/passwd).
     */
    private function resolverRutaBackup(?string $nombre): ?string
    {
        if (! $nombre) {
            return null;
        }

        $backupDir = realpath(storage_path('app/backups'));
        $candidate = realpath($backupDir . DIRECTORY_SEPARATOR . basename($nombre));

        // El archivo debe existir y estar estrictamente dentro del directorio.
        if ($candidate === false || strpos($candidate, $backupDir . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $candidate;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
