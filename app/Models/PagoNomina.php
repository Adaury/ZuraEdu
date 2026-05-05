<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoNomina extends Model
{
    use BelongsToTenant;

    protected $table = 'pagos_nomina';

    protected $fillable = [
        'nomina_empleado_id', 'mes',
        'salario_bruto', 'desc_tss', 'desc_isr', 'desc_otros', 'notas_deducciones',
        'horas_extra', 'bonificacion', 'otros_ingresos',
        'deducciones', 'salario_neto',
        'pagado', 'fecha_pago', 'pagado_por', 'metodo_pago', 'referencia_pago',
    ];

    protected $casts = [
        'salario_bruto'  => 'decimal:2',
        'desc_tss'       => 'decimal:2',
        'desc_isr'       => 'decimal:2',
        'desc_otros'     => 'decimal:2',
        'horas_extra'    => 'decimal:2',
        'bonificacion'   => 'decimal:2',
        'otros_ingresos' => 'decimal:2',
        'deducciones'    => 'decimal:2',
        'salario_neto'   => 'decimal:2',
        'pagado'         => 'boolean',
        'fecha_pago'     => 'date',
    ];

    const MESES = [
        '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
        '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
        '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(NominaEmpleado::class, 'nomina_empleado_id');
    }

    public function pagador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    public function getMesFormateadoAttribute(): string
    {
        [$anio, $num] = explode('-', $this->mes);
        return (self::MESES[$num] ?? $num) . ' ' . $anio;
    }

    public function getTotalIngresosAttribute(): float
    {
        return (float)$this->salario_bruto + (float)$this->horas_extra
             + (float)$this->bonificacion + (float)$this->otros_ingresos;
    }

    public function getTotalDeduccionesAttribute(): float
    {
        return (float)$this->desc_tss + (float)$this->desc_isr + (float)$this->desc_otros;
    }
}
