<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasSolicitudEstados;
use Illuminate\Database\Eloquent\Model;

class SolicitudRepresentante extends Model
{
    use BelongsToTenant, HasSolicitudEstados;

    protected $table = 'solicitudes_representante';

    protected $fillable = [
        'tenant_id', 'representante_id', 'estudiante_id',
        'tipo', 'asunto', 'descripcion', 'fecha_evento', 'adjunto',
        'estado', 'respuesta', 'respondido_por', 'respondido_en',
    ];

    protected $casts = [
        'fecha_evento'  => 'date',
        'respondido_en' => 'datetime',
    ];

    public const TIPOS = [
        'justificacion_ausencia' => 'Justificación de Ausencia',
        'cita_docente'           => 'Cita con Docente',
        'cita_direccion'         => 'Cita con Dirección',
        'solicitar_documento'    => 'Solicitud de Documento',
        'actualizar_datos'       => 'Actualizar Datos',
        'otro'                   => 'Otro',
    ];

    public function representante()
    {
        return $this->belongsTo(Representante::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function respondidoPor()
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }
}
