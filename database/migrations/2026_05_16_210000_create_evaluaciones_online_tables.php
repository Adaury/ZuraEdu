<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quiz/Evaluación ligada a una asignación
        Schema::create('eva_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->string('titulo', 200);
            $table->text('instrucciones')->nullable();
            $table->unsignedSmallInteger('duracion_minutos')->nullable();
            $table->unsignedTinyInteger('intentos_max')->default(1);
            $table->boolean('mostrar_resultados')->default(true);
            $table->boolean('aleatorizar')->default(false);
            $table->boolean('publicado')->default(false);
            $table->timestamp('disponible_desde')->nullable();
            $table->timestamp('disponible_hasta')->nullable();
            $table->timestamps();
        });

        // Preguntas (opciones almacenadas como JSON)
        Schema::create('eva_preguntas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('quiz_id')->constrained('eva_quizzes')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->text('enunciado');
            $table->enum('tipo', ['multiple', 'verdadero_falso', 'abierta'])->default('multiple');
            $table->json('opciones')->nullable(); // [{texto, correcta: bool}]
            $table->decimal('puntos', 5, 2)->default(1);
            $table->text('explicacion')->nullable();
            $table->timestamps();
        });

        // Intentos de un estudiante
        Schema::create('eva_intentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('quiz_id')->constrained('eva_quizzes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->enum('estado', ['en_curso', 'finalizado'])->default('en_curso');
            $table->json('respuestas')->nullable(); // {pregunta_id: {opcion_idx, texto, correcta, puntos}}
            $table->decimal('puntuacion', 6, 2)->default(0);
            $table->decimal('puntuacion_max', 6, 2)->default(0);
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eva_intentos');
        Schema::dropIfExists('eva_preguntas');
        Schema::dropIfExists('eva_quizzes');
    }
};
