<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones_academicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();

            // ── 4 Competencias × 4 Períodos ───────────────────────────────
            foreach ([1, 2, 3, 4] as $c) {
                foreach ([1, 2, 3, 4] as $p) {
                    $table->decimal("comp{$c}_p{$p}", 5, 2)->nullable();
                }
                $table->decimal("prom_comp{$c}", 5, 2)->nullable();
            }

            // ── Promedios y finales ────────────────────────────────────────
            $table->decimal('nota_final', 5, 2)->nullable();               // Promedio de prom_comp1..4
            $table->decimal('nota_cc', 5, 2)->nullable();                  // Calificación Complementaria
            $table->decimal('nota_completiva', 5, 2)->nullable();          // 50% nota_final + 50% CC
            $table->decimal('nota_ce', 5, 2)->nullable();                  // Calificación Extraordinaria
            $table->decimal('nota_extraordinaria', 5, 2)->nullable();      // 30% nota_final + 70% CE

            // ── Asistencia por período ────────────────────────────────────
            foreach ([1, 2, 3, 4] as $p) {
                $table->unsignedSmallInteger("asist_p{$p}")->nullable();  // clases asistidas
                $table->unsignedSmallInteger("clases_p{$p}")->nullable(); // total clases del período
            }
            $table->decimal('pct_asistencia', 5, 2)->nullable();

            // ── Meta ──────────────────────────────────────────────────────
            $table->enum('indicador', ['Excelente', 'Bueno', 'En proceso', 'Insuficiente'])->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('publicado')->default(false);
            $table->foreignId('modificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['matricula_id', 'asignacion_id', 'school_year_id'], 'cal_ac_unique');
            $table->index('asignacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_academicas');
    }
};
