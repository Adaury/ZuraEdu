<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class RecursoMateria extends Model
{
    use BelongsToTenant;

    protected $table = 'recursos_materia';

    protected $fillable = [
        'asignacion_id', 'school_year_id', 'created_by',
        'titulo', 'descripcion', 'tipo', 'url',
        'archivo_path', 'archivo_nombre', 'publicado', 'orden',
    ];

    protected $casts = ['publicado' => 'boolean'];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Devuelve el icono Bootstrap Icons según tipo
    public function getIconoAttribute(): string
    {
        return match ($this->tipo) {
            'video'     => 'bi-play-circle-fill',
            'documento' => 'bi-file-earmark-pdf-fill',
            'imagen'    => 'bi-image-fill',
            'enlace'    => 'bi-link-45deg',
            default     => 'bi-paperclip',
        };
    }

    // Color badge según tipo
    public function getColorAttribute(): string
    {
        return match ($this->tipo) {
            'video'     => '#ef4444',
            'documento' => '#3b82f6',
            'imagen'    => '#10b981',
            'enlace'    => '#8b5cf6',
            default     => '#64748b',
        };
    }
}
