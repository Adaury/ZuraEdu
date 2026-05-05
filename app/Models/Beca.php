<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Beca extends Model
{
    use BelongsToTenant;

    protected $table = 'becas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'valor',
        'criterio',
        'activo',
    ];

    protected $casts = [
        'valor'  => 'decimal:2',
        'activo' => 'boolean',
    ];

    /* ── Relaciones ─────────────────────────────────────────────────────── */

    public function becasEstudiante(): HasMany
    {
        return $this->hasMany(BecaEstudiante::class);
    }

    public function asignacionesActivas(): HasMany
    {
        return $this->hasMany(BecaEstudiante::class)->where('activo', true);
    }

    /* ── Scopes ─────────────────────────────────────────────────────────── */

    public function scopeActivas($q)
    {
        return $q->where('activo', true);
    }

    /* ── Helpers ────────────────────────────────────────────────────────── */

    /**
     * Calcular el descuento que aplica sobre un monto base.
     */
    public function calcularDescuento(float $montoBase): float
    {
        if ($this->tipo === 'porcentaje') {
            return round($montoBase * ($this->valor / 100), 2);
        }

        // monto_fijo: no puede superar el monto base
        return min((float) $this->valor, $montoBase);
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'porcentaje' => "Porcentaje ({$this->valor}%)",
            'monto_fijo' => 'Monto fijo (RD$ ' . number_format($this->valor, 2) . ')',
            default      => $this->tipo,
        };
    }

    public function getTipoBadgeAttribute(): string
    {
        return match ($this->tipo) {
            'porcentaje' => 'indigo',
            'monto_fijo' => 'emerald',
            default      => 'gray',
        };
    }
}
