<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectoEscolar extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'proyectos_escolares';

    protected $fillable = [
        'tenant_id',
        'titulo',
        'descripcion',
        'area',
        'tutor_id',
        'school_year_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    // ── Catálogos ────────────────────────────────────────────────────────────

    const AREAS = [
        'ciencias'     => 'Ciencias',
        'matematica'   => 'Matemática',
        'humanidades'  => 'Humanidades',
        'tecnologia'   => 'Tecnología',
        'arte'         => 'Arte',
        'otro'         => 'Otro',
    ];

    const ESTADOS = [
        'planificacion' => 'Planificación',
        'desarrollo'    => 'Desarrollo',
        'finalizado'    => 'Finalizado',
        'presentado'    => 'Presentado',
    ];

    const AREA_COLORS = [
        'ciencias'    => 'green',
        'matematica'  => 'blue',
        'humanidades' => 'purple',
        'tecnologia'  => 'indigo',
        'arte'        => 'pink',
        'otro'        => 'gray',
    ];

    const ESTADO_COLORS = [
        'planificacion' => 'yellow',
        'desarrollo'    => 'blue',
        'finalizado'    => 'green',
        'presentado'    => 'indigo',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id')->withDefault(['name' => '—']);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function integrantes()
    {
        return $this->hasMany(IntegranteProyecto::class, 'proyecto_id');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'integrantes_proyecto', 'proyecto_id', 'estudiante_id')
                    ->withPivot('rol')
                    ->withTimestamps();
    }

    public function fases()
    {
        return $this->hasMany(FaseProyecto::class, 'proyecto_id')->orderBy('fecha_limite');
    }

    // ── Accesores ────────────────────────────────────────────────────────────

    public function getAreaLabelAttribute(): string
    {
        return self::AREAS[$this->area] ?? ucfirst($this->area);
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }

    public function getAreaColorAttribute(): string
    {
        return self::AREA_COLORS[$this->area] ?? 'gray';
    }

    public function getEstadoColorAttribute(): string
    {
        return self::ESTADO_COLORS[$this->estado] ?? 'gray';
    }

    public function getProgresoAttribute(): int
    {
        $total = $this->fases->count();
        if ($total === 0) return 0;
        $completadas = $this->fases->where('completada', true)->count();
        return (int) round(($completadas / $total) * 100);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeDeArea($query, string $area)
    {
        return $query->where('area', $area);
    }

    public function scopeDeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }
}
