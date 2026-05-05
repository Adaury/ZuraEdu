<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    protected $fillable = ['nombre', 'nivel', 'ciclo', 'orden'];

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function indicadoresAprendizaje()
    {
        return $this->hasMany(IndicadorAprendizaje::class);
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeOrdenados($q) { return $q->orderBy('nivel'); }
    public function scopePrimerCiclo($q)  { return $q->where('ciclo', 'primer_ciclo'); }
    public function scopeSegundoCiclo($q) { return $q->where('ciclo', 'segundo_ciclo'); }

    /* ── Helpers ──────────────────────────────────────── */

    public function getCicloLabelAttribute(): string
    {
        return $this->ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo';
    }

    public function esPrimerCiclo(): bool  { return $this->ciclo === 'primer_ciclo'; }
    public function esSegundoCiclo(): bool { return $this->ciclo === 'segundo_ciclo'; }
}
