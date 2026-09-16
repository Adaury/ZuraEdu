<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\EvaluacionRegistro;
use App\Models\Matricula;
use App\Models\SchoolYear;

/**
 * Datos MINERD (Competencias/Indicadores de Logro) para el bloque CE/IL del
 * boletín de primer/segundo ciclo.
 *
 * Antes de este servicio, esta misma consulta (asignaciones activas del
 * grupo + registros de evaluación agrupados por asignación/competencia-o-
 * indicador/período) estaba copiada casi idéntica en 4 sitios: Portal
 * Estudiante, Portal Padre, Portal Docente y Admin\BoletinController —
 * cada uno con su propia copia de los mismos ~25 lines.
 */
class MinerdBoletinService
{
    /**
     * @param  Docente|null  $docenteFiltro  Si se pasa, restringe tanto las
     *         asignaciones como el mapa de evaluaciones a ese docente (uso:
     *         portal docente / vista "como docente" del admin). Sin este
     *         filtro se devuelven todas las asignaciones del grupo, igual
     *         que antes en Portal Estudiante y Portal Padre.
     */
    public function build(?Matricula $matricula, ?SchoolYear $schoolYear, ?Docente $docenteFiltro = null): ?array
    {
        if (!$matricula || !$schoolYear) return null;

        $ciclo = $matricula->grupo?->grado?->ciclo ?? null;
        if (!in_array($ciclo, ['primer_ciclo', 'segundo_ciclo'])) return null;

        $asignaciones = Asignacion::with([
            'asignatura.competenciasActivas' => fn($q) => $q->where('ciclo', $ciclo)
                ->orderBy('orden')->with(['indicadoresActivos']),
            'docente',
        ])
        ->where('grupo_id', $matricula->grupo_id)
        ->where('school_year_id', $schoolYear->id)
        ->where('activo', true)
        ->when($docenteFiltro, fn($q) => $q->where('docente_id', $docenteFiltro->id))
        ->get();

        $rawEvals = EvaluacionRegistro::where('matricula_id', $matricula->id)
            ->where('school_year_id', $schoolYear->id)
            ->when($docenteFiltro, fn($q) => $q->whereIn('asignacion_id', $asignaciones->pluck('id')))
            ->get();

        $evalMap = [];
        foreach ($rawEvals as $e) {
            $k = $e->indicador_id ? "il_{$e->indicador_id}" : "ce_{$e->competencia_id}";
            $evalMap[$e->asignacion_id][$k][$e->periodo_id] = $e->valor_cualitativo ?? $e->nota_numerica;
        }

        return [
            'ciclo'             => $ciclo,
            'asignaciones'      => $asignaciones,
            'evalMap'           => $evalMap,
            'tieneEvaluaciones' => $rawEvals->isNotEmpty(),
        ];
    }
}
