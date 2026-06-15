<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Índice compuesto para paginación en faltas disciplinarias
        $this->addIndexSafe('faltas_disciplinarias', 'idx_faltas_tenant_fecha', ['tenant_id', 'fecha']);

        // Índice compuesto para paginación en ventas cafetería
        $this->addIndexSafe('ventas_cafeteria', 'idx_ventas_tenant_created', ['tenant_id', 'created_at']);

        // Índice para clases_virtuales.asignacion_id
        $this->addIndexSafe('clases_virtuales', 'idx_cv_asignacion', ['asignacion_id']);

        // Índice compuesto para intentos de evaluación
        $this->addIndexSafe('eva_intentos', 'idx_ei_quiz_matricula', ['quiz_id', 'matricula_id']);

        // Índice compuesto para activity_logs (queries más comunes)
        $this->addIndexSafe('activity_logs', 'idx_al_tenant_modelo', ['tenant_id', 'modelo', 'modelo_id']);

        // Índice en horarios.creado_por
        $this->addIndexSafe('horarios', 'idx_horarios_creado_por', ['creado_por']);
        $this->addIndexSafe('horario_detalles', 'idx_hd_asignacion', ['asignacion_id']);
    }

    public function down(): void
    {
        $indexes = [
            'faltas_disciplinarias' => 'idx_faltas_tenant_fecha',
            'ventas_cafeteria'      => 'idx_ventas_tenant_created',
            'clases_virtuales'      => 'idx_cv_asignacion',
            'eva_intentos'          => 'idx_ei_quiz_matricula',
            'activity_logs'         => 'idx_al_tenant_modelo',
            'horarios'              => 'idx_horarios_creado_por',
            'horario_detalles'      => 'idx_hd_asignacion',
        ];

        foreach ($indexes as $table => $index) {
            $this->dropIndexSafe($table, $index);
        }
    }

    private function addIndexSafe(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) return;

        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]))->isNotEmpty();
        if ($exists) return;

        Schema::table($table, function (Blueprint $t) use ($columns, $name) {
            $t->index($columns, $name);
        });
    }

    private function dropIndexSafe(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) return;

        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]))->isNotEmpty();
        if (! $exists) return;

        Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
    }
};
