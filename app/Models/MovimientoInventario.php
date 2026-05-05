<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use BelongsToTenant;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'articulo_id',
        'tipo',
        'cantidad',
        'motivo',
        'usuario_id',
    ];

    const TIPOS = [
        'entrada' => ['label' => 'Entrada', 'color' => '#d1fae5', 'text' => '#065f46', 'icon' => 'bi-box-arrow-in-down', 'sign' => '+'],
        'salida'  => ['label' => 'Salida',  'color' => '#fee2e2', 'text' => '#991b1b', 'icon' => 'bi-box-arrow-up',      'sign' => '-'],
        'ajuste'  => ['label' => 'Ajuste',  'color' => '#fef3c7', 'text' => '#92400e', 'icon' => 'bi-sliders',           'sign' => '='],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(ArticuloInventario::class, 'articulo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // ── Accessor ──────────────────────────────────────────────────────────

    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['ajuste'];
    }
}
