<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ampliar el enum tipo_evaluacion con los nuevos tipos
        DB::statement("ALTER TABLE asignaciones MODIFY COLUMN tipo_evaluacion ENUM('componentes','ra','indicadores_logro','competencias') NOT NULL DEFAULT 'componentes'");

        // 2. Actualizar tipo_evaluacion en asignaciones existentes según área
        DB::statement("UPDATE asignaciones SET tipo_evaluacion = 'indicadores_logro' WHERE area = 'academica' AND tipo_evaluacion = 'componentes'");
        DB::statement("UPDATE asignaciones SET tipo_evaluacion = 'competencias'      WHERE area = 'tecnica'   AND tipo_evaluacion = 'ra'");

        // 3. Mover grupos al segundo ciclo (4to–6to de Secundaria)
        //    Distribución: grupos 1–2 → 4to, 3–4 → 5to, 5–6 → 6to
        DB::table('grupos')->whereIn('id', [1, 2])->update(['grado_id' => 4]);
        DB::table('grupos')->whereIn('id', [3, 4])->update(['grado_id' => 5]);
        DB::table('grupos')->whereIn('id', [5, 6])->update(['grado_id' => 6]);
    }

    public function down(): void
    {
        // Revertir grupos a 1ro de Secundaria
        DB::table('grupos')->whereIn('id', [1,2,3,4,5,6])->update(['grado_id' => 1]);

        // Revertir tipo_evaluacion
        DB::statement("UPDATE asignaciones SET tipo_evaluacion = 'componentes' WHERE tipo_evaluacion = 'indicadores_logro'");
        DB::statement("UPDATE asignaciones SET tipo_evaluacion = 'ra'          WHERE tipo_evaluacion = 'competencias'");

        // Revertir enum
        DB::statement("ALTER TABLE asignaciones MODIFY COLUMN tipo_evaluacion ENUM('componentes','ra') NOT NULL DEFAULT 'componentes'");
    }
};
