<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CapacitacionVista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recomendación 7 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md (#31): sección de
 * capacitación dentro del Centro de Ayuda, con guías cortas agrupadas por
 * perfil y progreso marcable como "visto" por usuario.
 */
class AyudaController extends Controller
{
    /** Catálogo estático de guías de capacitación, agrupadas por perfil. */
    public const GRUPOS = [
        'admin' => [
            'nombre' => 'Administración y Dirección',
            'icono'  => 'bi-briefcase-fill',
            'color'  => '#1d4ed8',
            'roles'  => ['Administrador', 'Director', 'Coordinador Académico', 'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo', 'Personal Administrativo'],
            'guias'  => [
                [
                    'id' => 'admin-config-anual', 'titulo' => 'Configurar el año escolar y periodos', 'duracion' => '10 min', 'ver_tab' => 'sCfg',
                    'pasos' => [
                        'Crea y activa el Año Escolar en Configuración → Año Escolar — todo el sistema depende de que exista uno activo.',
                        'Define los periodos (trimestres/cuatrimestres) en Configuración → Períodos.',
                        'Crea los grupos (Grado + Sección) en Gestión → Grupos/Cursos.',
                        'Registra docentes y crea las asignaciones (Docente → Grupo → Asignatura).',
                    ],
                ],
                [
                    'id' => 'admin-rbac', 'titulo' => 'Entender roles y permisos', 'duracion' => '5 min', 'ver_tab' => null,
                    'pasos' => [
                        'Cada usuario tiene un rol (Administrador, Docente, Secretaría, etc.) que determina qué puede ver y hacer.',
                        'Consulta la Matriz de Accesos por Rol (tarjeta en la parte superior de este Centro de Ayuda) para ver qué puede hacer cada rol en vivo.',
                        'Los permisos de "ver" e "imprimir/exportar" boletines están separados — revisa la matriz si un rol no puede imprimir aunque sí vea.',
                    ],
                ],
                [
                    'id' => 'admin-cierre-ano', 'titulo' => 'Cierre de año escolar y promoción', 'duracion' => '8 min', 'ver_tab' => null,
                    'pasos' => [
                        'El cierre de año calcula la situación final de cada estudiante (promovido / no promovido) según sus notas.',
                        'Revisa el reporte de situación antes de ejecutar el cierre — es la última oportunidad de corregir notas del año.',
                        'El traslado de matrículas al año nuevo se hace en bloque desde el asistente de cierre — verifica los grupos destino antes de confirmar.',
                    ],
                ],
                [
                    'id' => 'admin-mesa-ayuda', 'titulo' => 'Gestionar Mesa de Ayuda (tickets)', 'duracion' => '5 min', 'ver_tab' => null,
                    'pasos' => [
                        'El dashboard de soporte muestra tickets sin asignar y tickets que incumplieron su SLA (tiempo límite según prioridad).',
                        'Cada ticket tiene un SLA automático: urgente 4h, alta 24h, media 48h, baja 72h.',
                        'Al cerrar un ticket puedes registrar la causa raíz — útil para detectar problemas recurrentes.',
                    ],
                ],
            ],
        ],
        'docente' => [
            'nombre' => 'Docentes',
            'icono'  => 'bi-person-workspace',
            'color'  => '#047857',
            'roles'  => ['Docente', 'Docente Académico', 'Docente Técnico', 'Docente Guía'],
            'guias'  => [
                [
                    'id' => 'docente-asistencia', 'titulo' => 'Registrar asistencia diaria', 'duracion' => '3 min', 'ver_tab' => 'sAsist',
                    'pasos' => [
                        'Ve a Asistencia y selecciona la asignación (grupo + materia) del día.',
                        'Usa "Marcar todos Presente" y ajusta solo los que faltaron o llegaron tarde.',
                        'Guarda antes de cerrar — la asistencia del día no se guarda automáticamente.',
                    ],
                ],
                [
                    'id' => 'docente-notas-academicas', 'titulo' => 'Registrar notas (Planilla Académica)', 'duracion' => '6 min', 'ver_tab' => 'sAcad',
                    'pasos' => [
                        'Selecciona el grupo y la asignatura — la planilla anual carga automáticamente (4 Competencias × 4 Períodos).',
                        'Ingresa las notas por período; el promedio y la nota final se calculan solos.',
                        'El sistema guarda cada celda automáticamente al salir de ella.',
                        'Haz clic en "Publicar" para que la nota sea visible en boletines y portales.',
                    ],
                ],
                [
                    'id' => 'docente-notas-tecnicas', 'titulo' => 'Registrar notas técnicas (RA)', 'duracion' => '5 min', 'ver_tab' => 'sTec',
                    'pasos' => [
                        'Las asignaturas técnicas evalúan por Resultados de Aprendizaje (RA), no por competencias.',
                        'Ingresa la nota de cada RA configurado para la asignatura y período.',
                        'Verifica los pesos de RA en Configuración → Config. RA si la nota final no cuadra.',
                    ],
                ],
                [
                    'id' => 'docente-carga-masiva', 'titulo' => 'Importar notas desde Excel/CSV', 'duracion' => '4 min', 'ver_tab' => null,
                    'pasos' => [
                        'Descarga la plantilla desde Calificaciones → Importar → Descargar plantilla, ya con tus estudiantes precargados.',
                        'Llena las columnas de notas sin cambiar el número de matrícula ni el orden de columnas.',
                        'Sube el archivo — el sistema procesa la carga en segundo plano y te notifica cuando termina, con el detalle de filas importadas u omitidas.',
                    ],
                ],
                [
                    'id' => 'docente-planificacion', 'titulo' => 'Crear y publicar planificaciones', 'duracion' => '6 min', 'ver_tab' => 'sPlanif',
                    'pasos' => [
                        'Ve a Planificación Docente → Nueva Planificación y selecciona la asignatura y periodo.',
                        'Usa el asistente de ZuraAI para generar actividades o mejorar el texto si lo tienes activado.',
                        'Publica la planificación para que sea visible a estudiantes y padres en su portal.',
                    ],
                ],
            ],
        ],
        'secretaria' => [
            'nombre' => 'Secretaría y Registro',
            'icono'  => 'bi-folder2-open',
            'color'  => '#7c3aed',
            'roles'  => ['Secretaría', 'Secretaria Docente', 'Registrador Académico', 'Encargado de Registro Académico', 'Recepción'],
            'guias'  => [
                [
                    'id' => 'sec-matricula', 'titulo' => 'Matricular estudiantes', 'duracion' => '5 min', 'ver_tab' => 'sPC',
                    'pasos' => [
                        'Registra el estudiante con sus datos básicos (nombres, cédula, fecha de nacimiento).',
                        'Desde su ficha, crea la matrícula seleccionando el grupo (grado + sección) del año activo.',
                        'Para varios estudiantes a la vez, usa "Importar CSV" con matrícula automática al grupo de destino.',
                    ],
                ],
                [
                    'id' => 'sec-boletines', 'titulo' => 'Generar e imprimir boletines', 'duracion' => '4 min', 'ver_tab' => null,
                    'pasos' => [
                        'Ve a Boletines, selecciona el grupo y el período (o "Anual" para el boletín completo).',
                        'Revisa que todas las notas del período estén publicadas antes de imprimir — un boletín con notas sin publicar sale incompleto.',
                        'Usa "Imprimir por grupo" (ZIP) para generar todos los boletines de un grupo de una sola vez.',
                    ],
                ],
                [
                    'id' => 'sec-sigerd', 'titulo' => 'Exportaciones para SIGERD/MINERD', 'duracion' => '5 min', 'ver_tab' => null,
                    'pasos' => [
                        'El módulo SIGERD exporta nómina, calificaciones, docentes y asistencia en el formato que exige el MINERD.',
                        'Corre la validación (botón "Validar datos") antes de exportar para detectar campos incompletos.',
                        'Las exportaciones quedan disponibles en Excel, CSV y PDF según el reporte.',
                    ],
                ],
            ],
        ],
        'finanzas' => [
            'nombre' => 'Finanzas y Caja',
            'icono'  => 'bi-cash-coin',
            'color'  => '#0f766e',
            'roles'  => ['Caja / Finanzas'],
            'guias'  => [
                [
                    'id' => 'fin-pagos', 'titulo' => 'Registrar pagos y colegiaturas', 'duracion' => '5 min', 'ver_tab' => 'sPagos',
                    'pasos' => [
                        'Este módulo solo aplica a centros privados con el módulo de Pagos activado.',
                        'Ve a Pagos → Nuevo Pago, selecciona el estudiante y la cuota a saldar.',
                        'El estado de cuenta del estudiante se actualiza automáticamente al registrar el pago.',
                    ],
                ],
                [
                    'id' => 'fin-deudores', 'titulo' => 'Ver deudas y morosidad', 'duracion' => '3 min', 'ver_tab' => null,
                    'pasos' => [
                        'El listado de deudores muestra estudiantes con cuotas vencidas y el monto pendiente.',
                        'Puedes enviar un recordatorio de pago vencido directamente desde el listado.',
                        'Los boletines pueden mostrar una alerta si el estudiante tiene deudas, según la configuración financiera.',
                    ],
                ],
            ],
        ],
        'portales' => [
            'nombre' => 'Soporte a Padres y Estudiantes',
            'icono'  => 'bi-person-circle',
            'color'  => '#0891b2',
            'roles'  => ['Estudiante', 'Representante'],
            'guias'  => [
                [
                    'id' => 'por-que-ven', 'titulo' => 'Qué ve un padre/estudiante en su portal', 'duracion' => '4 min', 'ver_tab' => 'sPortales',
                    'pasos' => [
                        'El estudiante entra a /portal/estudiante: notas, boletín, asistencia, horario y planificaciones publicadas.',
                        'El representante entra a /portal/padre y ve la misma información de todos sus hijos registrados.',
                        'Si el padre no ve algo (una nota, un boletín), revisa primero si el docente ya publicó esa información.',
                    ],
                ],
                [
                    'id' => 'por-enlace-publico', 'titulo' => 'Generar el enlace público para un representante', 'duracion' => '2 min', 'ver_tab' => 'sPortales',
                    'pasos' => [
                        'Útil cuando el representante no quiere o no puede crear una cuenta.',
                        'Desde la ficha del estudiante, genera el "Enlace del Representante" — válido por 30 días, sin necesidad de login.',
                        'El enlace es único por estudiante — no debe compartirse entre hermanos ni con terceros.',
                    ],
                ],
            ],
        ],
    ];

    /** Devuelve el catálogo completo aplanado, indexado por id de contenido. */
    protected function idsValidos(): array
    {
        $ids = [];
        foreach (self::GRUPOS as $grupo) {
            foreach ($grupo['guias'] as $guia) {
                $ids[] = $guia['id'];
            }
        }
        return $ids;
    }

    public function capacitacion()
    {
        $vistos = CapacitacionVista::where('user_id', auth()->id())
            ->pluck('visto_at', 'contenido_id');

        return view('admin.ayuda.capacitacion', [
            'grupos' => self::GRUPOS,
            'vistos' => $vistos,
        ]);
    }

    public function marcarVisto(Request $request, string $contenidoId): JsonResponse
    {
        if (! in_array($contenidoId, $this->idsValidos(), true)) {
            abort(404);
        }

        $existente = CapacitacionVista::where('user_id', auth()->id())
            ->where('contenido_id', $contenidoId)
            ->first();

        if ($existente) {
            $existente->delete();
            return response()->json(['visto' => false]);
        }

        CapacitacionVista::create([
            'user_id'      => auth()->id(),
            'contenido_id' => $contenidoId,
            'visto_at'     => now(),
        ]);

        return response()->json(['visto' => true, 'visto_at' => now()->diffForHumans()]);
    }
}
