<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    /** Descarga un respaldo del servidor (solo superadministrador, ver rutas superadmin.respaldos.*). */
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
}
