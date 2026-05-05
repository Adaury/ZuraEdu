<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Docente extends Model
{
    use BelongsToTenant;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id','cedula','nombres','apellidos','fecha_nacimiento',
        'sexo','telefono','email','direccion','especialidad',
        'titulo_academico','foto','estado','area','cargo',
    ];

    protected $casts = ['fecha_nacimiento' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->apellidos . ', ' . $this->nombres;
    }

    public function getFotoUrlAttribute(): string
    {
        if ($this->foto) {
            return asset('storage/' . $this->foto);
        }

        return asset('img/default-avatar.png');
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }

    /** Grupos donde el docente tiene asignaciones activas */
    public function grupos()
    {
        return $this->hasManyThrough(
            Grupo::class,
            Asignacion::class,
            'docente_id',
            'id',
            'id',
            'grupo_id'
        );
    }

    public function scopeActivos($q)
    {
        return $q->where('estado', 'activo');
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(
                EspecialidadTecnica::class,
                'docente_especialidad',
                'docente_id',       // FK for this model (Docente) in pivot
                'especialidad_id'   // FK for related model (EspecialidadTecnica) in pivot
            )
            ->withPivot('es_coordinador', 'fecha_asignacion')
            ->withTimestamps();
    }

    public function especialidadesCoordinadas(): HasMany
    {
        return $this->hasMany(EspecialidadTecnica::class, 'coordinador_id');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionDocente::class);
    }
}
