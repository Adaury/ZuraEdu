<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\CalificacionAcademica;

class CalificacionAcademicaObserver
{
    public function saved(CalificacionAcademica $calificacion): void
    {
        if (app()->runningInConsole()) return;

        // Only log if relevant fields changed
        if (!$calificacion->wasChanged(['nota_final', 'publicado', 'situacion'])) {
            return;
        }

        $nota      = $calificacion->nota_final ?? '—';
        $publicado = $calificacion->publicado ? 'publicado' : 'borrador';
        ActivityLog::registrar(
            'calificacion_academica.guardada',
            CalificacionAcademica::class,
            $calificacion->id,
            "Matrícula #{$calificacion->matricula_id} | Asignación #{$calificacion->asignacion_id} | Nota final: {$nota} | Estado: {$publicado}"
        );
    }
}
