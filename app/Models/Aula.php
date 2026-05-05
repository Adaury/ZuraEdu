<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aula extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'codigo',
        'capacidad',
        'tipo',
        'disponible',
        'piso',
        'equipamiento',
        'centro_id',
    ];

    protected $casts = [
        'equipamiento' => 'array',
        'disponible'   => 'boolean',
    ];

    public function horarioDetalles()
    {
        return $this->hasMany(HorarioDetalle::class);
    }
}
