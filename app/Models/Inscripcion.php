<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use BelongsToTenant;

    protected $table = 'inscripciones';

    protected $fillable = [
        'school_year_id',
        'estudiante_id',
        'estado',
        'origen',
        'grado_id',
        'fecha_inscripcion',
        'observaciones',
        'grupo_id',
        'matricula_id',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePendientes($q)
    {
        return $q->where('estado', 'pendiente');
    }

    public function scopeAsignadas($q)
    {
        return $q->where('estado', 'asignada');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getOrigenLabelAttribute(): string
    {
        return match ($this->origen) {
            'continuidad' => 'Continuidad',
            'nueva'       => 'Nuevo Ingreso',
            'traslado'    => 'Traslado',
            default       => ucfirst($this->origen),
        };
    }

    public function getEstadoBadgeColorAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'warning',
            'asignada'  => 'success',
            'cancelada' => 'danger',
            default     => 'secondary',
        };
    }
}
