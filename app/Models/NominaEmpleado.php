<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominaEmpleado extends Model
{
    use BelongsToTenant;

    protected $table = 'nomina_empleados';

    protected $fillable = [
        'tenant_id', 'user_id', 'cargo', 'cedula', 'cuenta_bancaria', 'banco',
        'salario_base', 'tss_porcentaje', 'exento_isr',
        'tipo_contrato', 'horas_semana', 'fecha_ingreso', 'activo', 'notas',
    ];

    protected $casts = [
        'salario_base'   => 'decimal:2',
        'tss_porcentaje' => 'decimal:2',
        'fecha_ingreso'  => 'date',
        'activo'         => 'boolean',
        'exento_isr'     => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoNomina::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function getTipoContratoLabelAttribute(): string
    {
        return match ($this->tipo_contrato) {
            'fijo'     => 'Fijo',
            'temporal' => 'Temporal',
            'hora'     => 'Por hora',
            default    => ucfirst($this->tipo_contrato),
        };
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function calcularTSS(): float
    {
        return round($this->salario_base * ($this->tss_porcentaje / 100), 2);
    }

    public function calcularISR(): float
    {
        if ($this->exento_isr) return 0;
        // Escala simplificada ISR mensual RD (2024)
        $anual = $this->salario_base * 12;
        if ($anual <= 416220) return 0;
        if ($anual <= 624329) return round((($anual - 416220) * 0.15) / 12, 2);
        if ($anual <= 867123) return round((31216 + ($anual - 624329) * 0.20) / 12, 2);
        return round((79776 + ($anual - 867123) * 0.25) / 12, 2);
    }

    public function getAntiguedadAttribute(): string
    {
        if (!$this->fecha_ingreso) return '—';
        $diff = $this->fecha_ingreso->diff(now());
        $years = $diff->y; $months = $diff->m;
        if ($years > 0) return $years . ' año' . ($years > 1 ? 's' : '') . ($months ? ", {$months} mes" . ($months > 1 ? 'es' : '') : '');
        return $months . ' mes' . ($months > 1 ? 'es' : '');
    }
}
