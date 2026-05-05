<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Búsqueda rápida de estudiante por cédula
        Schema::table('estudiantes', function (Blueprint $table) {
            if (! $this->hasIndex('estudiantes', 'estudiantes_cedula_index')) {
                $table->index('cedula', 'estudiantes_cedula_index');
            }
            if (! $this->hasIndex('estudiantes', 'estudiantes_estado_index')) {
                $table->index('estado', 'estudiantes_estado_index');
            }
        });

        // Asistencia: búsqueda por fecha + asignación (reporte diario)
        Schema::table('asistencias', function (Blueprint $table) {
            if (! $this->hasIndex('asistencias', 'asistencias_fecha_asignacion_index')) {
                $table->index(['fecha', 'asignacion_id'], 'asistencias_fecha_asignacion_index');
            }
        });

        // Calificaciones: consulta compuesta para boletín
        Schema::table('calificaciones', function (Blueprint $table) {
            if (! $this->hasIndex('calificaciones', 'calificaciones_boletin_index')) {
                $table->index(
                    ['matricula_id', 'periodo_id', 'publicado'],
                    'calificaciones_boletin_index'
                );
            }
        });

        // Matriculas: estado activa + año escolar (panel docente)
        Schema::table('matriculas', function (Blueprint $table) {
            if (! $this->hasIndex('matriculas', 'matriculas_estado_year_index')) {
                $table->index(['estado', 'school_year_id'], 'matriculas_estado_year_index');
            }
        });

        // Docentes: búsqueda por estado
        Schema::table('docentes', function (Blueprint $table) {
            if (! $this->hasIndex('docentes', 'docentes_estado_index')) {
                $table->index('estado', 'docentes_estado_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropIndex('estudiantes_cedula_index');
            $table->dropIndex('estudiantes_estado_index');
        });
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('asistencias_fecha_asignacion_index');
        });
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex('calificaciones_boletin_index');
        });
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropIndex('matriculas_estado_year_index');
        });
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropIndex('docentes_estado_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$index]
            );
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
