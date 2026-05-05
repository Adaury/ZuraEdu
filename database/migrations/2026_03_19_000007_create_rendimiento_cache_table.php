<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendimiento_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->smallInteger('total_estudiantes')->default(0);
            $table->decimal('pct_excelente', 5, 2)->default(0);
            $table->decimal('pct_bueno', 5, 2)->default(0);
            $table->decimal('pct_regular', 5, 2)->default(0);
            $table->decimal('pct_bajo', 5, 2)->default(0);
            $table->decimal('promedio_grupo', 5, 2)->nullable();
            $table->smallInteger('total_riesgo')->default(0);
            $table->smallInteger('total_aprobados')->default(0);
            $table->smallInteger('total_reprobados')->default(0);
            $table->datetime('calculado_en');
            $table->unique(['school_year_id', 'grupo_id', 'periodo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendimiento_cache');
    }
};
