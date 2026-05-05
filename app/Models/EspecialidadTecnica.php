<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EspecialidadTecnica extends Model
{
    protected $table = 'especialidades_tecnicas';

    protected $fillable = [
        'nombre', 'codigo', 'descripcion', 'color', 'icono',
        'coordinador_id', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'coordinador_id');
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
                Docente::class,
                'docente_especialidad',
                'especialidad_id',  // FK for this model (EspecialidadTecnica) in pivot
                'docente_id'        // FK for related model (Docente) in pivot
            )
            ->withPivot('es_coordinador', 'fecha_asignacion')
            ->withTimestamps();
    }

    public function mallaCurricular(): HasMany
    {
        return $this->hasMany(MallaCurricular::class, 'especialidad_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function getDocentesActivosAttribute()
    {
        return $this->docentes()->where('docentes.estado', 'activo')->get();
    }
}
