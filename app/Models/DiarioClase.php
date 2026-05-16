<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiarioClase extends Model
{
    use BelongsToTenant;

    protected $table = 'diario_clases';

    protected $fillable = [
        'asignacion_id', 'docente_id', 'fecha',
        'tema', 'actividades', 'observaciones', 'incidencias', 'asistentes',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
