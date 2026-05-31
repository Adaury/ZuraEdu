<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea el acceso al panel /admin para roles de portal
 * (Estudiante, Representante). Los demás roles pasan.
 */
class EnsureAdminAccess
{
    /** Roles que SÍ pueden acceder al panel admin */
    private const ADMIN_ROLES = [
        'super_admin',
        'Administrador',
        'Director',
        'Coordinador Académico',
        'Coordinador Primer Ciclo',
        'Coordinador Segundo Ciclo',
        'Secretaría',
        'Secretaria Docente',
        'Personal Administrativo',
        'Encargado de Área',
        'Registrador Académico',
        'Encargado de Registro Académico',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // SuperAdmin sin tenant activo en sesión → redirigir a su panel
        if ($user->hasRole('super_admin') && ! session('sa_tenant_id')) {
            return redirect('/superadmin')
                ->with('info', 'Selecciona una institución para administrar su panel.');
        }

        // Docente → portal propio
        if ($user->hasRole('Docente')) {
            return redirect()->route('portal.docente.dashboard')
                ->with('info', 'Tu acceso es a través del portal docente.');
        }

        // Estudiante → portal propio
        if ($user->hasRole('Estudiante')) {
            return redirect()->route('portal.estudiante.dashboard')
                ->with('info', 'Tu acceso es a través del portal de estudiante.');
        }

        // Representante/Padre → portal propio
        if ($user->hasRole('Representante')) {
            return redirect()->route('portal.padre.dashboard')
                ->with('info', 'Tu acceso es a través del portal de representante.');
        }

        // Rol no reconocido
        if (! $user->hasAnyRole(self::ADMIN_ROLES)) {
            abort(403, 'No tienes permiso para acceder al panel administrativo.');
        }

        return $next($request);
    }
}
