<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Roadmap de producto: "centro de administración unificado tipo Moodle"
 * (docs/ZURAEDU_ADMIN_ARCHITECTURE.md). Página de aterrizaje que agrupa por
 * categoría los enlaces a páginas de administración YA EXISTENTES — no
 * reemplaza el sidebar, no duplica ningún controlador, no crea permisos
 * nuevos. Cada tarjeta se muestra solo si el usuario actual tiene el
 * permiso/gate que ya protege esa ruta, Y (si aplica) el tenant tiene
 * activo el módulo (feature) del que depende esa ruta.
 *
 * Decisión de arquitectura — por qué el filtro de módulos usa SOLO
 * TenantFeature/Tenant::can(), nunca ConfigInstitucional::moduloActivo():
 * existen dos sistemas paralelos y no sincronizados de "módulo activo"
 * (docs/ZURAEDU_PRODUCT_GAPS.md). TenantFeature es el que REALMENTE
 * bloquea el acceso (routes/web.php:659-717 envuelve los archivos de
 * rutas en Route::middleware('tenant.feature:X'), aplicado por
 * App\Http\Middleware\CheckTenantFeature); moduloActivo() es puramente
 * cosmético en el sidebar y no bloquea ninguna ruta. Filtrar por
 * moduloActivo() produciría tarjetas ocultas que sí son accesibles, y
 * tarjetas visibles que rebotan — justo el gap que este trabajo corrige.
 * Esto no resuelve el gap de arquitectura documentado, solo lo evita: el
 * día que se unifiquen los dos sistemas, este controlador hereda la
 * unificación sin cambios porque solo conoce Tenant::can().
 */
class CentroAdministracionController extends Controller
{
    /**
     * tarjetas por categoría:
     * [etiqueta, nombre de ruta, icono bootstrap, permiso o gate, ?feature, ?alias]
     *
     * - permiso/gate: nombre real que envuelve esa ruta (permiso Spatie o
     *   ability de Gate::define — $usuario->can() resuelve ambos igual).
     * - feature: nombre de TenantFeature si la ruta vive detrás de
     *   'tenant.feature:X' en routes/web.php, o si un abort() explícito la
     *   exige (ver PublicSiteController::show() para 'modo_publico'). null
     *   si es un módulo core sin flag.
     * - alias: sinónimos en español para el buscador (ver 'busqueda' en
     *   index()), o null si el nombre ya es suficientemente buscable.
     */
    private const CATEGORIAS = [
        'Personas y Comunidad' => [
            ['Docentes', 'admin.docentes.index', 'bi-person-badge', 'gestionar-docentes', null, null],
            ['Estudiantes', 'admin.estudiantes.index', 'bi-people', 'ver-estudiantes', null, null],
            ['Grupos y Cursos', 'admin.grupos.index', 'bi-diagram-3', 'gestionar-grupos', null, null],
            ['Secciones', 'admin.secciones.index', 'bi-grid-3x3', 'gestionar-configuracion', null, null],
            ['Matrículas', 'admin.matriculas.resumen', 'bi-journal-check', 'gestionar-matriculas', null, null],
            ['Inscripciones', 'admin.inscripciones.index', 'bi-pencil-square', 'gestionar-matriculas', null, null],
            ['Pre-matrículas', 'admin.pre-matriculas.index', 'bi-file-earmark-plus', 'gestionar-matriculas', null, null],
            ['Registro Académico', 'admin.registro-academico.dashboard', 'bi-archive', 'gestionar-matriculas', null, null],
            ['Asignaciones Docente-Materia', 'admin.asignaciones.index', 'bi-person-workspace', 'gestionar-matriculas', null, null],
            ['Usuarios del Sistema', 'admin.usuarios.index', 'bi-person-gear', 'gestionar-usuarios', null, null],
            ['Usuarios Pendientes', 'admin.usuarios.pendientes', 'bi-person-check', 'gestionar-usuarios', null, null],
        ],
        'Estructura Académica' => [
            ['Estructura Académica', 'admin.academico.index', 'bi-mortarboard', 'gestionar-grupos', null, null],
            ['Asignaturas', 'admin.asignaturas.index', 'bi-book', 'gestionar-asignaturas', null, null],
            ['Malla Curricular', 'admin.malla.index', 'bi-grid', 'gestionar-asignaturas', null, 'pensum'],
            ['Áreas del Currículo', 'admin.areas.index', 'bi-collection', 'ver-dashboard', null, null],
            ['Especialidades Técnicas', 'admin.especialidades.index', 'bi-tools', 'gestionar-asignaturas', null, null],
            ['Bachillerato Técnico', 'admin.bachillerato-tecnico.index', 'bi-wrench-adjustable', 'gestionar-asignaturas', null, null],
            ['Familias Profesionales', 'admin.familias.index', 'bi-diagram-2', 'gestionar-asignaturas', null, null],
            ['Competencias', 'admin.competencias.index', 'bi-bullseye', 'gestionar-indicadores', null, null],
            ['Indicadores de Logro', 'admin.indicadores.index', 'bi-check2-square', 'gestionar-indicadores', null, null],
        ],
        'Docencia y Aula' => [
            ['Horarios', 'admin.horarios.index', 'bi-clock', 'gestionar-asignaciones', 'horarios', null],
            ['Vista Maestra de Horarios', 'admin.horarios.vista-maestra', 'bi-calendar3-week', 'gestionar-asignaciones', 'horarios', null],
            ['Suplencias', 'admin.horarios.suplencias', 'bi-arrow-left-right', 'gestionar-asignaciones', 'horarios', null],
            ['Aula Virtual (Classroom)', 'admin.classroom.index', 'bi-easel', 'gestionar-asignaciones', 'classroom', null],
            ['Planes de Clase', 'admin.planes-clase.index', 'bi-journal-text', 'ingresar-calificaciones', null, null],
            ['Planificación Técnica', 'admin.planificacion.index', 'bi-diagram-2', 'ingresar-calificaciones', null, null],
            ['Proyectos Escolares', 'admin.proyectos.index', 'bi-lightbulb', 'ingresar-calificaciones', 'proyectos', null],
            ['Recursos y Aulas', 'admin.recursos.index', 'bi-door-open', 'ver-servicios', null, 'salones laboratorios reserva'],
            ['Evaluación de Docentes', 'admin.evaluaciones-docentes.index', 'bi-clipboard-check', 'gestionar-docentes', 'evaluaciones_docentes', null],
            ['Observaciones de Aula', 'admin.observaciones.index', 'bi-eye', 'ingresar-calificaciones', null, null],
        ],
        'Evaluación y Calificaciones' => [
            ['Calificaciones', 'admin.calificaciones.index', 'bi-clipboard-data', 'ver-calificaciones', null, null],
            ['Registro de Notas', 'admin.registro.index', 'bi-table', 'ver-calificaciones', null, null],
            ['Boletines', 'admin.boletines.index', 'bi-file-earmark-person', 'ver-boletines', null, 'notas reporte'],
            ['Asistencia', 'admin.asistencia.index', 'bi-calendar-check', 'ver-asistencia', null, null],
            ['Rúbricas', 'admin.rubricas.index', 'bi-list-check', 'ingresar-calificaciones', null, null],
            ['Instrumentos de Evaluación', 'admin.instrumentos.index', 'bi-rulers', 'ingresar-calificaciones', null, null],
            ['Auditoría de Calificaciones', 'admin.calificaciones.auditoria', 'bi-shield-check', 'supervisar-registros', null, null],
            ['Cierre de Año Escolar', 'admin.cierre-ano.index', 'bi-flag', 'acceso-direccion', null, null],
        ],
        'Bienestar y Convivencia' => [
            ['Fichas de Salud', 'admin.salud.dashboard', 'bi-heart-pulse', 'acceso-salud-disciplina', 'salud', null],
            ['Disciplina', 'admin.disciplina.index', 'bi-shield-exclamation', 'acceso-salud-disciplina', 'disciplina', null],
            ['Seguimiento Social', 'admin.seguimiento-social.index', 'bi-people-fill', 'acceso-salud-disciplina', 'seguimiento_social', 'psicologia orientacion'],
            ['Tutorías', 'admin.tutorias.index', 'bi-person-hearts', 'ingresar-calificaciones', 'tutorias', null],
            ['Riesgo Académico', 'admin.riesgo.index', 'bi-exclamation-octagon', 'acceso-direccion-coordinacion', null, 'desercion abandono'],
            ['Alertas Tempranas', 'admin.alertas.index', 'bi-bell', 'ver-dashboard', null, 'avisos'],
            ['Gamificación', 'admin.gamificacion.index', 'bi-controller', 'ingresar-calificaciones', 'gamificacion', null],
            ['Reconocimientos', 'admin.reconocimientos.index', 'bi-trophy', 'ingresar-calificaciones', 'reconocimientos', null],
        ],
        'Comunicación' => [
            ['Comunicados', 'admin.comunicados.index', 'bi-megaphone', 'acceso-direccion-coordinacion', null, null],
            ['Bandeja de Comunicaciones', 'admin.comunicaciones.index', 'bi-chat-left-text', 'ver-dashboard', null, null],
            ['Mensajes', 'admin.mensajes.index', 'bi-envelope', 'ver-dashboard', null, null],
            ['Avisos de Emergencia', 'admin.avisos-emergencia.index', 'bi-exclamation-triangle', 'acceso-direccion', null, null],
            ['Encuestas', 'admin.encuestas.index', 'bi-clipboard-data', 'acceso-direccion-coordinacion', null, null],
            ['Actas de Reuniones', 'admin.reuniones.index', 'bi-journal-bookmark', 'acceso-direccion-coordinacion', 'reuniones', null],
            ['Calendario Institucional', 'admin.calendario.index', 'bi-calendar-event', 'ver-dashboard', null, null],
        ],
        'Finanzas' => [
            ['Pagos y Colegiaturas', 'admin.pagos.index', 'bi-cash-coin', 'ver-pagos', 'pagos', 'cobros colegiatura mensualidad cuotas'],
            ['Deudores', 'admin.pagos.deudores', 'bi-exclamation-circle', 'ver-pagos', 'pagos', null],
            ['Becas', 'admin.becas.index', 'bi-award', 'gestionar-pagos', 'pagos', null],
            ['Nómina', 'admin.nomina.index', 'bi-wallet2', 'gestionar-pagos', 'nomina', 'salarios sueldos empleados'],
            ['Facturación y Plan', 'admin.billing.index', 'bi-receipt', 'acceso-billing', null, null],
        ],
        'Servicios y Logística' => [
            ['Biblioteca', 'admin.biblioteca.index', 'bi-book-half', 'gestionar-biblioteca', 'biblioteca', null],
            ['Préstamos de Biblioteca', 'admin.biblioteca.prestamos.index', 'bi-arrow-left-right', 'gestionar-biblioteca', 'biblioteca', null],
            ['Inventario', 'admin.inventario.index', 'bi-boxes', 'ver-servicios', 'inventario', null],
            ['Equipos y Préstamos', 'admin.equipos.index', 'bi-pc-display', 'ver-servicios', 'inventario', 'computadoras laptops proyectores'],
            ['Transporte Escolar', 'admin.transporte.index', 'bi-bus-front', 'ver-servicios', 'transporte', null],
            ['Cafetería', 'admin.cafeteria.dashboard', 'bi-cup-hot', 'ver-servicios', 'cafeteria', null],
            ['Carnet+ y Control de Acceso', 'admin.carnet.index', 'bi-person-vcard', 'ver-servicios', null, 'credencial qr acceso'],
            ['Eventos Institucionales', 'admin.eventos.index', 'bi-calendar2-event', 'ver-servicios', null, null],
        ],
        'Reportes y Analítica' => [
            ['Tablero Ejecutivo', 'admin.ejecutivo.index', 'bi-speedometer2', 'acceso-direccion', null, 'dashboard director'],
            ['KPIs Institucionales', 'admin.kpis.index', 'bi-graph-up', 'acceso-direccion', null, null],
            ['Reportes Institucionales', 'admin.reportes.index', 'bi-file-earmark-bar-graph', 'ver-reportes-institucionales', null, null],
            ['Rendimiento Académico', 'admin.rendimiento.dashboard', 'bi-bar-chart-line', 'ver-estadisticas', null, null],
            ['Rendimiento Comparativo', 'admin.rendimiento.comparativo', 'bi-bar-chart-steps', 'ver-estadisticas', null, 'estadisticas comparacion'],
            ['Exportación Masiva', 'admin.exportacion-masiva.index', 'bi-download', 'ver-reportes-institucionales', null, null],
            ['Estadísticas del Sistema', 'admin.sistema.estadisticas', 'bi-pie-chart', 'ver-reportes-institucionales', null, null],
            ['Ficha Institucional', 'admin.sistema.ficha-institucional', 'bi-building', 'ver-reportes-institucionales', null, null],
        ],
        'Solicitudes y Soporte' => [
            ['Solicitudes de Representantes', 'admin.solicitudes.index', 'bi-inbox', 'supervisar-registros', null, null],
            ['Solicitudes de Estudiantes', 'admin.solicitudes-est.index', 'bi-inbox-fill', 'supervisar-registros', null, null],
            ['Solicitudes de Docentes', 'admin.solicitudes-docente.index', 'bi-inboxes', 'supervisar-registros', null, null],
            ['Tickets de Soporte', 'admin.soporte.index', 'bi-life-preserver', 'ver-dashboard', null, null],
            ['Chat con Soporte ZuraEdu', 'admin.tenant-chat.index', 'bi-headset', 'ver-dashboard', null, null],
            ['Centro de Ayuda', 'admin.ayuda', 'bi-question-circle', 'ver-dashboard', null, null],
        ],
        'Integraciones e Importación' => [
            ['Centro de Integraciones', 'admin.integraciones.index', 'bi-plug', 'ver-dashboard', null, null],
            ['SIGERD', 'admin.sigerd.index', 'bi-bank', 'acceso-sigerd', null, 'minerd ministerio'],
            ['Validación SIGERD', 'admin.sigerd.validar', 'bi-patch-check', 'acceso-sigerd', null, null],
            ['Importaciones', 'admin.importaciones.index', 'bi-upload', 'acceso-direccion-coordinacion', null, null],
            ['Notificaciones WhatsApp', 'admin.sistema.whatsapp', 'bi-whatsapp', 'solo-administrador', 'whatsapp', null],
            ['Notificaciones por Email', 'admin.sistema.email-notif', 'bi-envelope-at', 'solo-administrador', null, null],
        ],
        'Configuración del Sistema' => [
            ['Configuración General', 'admin.sistema.index', 'bi-gear', 'solo-administrador', null, null],
            ['Año Escolar', 'admin.school-years.index', 'bi-calendar3', 'gestionar-school-years', null, null],
            ['Períodos', 'admin.periodos.index', 'bi-calendar-range', 'gestionar-periodos', null, null],
            ['Escala de Calificación', 'admin.config.calificacion', 'bi-sliders', 'gestionar-configuracion', null, null],
            ['Configuración de RA', 'admin.config.ra', 'bi-sliders2', 'gestionar-configuracion', null, null],
            ['Configuración de Boletines', 'admin.boletines.config', 'bi-file-earmark-ruled', 'gestionar-configuracion', null, null],
            ['Configuración de Pagos', 'admin.pagos.config', 'bi-credit-card-2-front', 'solo-administrador', 'pagos', null],
            ['Pantalla de Login', 'admin.sistema.login-config', 'bi-box-arrow-in-right', 'solo-administrador', null, null],
            ['Respaldos', 'admin.sistema.backup', 'bi-hdd', 'solo-administrador', null, 'backup'],
            ['Log de Actividad', 'admin.sistema.actividad', 'bi-clock-history', 'acceso-direccion', null, 'auditoria bitacora'],
        ],
        'Página Web Institucional' => [
            ['Editor de Página Principal', 'admin.homepage.edit', 'bi-palette', 'gestionar-configuracion', null, null],
            ['Ver Portal Público', 'sitio.show', 'bi-globe', 'gestionar-configuracion', 'modo_publico', null],
            ['Noticias y Publicaciones', 'admin.publicaciones.index', 'bi-newspaper', 'gestionar-configuracion', null, null],
            ['Galería', 'admin.galeria.index', 'bi-images', 'ver-servicios', null, null],
            ['Landing del Centro', 'admin.sistema.landing', 'bi-megaphone-fill', 'acceso-direccion-coordinacion', null, null],
        ],
    ];

    public function index()
    {
        $usuario = auth()->user();

        // Espejo exacto de App\Http\Middleware\CheckTenantFeature::handle():
        // sin tenant resuelto o siendo super_admin, el middleware deja
        // pasar — el hub debe mostrar todo en esos dos casos, o mostraría
        // menos de lo que ese usuario en realidad puede visitar.
        $tenant          = app()->bound('tenant') ? app('tenant') : null;
        $ignorarFeatures = ! $tenant || $usuario->hasRole('super_admin');

        // Memoización por request: ~35 tarjetas con flag pero muchas menos
        // features distintas; Tenant::can() ya cachea 300s pero evita
        // repetir el array-access/closure por cada tarjeta del mismo flag.
        $memo = [];
        $featureActiva = function (?string $feature) use ($tenant, $ignorarFeatures, &$memo): bool {
            if ($feature === null || $ignorarFeatures) {
                return true;
            }

            return $memo[$feature] ??= $tenant->can($feature);
        };

        $categorias = collect(self::CATEGORIAS)
            ->map(function (array $items, string $categoria) use ($usuario, $featureActiva) {
                return collect($items)
                    // Orden por coste: has() en memoria → can() (permisos ya
                    // cargados) → feature (cache/DB) al final.
                    ->filter(fn ($item) => RouteFacade::has($item[1])
                        && $usuario->can($item[3])
                        && $featureActiva($item[4] ?? null))
                    ->map(fn ($item) => [
                        'label'    => $item[0],
                        'url'      => route($item[1]),
                        'icon'     => $item[2],
                        // Índice del buscador: sin acentos + alias + categoría,
                        // para que "matriculas" (sin tilde) encuentre "Matrículas".
                        'busqueda' => Str::lower(Str::ascii(
                            $item[0] . ' ' . ($item[5] ?? '') . ' ' . $categoria
                        )),
                    ])
                    ->values();
            })
            ->filter(fn ($items) => $items->isNotEmpty());

        return view('admin.centro-administracion.index', compact('categorias'));
    }

    /**
     * Catálogo crudo — solo para tests de integridad (rutas/permisos/
     * features existentes). No usar desde vistas: index() es el único
     * punto que filtra por usuario/tenant.
     */
    public static function catalogo(): array
    {
        return self::CATEGORIAS;
    }
}
