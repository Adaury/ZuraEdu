<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BecaEstudiante extends Model
{
    use BelongsToTenant;

    protected $table = 'becas_estudiante';

    protected $fillable = [
        'tenant_id',
        'beca_id',
        'matricula_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    /* ── Relaciones ─────────────────────────────────────────────────────── */

    public function beca(): BelongsTo
    {
        return $this->belongsTo(Beca::class);
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /* ── Scopes ─────────────────────────────────────────────────────────── */

    public function scopeActivas($q)
    {
        return $q->where('activo', true)
                 ->where(fn($s) =>
                     $s->whereNull('fecha_fin')
                       ->orWhere('fecha_fin', '>=', today())
                 );
    }

    /* ── Helper ─────────────────────────────────────────────────────────── */

    /**
     * Devuelve el descuento calculado sobre un monto.
     */
    public function descuentoSobre(float $monto): float
    {
        return $this->beca?->calcularDescuento($monto) ?? 0.0;
    }
}
