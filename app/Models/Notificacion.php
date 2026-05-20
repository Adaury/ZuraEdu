<?php

namespace App\Models;

use App\Events\NotificationCreated;
use App\Jobs\EnviarNotificacionJob;
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
     */
    public static function enviar(int $userId, string $tipo, string $titulo, string $mensaje, array $datos = []): void
    {
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

        // Push notification al dispositivo móvil
        try {
            PushNotificationService::sendToUser($userId, $titulo, $mensaje, array_merge($datos, ['tipo' => $tipo]));
        } catch (\Throwable) {}

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
     */
    public static function enviarA(array $userIds, string $tipo, string $titulo, string $mensaje, array $datos = []): void
    {
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
    }
}
