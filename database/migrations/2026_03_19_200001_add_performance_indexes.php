<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing indexes to tables with frequent WHERE/JOIN lookups.
 * These indexes reduce full-table scans in calificaciones, asignaciones,
 * calificaciones_academicas, and activity_logs queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        // calificaciones.matricula_id — used in resumen, ranking, boletin, asistencia queries
        Schema::table('calificaciones', function (Blueprint $table) {
            if (! $this->indexExists('calificaciones', 'calificaciones_matricula_id_index')) {
                $table->index('matricula_id');
            }
        });

        // asignaciones.grupo_id + school_year_id — used in most group-based lookups
        Schema::table('asignaciones', function (Blueprint $table) {
            if (! $this->indexExists('asignaciones', 'asignaciones_grupo_id_index')) {
                $table->index('grupo_id');
            }
            if (! $this->indexExists('asignaciones', 'asignaciones_school_year_id_index')) {
                $table->index('school_year_id');
            }
        });

        // calificaciones_academicas.matricula_id — used in planilla académica lookups
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            if (! $this->indexExists('calificaciones_academicas', 'calificaciones_academicas_matricula_id_index')) {
                $table->index('matricula_id');
            }
        });

        // activity_logs — user_id + created_at for the activity log viewer
        Schema::table('activity_logs', function (Blueprint $table) {
            if (! $this->indexExists('activity_logs', 'activity_logs_user_id_index')) {
                $table->index('user_id');
            }
            if (! $this->indexExists('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex(['matricula_id']);
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropIndex(['grupo_id']);
            $table->dropIndex(['school_year_id']);
        });

        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            $table->dropIndex(['matricula_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(\Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn ($row) => $row->Key_name === $index);
    }
};
