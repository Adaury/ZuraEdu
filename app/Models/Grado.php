<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nombre', 'nivel', 'ciclo', 'orden', 'activo'];

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
        return match($this->ciclo) {
            'primer_ciclo'  => 'Primer Ciclo',
            'segundo_ciclo' => 'Segundo Ciclo',
            'bachillerato'  => 'Bachillerato',
            'inicial'       => 'Nivel Inicial',
            default         => ucfirst(str_replace('_', ' ', $this->ciclo ?? '')),
        };
    }

    public function esPrimerCiclo(): bool  { return $this->ciclo === 'primer_ciclo'; }
    public function esSegundoCiclo(): bool { return $this->ciclo === 'segundo_ciclo'; }
    public function esInicial(): bool      { return $this->ciclo === 'inicial'; }
}
