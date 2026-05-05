<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class EntregaTarea extends Model
{
    use BelongsToTenant;

    protected $table = 'entregas_tarea';

    protected $fillable = [
        'tarea_id',
        'estudiante_id',
        'estado',
        'notas_docente',
        'calificacion',
        'fecha_entrega',
    ];

    protected $casts = [
        'calificacion'  => 'decimal:2',
        'fecha_entrega' => 'datetime',
    ];

    // ── Estados ───────────────────────────────────────────────────────────
    const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'entregada'  => 'Entregada',
        'revisada'   => 'Revisada',
    ];

    const COLORES_ESTADO = [
        'pendiente' => '#f59e0b',
        'entregada' => '#3b82f6',
        'revisada'  => '#10b981',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function tarea()
    {
        return $this->belongsTo(Tarea::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    public function getEstadoColorAttribute(): string
    {
        return self::COLORES_ESTADO[$this->estado] ?? '#6b7280';
    }
}
