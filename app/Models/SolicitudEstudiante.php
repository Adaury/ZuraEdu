<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasSolicitudEstados;
use Illuminate\Database\Eloquent\Model;

class SolicitudEstudiante extends Model
{
    use BelongsToTenant, HasSolicitudEstados;

    protected $table = 'solicitudes_estudiante';

    protected $fillable = [
        'tenant_id', 'estudiante_id',
        'tipo', 'asunto', 'descripcion', 'fecha_evento', 'adjunto',
        'estado', 'respuesta', 'respondido_por', 'respondido_en',
    ];

    protected $casts = [
        'fecha_evento'  => 'date',
        'respondido_en' => 'datetime',
    ];

    public const TIPOS = [
        'justificacion_ausencia' => 'Justificación de Ausencia',
        'constancia_estudios'    => 'Constancia de Estudios',
        'certificado_notas'      => 'Certificado de Calificaciones',
        'solicitar_beca'         => 'Solicitud de Beca',
        'cambio_datos'           => 'Actualizar Datos',
        'otro'                   => 'Otro',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function respondidoPor()
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }
}
