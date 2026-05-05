<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticuloInventario extends Model
{
    use BelongsToTenant;

    protected $table = 'articulos_inventario';

    protected $fillable = [
        'nombre',
        'categoria',
        'cantidad_total',
        'cantidad_disponible',
        'ubicacion',
        'descripcion',
        'estado',
    ];

    // ── Constantes ────────────────────────────────────────────────────────

    const CATEGORIAS = [
        'mobiliario'        => ['label' => 'Mobiliario',         'color' => '#dbeafe', 'text' => '#1d4ed8', 'icon' => 'bi-chair'],
        'tecnologia'        => ['label' => 'Tecnología',         'color' => '#ede9fe', 'text' => '#6d28d9', 'icon' => 'bi-laptop'],
        'material_didactico'=> ['label' => 'Material Didáctico', 'color' => '#d1fae5', 'text' => '#065f46', 'icon' => 'bi-book'],
        'deportivo'         => ['label' => 'Deportivo',          'color' => '#fef3c7', 'text' => '#92400e', 'icon' => 'bi-trophy'],
        'limpieza'          => ['label' => 'Limpieza',           'color' => '#e0f2fe', 'text' => '#0369a1', 'icon' => 'bi-stars'],
        'otro'              => ['label' => 'Otro',               'color' => '#f3f4f6', 'text' => '#374151', 'icon' => 'bi-box'],
    ];

    const ESTADOS = [
        'bueno'   => ['label' => 'Bueno',   'color' => '#d1fae5', 'text' => '#065f46', 'dot' => '#10b981'],
        'regular' => ['label' => 'Regular', 'color' => '#fef3c7', 'text' => '#92400e', 'dot' => '#f59e0b'],
        'malo'    => ['label' => 'Malo',    'color' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444'],
    ];

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeDisponibles($query)
    {
        return $query->where('cantidad_disponible', '>', 0);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getCategoriaInfoAttribute(): array
    {
        return self::CATEGORIAS[$this->categoria] ?? self::CATEGORIAS['otro'];
    }

    public function getEstadoInfoAttribute(): array
    {
        return self::ESTADOS[$this->estado] ?? self::ESTADOS['bueno'];
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'articulo_id');
    }
}
