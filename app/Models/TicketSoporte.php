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
        'solicitante_id',
        'asignado_a_id',
        'titulo',
        'descripcion',
        'categoria',
        'prioridad',
        'estado',
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
}
