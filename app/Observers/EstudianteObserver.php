<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Estudiante;

class EstudianteObserver
{
    public function created(Estudiante $estudiante): void
    {
        if (app()->runningInConsole()) return;
        ActivityLog::registrar(
            'estudiante.creado',
            Estudiante::class,
            $estudiante->id,
            "Estudiante creado: {$estudiante->apellidos}, {$estudiante->nombres} (Matr: {$estudiante->numero_matricula})"
        );
    }

    public function updated(Estudiante $estudiante): void
    {
        if (app()->runningInConsole()) return;
        $changed = implode(', ', array_keys($estudiante->getChanges()));
        ActivityLog::registrar(
            'estudiante.actualizado',
            Estudiante::class,
            $estudiante->id,
            "Estudiante actualizado: {$estudiante->apellidos}, {$estudiante->nombres} | Campos: {$changed}"
        );
    }

    public function deleted(Estudiante $estudiante): void
    {
        if (app()->runningInConsole()) return;
        ActivityLog::registrar(
            'estudiante.eliminado',
            Estudiante::class,
            $estudiante->id,
            "Estudiante eliminado: {$estudiante->apellidos}, {$estudiante->nombres}"
        );
    }
}
