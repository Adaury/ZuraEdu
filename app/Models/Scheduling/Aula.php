<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table    = 'sch_aulas';
    protected $fillable = ['nombre', 'capacidad', 'tipo', 'disponible'];

    protected $casts = ['disponible' => 'boolean'];
}
