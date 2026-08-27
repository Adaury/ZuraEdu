<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nombre', 'nivel', 'ciclo', 'orden', 'activo'];

    /**
     * Valores permitidos de `ciclo`. Única fuente de verdad — usar
     * `Rule::in(self::CICLOS)` al validar en vez de repetir la lista.
     * `ciclo` es un VARCHAR validado a nivel de aplicación (no un ENUM de
     * MySQL): agregar un ciclo nuevo aquí no requiere ninguna migración
     * (recomendación #2 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md).
     */
    public const CICLOS = ['inicial', 'primer_ciclo', 'segundo_ciclo', 'bachillerato'];

    /**
     * IMPORTANTE: `nivel` y `ciclo` son independientes, NO una función fija
     * el uno del otro. `nivel` es un ordinal propio de cada institución
     * (posición del grado dentro de su propia numeración, usado sobre todo
     * como fallback de orden histórico — `orden` es la fuente de verdad
     * real para ordenar). `ciclo` es la fuente de verdad para agrupar
     * (scopePrimerCiclo/scopeSegundoCiclo, etc.). Confirmado con datos
     * reales: nivel=4 es 'primer_ciclo' bajo la convención "Básica" (1ro-8vo)
     * y 'segundo_ciclo' bajo la convención "Secundaria" (1ro-6to) — ambas
     * conviven en el sistema según cómo cada institución nombra sus grados.
     * No intentar derivar uno del otro.
     */

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function indicadoresAprendizaje()
    {
        return $this->hasMany(IndicadorAprendizaje::class);
    }

    /* ── Scopes ───────────────────────────────────────── */

    public function scopeOrdenados($q) { return $q->orderBy('orden'); }
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
