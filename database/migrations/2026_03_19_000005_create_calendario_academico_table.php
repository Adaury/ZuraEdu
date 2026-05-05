<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_academico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', [
                'entrega_notas',
                'examen',
                'suspension',
                'inicio_periodo',
                'fin_periodo',
                'actividad',
                'feriado',
                'reunion',
                'otro',
            ])->default('otro');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->string('color', 7)->default('#1e3a6e');
            $table->enum('aplica_a', [
                'todos', 'docentes', 'estudiantes', 'coordinadores', 'administrativos',
            ])->default('todos');
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['school_year_id', 'fecha_inicio']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_academico');
    }
};
