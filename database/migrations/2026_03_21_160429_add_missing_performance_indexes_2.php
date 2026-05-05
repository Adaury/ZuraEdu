<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Índices de rendimiento faltantes detectados en auditoría técnica 2026-03-21.
 * Impacto: queries de boletines, reportes y búsquedas de asistencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── calificaciones ──────────────────────────────────────────────────
        if (! $this->indexExists('calificaciones', 'idx_cal_asignacion_id')) {
            Schema::table('calificaciones', function (Blueprint $table) {
                $table->index('asignacion_id', 'idx_cal_asignacion_id');
            });
        }
        if (! $this->indexExists('calificaciones', 'idx_cal_periodo_id')) {
            Schema::table('calificaciones', function (Blueprint $table) {
                $table->index('periodo_id', 'idx_cal_periodo_id');
            });
        }
        // Índice compuesto para queries de boletín
        if (! $this->indexExists('calificaciones', 'idx_cal_mat_asi_per')) {
            Schema::table('calificaciones', function (Blueprint $table) {
                $table->index(['matricula_id', 'asignacion_id', 'periodo_id'], 'idx_cal_mat_asi_per');
            });
        }

        // ── asistencias ─────────────────────────────────────────────────────
        if (! $this->indexExists('asistencias', 'idx_asist_matricula_id')) {
            Schema::table('asistencias', function (Blueprint $table) {
                $table->index('matricula_id', 'idx_asist_matricula_id');
            });
        }

        // ── docentes ────────────────────────────────────────────────────────
        if (! $this->indexExists('docentes', 'idx_docentes_cedula')) {
            Schema::table('docentes', function (Blueprint $table) {
                $table->index('cedula', 'idx_docentes_cedula');
            });
        }

        // ── calificaciones_academicas ────────────────────────────────────────
        if (! $this->indexExists('calificaciones_academicas', 'idx_calac_mat_id')) {
            Schema::table('calificaciones_academicas', function (Blueprint $table) {
                $table->index('matricula_id', 'idx_calac_mat_id');
            });
        }
        if (! $this->indexExists('calificaciones_academicas', 'idx_calac_sy_publicado')) {
            Schema::table('calificaciones_academicas', function (Blueprint $table) {
                $table->index(['school_year_id', 'publicado'], 'idx_calac_sy_publicado');
            });
        }
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_cal_asignacion_id');
            $table->dropIndex('idx_cal_periodo_id');
            $table->dropIndex('idx_cal_mat_asi_per');
        });
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('idx_asist_matricula_id');
        });
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropIndex('idx_docentes_cedula');
        });
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            $table->dropIndex('idx_calac_mat_id');
            $table->dropIndex('idx_calac_sy_publicado');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"));
        return $indexes->contains('Key_name', $indexName);
    }
};
