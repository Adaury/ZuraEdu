<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use BelongsToTenant;

    public const ESTADOS = ['presente', 'ausente', 'tardanza', 'justificado'];

    protected $fillable = [
        'fecha',
        'matricula_id',
        'asignacion_id',
        'estado',
        'justificacion',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function scopeDelPeriodo($q, string $fechaInicio, string $fechaFin)
    {
        return $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }
}
