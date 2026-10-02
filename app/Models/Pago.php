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
        'numero_comprobante_fiscal',
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

    /**
     * Pasa el pago a 'pagado' UNA sola vez, de forma atómica, y devuelve true solo a quien realmente hizo el cambio.
     *
     * Antes cada sitio hacía "si ya está pagado, salir; si no, actualizar y disparar PagoConfirmado": dos confirmaciones
     * simultáneas (doble clic en "Pagar", el webhook de Stripe y su redirección, el aviso y el retorno de CardNet, o un reintento)
     * leían ambas 'pendiente' y las dos disparaban el aviso — con 20 "Pagar" a la vez, 20 WhatsApp y 20 notificaciones a la familia.
     * Un UPDATE ... WHERE estado <> 'pagado' lo decide la base de datos: solo una fila cambia, solo uno recibe true.
     * Quien reciba false no debe disparar PagoConfirmado ni hacer nada más.
     *
     * @param  array<string,mixed>  $atributos  campos a guardar (metodo_pago, referencia, fecha_pago, notas, registrado_por...)
     */
    public function confirmarPago(array $atributos): bool
    {
        $atributos = array_merge($atributos, ['estado' => 'pagado']);

        $filas = $this->newQueryWithoutScopes()
            ->whereKey($this->getKey())
            ->where('estado', '!=', 'pagado')
            ->update($atributos + ['updated_at' => now()]);

        if ($filas === 1) {
            $this->forceFill($atributos)->syncOriginal();

            return true;
        }

        return false;
    }

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
