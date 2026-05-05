<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionDocente extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'evaluaciones_docentes';

    protected $fillable = [
        'docente_id',
        'evaluador_id',
        'periodo_evaluado',
        'puntualidad',
        'dominio_contenido',
        'metodologia',
        'relacion_estudiantes',
        'planificacion',
        'promedio',
        'observaciones',
    ];

    protected $casts = [
        'puntualidad'          => 'integer',
        'dominio_contenido'    => 'integer',
        'metodologia'          => 'integer',
        'relacion_estudiantes' => 'integer',
        'planificacion'        => 'integer',
        'promedio'             => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    // ── Accessor: promedio calculado ────────────────────────────────────────

    /**
     * Calcula el promedio de los 5 criterios en tiempo real.
     * El valor persistido en BD se usa solo para ordenamiento/reportes.
     */
    public function getPromedioCalculadoAttribute(): float
    {
        return round(
            ($this->puntualidad +
             $this->dominio_contenido +
             $this->metodologia +
             $this->relacion_estudiantes +
             $this->planificacion) / 5,
            2
        );
    }

    /**
     * Nivel de desempeño basado en el promedio calculado.
     * Retorna array con label y color Tailwind/Bootstrap.
     */
    public function nivelDesempeno(): array
    {
        $p = $this->promedio_calculado;

        if ($p >= 4.5) {
            return ['label' => 'Excelente', 'color' => '#dcfce7', 'text' => '#166534', 'badge' => 'success'];
        }
        if ($p >= 3.5) {
            return ['label' => 'Bueno',     'color' => '#dbeafe', 'text' => '#1e40af', 'badge' => 'primary'];
        }
        if ($p >= 2.5) {
            return ['label' => 'Regular',   'color' => '#fef9c3', 'text' => '#854d0e', 'badge' => 'warning'];
        }

        return ['label' => 'Deficiente',   'color' => '#fee2e2', 'text' => '#991b1b', 'badge' => 'danger'];
    }

    /**
     * Lista de criterios con sus etiquetas y puntajes.
     */
    public function criterios(): array
    {
        return [
            ['key' => 'puntualidad',          'label' => 'Puntualidad y Asistencia'],
            ['key' => 'dominio_contenido',     'label' => 'Dominio del Contenido'],
            ['key' => 'metodologia',           'label' => 'Metodología de Enseñanza'],
            ['key' => 'relacion_estudiantes',  'label' => 'Relación con Estudiantes'],
            ['key' => 'planificacion',         'label' => 'Planificación Docente'],
        ];
    }

    // ── Boot: calcular promedio antes de guardar ────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (EvaluacionDocente $ev) {
            $ev->promedio = round(
                ($ev->puntualidad +
                 $ev->dominio_contenido +
                 $ev->metodologia +
                 $ev->relacion_estudiantes +
                 $ev->planificacion) / 5,
                2
            );
        });
    }
}
