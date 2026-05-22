<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ConceptoPago extends Model
{
    use BelongsToTenant;

    protected $table = 'concepto_pagos';

    protected $fillable = ['tenant_id', 'nombre', 'monto_defecto', 'tipo', 'activo', 'descripcion'];

    protected $casts = [
        'monto_defecto' => 'decimal:2',
        'activo'        => 'boolean',
    ];

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'mensualidad' => 'Mensualidad',
            'inscripcion' => 'Inscripción',
            default       => 'Otro',
        };
    }

    public function getTipoBadgeColorAttribute(): string
    {
        return match ($this->tipo) {
            'mensualidad' => '#dbeafe',
            'inscripcion' => '#d1fae5',
            default       => '#f3f4f6',
        };
    }

    public function getTipoTextColorAttribute(): string
    {
        return match ($this->tipo) {
            'mensualidad' => '#1d4ed8',
            'inscripcion' => '#065f46',
            default       => '#374151',
        };
    }
}
