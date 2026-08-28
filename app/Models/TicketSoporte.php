<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketSoporte extends Model
{
    use BelongsToTenant;

    protected $table = 'tickets_soporte';

    protected $fillable = [
        'tenant_id',
        'solicitante_id',
        'asignado_a_id',
        'titulo',
        'descripcion',
        'categoria',
        'prioridad',
        'estado',
        'sla_vencimiento_at',
        'sla_incumplido',
        'causa_raiz',
    ];

    protected $casts = [
        'sla_vencimiento_at' => 'datetime',
        'sla_incumplido'     => 'boolean',
    ];

    // ── Constantes de etiquetas ───────────────────────────────────────────
    const CATEGORIAS = [
        'tecnico'        => 'Técnico',
        'academico'      => 'Académico',
        'administrativo' => 'Administrativo',
        'otro'           => 'Otro',
    ];

    const PRIORIDADES = [
        'baja'    => 'Baja',
        'media'   => 'Media',
        'alta'    => 'Alta',
        'urgente' => 'Urgente',
    ];

    const ESTADOS = [
        'abierto'    => 'Abierto',
        'en_proceso' => 'En proceso',
        'resuelto'   => 'Resuelto',
        'cerrado'    => 'Cerrado',
    ];

    // Colores Tailwind para badges de prioridad
    const COLORES_PRIORIDAD = [
        'baja'    => 'bg-gray-100 text-gray-700',
        'media'   => 'bg-blue-100 text-blue-700',
        'alta'    => 'bg-orange-100 text-orange-700',
        'urgente' => 'bg-red-100 text-red-700',
    ];

    // Colores Tailwind para badges de estado
    const COLORES_ESTADO = [
        'abierto'    => 'bg-green-100 text-green-700',
        'en_proceso' => 'bg-yellow-100 text-yellow-700',
        'resuelto'   => 'bg-indigo-100 text-indigo-700',
        'cerrado'    => 'bg-gray-100 text-gray-500',
    ];

    // ── SLA: horas máximas de primera resolución por prioridad ─────────────
    // Recomendación #5 de la auditoría Don Bosco (#26). No configurable por
    // institución todavía (fuera de alcance de "campos aditivos") — valores
    // razonables por defecto, ajustables aquí si se necesita en el futuro.
    const SLA_HORAS = [
        'baja'    => 72,
        'media'   => 48,
        'alta'    => 24,
        'urgente' => 4,
    ];

    const SLA_ESTADO_LABELS = [
        'vencido'     => 'Vencido',
        'por_vencer'  => 'Por vencer',
        'a_tiempo'    => 'A tiempo',
        'cumplido'    => 'Cumplido',
        'incumplido'  => 'Incumplido',
    ];

    const SLA_ESTADO_COLORES = [
        'vencido'    => 'bg-red-100 text-red-700',
        'por_vencer' => 'bg-orange-100 text-orange-700',
        'a_tiempo'   => 'bg-green-100 text-green-700',
        'cumplido'   => 'bg-green-100 text-green-700',
        'incumplido' => 'bg-red-100 text-red-700',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            if (! $ticket->sla_vencimiento_at) {
                $horas = self::SLA_HORAS[$ticket->prioridad] ?? self::SLA_HORAS['media'];
                $ticket->sla_vencimiento_at = now()->addHours($horas);
            }
        });
    }

    /**
     * Marca sla_incumplido de forma permanente en el momento en que el ticket
     * se resuelve por primera vez — deja un registro histórico aunque el
     * ticket se reabra o cierre después. Llamar antes de guardar el nuevo
     * estado 'resuelto'.
     */
    public function marcarSlaAlResolver(): void
    {
        if ($this->sla_vencimiento_at && ! $this->sla_incumplido) {
            $this->sla_incumplido = now()->greaterThan($this->sla_vencimiento_at);
        }
    }

    // ── Relaciones ─────────────────────────────────────────────────────────
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaTicket::class, 'ticket_id')->orderBy('created_at');
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getCategoriaNombreAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }

    public function getPrioridadNombreAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getColorPrioridadAttribute(): string
    {
        return self::COLORES_PRIORIDAD[$this->prioridad] ?? 'bg-gray-100 text-gray-700';
    }

    public function getColorEstadoAttribute(): string
    {
        return self::COLORES_ESTADO[$this->estado] ?? 'bg-gray-100 text-gray-500';
    }

    /**
     * Estado del SLA: 'vencido'/'por_vencer'/'a_tiempo' mientras el ticket
     * sigue abierto (calculado en vivo contra now()); 'cumplido'/'incumplido'
     * una vez resuelto/cerrado (usa el valor fijado por marcarSlaAlResolver()).
     * Null si el ticket no tiene sla_vencimiento_at (tickets creados antes
     * de esta migración).
     */
    public function getSlaEstadoAttribute(): ?string
    {
        if (! $this->sla_vencimiento_at) {
            return null;
        }

        if (in_array($this->estado, ['resuelto', 'cerrado'])) {
            return $this->sla_incumplido ? 'incumplido' : 'cumplido';
        }

        if (now()->greaterThan($this->sla_vencimiento_at)) {
            return 'vencido';
        }

        // abs(): Carbon 3 puede devolver diffIn*() con signo según dirección; el valor
        // absoluto es lo que importa aquí (ya se descartó "vencido" arriba).
        return abs(now()->diffInHours($this->sla_vencimiento_at)) <= 4 ? 'por_vencer' : 'a_tiempo';
    }

    public function getSlaEstadoNombreAttribute(): ?string
    {
        $estado = $this->sla_estado;
        return $estado ? (self::SLA_ESTADO_LABELS[$estado] ?? $estado) : null;
    }

    public function getSlaEstadoColorAttribute(): ?string
    {
        $estado = $this->sla_estado;
        return $estado ? (self::SLA_ESTADO_COLORES[$estado] ?? 'bg-gray-100 text-gray-500') : null;
    }

    // ── Scopes ─────────────────────────────────────────────────────────────
    public function scopeDelSolicitante($query, int $userId)
    {
        return $query->where('solicitante_id', $userId);
    }

    public function scopeConEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeConCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeConPrioridad($query, string $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    public function scopeVencidos($query)
    {
        return $query->whereIn('estado', ['abierto', 'en_proceso'])
            ->whereNotNull('sla_vencimiento_at')
            ->where('sla_vencimiento_at', '<', now());
    }
}
