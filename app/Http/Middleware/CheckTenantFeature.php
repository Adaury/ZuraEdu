<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea el acceso a una ruta si el tenant no tiene activa la feature requerida.
 * Uso: Route::middleware('tenant.feature:horarios')
 */
class CheckTenantFeature
{
    private const LABELS = [
        'asistencia'            => 'Control de Asistencia',
        'calificaciones'        => 'Calificaciones',
        'boletines'             => 'Boletines',
        'reportes'              => 'Reportes',
        'horarios'              => 'Módulo de Horarios',
        'competencias'          => 'Competencias y RA',
        'portal_padre'          => 'Portal del Representante',
        'portal_estudiante'     => 'Portal del Estudiante',
        'portal_docente'        => 'Portal del Docente',
        'comunicados'           => 'Comunicados',
        'calendario'            => 'Calendario Académico',
        'pagos'                 => 'Pagos y Colegiaturas',
        'classroom'             => 'Classroom Virtual',
        'whatsapp'              => 'Notificaciones WhatsApp',
        'admisiones'            => 'Portal de Admisiones',
        'nomina'                => 'Nómina de Empleados',
        'biblioteca'            => 'Biblioteca',
        'inventario'            => 'Inventario Escolar',
        'cafeteria'             => 'Cafetería',
        'disciplina'            => 'Disciplina',
        'tutorias'              => 'Tutorías',
        'seguimiento_social'    => 'Seguimiento Social',
        'gamificacion'          => 'Gamificación',
        'proyectos'             => 'Proyectos Escolares',
        'reconocimientos'       => 'Reconocimientos',
        'evaluaciones_docentes' => 'Evaluación de Docentes',
        'transporte'            => 'Transporte Escolar',
        'salud'                 => 'Salud Escolar',
        'reuniones'             => 'Actas de Reuniones',
    ];

    public function handle(Request $request, Closure $next, string $feature)
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        // Sin tenant o super_admin → sin restricción
        if (! $tenant || auth()->user()?->hasRole('super_admin')) {
            return $next($request);
        }

        if ($tenant->can($feature)) {
            return $next($request);
        }

        $label = self::LABELS[$feature] ?? $feature;

        if ($request->expectsJson()) {
            return response()->json([
                'error'   => "El módulo «{$label}» no está disponible en tu plan.",
                'feature' => $feature,
            ], 403);
        }

        return redirect()->route($this->dashboardRuta($request))
            ->with('warning', "El módulo «{$label}» no está disponible en tu plan actual. Contacta a soporte para actualizar.");
    }

    private function dashboardRuta(Request $request): string
    {
        if ($request->is('portal/docente*'))    return 'portal.docente.dashboard';
        if ($request->is('portal/estudiante*')) return 'portal.estudiante.dashboard';
        if ($request->is('portal/padre*'))      return 'portal.padre.dashboard';

        return 'admin.dashboard';
    }
}
