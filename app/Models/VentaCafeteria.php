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
        $ultima = static::where('estudiante_id', $estudianteId)
            ->latest()
            ->first();

        return $ultima ? (float) $ultima->saldo_nuevo : 0.0;
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
