<?php

namespace App\Models;

use App\Events\NotificationCreated;
use App\Jobs\EnviarNotificacionJob;
use App\Jobs\EnviarPushLoteJob;
use App\Services\NotificacionPreferenciaService;
use App\Services\PushNotificationService;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Notificacion extends Model
{
    use BelongsToTenant;

    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id', 'tipo', 'titulo', 'mensaje', 'datos', 'leida', 'leida_en',
    ];

    protected $casts = [
        'datos'    => 'array',
        'leida'    => 'boolean',
        'leida_en' => 'datetime',
    ];

    // ── Iconos por tipo ───────────────────────────────────────────────────
    const ICONOS = [
        'nueva_nota'       => 'bi-journal-check',
        'ausencia'         => 'bi-calendar-x',
        'comunicado'       => 'bi-megaphone',
        'observacion'      => 'bi-chat-square-text',
        'alerta'           => 'bi-exclamation-triangle',
        'horario'          => 'bi-calendar-week',
        'recursos'         => 'bi-folder-fill',
        'planificacion'    => 'bi-journal-text',
        'general'          => 'bi-bell',
        // ZuraClass
        'zura_tarea'       => 'bi-pencil-fill',
        'zura_calificado'  => 'bi-check-circle-fill',
        'zura_devuelto'    => 'bi-arrow-return-left',
        'zura_quiz'        => 'bi-clipboard-check-fill',
        'zura_anuncio'     => 'bi-megaphone-fill',
        'zura_material'    => 'bi-book-fill',
        'zura_boletin'     => 'bi-file-earmark-text',
        'boletin'          => 'bi-file-earmark-text',
        'asistencia'       => 'bi-calendar-check',
        'carnet_acceso'    => 'bi-person-badge-fill',
        // Tipos que ya se usaban en ~15 call sites reales pero nunca se
        // catalogaron aquí (caían al fallback 'bi-bell' silenciosamente).
        'pago'             => 'bi-cash-coin',
        'academica'        => 'bi-mortarboard-fill',
        'cumpleanos'       => 'bi-balloon-fill',
        'solicitud'        => 'bi-inbox-fill',
        'info'             => 'bi-info-circle-fill',
    ];

    const COLORES = [
        'nueva_nota'       => '#10b981',
        'ausencia'         => '#ef4444',
        'comunicado'       => '#3b82f6',
        'observacion'      => '#f59e0b',
        'alerta'           => '#dc2626',
        'horario'          => '#6366f1',
        'recursos'         => '#2563eb',
        'planificacion'    => '#7c3aed',
        'general'          => '#6b7280',
        // ZuraClass
        'zura_tarea'       => '#f59e0b',
        'zura_calificado'  => '#16a34a',
        'zura_devuelto'    => '#f59e0b',
        'zura_quiz'        => '#4f46e5',
        'zura_anuncio'     => '#6366f1',
        'zura_material'    => '#10b981',
        'zura_boletin'     => '#1e40af',
        'boletin'          => '#1e40af',
        'asistencia'       => '#dc2626',
        'carnet_acceso'    => '#0d9488',
        // Mismo lote agregado en ICONOS -- ver comentario arriba.
        'pago'             => '#16a34a',
        'academica'        => '#2563eb',
        'cumpleanos'       => '#ec4899',
        'solicitud'        => '#0891b2',
        'info'             => '#0ea5e9',
    ];

    /**
     * Categorías de notificación -- matriz de institución (Setting
     * notif_inapp_{cat}/notif_push_{cat}) y preferencias de usuario
     * (User::notif_push_prefs). 'inapp_bloqueado' = la institución NO puede
     * apagar el in-app de esa categoría -- hoy solo 'sistema', el catch-all
     * de 'general' que arrastra avisos críticos de cuenta (aprobación de
     * acceso, mensajes directos, tickets, nómina). Follow-up documentado:
     * partir 'general' en tipos finos y quitar la bandera.
     */
    const CATEGORIAS = [
        'academico'    => ['label' => 'Académico',                       'icono' => 'bi-mortarboard-fill',          'color' => '#2563eb', 'desc' => 'Calificaciones, boletines, planificaciones y recursos didácticos.'],
        'zuraclass'    => ['label' => 'Aula Virtual (ZuraClass)',         'icono' => 'bi-easel-fill',                'color' => '#4f46e5', 'desc' => 'Tareas, evaluaciones, anuncios, materiales y entregas calificadas.'],
        'comunicacion' => ['label' => 'Comunicación y Comunidad',         'icono' => 'bi-megaphone-fill',            'color' => '#0ea5e9', 'desc' => 'Comunicados del centro y felicitaciones de cumpleaños.'],
        'alertas'      => ['label' => 'Alertas, Asistencia y Disciplina', 'icono' => 'bi-exclamation-triangle-fill', 'color' => '#dc2626', 'desc' => 'Ausencias, asistencia, observaciones, faltas y entradas/salidas del carnet.'],
        'pagos'        => ['label' => 'Pagos y Finanzas',                 'icono' => 'bi-credit-card-fill',          'color' => '#16a34a', 'desc' => 'Pagos confirmados y recordatorios de cuotas próximas o vencidas.'],
        'operacion'    => ['label' => 'Operación del Centro',             'icono' => 'bi-building-gear',             'color' => '#0891b2', 'desc' => 'Solicitudes de estudiantes y docentes y su resolución.'],
        'sistema'      => ['label' => 'Mensajes y Sistema',               'icono' => 'bi-bell-fill',                 'color' => '#6b7280', 'desc' => 'Mensajes directos, tickets de soporte, aprobación de acceso y avisos generales.', 'inapp_bloqueado' => true],
    ];

    /** tipo → categoría. Tipo desconocido cae en 'sistema' (nunca se pierde una notificación). */
    const TIPO_CATEGORIA = [
        'academica' => 'academico', 'nueva_nota' => 'academico', 'boletin' => 'academico',
        'zura_boletin' => 'academico', 'planificacion' => 'academico', 'recursos' => 'academico',
        'horario' => 'academico',

        'zura_tarea' => 'zuraclass', 'zura_quiz' => 'zuraclass', 'zura_anuncio' => 'zuraclass',
        'zura_material' => 'zuraclass', 'zura_calificado' => 'zuraclass', 'zura_devuelto' => 'zuraclass',

        'comunicado' => 'comunicacion', 'cumpleanos' => 'comunicacion',

        'alerta' => 'alertas', 'observacion' => 'alertas', 'ausencia' => 'alertas',
        'asistencia' => 'alertas', 'carnet_acceso' => 'alertas',

        'pago' => 'pagos',

        'solicitud' => 'operacion', 'info' => 'operacion',

        'general' => 'sistema',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getIconoAttribute(): string
    {
        return self::ICONOS[$this->tipo] ?? 'bi-bell';
    }

    public function getColorAttribute(): string
    {
        return self::COLORES[$this->tipo] ?? '#6b7280';
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeDelUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────

    /**
     * Crea una notificación. Cuando la cola es asíncrona la despacha como job
     * para no bloquear el request HTTP.
     *
     * Gating de Notificaciones Configurables (Fase 5, pieza 3):
     * - Gate 1 (in-app, institución): se evalúa ANTES del dispatch/creación
     *   -- si la categoría está apagada, no queda ningún rastro (ni fila,
     *   ni job, ni push, ni broadcast). Ir después del `if (queue...)`
     *   encolaría trabajo que el worker descartaría igual.
     * - Gate 2 (push, institución + usuario): envuelve SOLO la llamada a
     *   PushNotificationService. NotificationCreated::dispatch() queda
     *   fuera del gate: es la contraparte realtime del in-app (campanita),
     *   no del push -- anidarlo apagaría el realtime al silenciar solo el
     *   celular.
     */
    public static function enviar(int $userId, string $tipo, string $titulo, string $mensaje, array $datos = []): void
    {
        if (! NotificacionPreferenciaService::inAppActivo($tipo)) {
            return;
        }

        if (config('queue.default') !== 'sync') {
            $tenantId = tenant_id();
            if ($tenantId) {
                EnviarNotificacionJob::dispatch(
                    userId:   $userId,
                    tipo:     $tipo,
                    titulo:   $titulo,
                    mensaje:  $mensaje,
                    datos:    $datos,
                    tenantId: $tenantId,
                )->onQueue('notifications');
                return;
            }
        }

        $notif = static::create([
            'user_id' => $userId,
            'tipo'    => $tipo,
            'titulo'  => $titulo,
            'mensaje' => $mensaje,
            'datos'   => $datos ?: null,
        ]);
        Cache::forget("user_{$userId}_notif_unread");

        if (NotificacionPreferenciaService::pushActivo($userId, $tipo)) {
            try {
                PushNotificationService::sendToUser($userId, $titulo, $mensaje, array_merge($datos, ['tipo' => $tipo]));
            } catch (\Throwable) {}
        }

        try {
            NotificationCreated::dispatch(
                $notif->user_id,
                $notif->tipo,
                $notif->titulo,
                $notif->mensaje,
                $notif->datos['url'] ?? null,
                self::ICONOS[$notif->tipo] ?? 'bi-bell',
            );
        } catch (\Throwable) {}
    }

    /**
     * Envía la misma notificación a múltiples usuarios (bulk insert síncrono).
     * Usar solo para operaciones masivas donde el tiempo de inserción es aceptable.
     *
     * Gate 1 (in-app) es por CATEGORÍA, no por usuario -- una sola lectura
     * de Setting (memoizada) decide todo o nada antes del insert, sin
     * bucle. Gate 2 (push) se saca del request por completo: si la
     * institución permite push para la categoría, se despacha un job POR
     * LOTE (chunks de 500) que filtra la preferencia individual de cada
     * destinatario con 1 query para los N -- nunca un bucle de N pushes
     * síncronos dentro del request HTTP.
     */
    public static function enviarA(array $userIds, string $tipo, string $titulo, string $mensaje, array $datos = []): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if (empty($userIds)) {
            return;
        }

        if (! NotificacionPreferenciaService::inAppActivo($tipo)) {
            return;
        }

        $now = now();
        $tenantId = tenant_id();
        $rows = array_map(fn($id) => [
            'tenant_id'  => $tenantId,
            'user_id'    => $id,
            'tipo'       => $tipo,
            'titulo'     => $titulo,
            'mensaje'    => $mensaje,
            'datos'      => $datos ? json_encode($datos) : null,
            'leida'      => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        static::withoutTenant()->insert($rows);
        foreach ($userIds as $id) {
            Cache::forget("user_{$id}_notif_unread");
        }

        if (NotificacionPreferenciaService::pushInstitucionActivo($tipo)) {
            foreach (array_chunk($userIds, 500) as $chunk) {
                EnviarPushLoteJob::dispatch(
                    userIds:  $chunk,
                    tipo:     $tipo,
                    titulo:   $titulo,
                    mensaje:  $mensaje,
                    datos:    $datos,
                    tenantId: $tenantId ?? 0,
                )->onQueue('notifications');
            }
        }
    }
}
