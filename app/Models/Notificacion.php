<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

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
     * Crea y envía una notificación a un usuario.
     */
    public static function enviar(int $userId, string $tipo, string $titulo, string $mensaje, array $datos = []): self
    {
        return static::create([
            'user_id' => $userId,
            'tipo'    => $tipo,
            'titulo'  => $titulo,
            'mensaje' => $mensaje,
            'datos'   => $datos ?: null,
        ]);
    }

    /**
     * Envía la misma notificación a múltiples usuarios.
     */
    public static function enviarA(array $userIds, string $tipo, string $titulo, string $mensaje, array $datos = []): void
    {
        $now = now();
        $rows = array_map(fn($id) => [
            'user_id'    => $id,
            'tipo'       => $tipo,
            'titulo'     => $titulo,
            'mensaje'    => $mensaje,
            'datos'      => $datos ? json_encode($datos) : null,
            'leida'      => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        static::insert($rows);
    }
}
