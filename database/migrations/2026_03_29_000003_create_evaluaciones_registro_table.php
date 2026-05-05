<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla universal de evaluaciones para el registro MINERD.
     *
     * - Primer ciclo  → valor_cualitativo (1–4)  + indicador_id
     * - Segundo ciclo → nota_numerica   (0–100) + indicador_id o competencia_id
     */
    public function up(): void
    {
        Schema::create('evaluaciones_registro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();

            // Referencia al indicador (IL) o a la competencia (CE) directa
            $table->foreignId('competencia_id')
                  ->nullable()
                  ->constrained('competencias_especificas')
                  ->nullOnDelete();
            $table->foreignId('indicador_id')
                  ->nullable()
                  ->constrained('indicadores_logro')
                  ->nullOnDelete();

            // Primer ciclo: escala 1=Inicial 2=En proceso 3=Logrado 4=Avanzado
            $table->unsignedTinyInteger('valor_cualitativo')->nullable();
            // Segundo ciclo: nota 0–100
            $table->decimal('nota_numerica', 5, 2)->nullable();

            $table->foreignId('registrado_por')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['matricula_id', 'asignacion_id', 'periodo_id', 'indicador_id', 'competencia_id'],
                'eval_reg_unique'
            );
            $table->index(['matricula_id', 'school_year_id'], 'eval_matricula_year');
            $table->index(['asignacion_id', 'periodo_id'],    'eval_asig_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_registro');
    }
};
