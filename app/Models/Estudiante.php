<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estudiante extends Model
{
    use BelongsToTenant;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'numero_matricula',
        'cedula',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'nacionalidad',
        'lugar_nacimiento',
        'telefono',
        'email',
        'direccion',
        'sector',
        'municipio',
        'provincia',
        'foto',
        'estado',
        'tutor_nombre',
        'tutor_parentesco',
        'tutor_telefono',
        'tutor_trabajo',
        'notas_medicas',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
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

    public function getEdadAttribute(): int
    {
        return $this->fecha_nacimiento->age;
    }

    public function representantes()
    {
        return $this->belongsToMany(Representante::class, 'estudiante_representante')
            ->withPivot('parentesco', 'es_principal')
            ->withTimestamps();
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }

    public function reconocimientos()
    {
        return $this->hasMany(Reconocimiento::class);
    }

    public function faltasDisciplinarias()
    {
        return $this->hasMany(FaltaDisciplinaria::class);
    }

    public function fichaSalud()
    {
        return $this->hasOne(FichaSalud::class);
    }

    public function incidentesMedicos()
    {
        return $this->hasMany(IncidenteMedico::class);
    }

    /* ── Relaciones adicionales ─────────────────────────────── */

    /** Asistencias a través de matriculas */
    public function asistencias()
    {
        return $this->hasManyThrough(
            Asistencia::class,
            Matricula::class,
            'estudiante_id', // FK en matriculas
            'matricula_id',  // FK en asistencias
            'id',
            'id'
        );
    }

    /** Calificaciones (segundo ciclo) a través de matriculas */
    public function calificaciones()
    {
        return $this->hasManyThrough(
            CalificacionAcademica::class,
            Matricula::class,
            'estudiante_id',
            'matricula_id',
            'id',
            'id'
        );
    }

    /** Evaluaciones primer ciclo (IL) a través de matriculas */
    public function evaluacionesRegistro()
    {
        return $this->hasManyThrough(
            EvaluacionRegistro::class,
            Matricula::class,
            'estudiante_id',
            'matricula_id',
            'id',
            'id'
        );
    }

    /** Matrícula activa del año escolar en curso */
    public function matriculaActiva()
    {
        return $this->hasOne(Matricula::class)
                    ->where('estado', 'activa')
                    ->whereHas('schoolYear', fn($q) => $q->where('activo', true));
    }

    /* ── Scopes ─────────────────────────────────────────────── */

    public function scopeActivos($q)
    {
        return $q->where('estado', 'activo');
    }

    public function casosSeguimiento()
    {
        return $this->hasMany(CasoSeguimiento::class);
    }

    public function currentMatricula()
    {
        return $this->hasOneThrough(
            SchoolYear::class,
            Matricula::class,
            'estudiante_id',
            'id',
            'id',
            'school_year_id'
        )->where('matriculas.estado', 'activa')
         ->where(function ($q) {
             $year = SchoolYear::actual();
             if ($year) {
                 $q->where('matriculas.school_year_id', $year->id);
             }
         });
    }
}
