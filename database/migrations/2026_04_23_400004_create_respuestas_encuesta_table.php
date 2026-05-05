<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_encuesta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('encuestas')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('preguntas_encuesta')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('respuesta_texto')->nullable();
            $table->foreignId('opcion_id')->nullable()->constrained('opciones_pregunta')->nullOnDelete();
            $table->tinyInteger('escala_valor')->nullable();
            $table->timestamps();

            // Un usuario no puede responder la misma pregunta dos veces
            $table->unique(['encuesta_id', 'pregunta_id', 'user_id'], 'unica_respuesta_por_pregunta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_encuesta');
    }
};
