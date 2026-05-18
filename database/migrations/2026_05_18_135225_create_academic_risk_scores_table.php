<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();

            // Puntuación compuesta 0-100 (mayor = más riesgo)
            $table->unsignedTinyInteger('score')->default(0);
            $table->enum('nivel', ['sin_riesgo','bajo','moderado','alto','critico'])
                  ->default('sin_riesgo')->index();

            // Dimensiones individuales (0-100 cada una)
            $table->decimal('dim_academico',  5, 2)->default(0);
            $table->decimal('dim_asistencia', 5, 2)->default(0);
            $table->decimal('dim_disciplina', 5, 2)->default(0);
            $table->decimal('dim_tendencia',  5, 2)->default(0);

            // Datos fuente usados en el cálculo
            $table->unsignedTinyInteger('materias_en_riesgo')->default(0);
            $table->unsignedTinyInteger('total_materias')->default(0);
            $table->decimal('promedio_general', 5, 2)->nullable();
            $table->decimal('pct_asistencia',   5, 2)->nullable();
            $table->unsignedTinyInteger('tardanzas')->default(0);
            $table->unsignedTinyInteger('faltas_leves')->default(0);
            $table->unsignedTinyInteger('faltas_graves')->default(0);
            $table->unsignedTinyInteger('suspensiones')->default(0);

            $table->timestamp('calculado_en')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'estudiante_id', 'school_year_id'], 'ars_tenant_est_year_unique');
            $table->index(['tenant_id', 'school_year_id', 'nivel'], 'ars_tenant_year_nivel_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_risk_scores');
    }
};
