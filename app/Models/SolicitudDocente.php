<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasSolicitudEstados;
use Illuminate\Database\Eloquent\Model;

class SolicitudDocente extends Model
{
    use BelongsToTenant, HasSolicitudEstados;

    protected $table = 'solicitudes_docente';

    protected $fillable = [
        'tenant_id', 'docente_id',
        'tipo', 'asunto', 'descripcion', 'fecha_inicio', 'fecha_fin', 'adjunto',
        'estado', 'respuesta', 'respondido_por', 'respondido_en',
    ];

    protected $casts = [
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
        'respondido_en' => 'datetime',
    ];

    public const TIPOS = [
        'permiso_dia'            => 'Permiso por Día(s)',
        'licencia_enfermedad'    => 'Licencia por Enfermedad',
        'licencia_maternidad'    => 'Licencia de Maternidad/Paternidad',
        'constancia_trabajo'     => 'Constancia de Trabajo',
        'constancia_salario'     => 'Constancia de Salario',
        'actualizacion_datos'    => 'Actualización de Datos',
        'capacitacion'           => 'Solicitud de Capacitación',
        'cambio_horario'         => 'Cambio de Horario',
        'otro'                   => 'Otro',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function respondidoPor()
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }
}
