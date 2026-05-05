<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_indicadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('indicador_id')->constrained('indicadores_aprendizaje')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->enum('nivel', ['Excelente', 'Bueno', 'En proceso', 'Insuficiente'])->notNull();
            $table->timestamps();

            $table->unique(['matricula_id', 'indicador_id', 'periodo_id'], 'uk_eval_ind');
            $table->index(['matricula_id', 'periodo_id'], 'idx_eval_mat_per');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_indicadores');
    }
};
