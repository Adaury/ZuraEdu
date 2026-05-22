<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductoCafeteria extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'productos_cafeteria';

    protected $fillable = [
        'tenant_id',
        'nombre',
        'precio',
        'categoria',
        'activo',
        'descripcion',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public const CATEGORIAS = [
        'comida' => 'Comida',
        'bebida' => 'Bebida',
        'snack'  => 'Snack',
        'otro'   => 'Otro',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function ventas()
    {
        return $this->hasMany(VentaCafeteria::class, 'producto_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeDeCategoria($q, $categoria)
    {
        return $q->where('categoria', $categoria);
    }
}
