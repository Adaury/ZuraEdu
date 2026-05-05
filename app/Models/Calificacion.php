<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    use BelongsToTenant;

    protected $table = 'calificaciones';

    protected $fillable = [
        'matricula_id', 'asignacion_id', 'periodo_id',
        'tareas', 'practicas', 'participacion', 'proyecto', 'examen',
        'ra1','ra2','ra3','ra4','ra5','ra6','ra7','ra8','ra9','ra10',
        'recuperaciones_ra', 'criterios_ra',
        'nota_final',
        'nota_cc', 'nota_completiva',
        'nota_ce', 'nota_extraordinaria',
        'asistencia_clases', 'asistencia_total',
        'indicador', 'observaciones', 'publicado', 'modificado_por',
    ];

    /** Cast all RA columns as float */
    protected function raColumns(): array
    {
        return ['ra1','ra2','ra3','ra4','ra5','ra6','ra7','ra8','ra9','ra10'];
    }

    protected $casts = [
        'recuperaciones_ra'   => 'array',
        'criterios_ra'        => 'array',
        'tareas'              => 'float',
        'practicas'           => 'float',
        'participacion'       => 'float',
        'proyecto'            => 'float',
        'examen'              => 'float',
        'nota_final'          => 'float',
        'nota_cc'             => 'float',
        'nota_completiva'     => 'float',
        'nota_ce'             => 'float',
        'nota_extraordinaria' => 'float',
        'publicado'           => 'boolean',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function modificadoPor()
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    public function getLetraAttribute(): string
    {
        $nota = $this->nota_final;

        if ($nota === null) {
            return '-';
        }

        if ($nota >= 90) return 'A';
        if ($nota >= 80) return 'B';
        if ($nota >= 70) return 'C';
        if ($nota >= 60) return 'D';

        return 'F';
    }

    public function scopePublicadas($q)
    {
        return $q->where('publicado', true);
    }
}
