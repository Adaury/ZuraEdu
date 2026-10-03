<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Accesos rápidos con icono para cada rol: las 6-12 tareas que de verdad se repiten cada día, a un toque desde cualquier pantalla
 * (icono de la barra superior), en la portada de cada panel y como atajos del icono de la app instalada (PWA).
 *
 * Cada acceso se filtra con MenuFiltro: solo aparece lo que ese usuario puede abrir de verdad, así que nunca promete una pantalla
 * que daría 403 o rebotaría por el plan del colegio.
 */
class AccesosRapidos
{
    /** Orden de prioridad cuando un usuario tiene varios roles. */
    private const ROLES = [
        'super_admin'    => ['super_admin'],
        'administrador'  => ['Administrador'],
        'director'       => ['Director'],
        'coordinacion'   => ['Coordinador Académico', 'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo'],
        'registro'       => ['Registrador Académico', 'Encargado de Registro Académico'],
        'secretaria'     => ['Secretaría', 'Secretaria Docente', 'Secretaria'],
        'personal'       => ['Personal Administrativo'],
        'caja'           => ['Caja / Finanzas'],
        'biblioteca'     => ['Biblioteca'],
        'recepcion'      => ['Recepción'],
        'docente'        => ['Docente', 'Docente Académico', 'Docente Técnico', 'Docente Guía', 'Encargado de Área'],
        'estudiante'     => ['Estudiante'],
        'representante'  => ['Representante'],
    ];

    /** [etiqueta, ruta, icono Bootstrap, color] */
    private const POR_ROL = [
        'super_admin' => [
            ['Instituciones', 'superadmin.tenants.index', 'bi-building-fill', '#6366f1'],
            ['Nueva institución', 'superadmin.tenants.create', 'bi-plus-circle-fill', '#22c55e'],
            ['Respaldos', 'superadmin.respaldos.index', 'bi-cloud-arrow-up-fill', '#0ea5e9'],
        ],
        'administrador' => [
            ['Nuevo estudiante', 'admin.estudiantes.wizard', 'bi-person-plus-fill', '#22c55e'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#6366f1'],
            ['Matrículas', 'admin.matriculas.index', 'bi-card-checklist', '#0ea5e9'],
            ['Asistencia', 'admin.asistencia.index', 'bi-calendar-check-fill', '#f59e0b'],
            ['Calificaciones', 'admin.calificaciones.index', 'bi-journal-check', '#8b5cf6'],
            ['Boletines', 'admin.boletines.index', 'bi-file-earmark-text-fill', '#ef4444'],
            ['Pagos', 'admin.pagos.index', 'bi-cash-coin', '#10b981'],
            ['Deudores', 'admin.pagos.deudores', 'bi-exclamation-circle-fill', '#f97316'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
            ['Calendario', 'admin.calendario.index', 'bi-calendar3', '#14b8a6'],
            ['Reportes', 'admin.reportes.index', 'bi-graph-up-arrow', '#3b82f6'],
            ['Usuarios', 'admin.usuarios.index', 'bi-person-gear', '#64748b'],
        ],
        'director' => [
            ['Panel ejecutivo', 'admin.ejecutivo.index', 'bi-bar-chart-line-fill', '#6366f1'],
            ['Indicadores', 'admin.kpis.index', 'bi-speedometer2', '#0ea5e9'],
            ['Asistencia', 'admin.asistencia.index', 'bi-calendar-check-fill', '#f59e0b'],
            ['Calificaciones', 'admin.calificaciones.index', 'bi-journal-check', '#8b5cf6'],
            ['Boletines', 'admin.boletines.index', 'bi-file-earmark-text-fill', '#ef4444'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#22c55e'],
            ['Reportes', 'admin.reportes.index', 'bi-graph-up-arrow', '#3b82f6'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
            ['Calendario', 'admin.calendario.index', 'bi-calendar3', '#14b8a6'],
        ],
        'coordinacion' => [
            ['Calificaciones', 'admin.calificaciones.index', 'bi-journal-check', '#8b5cf6'],
            ['Asistencia', 'admin.asistencia.index', 'bi-calendar-check-fill', '#f59e0b'],
            ['Boletines', 'admin.boletines.index', 'bi-file-earmark-text-fill', '#ef4444'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#22c55e'],
            ['Grupos', 'admin.grupos.index', 'bi-diagram-3-fill', '#0ea5e9'],
            ['Horarios', 'admin.horarios.index', 'bi-clock-fill', '#6366f1'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
            ['Calendario', 'admin.calendario.index', 'bi-calendar3', '#14b8a6'],
        ],
        'registro' => [
            ['Mi escritorio', 'admin.registro-academico.dashboard', 'bi-journal-bookmark-fill', '#6366f1'],
            ['Nuevo estudiante', 'admin.estudiantes.wizard', 'bi-person-plus-fill', '#22c55e'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#0ea5e9'],
            ['Matrículas', 'admin.matriculas.index', 'bi-card-checklist', '#f59e0b'],
            ['Boletines', 'admin.boletines.index', 'bi-file-earmark-text-fill', '#ef4444'],
            ['Reportes', 'admin.reportes.index', 'bi-graph-up-arrow', '#3b82f6'],
        ],
        'secretaria' => [
            ['Nuevo estudiante', 'admin.estudiantes.wizard', 'bi-person-plus-fill', '#22c55e'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#6366f1'],
            ['Inscripciones', 'admin.inscripciones.index', 'bi-pencil-square', '#0ea5e9'],
            ['Matrículas', 'admin.matriculas.index', 'bi-card-checklist', '#f59e0b'],
            ['Boletines', 'admin.boletines.index', 'bi-file-earmark-text-fill', '#ef4444'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
            ['Calendario', 'admin.calendario.index', 'bi-calendar3', '#14b8a6'],
        ],
        'personal' => [
            ['Reportes', 'admin.reportes.index', 'bi-graph-up-arrow', '#3b82f6'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
            ['Calendario', 'admin.calendario.index', 'bi-calendar3', '#14b8a6'],
        ],
        'caja' => [
            ['Pagos', 'admin.pagos.index', 'bi-cash-coin', '#10b981'],
            ['Deudores', 'admin.pagos.deudores', 'bi-exclamation-circle-fill', '#f97316'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#6366f1'],
            ['Reportes', 'admin.reportes.index', 'bi-graph-up-arrow', '#3b82f6'],
        ],
        'biblioteca' => [
            ['Biblioteca', 'admin.biblioteca.index', 'bi-book-fill', '#0ea5e9'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#6366f1'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
        ],
        'recepcion' => [
            ['Entrada y salida', 'admin.carnet.checkin', 'bi-qr-code-scan', '#22c55e'],
            ['Carnets', 'admin.carnet.index', 'bi-person-vcard-fill', '#6366f1'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people-fill', '#0ea5e9'],
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone-fill', '#ec4899'],
        ],
        'docente' => [
            ['Tomar asistencia', 'portal.docente.asistencia-rapida', 'bi-lightning-charge-fill', '#f59e0b'],
            ['Mis estudiantes', 'portal.docente.mis-estudiantes', 'bi-people-fill', '#6366f1'],
            ['Classroom', 'portal.docente.classroom.index', 'bi-easel2-fill', '#0ea5e9'],
            ['Planificaciones', 'portal.docente.mis-planificaciones', 'bi-journal-text', '#8b5cf6'],
            ['Mi horario', 'portal.docente.horario', 'bi-clock-fill', '#14b8a6'],
            ['Calendario', 'portal.docente.calendario', 'bi-calendar3', '#22c55e'],
            ['Mensajes', 'portal.docente.mensajes.index', 'bi-chat-dots-fill', '#ec4899'],
            ['Rúbricas', 'portal.docente.rubricas.index', 'bi-ui-checks-grid', '#f97316'],
        ],
        'estudiante' => [
            ['Mi boletín', 'portal.estudiante.boletin', 'bi-file-earmark-text-fill', '#ef4444'],
            ['Mis tareas', 'portal.estudiante.tareas', 'bi-list-check', '#f59e0b'],
            ['Classroom', 'portal.estudiante.classroom.index', 'bi-easel2-fill', '#0ea5e9'],
            ['Mi horario', 'portal.estudiante.horario', 'bi-clock-fill', '#14b8a6'],
            ['Mi asistencia', 'portal.estudiante.asistencia', 'bi-calendar-check-fill', '#22c55e'],
            ['Mi carnet', 'portal.estudiante.mi-carnet', 'bi-person-vcard-fill', '#6366f1'],
            ['Mis pagos', 'portal.estudiante.mis-pagos', 'bi-cash-coin', '#10b981'],
            ['Calendario', 'portal.estudiante.calendario', 'bi-calendar3', '#8b5cf6'],
            ['Tutor IA', 'portal.estudiante.tutor-ia', 'bi-stars', '#ec4899'],
        ],
        'representante' => [
            ['Mis hijos', 'portal.padre.dashboard', 'bi-people-fill', '#6366f1'],
            ['Comunicados', 'portal.padre.comunicados', 'bi-megaphone-fill', '#ec4899'],
            ['Calendario', 'portal.padre.calendario', 'bi-calendar3', '#14b8a6'],
            ['Mensajes', 'portal.padre.mensajes.index', 'bi-chat-dots-fill', '#0ea5e9'],
            ['Solicitudes', 'portal.padre.solicitudes.index', 'bi-inbox-fill', '#f59e0b'],
            ['Notificaciones', 'portal.padre.notificaciones', 'bi-bell-fill', '#ef4444'],
            ['Tutor IA', 'portal.padre.tutor-ia', 'bi-stars', '#8b5cf6'],
        ],
    ];

    /** Pantalla de inicio de cada rol. */
    private const INICIO = [
        'super_admin' => 'superadmin.tenants.index', 'administrador' => 'admin.dashboard', 'director' => 'admin.dashboard',
        'coordinacion' => 'admin.dashboard', 'registro' => 'admin.registro-academico.dashboard', 'secretaria' => 'admin.dashboard',
        'personal' => 'admin.dashboard', 'caja' => 'admin.dashboard', 'biblioteca' => 'admin.dashboard', 'recepcion' => 'admin.dashboard',
        'docente' => 'portal.docente.dashboard', 'estudiante' => 'portal.estudiante.dashboard', 'representante' => 'portal.padre.dashboard',
    ];

    public static function rol(?User $usuario): ?string
    {
        if (! $usuario) {
            return null;
        }
        foreach (self::ROLES as $clave => $nombres) {
            if ($usuario->hasAnyRole($nombres)) {
                return $clave;
            }
        }

        return null;
    }

    /** Ruta de la pantalla de inicio del usuario (para el «start_url» de la app instalada). */
    public static function inicio(?User $usuario): ?string
    {
        $rol = self::rol($usuario);

        return $rol && Route::has(self::INICIO[$rol]) ? route(self::INICIO[$rol], [], false) : null;
    }

    /**
     * @return list<array{etiqueta: string, url: string, path: string, icono: string, color: string}>
     */
    public static function para(?User $usuario, ?int $max = null): array
    {
        $rol = self::rol($usuario);
        if (! $rol) {
            return [];
        }

        $salida = [];
        foreach (self::POR_ROL[$rol] as [$etiqueta, $ruta, $icono, $color]) {
            if (! Route::has($ruta)) {
                continue;
            }
            $url = route($ruta);
            if (! MenuFiltro::permite($url, $usuario)) {
                continue;
            }
            $salida[] = ['etiqueta' => $etiqueta, 'url' => $url, 'path' => route($ruta, [], false), 'icono' => $icono, 'color' => $color];
            if ($max && count($salida) >= $max) {
                break;
            }
        }

        return $salida;
    }
}
