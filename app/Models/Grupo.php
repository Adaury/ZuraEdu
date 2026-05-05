<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grupo extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'school_year_id',
        'grado_id',
        'seccion_id',
        'tutor_id',
        'aula',
        'capacidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /**
     * Returns the Docente record linked to the tutor user, for use in forms.
     */
    public function getTutorDocenteIdAttribute(): ?int
    {
        if (!$this->tutor_id) return null;
        $docente = \App\Models\Docente::where('user_id', $this->tutor_id)->first();
        return $docente?->id;
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->grado->nombre . ' ' . $this->seccion->nombre;
    }

    public function getNombreCortoAttribute(): string
    {
        $niveles = [
            1 => '1ro',
            2 => '2do',
            3 => '3ro',
            4 => '4to',
            5 => '5to',
            6 => '6to',
        ];

        $prefijo = $niveles[$this->grado->nivel] ?? $this->grado->nivel . 'mo';

        return $prefijo . ' ' . $this->seccion->nombre;
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeDelAnio($q, int $yearId)
    {
        return $q->where('school_year_id', $yearId);
    }

    public function scopePrimerCiclo($q)
    {
        return $q->whereHas('grado', fn($g) => $g->where('ciclo', 'primer_ciclo'));
    }

    public function scopeSegundoCiclo($q)
    {
        return $q->whereHas('grado', fn($g) => $g->where('ciclo', 'segundo_ciclo'));
    }

    /* ── Relaciones adicionales ─────────────────────────────── */

    /** Estudiantes matriculados (acceso directo) */
    public function estudiantes()
    {
        return $this->hasManyThrough(
            Estudiante::class,
            Matricula::class,
            'grupo_id',      // FK en matriculas
            'id',            // PK en estudiantes
            'id',            // PK en grupos
            'estudiante_id'  // FK en matriculas
        );
    }

    /* ── Accessors ──────────────────────────────────────────── */

    /** Ciclo del grupo derivado del grado cargado */
    public function getCicloAttribute(): ?string
    {
        return $this->grado?->ciclo;
    }

    public function esPrimerCiclo(): bool
    {
        return $this->grado?->ciclo === 'primer_ciclo';
    }
}
