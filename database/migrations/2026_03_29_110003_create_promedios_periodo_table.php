<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache de promedios por estudiante/asignación/período.
 * Permite generar boletines sin recalcular en tiempo real.
 *
 * Segundo ciclo  → usa 'promedio' (0-100) + 'indicador'
 * Primer ciclo   → usa 'valor_cualitativo' (1-4) + 'indicador'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promedios_periodo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('matricula_id')
                  ->constrained('matriculas')
                  ->cascadeOnDelete();

            $table->foreignId('asignacion_id')
                  ->constrained('asignaciones')
                  ->cascadeOnDelete();

            $table->foreignId('periodo_id')
                  ->constrained('periodos')
                  ->cascadeOnDelete();

            $table->foreignId('school_year_id')
                  ->constrained('school_years')
                  ->cascadeOnDelete();

            // Segundo ciclo (numérico 0–100)
            $table->decimal('promedio', 5, 2)->nullable();

            // Primer ciclo (cualitativo 1–4)
            $table->tinyInteger('valor_cualitativo')->unsigned()->nullable();

            // Indicador calculado en ambos ciclos
            $table->enum('indicador', [
                'Excelente', 'Bueno', 'En proceso', 'Insuficiente'
            ])->nullable();

            // % asistencia del período para este grupo/asignación
            $table->decimal('pct_asistencia', 5, 2)->nullable();

            $table->boolean('publicado')->default(false);
            $table->timestamp('calculado_en')->nullable();
            $table->timestamps();

            // Una fila por estudiante + asignación + período
            $table->unique(['matricula_id', 'asignacion_id', 'periodo_id'], 'uq_promedio_periodo');

            // Índices para consultas de reportes
            $table->index(['school_year_id', 'periodo_id'], 'idx_pp_year_periodo');
            $table->index(['matricula_id', 'school_year_id'], 'idx_pp_matricula_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promedios_periodo');
    }
};
