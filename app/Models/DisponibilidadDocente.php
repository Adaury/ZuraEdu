<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class DisponibilidadDocente extends Model
{
    use BelongsToTenant;

    protected $table = 'disponibilidad_docente';

    protected $fillable = [
        'docente_id',
        'dia',
        'franja_id',
        'disponible',
        'motivo',
        'school_year_id',
    ];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function franja()
    {
        return $this->belongsTo(FranjaHoraria::class, 'franja_id');
    }
}
