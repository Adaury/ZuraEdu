<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecursoFisico extends Model
{
    use BelongsToTenant;

    protected $table = 'recursos_fisicos';

    protected $fillable = [
        'nombre',
        'tipo',
        'capacidad',
        'ubicacion',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo'    => 'boolean',
        'capacidad' => 'integer',
    ];

    // ── Constantes ────────────────────────────────────────────────────────
    const TIPOS = [
        'aula'              => ['label' => 'Aula',               'color' => 'blue',   'icon' => 'academic-cap'],
        'laboratorio'       => ['label' => 'Laboratorio',        'color' => 'purple', 'icon' => 'beaker'],
        'sala_computadoras' => ['label' => 'Sala de Cómputos',   'color' => 'indigo', 'icon' => 'computer-desktop'],
        'cancha'            => ['label' => 'Cancha',             'color' => 'green',  'icon' => 'trophy'],
        'auditorio'         => ['label' => 'Auditorio',          'color' => 'yellow', 'icon' => 'musical-note'],
        'proyector'         => ['label' => 'Proyector',          'color' => 'orange', 'icon' => 'film'],
        'otro'              => ['label' => 'Otro',               'color' => 'gray',   'icon' => 'squares-2x2'],
    ];

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // ── Relaciones ────────────────────────────────────────────────────────
    public function reservas(): HasMany
    {
        return $this->hasMany(ReservaRecurso::class, 'recurso_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? ['label' => $this->tipo, 'color' => 'gray', 'icon' => 'squares-2x2'];
    }

    /**
     * Reservas aprobadas en una fecha dada.
     */
    public function reservasEnFecha(string $fecha)
    {
        return $this->reservas()
            ->where('fecha', $fecha)
            ->where('estado', 'aprobada')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Verifica si hay conflicto con un rango horario (excluye $exceptId).
     */
    public function tieneConflicto(string $fecha, string $horaInicio, string $horaFin, ?int $exceptId = null): bool
    {
        return $this->reservas()
            ->where('fecha', $fecha)
            ->where('estado', 'aprobada')
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->where(function ($q) use ($horaInicio, $horaFin) {
                // Solapamiento: inicio < fin_existente && fin > inicio_existente
                $q->where('hora_inicio', '<', $horaFin)
                  ->where('hora_fin',    '>',  $horaInicio);
            })
            ->exists();
    }
}
