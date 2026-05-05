<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Libro extends Model
{
    use BelongsToTenant;

    protected $table = 'libros';

    protected $fillable = [
        'titulo',
        'autor',
        'isbn',
        'categoria',
        'cantidad_total',
        'cantidad_disponible',
        'descripcion',
    ];

    protected $casts = [
        'cantidad_total'       => 'integer',
        'cantidad_disponible'  => 'integer',
    ];

    // ── Categorías predefinidas ───────────────────────────────────────────
    const CATEGORIAS = [
        'Literatura'        => 'Literatura',
        'Ciencias'          => 'Ciencias',
        'Historia'          => 'Historia',
        'Matemáticas'       => 'Matemáticas',
        'Tecnología'        => 'Tecnología',
        'Arte'              => 'Arte',
        'Religión'          => 'Religión',
        'Idiomas'           => 'Idiomas',
        'Enciclopedia'      => 'Enciclopedia',
        'Referencia'        => 'Referencia',
        'Otro'              => 'Otro',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────
    public function prestamos(): HasMany
    {
        return $this->hasMany(PrestamoBiblioteca::class, 'libro_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopeDisponibles($query)
    {
        return $query->where('cantidad_disponible', '>', 0);
    }

    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getDisponibleAttribute(): bool
    {
        return $this->cantidad_disponible > 0;
    }

    public function getBadgeDisponibilidadAttribute(): string
    {
        if ($this->cantidad_disponible <= 0) {
            return 'danger';
        }
        if ($this->cantidad_disponible <= 2) {
            return 'warning';
        }
        return 'success';
    }
}
