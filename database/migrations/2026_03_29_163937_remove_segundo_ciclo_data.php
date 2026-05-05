<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina todo lo relacionado con grados de segundo ciclo (4to, 5to, 6to).
     * La institución opera únicamente con Primer Ciclo (1ro–3ro de Secundaria).
     */
    public function up(): void
    {
        // Grados de segundo ciclo
        $gradosSegundoCiclo = DB::table('grados')
            ->where('ciclo', 'segundo_ciclo')
            ->pluck('id');

        if ($gradosSegundoCiclo->isEmpty()) {
            return;
        }

        // Grupos de esos grados
        $grupoIds = DB::table('grupos')
            ->whereIn('grado_id', $gradosSegundoCiclo)
            ->pluck('id');

        if ($grupoIds->isNotEmpty()) {
            // Matrículas
            $matriculaIds = DB::table('matriculas')
                ->whereIn('grupo_id', $grupoIds)
                ->pluck('id');

            if ($matriculaIds->isNotEmpty()) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                // Cascada de datos de matrículas
                DB::table('calificaciones_academicas')->whereIn('matricula_id', $matriculaIds)->delete();
                DB::table('calificaciones')->whereIn('matricula_id', $matriculaIds)->delete();
                DB::table('asistencias')->whereIn('matricula_id', $matriculaIds)->delete();
                DB::table('evaluaciones_indicadores')->whereIn('matricula_id', $matriculaIds)->delete();
                DB::table('evaluaciones_registro')->whereIn('matricula_id', $matriculaIds)->whereIn('matricula_id', $matriculaIds)->delete();
                DB::table('promedios_periodo')->whereIn('matricula_id', $matriculaIds)->delete();

                // Estudiantes vinculados solo a esos grupos
                $estudianteIds = DB::table('matriculas')
                    ->whereIn('id', $matriculaIds)
                    ->pluck('estudiante_id');

                DB::table('matriculas')->whereIn('id', $matriculaIds)->delete();

                // Eliminar estudiantes que ya no tengan ninguna matrícula
                foreach ($estudianteIds as $eId) {
                    $tieneMasMatriculas = DB::table('matriculas')->where('estudiante_id', $eId)->exists();
                    if (! $tieneMasMatriculas) {
                        DB::table('estudiante_representante')->where('estudiante_id', $eId)->delete();
                        DB::table('observaciones')->where('estudiante_id', $eId)->delete();
                        DB::table('estudiantes')->where('id', $eId)->delete();
                    }
                }

                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            // Asignaciones de esos grupos
            $asignacionIds = DB::table('asignaciones')
                ->whereIn('grupo_id', $grupoIds)
                ->pluck('id');

            if ($asignacionIds->isNotEmpty()) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('calificaciones_academicas')->whereIn('asignacion_id', $asignacionIds)->delete();
                DB::table('calificaciones')->whereIn('asignacion_id', $asignacionIds)->delete();
                DB::table('asistencias')->whereIn('asignacion_id', $asignacionIds)->delete();
                DB::table('horario_detalles')->whereIn('asignacion_id', $asignacionIds)->delete();
                DB::table('observaciones')->whereIn('asignacion_id', $asignacionIds)->delete();
                DB::table('asignaciones')->whereIn('id', $asignacionIds)->delete();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            // Grupos
            DB::table('grupos')->whereIn('id', $grupoIds)->delete();
        }

        // Eliminar los grados de segundo ciclo
        DB::table('grados')->whereIn('id', $gradosSegundoCiclo)->delete();
    }

    public function down(): void
    {
        // Recrear grados de segundo ciclo (sin datos, solo la estructura)
        foreach ([
            ['nivel' => 4, 'nombre' => '4to de Secundaria', 'orden' => 4, 'ciclo' => 'segundo_ciclo'],
            ['nivel' => 5, 'nombre' => '5to de Secundaria', 'orden' => 5, 'ciclo' => 'segundo_ciclo'],
            ['nivel' => 6, 'nombre' => '6to de Secundaria', 'orden' => 6, 'ciclo' => 'segundo_ciclo'],
        ] as $g) {
            DB::table('grados')->insertOrIgnore($g + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
};
