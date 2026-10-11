<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use BelongsToTenant;

    public const ESTADOS = ['presente', 'ausente', 'tardanza', 'justificado'];

    /**
     * Estados que cuentan como "asistió" al calcular porcentajes. El ENUM real
     * de la columna es presente/ausente/tarde/excusa/retiro (migración
     * 2026_03_17_000071); 'tardanza' y 'justificado' se conservan por datos
     * históricos y por la API móvil, que aún los valida. 'retiro' no cuenta.
     */
    public const ESTADOS_ASISTIDO = ['presente', 'tarde', 'tardanza', 'excusa', 'justificado'];

    public const TIPOS_JUSTIFICACION = [
        'medica'              => 'Cita médica',
        'personal'            => 'Asunto personal',
        'emergencia_familiar' => 'Emergencia familiar',
        'duelo'               => 'Duelo',
        'otra'                => 'Otra',
    ];

    protected $fillable = [
        'fecha',
        'matricula_id',
        'asignacion_id',
        'estado',
        'justificacion',
        'justificacion_tipo',
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
