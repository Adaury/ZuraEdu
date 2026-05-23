<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CarnetZona extends Model
{
    use BelongsToTenant;

    protected $table = 'carnet_zonas';

    protected $fillable = ['nombre', 'tipo', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    const TIPOS = [
        'porteria'   => 'Portería',
        'biblioteca' => 'Biblioteca',
        'comedor'    => 'Comedor',
        'laboratorio'=> 'Laboratorio',
        'salon'      => 'Salón',
        'otro'       => 'Otro',
    ];

    const ICONOS = [
        'porteria'   => 'bi-door-open',
        'biblioteca' => 'bi-book',
        'comedor'    => 'bi-egg-fried',
        'laboratorio'=> 'bi-flask',
        'salon'      => 'bi-building',
        'otro'       => 'bi-geo-alt',
    ];

    public function scopeActivas($q)
    {
        return $q->where('activo', true);
    }
}
