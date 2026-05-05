<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['nombre', 'tipo', 'ciclo', 'color', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    /* ── Relaciones ─────────────────────────────────────────── */

    public function asignaturas()
    {
        return $this->hasMany(Asignatura::class);
    }

    /* ── Scopes ─────────────────────────────────────────────── */

    public function scopeActivas($q) { return $q->where('activo', true); }
    public function scopeAcademica($q) { return $q->where('tipo', 'academica'); }
    public function scopeTecnica($q)   { return $q->where('tipo', 'tecnica'); }

    /* ── Helpers ─────────────────────────────────────────────── */

    /** Etiqueta legible del ciclo */
    public function getCicloLabelAttribute(): string
    {
        return match($this->ciclo) {
            'primer_ciclo'  => 'Primer Ciclo',
            'segundo_ciclo' => 'Segundo Ciclo',
            default         => 'Ambos Ciclos',
        };
    }
}
