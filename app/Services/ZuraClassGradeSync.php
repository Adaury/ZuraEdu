<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\EntregaClassroom;
use App\Models\MaterialClase;
use App\Models\Matricula;

class ZuraClassGradeSync
{
    /**
     * Sincroniza la calificación de una entrega al libro de notas (calificaciones).
     * Solo aplica cuando el material tiene periodo_id asignado.
     */
    public function sincronizar(EntregaClassroom $entrega): bool
    {
        $material = $entrega->material;

        if (!$material || !$material->periodo_id || !$material->puntos) {
            return false;
        }

        $asignacionId = $material->claseVirtual?->asignacion_id;
        if (!$asignacionId) {
            return false;
        }

        $this->actualizarPromedioTareas($entrega->matricula_id, $asignacionId, $material->periodo_id);

        return true;
    }

    /**
     * Recalcula el promedio de todas las entregas calificadas de un grupo
     * para un material específico y actualiza el campo tareas en calificaciones.
     */
    public function recalcularPromedioGrupo(MaterialClase $material): void
    {
        if (!$material->periodo_id || !$material->puntos) {
            return;
        }

        $asignacionId = $material->claseVirtual?->asignacion_id;
        if (!$asignacionId) {
            return;
        }

        $matriculaIds = EntregaClassroom::where('material_id', $material->id)
            ->where('estado', 'calificado')
            ->whereNotNull('calificacion')
            ->pluck('matricula_id')
            ->unique();

        foreach ($matriculaIds as $matriculaId) {
            $this->actualizarPromedioTareas($matriculaId, $asignacionId, $material->periodo_id);
        }
    }

    /**
     * calificaciones.tareas debe representar el promedio de TODAS las tareas
     * de ZuraClass del estudiante en esta asignación+período — no solo la
     * última entrega sincronizada. Antes de este fix, cada sincronización
     * sobrescribía el campo con la nota de una sola tarea: si un docente
     * creaba 2+ tareas en el mismo período, solo la última calificada
     * contaba, perdiendo silenciosamente la contribución de las demás.
     */
    private function actualizarPromedioTareas(int $matriculaId, int $asignacionId, int $periodoId): void
    {
        $materialIds = MaterialClase::whereHas('claseVirtual', fn ($q) => $q->where('asignacion_id', $asignacionId))
            ->where('periodo_id', $periodoId)
            ->whereNotNull('puntos')
            ->pluck('id');

        $notas = EntregaClassroom::whereIn('material_id', $materialIds)
            ->where('matricula_id', $matriculaId)
            ->where('estado', 'calificado')
            ->whereNotNull('calificacion')
            ->with('material')
            ->get()
            ->map(fn ($e) => round(($e->calificacion / $e->material->puntos) * 100, 2));

        if ($notas->isEmpty()) {
            return;
        }

        Calificacion::updateOrCreate(
            ['matricula_id' => $matriculaId, 'asignacion_id' => $asignacionId, 'periodo_id' => $periodoId],
            ['tareas' => round($notas->avg(), 2)]
        );
    }
}
