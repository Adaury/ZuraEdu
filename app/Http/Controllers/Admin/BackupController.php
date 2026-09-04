<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupRun;
use App\Services\BackupService;
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

        $ultimoExitoso = BackupRun::ultimoExitoso();

        return view('admin.sistema.backup', compact('backups', 'ultimoExitoso'));
    }

    public function crear(BackupService $service)
    {
        $inicio     = now();
        $bd         = $service->respaldarBaseDatos();
        $finalizado = now();

        if (! $bd['ok']) {
            BackupRun::create([
                'iniciado_en'       => $inicio,
                'finalizado_en'     => $finalizado,
                'duracion_segundos' => max(0, $finalizado->getTimestamp() - $inicio->getTimestamp()),
                'estado'            => 'fallido',
                'etapa_fallo'       => 'backup_bd',
                'error_mensaje'     => $bd['error'],
            ]);

            return back()->with('error', 'Error al generar el backup: ' . $bd['error']);
        }

        BackupRun::create([
            'iniciado_en'       => $inicio,
            'finalizado_en'     => $finalizado,
            'duracion_segundos' => max(0, $finalizado->getTimestamp() - $inicio->getTimestamp()),
            'estado'            => 'exitoso',
            'bd_archivo'        => $bd['filename'],
            'bd_tamano_bytes'   => $bd['size'],
        ]);

        return back()->with('success', "Backup creado: {$bd['filename']} (" . $this->formatBytes($bd['size']) . ')');
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
