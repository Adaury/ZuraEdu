<?php

namespace App\Support;

use App\Models\User;

/**
 * Contenido de los flyers («guías rápidas») por rol: una hoja que explica, en lenguaje llano, qué puede hacer esa persona en ZuraEdu,
 * cómo empezar y qué atajos usar. Se muestra en la página de bienvenida, dentro de la plataforma y como PDF imprimible.
 *
 * Solo se describen funciones que existen en el sistema (los nombres coinciden con los del menú).
 */
class GuiasRol
{
    public const GUIAS = [
        'direccion' => [
            'nombre'   => 'Dirección y administración',
            'perfiles' => 'Administrador · Director',
            'lema'     => 'Todo el centro, de un vistazo y bajo control.',
            'color'    => '#1e3a6e',
            'hacer'    => [
                'Ver el estado del centro en el Panel ejecutivo y los Indicadores (asistencia, notas, pagos, riesgo).',
                'Registrar estudiantes con el asistente «Nuevo estudiante» y matricularlos en su grupo.',
                'Controlar Pagos y Deudores, y enviar recordatorios a las familias.',
                'Revisar Calificaciones y publicar los Boletines por período.',
                'Enviar Comunicados y mantener el Calendario escolar al día.',
                'Crear Usuarios, asignar roles y definir los años escolares y períodos.',
            ],
            'pasos' => [
                ['Prepara el año', 'En Configuración crea el año escolar y sus cuatro períodos.'],
                ['Carga a tu gente', 'Registra estudiantes y docentes, y asigna cada docente a sus cursos (Asignaciones).'],
                ['Revisa cada semana', 'Abre el Panel ejecutivo: asistencia, notas y pagos pendientes en una sola pantalla.'],
            ],
            'consejos' => [
                'Usa el icono de accesos rápidos (arriba a la derecha) o Alt + Q para saltar a lo que más usas.',
                'Cierra cada período solo cuando todas las notas estén cargadas: después ya no se pueden modificar.',
                'Cada cambio importante queda registrado en el Log de Actividad.',
            ],
        ],
        'coordinacion' => [
            'nombre'   => 'Coordinación académica',
            'perfiles' => 'Coordinador Académico · Primer Ciclo · Segundo Ciclo',
            'lema'     => 'Que ningún estudiante se quede atrás.',
            'color'    => '#7c3aed',
            'hacer'    => [
                'Seguir las Calificaciones de todos los cursos y detectar quién necesita apoyo.',
                'Revisar la Asistencia y las alertas de inasistencias.',
                'Preparar los Boletines y las actas de cada período.',
                'Organizar Grupos y Horarios sin choques de docentes ni aulas.',
                'Revisar las Planificaciones de los docentes.',
                'Comunicarte con docentes y familias mediante Comunicados.',
            ],
            'pasos' => [
                ['Revisa las alertas', 'Empieza por asistencia y notas bajas: son lo urgente de la semana.'],
                ['Acompaña a los docentes', 'Mira sus planificaciones y el avance de calificaciones por curso.'],
                ['Cierra el período', 'Verifica que no falten notas y publica los boletines.'],
            ],
            'consejos' => [
                'En Calificaciones puedes comparar el rendimiento de un período con el anterior.',
                'Los boletines solo muestran notas publicadas: confirma que cada docente publicó las suyas.',
                'Con los accesos rápidos llegas a tus 8 tareas más frecuentes en un toque.',
            ],
        ],
        'secretaria' => [
            'nombre'   => 'Secretaría y registro',
            'perfiles' => 'Secretaría · Registrador Académico · Encargado de Registro · Personal Administrativo',
            'lema'     => 'Los datos correctos, a tiempo y sin papeles de más.',
            'color'    => '#0891b2',
            'hacer'    => [
                'Registrar estudiantes paso a paso con «Nuevo estudiante» (datos, familia y matrícula).',
                'Gestionar Inscripciones y Pre-matrículas que llegan desde la página pública.',
                'Mantener Matrículas y Grupos: cambios de sección, retiros y traslados.',
                'Emitir constancias, certificados y documentos del estudiante.',
                'Exportar la información oficial para SIGERD / MINERD.',
                'Enviar Comunicados a familias, docentes o grupos.',
            ],
            'pasos' => [
                ['Inscribe', 'Revisa las pre-matrículas pendientes y conviértelas en matrícula.'],
                ['Verifica los datos', 'Usa la validación de SIGERD antes de exportar: señala lo que falta.'],
                ['Entrega documentos', 'Genera constancias y boletines en PDF desde la ficha del estudiante.'],
            ],
            'consejos' => [
                'El asistente de nuevo estudiante evita duplicados: busca antes de crear.',
                'Si una pantalla no aparece en tu menú, tu rol no la usa: el menú solo muestra lo que puedes abrir.',
                'Los cambios de matrícula (retiros, traslados, cambio de grupo) quedan registrados en el Log de Actividad.',
            ],
        ],
        'docente' => [
            'nombre'   => 'Docentes',
            'perfiles' => 'Docente · Docente Académico · Docente Técnico · Docente Guía',
            'lema'     => 'Menos papeleo, más tiempo para enseñar.',
            'color'    => '#16a34a',
            'hacer'    => [
                'Tomar asistencia en segundos con Asistencia rápida (también con código QR).',
                'Registrar calificaciones por período (P1–P4) y competencias; se guardan celda por celda.',
                'Crear tareas, quizzes y recursos en tu aula virtual (Classroom) y calificar las entregas.',
                'Preparar Planes de clase y la Planificación anual.',
                'Generar boletines y actas de tus cursos, y observaciones por estudiante.',
                'Escribir a estudiantes y familias con Mensajes y Comunicados.',
            ],
            'pasos' => [
                ['Entra a tu curso', 'Desde tu inicio elige el curso: el menú pasa a mostrar las herramientas de ese curso.'],
                ['Pasa lista', 'Asistencia rápida: marca ausentes y listo; el resto queda como presente.'],
                ['Publica una tarea', 'En Classroom crea la tarea, define la fecha y la nota se sincroniza al libro de calificaciones.'],
            ],
            'consejos' => [
                'Si el período está cerrado, el sistema te avisa y no permite cambiar sus notas.',
                'Una nota fuera de 0–100 se rechaza y la celda vuelve a su valor anterior.',
                'Alt + Q abre tus accesos rápidos desde cualquier pantalla.',
            ],
        ],
        'estudiante' => [
            'nombre'   => 'Estudiantes',
            'perfiles' => 'Estudiante',
            'lema'     => 'Tu escuela en tu bolsillo.',
            'color'    => '#f59e0b',
            'hacer'    => [
                'Ver tus calificaciones y descargar tu Boletín.',
                'Entregar tareas y hacer quizzes en el aula virtual (Classroom).',
                'Consultar tu Horario, tu Asistencia y el Calendario escolar.',
                'Usar tu Carnet digital con código QR para entrar y salir.',
                'Ver tus pagos y tu saldo de cafetería.',
                'Pedir ayuda con el Tutor IA para estudiar.',
            ],
            'pasos' => [
                ['Entra con tu usuario', 'Tu escuela te entrega el correo y la clave; cámbiala la primera vez.'],
                ['Mira tus tareas', 'En «Mis tareas» ves lo pendiente y la fecha límite de cada una.'],
                ['Instálala en tu celular', 'Desde el navegador elige «Agregar a pantalla de inicio»: se abre como una app.'],
            ],
            'consejos' => [
                'Entrega tus tareas antes de la fecha límite: después aparecen como «atrasadas».',
                'Tu boletín solo muestra las notas que el docente ya publicó.',
                'Mantén tu carnet QR en el celular; es personal, no lo compartas.',
            ],
        ],
        'familia' => [
            'nombre'   => 'Familias',
            'perfiles' => 'Padre, madre o representante',
            'lema'     => 'Acompaña a tu hijo, a un toque de distancia.',
            'color'    => '#ec4899',
            'hacer'    => [
                'Ver las notas y el Boletín de cada hijo, período por período.',
                'Revisar su Asistencia y las alertas de ausencias.',
                'Seguir sus tareas y su aula virtual.',
                'Consultar Pagos y el estado de cuenta, y recibir recordatorios.',
                'Leer Comunicados y escribir mensajes al centro.',
                'Hacer Solicitudes (constancias, cartas) sin ir a la escuela.',
            ],
            'pasos' => [
                ['Entra a «Mis hijos»', 'Verás un resumen de cada hijo: asistencia, notas y pendientes.'],
                ['Activa las notificaciones', 'Instala la app en tu celular y recibe avisos de ausencias y comunicados.'],
                ['Mantén tus datos al día', 'Pide al centro actualizar tu teléfono y correo para no perder avisos.'],
            ],
            'consejos' => [
                'Solo ves a tus propios hijos: tus datos y los de ellos están protegidos.',
                'Si tienes varios hijos, cámbialos desde el inicio de tu panel.',
                'Para dudas académicas, usa Mensajes: queda constancia de la conversación.',
            ],
        ],
        'finanzas' => [
            'nombre'   => 'Caja y finanzas',
            'perfiles' => 'Caja / Finanzas',
            'lema'     => 'Cada peso registrado, cada familia informada.',
            'color'    => '#059669',
            'hacer'    => [
                'Registrar Pagos y entregar el recibo en PDF.',
                'Ver Deudores y enviar recordatorios de cobro.',
                'Consultar el estado de cuenta de cada estudiante.',
                'Aplicar Becas y descuentos con trazabilidad.',
                'Sacar Reportes de ingresos por período, concepto o grupo.',
                'Exportar los pagos a Excel para tu contabilidad.',
            ],
            'pasos' => [
                ['Registra el pago', 'Busca al estudiante, elige el concepto y el método; el recibo se genera solo.'],
                ['Revisa deudores', 'Cada mañana mira quién tiene pagos vencidos y envía el recordatorio.'],
                ['Cierra el día', 'Compara el total del día con el reporte de ingresos antes de entregar caja.'],
            ],
            'consejos' => [
                'Todo cambio o eliminación de un pago queda registrado en el Log de Actividad, con fecha y responsable.',
                'Los recordatorios se envían a la familia por los canales que el centro tenga activos.',
                'Exportar pagos a Excel requiere permiso: pídelo a la dirección si no lo ves.',
            ],
        ],
        'apoyo' => [
            'nombre'   => 'Biblioteca y recepción',
            'perfiles' => 'Biblioteca · Recepción',
            'lema'     => 'La puerta y la biblioteca, ordenadas.',
            'color'    => '#64748b',
            'hacer'    => [
                'Biblioteca: administrar el catálogo y registrar préstamos y devoluciones.',
                'Biblioteca: ver préstamos vencidos y quién los tiene.',
                'Recepción: registrar la entrada y salida con el carnet QR.',
                'Recepción: ver el historial de accesos de cada estudiante.',
                'Consultar la ficha básica del estudiante.',
                'Leer los Comunicados del centro.',
            ],
            'pasos' => [
                ['Abre tu pantalla de trabajo', 'Biblioteca o «Entrada y salida»: son tus accesos rápidos principales.'],
                ['Escanea o busca', 'El carnet QR identifica al estudiante al instante; también puedes buscarlo por nombre.'],
                ['Revisa lo pendiente', 'Al cerrar el día, mira préstamos vencidos o accesos sin salida.'],
            ],
            'consejos' => [
                'El kiosco de recepción funciona en una tablet a pantalla completa.',
                'Si el QR no se lee, busca al estudiante por nombre o matrícula.',
                'Todos los registros guardan fecha, hora y quién los hizo.',
            ],
        ],
    ];

    /** Rol del sistema (clave de AccesosRapidos) → guía que le corresponde. */
    private const POR_ROL = [
        'super_admin' => 'direccion', 'administrador' => 'direccion', 'director' => 'direccion', 'coordinacion' => 'coordinacion',
        'registro' => 'secretaria', 'secretaria' => 'secretaria', 'personal' => 'secretaria', 'caja' => 'finanzas',
        'biblioteca' => 'apoyo', 'recepcion' => 'apoyo', 'docente' => 'docente', 'estudiante' => 'estudiante', 'representante' => 'familia',
    ];

    public static function existe(string $slug): bool
    {
        return isset(self::GUIAS[$slug]);
    }

    /** @return array<string, mixed>|null */
    public static function obtener(string $slug): ?array
    {
        return self::GUIAS[$slug] ?? null;
    }

    public static function slugPara(?User $usuario): ?string
    {
        $rol = AccesosRapidos::rol($usuario);

        return $rol ? (self::POR_ROL[$rol] ?? null) : null;
    }

    /** Roles del sistema que cubre cada guía, para comprobar que ninguno se queda sin su hoja. */
    public static function rolesCubiertos(): array
    {
        return array_keys(self::POR_ROL);
    }
}
