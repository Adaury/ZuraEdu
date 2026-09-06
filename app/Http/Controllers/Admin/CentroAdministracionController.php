<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Roadmap de producto: "centro de administración unificado tipo Moodle"
 * (docs/ZURAEDU_ADMIN_ARCHITECTURE.md). Página de aterrizaje que agrupa por
 * categoría los enlaces a páginas de administración YA EXISTENTES — no
 * reemplaza el sidebar, no duplica ningún controlador, no crea permisos
 * nuevos. Cada tarjeta se muestra solo si el usuario actual tiene el
 * permiso Spatie que ya protege esa ruta.
 */
class CentroAdministracionController extends Controller
{
    /**
     * tarjetas por categoría: [etiqueta, nombre de ruta, icono bootstrap, permiso]
     */
    private const CATEGORIAS = [
        'Personas' => [
            ['Docentes', 'admin.docentes.index', 'bi-person-badge', 'gestionar-docentes'],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people', 'ver-estudiantes'],
            ['Grupos y Secciones', 'admin.grupos.index', 'bi-diagram-3', 'gestionar-grupos'],
            ['Matrículas', 'admin.matriculas.resumen', 'bi-journal-check', 'gestionar-matriculas'],
            ['Pre-matrículas', 'admin.pre-matriculas.index', 'bi-file-earmark-plus', 'gestionar-matriculas'],
            ['Usuarios del Sistema', 'admin.usuarios.index', 'bi-person-gear', 'gestionar-usuarios'],
        ],
        'Académico' => [
            ['Estructura Académica', 'admin.academico.index', 'bi-mortarboard', 'gestionar-grupos'],
            ['Asignaturas', 'admin.asignaturas.index', 'bi-book', 'gestionar-asignaturas'],
            ['Calificaciones', 'admin.calificaciones.index', 'bi-clipboard-data', 'ver-calificaciones'],
            ['Asistencia', 'admin.asistencia.index', 'bi-calendar-check', 'ver-asistencia'],
            ['Horarios', 'admin.horarios.index', 'bi-clock', 'gestionar-asignaciones'],
            ['Planes de Clase', 'admin.planes-clase.index', 'bi-journal-text', 'ingresar-calificaciones'],
            ['Planificación Técnica', 'admin.planificacion.index', 'bi-diagram-2', 'ingresar-calificaciones'],
            ['Rúbricas', 'admin.rubricas.index', 'bi-list-check', 'ingresar-calificaciones'],
            ['Cierre de Año Escolar', 'admin.cierre-ano.index', 'bi-flag', 'acceso-direccion'],
        ],
        'Comunicación' => [
            ['Comunicados', 'admin.comunicaciones.index', 'bi-megaphone', 'ver-dashboard'],
            ['Avisos de Emergencia', 'admin.avisos-emergencia.index', 'bi-exclamation-triangle', 'acceso-direccion'],
            ['Encuestas', 'admin.encuestas.index', 'bi-clipboard-data', 'acceso-direccion-coordinacion'],
            ['Actas de Reuniones', 'admin.reuniones.index', 'bi-journal-bookmark', 'acceso-direccion-coordinacion'],
            ['Mensajes', 'admin.mensajes.index', 'bi-envelope', 'ver-dashboard'],
        ],
        'Finanzas' => [
            ['Pagos', 'admin.pagos.index', 'bi-cash-coin', 'ver-pagos'],
            ['Becas', 'admin.becas.index', 'bi-award', 'gestionar-pagos'],
            ['Nómina', 'admin.nomina.index', 'bi-wallet2', 'gestionar-pagos'],
            ['Facturación', 'admin.billing.index', 'bi-receipt', 'acceso-billing'],
            ['Cafetería', 'admin.cafeteria.dashboard', 'bi-cup-hot', 'ver-servicios'],
        ],
        'Configuración' => [
            ['Año Escolar', 'admin.school-years.index', 'bi-calendar3', 'gestionar-school-years'],
            ['Períodos', 'admin.periodos.index', 'bi-calendar-range', 'gestionar-periodos'],
            ['SIGERD', 'admin.sigerd.index', 'bi-bank', 'acceso-sigerd'],
            ['KPIs Institucionales', 'admin.kpis.index', 'bi-graph-up', 'acceso-direccion'],
            ['Reportes Institucionales', 'admin.reportes.index', 'bi-file-earmark-bar-graph', 'ver-reportes-institucionales'],
            ['Log de Actividad', 'admin.sistema.actividad', 'bi-clock-history', 'acceso-direccion'],
        ],
        'Página Web' => [
            ['Editor de Página Principal', 'admin.homepage.edit', 'bi-palette', 'gestionar-configuracion'],
            ['Ver Portal Público', 'sitio.show', 'bi-globe', 'gestionar-configuracion'],
            ['Galería', 'admin.galeria.index', 'bi-images', 'ver-servicios'],
        ],
    ];

    public function index()
    {
        $usuario = auth()->user();

        $categorias = collect(self::CATEGORIAS)
            ->map(function (array $items) use ($usuario) {
                return collect($items)
                    ->filter(fn ($item) => RouteFacade::has($item[1]) && $usuario->can($item[3]))
                    ->map(fn ($item) => [
                        'label' => $item[0],
                        'url'   => route($item[1]),
                        'icon'  => $item[2],
                    ])
                    ->values();
            })
            ->filter(fn ($items) => $items->isNotEmpty());

        return view('admin.centro-administracion.index', compact('categorias'));
    }
}
