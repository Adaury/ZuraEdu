<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class ClaseVirtual extends Model
{
    use BelongsToTenant;

    protected $table = 'clases_virtuales';

    protected $fillable = [
        'asignacion_id',
        'nombre',
        'descripcion',
        'portada_color',
        'activo',
        'permite_comentarios',
        'meeting_url',
        'meeting_status',
        'meeting_started_at',
    ];

    protected $casts = [
        'activo'              => 'boolean',
        'permite_comentarios' => 'boolean',
        'meeting_started_at'  => 'datetime',
    ];

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function materiales()
    {
        return $this->hasMany(MaterialClase::class)->orderByDesc('created_at');
    }

    public function materialesPublicados()
    {
        return $this->hasMany(MaterialClase::class)
            ->where('publicado', true)
            ->where(fn($q) => $q->whereNull('publicar_en')->orWhere('publicar_en', '<=', now()))
            ->orderByDesc('created_at');
    }

    public function recursos()
    {
        return $this->hasMany(ZcRecurso::class)->orderBy('orden');
    }

    /** Estudiantes matriculados en el grupo de esta clase */
    public function estudiantesMatriculados()
    {
        return Matricula::with('estudiante')
            ->where('grupo_id', $this->asignacion?->grupo_id)
            ->where('school_year_id', $this->asignacion?->school_year_id)
            ->where('estado', 'activa');
    }

    public function generarCodigo(): string
    {
        do {
            $codigo = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 7));
        } while (static::where('codigo_clase', $codigo)->exists());

        $this->update(['codigo_clase' => $codigo]);
        return $codigo;
    }

    public function messages()
    {
        return $this->hasMany(ClassroomMessage::class);
    }

    public function meetingActiva(): bool
    {
        return $this->meeting_status === 'active' && !empty($this->meeting_url);
    }

    /** Cantidad de tareas/evaluaciones pendientes de entrega para una matrícula */
    public function tareasPendientes(int $matriculaId): int
    {
        return $this->materiales()
            ->whereIn('tipo', ['tarea', 'evaluacion'])
            ->whereDoesntHave('entregas', fn($q) => $q->where('matricula_id', $matriculaId))
            ->count();
    }
}
