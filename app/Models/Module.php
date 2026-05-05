<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'nombre', 'clave', 'descripcion', 'icono',
        'categoria', 'plan_minimo', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
                    ->withPivot('activo', 'config')
                    ->withTimestamps();
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true)->orderBy('orden');
    }

    public function scopeParaPlan($q, string $plan)
    {
        $order = ['free' => 0, 'pro' => 1, 'premium' => 2];
        $nivel = $order[$plan] ?? 0;

        return $q->whereIn('plan_minimo', array_keys(
            array_filter($order, fn($v) => $v <= $nivel)
        ));
    }
}
