<?php

namespace App\Traits;

use App\Models\Asignacion;
use App\Models\Asignatura;

trait AsignaMateriasBasicas
{
    /**
     * Asigna todas las materias marcadas como básicas al grupo indicado.
     * Omite silenciosamente las que ya existan para evitar duplicados.
     */
    protected function asignarMateriasBasicas(int $grupoId, int $schoolYearId): int
    {
        $basicas = Asignatura::basicas()->get();
        $creadas = 0;

        foreach ($basicas as $asignatura) {
            $existe = Asignacion::where('school_year_id', $schoolYearId)
                ->where('grupo_id', $grupoId)
                ->where('asignatura_id', $asignatura->id)
                ->exists();

            if ($existe) continue;

            Asignacion::create([
                'school_year_id'  => $schoolYearId,
                'grupo_id'        => $grupoId,
                'asignatura_id'   => $asignatura->id,
                'docente_id'      => null,
                'area'            => 'academica',
                'tipo_evaluacion' => 'indicadores_logro',
                'horas_semana'    => $asignatura->horas_semanales ?? 4,
                'activo'          => true,
            ]);
            $creadas++;
        }

        return $creadas;
    }
}
