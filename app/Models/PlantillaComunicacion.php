<?php

namespace App\Models;

use App\Services\PlantillaComunicacionService;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Plantillas de Comunicación — permite a cada centro editar el TEXTO de sus
 * notificaciones automáticas (WhatsApp/email) sin tocar código. El sistema
 * hardcodeado actual (WhatsAppService, clases Mail) sigue siendo el
 * fallback permanente: una fila aquí es una PERSONALIZACIÓN opcional, no un
 * reemplazo -- ver PlantillaComunicacionService::render()/renderEmail().
 *
 * Fuera de catálogo a propósito (plataforma ZuraEdu -> dueño del centro, no
 * centro -> representante, y corren sin tenant bindeado): SuscripcionActivada
 * y PagoReembolsado (WebhookStripeController), y el lead de EncuestaInteresController.
 */
class PlantillaComunicacion extends Model
{
    use BelongsToTenant;

    protected $table = 'plantillas_comunicacion';

    protected $fillable = ['tenant_id', 'evento', 'canal', 'asunto', 'cuerpo', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public const CANALES = [
        'whatsapp' => 'WhatsApp',
        'email'    => 'Correo electrónico',
    ];

    public const GRUPOS = [
        'academico' => 'Académico',
        'pagos'     => 'Pagos y Finanzas',
        'admision'  => 'Admisión y Accesos',
        'operacion' => 'Operación del Centro',
    ];

    /**
     * Catálogo de eventos. 'default' es el texto tal cual como está hoy
     * hardcodeado (interpolaciones PHP convertidas a {{variable}}) -- NUNCA
     * se persiste, solo precarga el editor vía "Cargar texto predeterminado".
     * Para los eventos de email, 'default' es una versión simplificada del
     * diseño real (el email real tiene su propio maquetado HTML en
     * resources/views/emails/*.blade.php); no es una transcripción
     * pixel-perfect, es un punto de partida honesto para editar.
     */
    public const EVENTOS = [
        // ── WhatsApp ─────────────────────────────────────────────────────
        'ausencia_registrada' => [
            'label' => 'Ausencia registrada', 'grupo' => 'academico', 'canales' => ['whatsapp'],
            'origen' => 'app/Services/WhatsAppService.php:26-33 (sendAbsence)',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'asignatura' => ['desc' => 'Asignatura', 'ej' => 'Matemática'],
                'fecha' => ['desc' => 'Fecha de la ausencia', 'ej' => '15/03/2026'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['whatsapp' => "⚠️ *{{centro}}*\n\nEstimado representante, *{{estudiante}}* registró una *ausencia* en *{{asignatura}}* el {{fecha}}.\n\nRevise el portal del representante."],
        ],
        'calificacion_publicada' => [
            'label' => 'Calificación publicada', 'grupo' => 'academico', 'canales' => ['whatsapp'],
            'origen' => 'app/Services/WhatsAppService.php:35-43 (sendGradePublished)',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'asignatura' => ['desc' => 'Asignatura', 'ej' => 'Matemática'],
                'nota' => ['desc' => 'Calificación', 'ej' => '95'],
                'emoji' => ['desc' => 'Semáforo según la nota (🟢/🟡/🔴)', 'ej' => '🟢'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['whatsapp' => "{{emoji}} *{{centro}}*\n\nCalificaciones de *{{estudiante}}* en *{{asignatura}}* publicadas.\n📊 Nota: *{{nota}}*\n\nRevise el portal."],
        ],
        'alerta_generica' => [
            'label' => 'Alerta genérica al representante', 'grupo' => 'academico', 'canales' => ['whatsapp'],
            'origen' => 'app/Services/WhatsAppService.php:45-52 (sendAlert)',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'mensaje' => ['desc' => 'Texto de la alerta', 'ej' => 'Recuerde traer el uniforme deportivo mañana.'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['whatsapp' => "🔔 *{{centro}}*\n\nEstimado representante de *{{estudiante}}*:\n\n{{mensaje}}\n\n{{url_portal}}"],
        ],
        'pago_confirmado' => [
            'label' => 'Pago confirmado', 'grupo' => 'pagos', 'canales' => ['whatsapp'],
            'origen' => 'app/Listeners/NotificarPagoConfirmado.php:66',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'concepto' => ['desc' => 'Concepto del pago', 'ej' => 'Mensualidad Marzo'],
                'monto' => ['desc' => 'Monto con moneda', 'ej' => 'RD$ 4,500.00'],
                'fecha' => ['desc' => 'Fecha del pago', 'ej' => '15/03/2026'],
                'metodo' => ['desc' => 'Método de pago', 'ej' => 'Transferencia'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['whatsapp' => "✅ *{{centro}}*\n\nEstimado representante, el pago de *{{concepto}}* por *{{monto}}* ha sido confirmado.\n📅 Fecha: {{fecha}}\n💳 Método: {{metodo}}\n\nDescargue su recibo desde el portal."],
        ],
        'pago_vencido_recordatorio' => [
            'label' => 'Recordatorio de pagos vencidos (automático)', 'grupo' => 'pagos', 'canales' => ['whatsapp', 'email'],
            'origen' => 'app/Console/Commands/RecordatorioPagosVencidos.php:~96-110',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'monto_total' => ['desc' => 'Monto total vencido', 'ej' => 'RD$ 9,000.00'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => [
                'whatsapp' => "⚠️ *{{centro}}*\n\nEstimado representante, tiene *{{monto_total}}* en pagos escolares vencidos de *{{estudiante}}*.\n\nPor favor regularice su situación ingresando al portal: {{url_portal}}",
                'email' => "<p>Estimado representante,</p><p>Tiene <strong>{{monto_total}}</strong> en pagos escolares vencidos de <strong>{{estudiante}}</strong>.</p><p>Por favor regularice su situación ingresando al portal: {{url_portal}}</p>",
            ],
        ],
        'pago_proximo_vencer' => [
            'label' => 'Pago próximo a vencer', 'grupo' => 'pagos', 'canales' => ['whatsapp'],
            'origen' => 'app/Console/Commands/AlertasProximosPagos.php:~106-113',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'concepto' => ['desc' => 'Concepto del pago', 'ej' => 'Mensualidad Abril'],
                'monto' => ['desc' => 'Monto con moneda', 'ej' => 'RD$ 4,500.00'],
                'fecha_vencimiento' => ['desc' => 'Fecha de vencimiento', 'ej' => '30/04/2026'],
                'cuando' => ['desc' => 'Frase relativa', 'ej' => 'en 3 días'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['whatsapp' => "⏰ *{{centro}}*\n\nEstimado representante, el pago de *{{estudiante}}*:\n\n📋 *{{concepto}}*\n💰 Monto: *{{monto}}*\n📅 Vence: *{{fecha_vencimiento}}* ({{cuando}})\n\nPuede pagar desde el portal: {{url_portal}}"],
        ],
        'pago_recordatorio_manual' => [
            'label' => 'Recordatorio de pago (envío manual del admin)', 'grupo' => 'pagos', 'canales' => ['whatsapp'],
            'origen' => 'app/Http/Controllers/Admin/PagoController.php:783',
            'variables' => [
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'cuotas' => ['desc' => 'Cantidad de cuotas vencidas', 'ej' => '2'],
                'monto' => ['desc' => 'Monto total con moneda', 'ej' => 'RD$ 9,000.00'],
            ],
            'default' => ['whatsapp' => "Recordatorio: {{estudiante}} tiene {{cuotas}} cuota(s) vencida(s) por {{monto}}. Por favor regularice su situación."],
        ],
        'cumpleanos_estudiante' => [
            'label' => 'Cumpleaños del estudiante', 'grupo' => 'operacion', 'canales' => ['whatsapp'],
            'origen' => 'app/Console/Commands/AlertasCumpleanos.php:~84-93',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'edad' => ['desc' => 'Frase de edad (vacía si no se conoce)', 'ej' => ' (10 años)'],
            ],
            'default' => ['whatsapp' => "🎂 *{{centro}}*\n\nEstimado representante, hoy {{estudiante}}{{edad}} celebra su cumpleaños.\n\n¡Felicitaciones de parte de todo el equipo educativo! 🎉"],
        ],
        'carnet_acceso' => [
            'label' => 'Entrada/salida del centro (Carnet+)', 'grupo' => 'operacion', 'canales' => ['whatsapp'],
            'origen' => 'app/Jobs/NotificarPadreAccesoJob.php:35-72',
            'variables' => [
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'accion' => ['desc' => 'ingresó / salió', 'ej' => 'ingresó'],
                'hora' => ['desc' => 'Hora del evento', 'ej' => '7:45 am'],
            ],
            'default' => ['whatsapp' => "{{estudiante}} {{accion}} del centro a las {{hora}}."],
        ],
        'aviso_emergencia' => [
            'label' => 'Aviso / emergencia masiva', 'grupo' => 'operacion', 'canales' => ['whatsapp'],
            'origen' => 'app/Http/Controllers/Admin/AvisoEmergenciaController.php:226-232',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'emoji' => ['desc' => 'Icono según el tipo (🚨/📢)', 'ej' => '🚨'],
                'titulo' => ['desc' => 'Título del aviso', 'ej' => 'Suspensión de clases'],
                'mensaje' => ['desc' => 'Cuerpo del aviso', 'ej' => 'Mañana no habrá clases por mantenimiento.'],
            ],
            'default' => ['whatsapp' => "{{emoji}} *{{centro}}* — *{{titulo}}*\n\n{{mensaje}}"],
        ],
        'sigerd_validacion' => [
            'label' => 'Resultado de validación SIGERD', 'grupo' => 'operacion', 'canales' => ['whatsapp'],
            'origen' => 'app/Console/Commands/SigerdValidar.php:~96-110',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'ano_escolar' => ['desc' => 'Año escolar', 'ej' => '2025-2026'],
                'estado' => ['desc' => 'Estado de la validación', 'ej' => '✅ LISTO'],
                'total_estudiantes' => ['desc' => 'Total de estudiantes validados', 'ej' => '412'],
                'total_errores' => ['desc' => 'Cantidad de errores encontrados', 'ej' => '0'],
            ],
            'default' => ['whatsapp' => "📋 *{{centro}}* — Validación SIGERD\n\nAño escolar: *{{ano_escolar}}*\nEstado: *{{estado}}*\nEstudiantes: {{total_estudiantes}}\nErrores: {{total_errores}}\n\nIr a: Integraciones → SIGERD"],
        ],
        'sigerd_exportacion' => [
            'label' => 'Exportación SIGERD completada', 'grupo' => 'operacion', 'canales' => ['whatsapp'],
            'origen' => 'app/Http/Controllers/Admin/SigerdController.php:~140-151',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'tipo_export' => ['desc' => 'Tipo de exportación', 'ej' => 'Nómina y Matrícula'],
                'total_registros' => ['desc' => 'Cantidad de registros exportados', 'ej' => '412'],
                'formato' => ['desc' => 'Formato del archivo', 'ej' => 'XML'],
            ],
            'default' => ['whatsapp' => "✅ *{{centro}}* — SIGERD\n\n*{{tipo_export}}* exportado correctamente.\n📊 {{total_registros}} registros en formato {{formato}}.\n\nEl archivo está listo para cargar en el portal SIGERD/MINERD."],
        ],

        // ── Email ────────────────────────────────────────────────────────
        'inasistencia_alerta' => [
            'label' => 'Alerta de inasistencias', 'grupo' => 'academico', 'canales' => ['email'],
            'origen' => 'app/Mail/AlertaInasistencia.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'asignatura' => ['desc' => 'Asignatura', 'ej' => 'Matemática'],
                'total_ausencias' => ['desc' => 'Total de ausencias', 'ej' => '5'],
                'porcentaje_asistencia' => ['desc' => 'Porcentaje de asistencia', 'ej' => '82%'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['email' => '<p>Estimado representante,</p><p><strong>{{estudiante}}</strong> registra <strong>{{total_ausencias}}</strong> ausencias en {{asignatura}} (asistencia: {{porcentaje_asistencia}}).</p><p>Puede revisar el detalle en el <a href="{{url_portal}}">portal del representante</a>.</p>'],
        ],
        'riesgo_academico' => [
            'label' => 'Alerta de riesgo académico', 'grupo' => 'academico', 'canales' => ['email'],
            'origen' => 'app/Mail/AlertaRiesgoAcademico.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'asignatura' => ['desc' => 'Asignatura', 'ej' => 'Matemática'],
                'nota' => ['desc' => 'Calificación actual', 'ej' => '58'],
                'grupo' => ['desc' => 'Grupo/sección', 'ej' => '6to A'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['email' => '<p>Estimado representante,</p><p><strong>{{estudiante}}</strong> ({{grupo}}) presenta riesgo académico en {{asignatura}} con una nota de {{nota}}.</p><p>Puede revisar el detalle en el <a href="{{url_portal}}">portal del representante</a>.</p>'],
        ],
        'boletin_disponible' => [
            'label' => 'Boletín disponible', 'grupo' => 'academico', 'canales' => ['email'],
            'origen' => 'app/Mail/BoletinDisponible.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'periodo' => ['desc' => 'Período académico', 'ej' => 'Primer Período'],
                'url_boletin' => ['desc' => 'URL para ver/descargar el boletín', 'ej' => 'https://sanrafael.zuraedu.com/boletin/123'],
            ],
            'default' => ['email' => '<p>Estimado representante,</p><p>El boletín de <strong>{{estudiante}}</strong> correspondiente a {{periodo}} ya está disponible.</p><p><a href="{{url_boletin}}">Ver boletín</a></p>'],
        ],
        'cierre_periodo_recordatorio' => [
            'label' => 'Recordatorio de entrega de notas (a docentes)', 'grupo' => 'academico', 'canales' => ['email'],
            'origen' => 'app/Mail/RecordatorioCierrePeriodo.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'docente' => ['desc' => 'Nombre del docente', 'ej' => 'Prof. Juan Ramírez'],
                'evento' => ['desc' => 'Nombre del período/evento', 'ej' => 'Cierre Primer Período'],
                'dias_restantes' => ['desc' => 'Días restantes', 'ej' => '2'],
                'fecha_limite' => ['desc' => 'Fecha límite', 'ej' => '30/03/2026'],
            ],
            'default' => ['email' => '<p>Estimado(a) {{docente}},</p><p>Recuerde que {{evento}} vence en {{dias_restantes}} día(s) ({{fecha_limite}}). Por favor complete la entrega de calificaciones a tiempo.</p>'],
        ],
        'comunicado_publicado' => [
            'label' => 'Comunicado publicado', 'grupo' => 'operacion', 'canales' => ['email'],
            'origen' => 'app/Mail/ComunicadoPublicado.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'titulo' => ['desc' => 'Título del comunicado', 'ej' => 'Reunión de padres'],
                'contenido' => ['desc' => 'Cuerpo del comunicado', 'ej' => 'La reunión será el viernes a las 4pm.'],
                'fecha' => ['desc' => 'Fecha de publicación', 'ej' => '15/03/2026'],
                'url_portal' => ['desc' => 'URL del portal', 'ej' => 'https://sanrafael.zuraedu.com'],
            ],
            'default' => ['email' => '<p><strong>{{titulo}}</strong></p><p>{{contenido}}</p><p><a href="{{url_portal}}">Ver en el portal</a></p>'],
        ],
        'bienvenida_representante' => [
            'label' => 'Bienvenida al Portal de Representantes', 'grupo' => 'admision', 'canales' => ['email'],
            'origen' => 'app/Mail/BienvenidaRepresentante.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'representante' => ['desc' => 'Nombre del representante', 'ej' => 'María Pérez'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'numero_matricula' => ['desc' => 'Número de matrícula', 'ej' => 'MAT-2026-0123'],
                'grado' => ['desc' => 'Grado', 'ej' => '6to de Primaria'],
                'ano_escolar' => ['desc' => 'Año escolar', 'ej' => '2025-2026'],
                'usuario' => ['desc' => 'Usuario de acceso (obligatoria)', 'ej' => 'maria.perez'],
                'clave_temporal' => ['desc' => 'Contraseña temporal (obligatoria)', 'ej' => 'Tmp8x2Kq'],
                'url_login' => ['desc' => 'URL de acceso', 'ej' => 'https://sanrafael.zuraedu.com/login'],
            ],
            'requiere' => ['usuario', 'clave_temporal'],
            'default' => ['email' => '<p>Estimado(a) {{representante}},</p><p>La matrícula de <strong>{{estudiante}}</strong> ({{grado}}, {{ano_escolar}}) ha sido confirmada. Número de matrícula: {{numero_matricula}}.</p><p>Ya puede acceder al Portal de Representantes:</p><p>Usuario: <strong>{{usuario}}</strong><br>Contraseña temporal: <strong>{{clave_temporal}}</strong></p><p><a href="{{url_login}}">Ingresar al portal</a></p>'],
        ],
        'pre_matricula_recibida' => [
            'label' => 'Pre-matrícula recibida', 'grupo' => 'admision', 'canales' => ['email'],
            'origen' => 'app/Mail/PreMatriculaConfirmacion.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'solicitante' => ['desc' => 'Nombre del solicitante', 'ej' => 'María Pérez'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'codigo' => ['desc' => 'Código de seguimiento', 'ej' => 'PM-2026-0045'],
                'grado' => ['desc' => 'Grado solicitado', 'ej' => '6to de Primaria'],
                'fecha' => ['desc' => 'Fecha de la solicitud', 'ej' => '15/03/2026'],
            ],
            'default' => ['email' => '<p>Estimado(a) {{solicitante}},</p><p>Hemos recibido la solicitud de pre-matrícula de <strong>{{estudiante}}</strong> para {{grado}}.</p><p>Código de seguimiento: <strong>{{codigo}}</strong></p><p>Nos comunicaremos pronto con la resolución.</p>'],
        ],
        'pre_matricula_resolucion' => [
            'label' => 'Pre-matrícula aprobada / rechazada', 'grupo' => 'admision', 'canales' => ['email'],
            'origen' => 'app/Mail/PreMatriculaResolucion.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'solicitante' => ['desc' => 'Nombre del solicitante', 'ej' => 'María Pérez'],
                'estudiante' => ['desc' => 'Nombre del estudiante', 'ej' => 'Ana Pérez'],
                'estado' => ['desc' => 'Aprobada / Rechazada', 'ej' => 'Aprobada'],
                'motivo' => ['desc' => 'Motivo (si fue rechazada)', 'ej' => ''],
                'codigo' => ['desc' => 'Código de seguimiento', 'ej' => 'PM-2026-0045'],
            ],
            'default' => ['email' => '<p>Estimado(a) {{solicitante}},</p><p>Su solicitud de pre-matrícula ({{codigo}}) para <strong>{{estudiante}}</strong> ha sido <strong>{{estado}}</strong>.</p><p>{{motivo}}</p>'],
        ],
        'usuario_aprobado' => [
            'label' => 'Acceso de usuario aprobado', 'grupo' => 'admision', 'canales' => ['email'],
            'origen' => 'app/Mail/UsuarioAprobado.php',
            'variables' => [
                'centro' => ['desc' => 'Nombre del centro', 'ej' => 'Colegio San Rafael'],
                'usuario' => ['desc' => 'Nombre del usuario', 'ej' => 'Juan Ramírez'],
                'email' => ['desc' => 'Correo del usuario', 'ej' => 'juan.ramirez@correo.com'],
                'rol' => ['desc' => 'Rol asignado', 'ej' => 'Docente'],
                'url_login' => ['desc' => 'URL de acceso', 'ej' => 'https://sanrafael.zuraedu.com/login'],
            ],
            'default' => ['email' => '<p>Hola {{usuario}},</p><p>Tu acceso a <strong>{{centro}}</strong> como {{rol}} ha sido aprobado.</p><p><a href="{{url_login}}">Iniciar sesión</a></p>'],
        ],
    ];

    public static function eventosPorCanal(string $canal): array
    {
        return array_filter(self::EVENTOS, fn ($def) => in_array($canal, $def['canales'], true));
    }

    public static function existe(string $evento, string $canal): bool
    {
        $def = self::EVENTOS[$evento] ?? null;

        return $def && in_array($canal, $def['canales'], true);
    }

    public static function variablesDe(string $evento): array
    {
        return array_keys(self::EVENTOS[$evento]['variables'] ?? []);
    }

    /** Valores de ejemplo para la vista previa en vivo del editor. */
    public static function ejemplosDe(string $evento): array
    {
        $vars = self::EVENTOS[$evento]['variables'] ?? [];

        return array_map(fn ($v) => $v['ej'], $vars);
    }

    public static function textoDefault(string $evento, string $canal): string
    {
        return self::EVENTOS[$evento]['default'][$canal] ?? '';
    }

    /** Variables que un evento exige explícitamente en el cuerpo (ver bienvenida_representante). */
    public static function variablesRequeridas(string $evento): array
    {
        return self::EVENTOS[$evento]['requiere'] ?? [];
    }

    public function scopeActivas(Builder $q): Builder
    {
        return $q->where('activa', true);
    }

    protected static function booted(): void
    {
        static::saved(fn ($m) => PlantillaComunicacionService::olvidarCache($m->tenant_id));
        static::deleted(fn ($m) => PlantillaComunicacionService::olvidarCache($m->tenant_id));
    }
}
