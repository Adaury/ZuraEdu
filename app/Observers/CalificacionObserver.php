<?php

namespace App\Observers;

use App\Models\Calificacion;
use App\Models\AlertaSistema;
use App\Models\User;
use App\Models\SchoolYear;

class CalificacionObserver
{
    /**
     * After a grade is saved, check if the student is at academic risk (<70)
     * and create alerts for the teacher, coordinators, and director.
     */
    public function saved(Calificacion $calificacion): void
    {
        // Only trigger when there is a final grade and it's published
        if (!$calificacion->publicado || $calificacion->nota_final === null) {
            return;
        }

        $notaFinal = (float) $calificacion->nota_final;

        if ($notaFinal >= 70) {
            return; // Student is passing — no alert needed
        }

        // Avoid re-creating the same alert within the same school year
        $schoolYear = SchoolYear::actual();
        if (!$schoolYear) {
            return;
        }

        $matricula  = $calificacion->matricula()->with(['estudiante', 'grupo.grado'])->first();
        $asignacion = $calificacion->asignacion()->with(['asignatura', 'docente.user'])->first();

        if (!$matricula || !$asignacion) {
            return;
        }

        $estudiante    = $matricula->estudiante;
        $asignatura    = $asignacion->asignatura;
        $grupo         = $matricula->grupo;
        $docenteUser   = $asignacion->docente?->user;

        $nombreEstudiante = $estudiante
            ? trim(($estudiante->nombres ?? '') . ' ' . ($estudiante->apellidos ?? ''))
            : 'Estudiante';

        $nombreAsignatura = $asignatura?->nombre ?? 'asignatura';
        $nombreGrupo      = $grupo?->nombre_corto ?? 'grupo';
        $notaFmt          = number_format($notaFinal, 1);

        $titulo  = "Riesgo Académico — {$nombreEstudiante}";
        $mensaje = "{$nombreEstudiante} tiene nota final de {$notaFmt} en {$nombreAsignatura} ({$nombreGrupo}). "
                 . "Nota mínima de aprobación: 70 puntos.";

        $base = [
            'tipo'            => 'riesgo_academico',
            'titulo'          => $titulo,
            'mensaje'         => $mensaje,
            'nivel'           => 'danger',
            'referencia_tipo' => Calificacion::class,
            'referencia_id'   => $calificacion->id,
            'school_year_id'  => $schoolYear->id,
            'creado_por'      => null,
            'leida'           => false,
            'expira_en'       => now()->addDays(30),
        ];

        // Alert for the teacher who owns the grade
        if ($docenteUser) {
            $this->createIfNotExists($base + ['destinatario_id' => $docenteUser->id]);
        }

        // Alerts for all coordinators and the director
        $rolesNotificar = ['Coordinador Académico', 'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo', 'Director'];
        foreach ($rolesNotificar as $rol) {
            $usersConRol = User::role($rol)->get();
            foreach ($usersConRol as $u) {
                $this->createIfNotExists($base + ['destinatario_id' => $u->id]);
            }
        }
    }

    /**
     * Avoid duplicate alerts for the same calificacion + destinatario.
     */
    private function createIfNotExists(array $data): void
    {
        AlertaSistema::firstOrCreate(
            [
                'tipo'            => $data['tipo'],
                'referencia_tipo' => $data['referencia_tipo'],
                'referencia_id'   => $data['referencia_id'],
                'destinatario_id' => $data['destinatario_id'],
            ],
            $data
        );
    }
}
