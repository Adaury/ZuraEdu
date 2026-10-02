<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VentaCafeteria extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'ventas_cafeteria';

    protected $fillable = [
        'tenant_id',
        'estudiante_id',
        'producto_id',
        'descripcion',
        'tipo',
        'monto',
        'saldo_anterior',
        'saldo_nuevo',
        'created_by_id',
    ];

    protected $casts = [
        'monto'          => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_nuevo'    => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function producto()
    {
        return $this->belongsTo(ProductoCafeteria::class, 'producto_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'created_by_id')->withDefault(['name' => 'Sistema']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Obtener el saldo actual de un estudiante.
     * Suma recargas y ajustes positivos, resta ventas.
     */
    public static function saldoEstudiante(int $estudianteId): float
    {
        // Por id, no por created_at: esa columna tiene resolución de segundos y dos movimientos del mismo segundo (una recarga y una
        // venta seguidas) empataban, dejando el "último" a elección del motor y el saldo equivocado.
        $ultima = static::where('estudiante_id', $estudianteId)
            ->orderByDesc('id')
            ->first();

        return $ultima ? (float) $ultima->saldo_nuevo : 0.0;
    }

    /**
     * Registra un movimiento de saldo (venta, recarga o ajuste) de forma atómica y devuelve la fila creada, o null si es una venta
     * y el saldo no alcanza.
     *
     * Antes cada operación hacía "leer saldo → comprobar → insertar" sin bloqueo: 20 ventas de 50 a la vez con saldo 100 se aprobaron
     * TODAS (RD$1.000 gastados con RD$100) y 20 recargas de 10 a la vez dejaron el saldo visible en 10 en vez de 200 (cada una partía
     * de una lectura vieja y pisaba a las demás). Aquí se bloquea la fila del estudiante (la PRIMERA lectura de la transacción, para
     * que el saldo leído después sea el actual) y el saldo se calcula dentro del bloqueo: los movimientos del mismo estudiante se
     * serializan y la cadena saldo_anterior → saldo_nuevo queda consistente.
     *
     * @param  float  $monto  siempre positivo salvo en 'ajuste', donde el signo indica suma o resta
     */
    public static function registrarMovimiento(int $estudianteId, string $tipo, float $monto, ?string $descripcion, ?int $productoId, ?int $creadoPor): ?self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($estudianteId, $tipo, $monto, $descripcion, $productoId, $creadoPor) {
            // Bloqueo por estudiante (con el scope de tenant: un ID de otro colegio da 404, no un movimiento ajeno).
            Estudiante::where('id', $estudianteId)->lockForUpdate()->firstOrFail();

            $anterior = round(static::saldoEstudiante($estudianteId), 2);
            $monto    = round($monto, 2);

            $delta = match ($tipo) {
                'venta'   => -$monto,
                'recarga' => $monto,
                'ajuste'  => $monto,
            };

            if ($tipo === 'venta' && $anterior < $monto) {
                return null;   // saldo insuficiente
            }

            return static::create([
                'estudiante_id'  => $estudianteId,
                'producto_id'    => $productoId,
                'descripcion'    => $descripcion,
                'tipo'           => $tipo,
                'monto'          => $tipo === 'ajuste' ? abs($monto) : $monto,
                'saldo_anterior' => $anterior,
                'saldo_nuevo'    => round($anterior + $delta, 2),
                'created_by_id'  => $creadoPor,
            ]);
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeVentas($q)
    {
        return $q->where('tipo', 'venta');
    }

    public function scopeRecargas($q)
    {
        return $q->where('tipo', 'recarga');
    }

    public function scopeHoy($q)
    {
        return $q->whereDate('created_at', today());
    }
}
