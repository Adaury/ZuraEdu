<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas_encuesta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('encuestas')->cascadeOnDelete();
            $table->text('texto');
            $table->enum('tipo', ['opcion_multiple', 'texto_libre', 'escala_1_5'])->default('opcion_multiple');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas_encuesta');
    }
};
