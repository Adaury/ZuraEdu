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

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
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

    /** Índice de la migración 2026_09_30_000003 (tenant_id, deleted_at, apellidos, nombres). */
    public const INDICE_LISTADO = 'est_tenant_listado_idx';

    /**
     * Fuerza el índice del listado paginado SIN filtros. El paginador ejecuta
     * `count(*) where tenant_id = ? and deleted_at is null`; el optimizador de MySQL estima mal las
     * filas del índice único (tenant_id, cedula) y lo prefiere, leyendo cada fila: ~12 ms con 4.950
     * estudiantes. Con este índice es index-only: ~1,6 ms, total exacto.
     *
     * Solo se aplica si el índice EXISTE: FORCE INDEX sobre un índice inexistente da el error 1176
     * (500), y un entorno puede tener el código nuevo con la migración sin aplicar. La comprobación
     * se cachea 1 h (es una propiedad del esquema, común a todos los tenants; no hay datos de tenant).
     * No usar con filtros de texto/grado/ciclo: ahí el optimizador debe decidir.
     */
    public function scopeConIndiceDeListado($q)
    {
        $existe = \Illuminate\Support\Facades\Cache::remember(
            'db_schema_idx_' . self::INDICE_LISTADO,
            3600,
            fn () => \Illuminate\Support\Facades\Schema::hasIndex('estudiantes', self::INDICE_LISTADO)
        );

        return $existe ? $q->forceIndex(self::INDICE_LISTADO) : $q;
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
