<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pago extends Model
{
    use BelongsToTenant;

    protected $table = 'pagos';

    protected $fillable = [
        'tenant_id',
        'matricula_id',
        'concepto',
        'monto',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'metodo_pago',
        'referencia',
        'notas',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago'        => 'date',
        'monto'             => 'decimal:2',
    ];

    /* ── Relaciones ──────────────────────────────────────────────────── */

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /* ── Scopes ──────────────────────────────────────────────────────── */

    public function scopePendientes($q)    { return $q->where('estado', 'pendiente'); }
    public function scopePagados($q)       { return $q->where('estado', 'pagado'); }
    public function scopeVencidos($q)      { return $q->where('estado', 'vencido'); }

    /* ── Helpers ─────────────────────────────────────────────────────── */

    public function estaVencido(): bool
    {
        return $this->estado === 'pendiente'
            && $this->fecha_vencimiento->isPast();
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'pagado'    => 'success',
            'vencido'   => 'danger',
            'cancelado' => 'secondary',
            default     => 'warning',
        };
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'pagado'    => 'Pagado',
            'vencido'   => 'Vencido',
            'cancelado' => 'Cancelado',
            default     => 'Pendiente',
        };
    }

    /* ── Actualiza estados vencidos automáticamente ─────────────────── */
    public static function sincronizarVencidos(): int
    {
        return static::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', today())
            ->update(['estado' => 'vencido']);
    }
}
