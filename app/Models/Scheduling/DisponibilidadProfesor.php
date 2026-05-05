<?php

namespace App\Models\Scheduling;

use Illuminate\Database\Eloquent\Model;

class DisponibilidadProfesor extends Model
{
    protected $table    = 'sch_disponibilidad_profesor';
    protected $fillable = ['profesor_id', 'franja_id', 'dia', 'disponible'];

    protected $casts = ['disponible' => 'boolean'];

    public function profesor() { return $this->belongsTo(Profesor::class, 'profesor_id'); }
    public function franja()   { return $this->belongsTo(Franja::class,   'franja_id'); }
}
