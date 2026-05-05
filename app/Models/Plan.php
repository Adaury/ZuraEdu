<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'nombre', 'slug', 'precio_mensual', 'precio_anual', 'moneda',
        'limite_estudiantes', 'limite_docentes', 'limite_usuarios',
        'almacenamiento_gb', 'descripcion', 'caracteristicas',
        'es_popular', 'activo', 'orden',
    ];

    protected $casts = [
        'caracteristicas'   => 'array',
        'es_popular'        => 'boolean',
        'activo'            => 'boolean',
        'precio_mensual'    => 'float',
        'precio_anual'      => 'float',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class, 'plan_minimo', 'slug');
    }

    public static function free(): self
    {
        return static::where('slug', 'free')->firstOrFail();
    }

    public static function bySlug(string $slug): self
    {
        return static::where('slug', $slug)->firstOrFail();
    }

    public function esPago(): bool
    {
        return $this->precio_mensual > 0;
    }
}
