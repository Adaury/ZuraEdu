<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SolicitudRepresentante extends Model
{
    use BelongsToTenant;

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

    public const ESTADOS = [
        'pendiente'   => ['label' => 'Pendiente',   'color' => '#d97706', 'bg' => '#fffbeb'],
        'en_proceso'  => ['label' => 'En Proceso',  'color' => '#2563eb', 'bg' => '#eff6ff'],
        'aprobada'    => ['label' => 'Aprobada',    'color' => '#16a34a', 'bg' => '#f0fdf4'],
        'rechazada'   => ['label' => 'Rechazada',   'color' => '#dc2626', 'bg' => '#fef2f2'],
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

    public function getEstadoConfigAttribute(): array
    {
        return self::ESTADOS[$this->estado] ?? self::ESTADOS['pendiente'];
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
}
