<?php

namespace App\Http\Middleware;

use App\Models\ConfigInstitucional;
use Closure;
use Illuminate\Http\Request;

/**
 * Bloquea el acceso a una ruta si el tenant no tiene activa la feature requerida.
 * Uso: Route::middleware('tenant.feature:horarios')
 *
 * Fase 0 del roadmap (docs/ZURAEDU_PRODUCT_GAPS.md): existían dos sistemas
 * paralelos de "módulo activo" sin sincronizar -- TenantFeature (plan del
 * tenant, solo lo toca SuperAdmin) y ConfigInstitucional::moduloActivo()
 * (autoservicio del propio centro en /admin/sistema, que hasta ahora era
 * puramente cosmético y no bloqueaba ninguna ruta). Ahora exige AMBOS: el
 * plan tiene que incluir el módulo Y el centro tiene que tenerlo encendido.
 */
class CheckTenantFeature
{
    /**
     * Mapa feature (TenantFeature) → módulo (ConfigInstitucional). Solo los
     * slugs con equivalente real de autoservicio en SistemaController::
     * updateModulos(). Dos alias verificados donde el nombre no coincide
     * (classroom↔zuraclass, seguimiento_social↔seguimiento); el resto de
     * LABELS (asistencia, boletines, portal_padre, whatsapp, etc.) no tiene
     * toggle de autoservicio y sigue dependiendo solo de TenantFeature.
     */
    private const MODULO_CONFIG = [
        'pagos'              => 'pagos',
        'biblioteca'         => 'biblioteca',
        'cafeteria'          => 'cafeteria',
        'transporte'         => 'transporte',
        'salud'              => 'salud',
        'gamificacion'       => 'gamificacion',
        'inventario'         => 'inventario',
        'disciplina'         => 'disciplina',
        'reuniones'          => 'reuniones',
        'proyectos'          => 'proyectos',
        'seguimiento_social' => 'seguimiento',
        'classroom'          => 'zuraclass',
    ];

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

        if (! $tenant->can($feature)) {
            return $this->bloquear($request, $feature, autoservicio: false);
        }

        $modulo = self::MODULO_CONFIG[$feature] ?? null;
        if ($modulo !== null && ! ConfigInstitucional::moduloActivo($modulo)) {
            return $this->bloquear($request, $feature, autoservicio: true);
        }

        return $next($request);
    }

    private function bloquear(Request $request, string $feature, bool $autoservicio)
    {
        $label = self::LABELS[$feature] ?? $feature;

        $mensaje = $autoservicio
            ? "El módulo «{$label}» está desactivado en la configuración de tu institución. Actívalo en Configuración del Sistema → Módulos."
            : "El módulo «{$label}» no está disponible en tu plan actual. Contacta a soporte para actualizar.";

        if ($request->expectsJson()) {
            return response()->json([
                'error'   => $autoservicio ? "El módulo «{$label}» está desactivado en la configuración de tu institución." : "El módulo «{$label}» no está disponible en tu plan.",
                'feature' => $feature,
            ], 403);
        }

        return redirect()->route($this->dashboardRuta($request))->with('warning', $mensaje);
    }

    private function dashboardRuta(Request $request): string
    {
        if ($request->is('portal/docente*'))    return 'portal.docente.dashboard';
        if ($request->is('portal/estudiante*')) return 'portal.estudiante.dashboard';
        if ($request->is('portal/padre*'))      return 'portal.padre.dashboard';

        return 'admin.dashboard';
    }
}
