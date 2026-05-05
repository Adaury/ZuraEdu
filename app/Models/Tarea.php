<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use BelongsToTenant;

    protected $table = 'tareas';

    protected $fillable = [
        'asignacion_id',
        'titulo',
        'descripcion',
        'fecha_limite',
        'tipo',
        'puntos_valor',
        'activo',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'activo'       => 'boolean',
    ];

    // ── Tipos disponibles ─────────────────────────────────────────────────
    const TIPOS = [
        'tarea'      => 'Tarea',
        'actividad'  => 'Actividad',
        'proyecto'   => 'Proyecto',
        'evaluacion' => 'Evaluación',
    ];

    const COLORES_TIPO = [
        'tarea'      => '#3b82f6',
        'actividad'  => '#10b981',
        'proyecto'   => '#8b5cf6',
        'evaluacion' => '#ef4444',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function entregas()
    {
        return $this->hasMany(EntregaTarea::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function getTipoColorAttribute(): string
    {
        return self::COLORES_TIPO[$this->tipo] ?? '#6b7280';
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_limite->isPast();
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
