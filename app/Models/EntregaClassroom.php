<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class EntregaClassroom extends Model
{
    use BelongsToTenant;

    protected $table = 'entregas_classroom';

    protected $fillable = [
        'material_id',
        'matricula_id',
        'contenido',
        'url_entrega',
        'estado',
        'calificacion',
        'comentario_docente',
        'fecha_entrega',
        'devuelta',
        'retroalimentacion',
        'intentos',
        'fecha_revision',
        'revisado_por',
    ];

    protected $casts = [
        'fecha_entrega'  => 'datetime',
        'fecha_revision' => 'datetime',
        'devuelta'       => 'boolean',
        'calificacion'   => 'float',
    ];

    const ESTADOS = [
        'pendiente'   => ['label' => 'Pendiente',   'color' => 'warning', 'icon' => 'bi-clock'],
        'entregado'   => ['label' => 'Entregado',   'color' => 'info',    'icon' => 'bi-send-check'],
        'calificado'  => ['label' => 'Calificado',  'color' => 'success', 'icon' => 'bi-check-circle-fill'],
        'atrasado'    => ['label' => 'Atrasado',    'color' => 'danger',  'icon' => 'bi-exclamation-triangle'],
        'devuelto'    => ['label' => 'Devuelto',    'color' => 'secondary','icon' => 'bi-arrow-return-left'],
    ];

    public function material()
    {
        return $this->belongsTo(MaterialClase::class, 'material_id');
    }

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function archivos()
    {
        return $this->hasMany(ArchivoEntrega::class, 'entrega_id');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function rubricCalificaciones()
    {
        return $this->hasMany(ZcRubricCalificacion::class, 'entrega_id');
    }

    public function getEstadoInfoAttribute(): array
    {
        return self::ESTADOS[$this->estado] ?? ['label' => $this->estado, 'color' => 'secondary', 'icon' => 'bi-circle'];
    }

    public function getNombreEstudianteAttribute(): string
    {
        $est = $this->matricula?->estudiante;
        return $est ? ($est->nombres . ' ' . $est->apellidos) : '—';
    }
}
