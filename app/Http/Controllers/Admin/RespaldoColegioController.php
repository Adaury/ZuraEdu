<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\RespaldoColegioService;
use Illuminate\Http\Request;

/**
 * Copia de los datos del PROPIO colegio (ZIP de CSV). El tenant sale del usuario autenticado, nunca de la petición.
 * Permisos: `exportar-respaldo-colegio` (Administrador y Director) y `exportar-datos-sensibles` (solo Director).
 */
class RespaldoColegioController extends Controller
{
    public function index(RespaldoColegioService $servicio)
    {
        $this->tenantId();   // 403 si el usuario no pertenece a un colegio

        $puedeSensibles = auth()->user()->can('exportar-datos-sensibles');
        $plan = $servicio->plan($puedeSensibles);

        return view('admin.respaldo_colegio.index', [
            'tablas'         => count($plan),
            'puedeSensibles' => $puedeSensibles,
            'sensiblesFuera' => $puedeSensibles ? [] : RespaldoColegioService::TABLAS_SENSIBLES,
        ]);
    }

    public function descargar(Request $request, RespaldoColegioService $servicio)
    {
        $tenantId = $this->tenantId();
        $user = auth()->user();

        // Los sensibles solo si el usuario tiene el permiso Y los pidió (el valor del navegador nunca concede nada por sí solo)
        $sensibles = $request->boolean('incluir_sensibles') && $user->can('exportar-datos-sensibles');

        $r = $servicio->generar($tenantId, tenant()?->nombre_institucion ?? 'Colegio', $user->name, $sensibles);

        ActivityLog::registrar(
            'respaldo_colegio',
            'Tenant',
            $tenantId,
            sprintf('Descarga de copia de datos del colegio: %d tablas, %s filas%s', $r['tablas'], number_format($r['filas']), $sensibles ? ', incluye datos sensibles' : '')
        );

        $nombre = 'datos_' . \Illuminate\Support\Str::slug(tenant()?->nombre_institucion ?? 'colegio') . '_' . now()->format('Ymd_His') . '.zip';

        return response()->download($r['ruta'], $nombre, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    /** ID del colegio del usuario; si el contexto no coincide con el del usuario, se rechaza. */
    private function tenantId(): int
    {
        $tenant = tenant();
        abort_unless($tenant && (int) auth()->user()->tenant_id === (int) $tenant->id, 403, 'No hay un colegio asociado a su sesión.');

        return (int) $tenant->id;
    }
}
