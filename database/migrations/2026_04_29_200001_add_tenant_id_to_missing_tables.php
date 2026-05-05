<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Tablas que faltaron en la migración anterior (nombres reales en BD)
    private array $tables = [
        'planes_clase',              // era 'plan_clases'
        'plan_clase_momentos',       // no estaba en la lista
        'planificacion_actividades', // era 'planificaciones_actividades'
        'planificacion_ra_items',    // era 'planificaciones_ra_items'
        'sesiones_tutoria',          // era 'sesiones_tutorias'
        'casos_seguimiento',         // era 'seguimiento_social_casos'
        'prestamos_equipo',          // era 'prestamos_equipos'
        'disponibilidad_docente',    // era 'disponibilidad_docentes'
        'entregas_tarea',            // no estaba en la lista
        'familias_profesionales',    // no estaba en la lista
        'promedios_periodo',         // no estaba en la lista
        'recursos_materia',          // no estaba en la lista
        'instrumento_evaluaciones',  // era 'instrumentos_evaluacion_estudiantes'
        'restricciones_horario',     // no estaba en la lista
        'tareas',                    // no estaba (modelo Tarea)
        'system_settings',           // KV global — se agrega para consistencia
        'sch_cursos', 'sch_materias', 'sch_profesores', 'sch_aulas',
        'sch_franjas', 'sch_asignaciones', 'sch_horarios', 'sch_horario_detalles',
        'sch_disponibilidad_profesor',
        'docente_especialidad',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) continue;
            if (Schema::hasColumn($table, 'tenant_id')) continue;

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $t->index('tenant_id', "idx_{$table}_tenant");
            });

            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) continue;
            if (! Schema::hasColumn($table, 'tenant_id')) continue;

            Schema::table($table, function (Blueprint $t) use ($table) {
                try { $t->dropIndex("idx_{$table}_tenant"); } catch (\Exception $e) {}
                $t->dropColumn('tenant_id');
            });
        }
    }
};
