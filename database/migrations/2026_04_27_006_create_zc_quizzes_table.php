<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zc_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales_clase')->cascadeOnDelete();
            $table->integer('duracion_minutos')->nullable();
            $table->integer('intentos_max')->default(1);
            $table->boolean('autocorreccion')->default(true);
            $table->boolean('aleatorizar_preguntas')->default(false);
            $table->boolean('mostrar_respuestas')->default(true); // mostrar correctas al final
            $table->timestamps();
        });

        Schema::create('zc_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('zc_quizzes')->cascadeOnDelete();
            $table->text('enunciado');
            $table->enum('tipo', ['multiple', 'verdadero_falso', 'abierta']);
            $table->decimal('puntos', 5, 2)->default(1);
            $table->integer('orden')->default(0);
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('zc_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('zc_preguntas')->cascadeOnDelete();
            $table->string('texto');
            $table->boolean('es_correcta')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('zc_intentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('zc_quizzes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->enum('estado', ['en_curso', 'finalizado', 'abandonado'])->default('en_curso');
            $table->decimal('puntuacion', 5, 2)->nullable();
            $table->decimal('puntuacion_max', 5, 2)->nullable();
            $table->dateTime('iniciado_en');
            $table->dateTime('finalizado_en')->nullable();
            $table->integer('numero_intento')->default(1);
            $table->timestamps();

            $table->index(['quiz_id', 'matricula_id']);
        });

        Schema::create('zc_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_id')->constrained('zc_intentos')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('zc_preguntas')->cascadeOnDelete();
            $table->foreignId('opcion_id')->nullable()->constrained('zc_opciones')->nullOnDelete();
            $table->text('texto_respuesta')->nullable();
            $table->boolean('es_correcta')->nullable();
            $table->decimal('puntos_obtenidos', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['intento_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zc_respuestas');
        Schema::dropIfExists('zc_intentos');
        Schema::dropIfExists('zc_opciones');
        Schema::dropIfExists('zc_preguntas');
        Schema::dropIfExists('zc_quizzes');
    }
};
