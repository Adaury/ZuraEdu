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

        // Convertir nota de la tarea (sobre puntos) a escala 100
        $notaSobre100 = $material->puntos
            ? round(($entrega->calificacion / $material->puntos) * 100, 2)
            : $entrega->calificacion;

        $calificacion = Calificacion::firstOrNew([
            'matricula_id'  => $entrega->matricula_id,
            'asignacion_id' => $asignacionId,
            'periodo_id'    => $material->periodo_id,
        ]);

        // Acumular en campo "tareas" como promedio ponderado
        $tareaActual  = (float) ($calificacion->tareas ?? 0);
        $contadorKey  = "classroom_tareas_count_{$asignacionId}_{$material->periodo_id}";

        // Guardar en campo tareas (promedio simple con entregas previas)
        $calificacion->tareas = $notaSobre100;
        $calificacion->save();

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

        $entregas = EntregaClassroom::where('material_id', $material->id)
            ->where('estado', 'calificado')
            ->whereNotNull('calificacion')
            ->get();

        foreach ($entregas as $entrega) {
            $notaSobre100 = round(($entrega->calificacion / $material->puntos) * 100, 2);

            Calificacion::updateOrCreate(
                [
                    'matricula_id'  => $entrega->matricula_id,
                    'asignacion_id' => $asignacionId,
                    'periodo_id'    => $material->periodo_id,
                ],
                ['tareas' => $notaSobre100]
            );
        }
    }
}
