<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class DemoMode
{
    /**
     * Routes that remain fully functional in demo mode.
     */
    protected array $siemprePermitidos = [
        'logout',
        'portal.docente.asistencia.guardar',
        'portal.docente.observaciones.guardar',
        'portal.docente.notif.leer-todas',
        'portal.estudiante.notif.leer',
        'portal.estudiante.notif.leer-todas',
        'portal.padre.notif.leer-todas',
    ];

    /**
     * Route name patterns always blocked in demo mode (besides DELETE).
     */
    protected array $siempreBloqueados = [
        'admin.usuarios.*',
        'admin.sistema.*',
        'admin.backup.*',
        'admin.horarios.configuracion.guardar',
        'admin.horarios.limpiar',
        'admin.periodos.destroy',
        'admin.grupos.destroy',
        'admin.asignaturas.destroy',
        'admin.estudiantes.destroy',
        'admin.docentes.destroy',
        'admin.school-years.destroy',
        'register.post',
        'demo.login',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Only act if session flag is set
        if (! session('demo_mode')) {
            return $next($request);
        }

        // GET / HEAD always pass through
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $route = Route::currentRouteName() ?? '';

        // Always-allowed routes (for demo usability)
        foreach ($this->siemprePermitidos as $pattern) {
            if (Str::is($pattern, $route)) {
                return $next($request);
            }
        }

        // DELETE always blocked
        if ($request->isMethod('DELETE')) {
            return $this->bloquear($request, 'No se pueden eliminar registros en modo DEMO.');
        }

        // Specific blocked routes
        foreach ($this->siempreBloqueados as $pattern) {
            if (Str::is($pattern, $route)) {
                return $this->bloquear($request, 'Esta acción está bloqueada en modo DEMO.');
            }
        }

        return $next($request);
    }

    private function bloquear(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => $mensaje,
                'demo'    => true,
                'message' => $mensaje,
            ], 403);
        }

        return back()
            ->withErrors(['demo_mode' => '🔒 ' . $mensaje])
            ->withInput();
    }
}
