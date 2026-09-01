<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Promedio general de un estudiante (a través de todas sus asignaturas, no
 * el promedio final de UNA asignatura a través de períodos — eso es un
 * cálculo distinto, ver BoletinController::tablaNotas()).
 *
 * Regla de negocio (confirmada por CierreAnoRegressionTest::
 * test_ejecutar_prioriza_calificacion_academica_sobre_tecnica, la única de
 * las 4 implementaciones previas que tenía esto probado): si el estudiante
 * tiene alguna calificación en el área académica, esa es la ÚNICA fuente
 * del promedio — nunca se mezcla con el área técnica. Solo se usa técnica
 * cuando el estudiante no tiene ninguna nota académica (estudiantes 100%
 * técnicos).
 *
 * Antes de este servicio, esta regla estaba duplicada e inconsistente en
 * 4 lugares — ver auditoría del proyecto
 * (project_auditoria_2026_09_01_system_settings.md): CierreAnoController la
 * tenía correcta y probada; BoletinController tenía una copia casi idéntica
 * pero con un bug sutil (un estudiante con filas académicas de nota_final
 * NULL no caía a técnica); GamificacionController mezclaba ambas áreas en
 * vez de priorizar; CalificacionController::ranking/rankingPdf/rankingExcel
 * ignoraban el área académica por completo.
 */
class PromedioEstudianteService
{
    /**
     * @param Collection $notasAcademicas Filas con nota_final del área académica de UN estudiante
     * @param Collection $notasTecnicas   Filas con nota_final del área técnica de UN estudiante
     */
    public function calcular(Collection $notasAcademicas, Collection $notasTecnicas): ?float
    {
        $academicas = $notasAcademicas
            ->pluck('nota_final')
            ->filter(fn ($n) => $n !== null)
            ->map(fn ($n) => (float) $n);

        if ($academicas->isNotEmpty()) {
            return round($academicas->avg(), 2);
        }

        $tecnicas = $notasTecnicas
            ->pluck('nota_final')
            ->filter(fn ($n) => $n !== null)
            ->map(fn ($n) => (float) $n);

        return $tecnicas->isNotEmpty() ? round($tecnicas->avg(), 2) : null;
    }

    /**
     * Variante para cuando ya se cargaron en bulk (groupBy('matricula_id'))
     * las calificaciones de todo un grupo/año — evita N+1 al procesar varios
     * estudiantes.
     */
    public function calcularDesdeBulk(int $matriculaId, Collection $bulkAcademicas, Collection $bulkTecnicas): ?float
    {
        return $this->calcular(
            $bulkAcademicas->get($matriculaId) ?? collect(),
            $bulkTecnicas->get($matriculaId) ?? collect(),
        );
    }
}
