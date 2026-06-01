<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaInteres extends Model
{
    protected $table = 'encuestas_interes';

    protected $fillable = [
        'tipo', 'nombre', 'apellido', 'telefono',
        'nivel_interes', 'respuestas', 'ip',
    ];

    protected $casts = [
        'respuestas'     => 'array',
        'nivel_interes'  => 'integer',
    ];
}
