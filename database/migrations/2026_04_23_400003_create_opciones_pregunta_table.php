<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opciones_pregunta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('preguntas_encuesta')->cascadeOnDelete();
            $table->string('texto');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_pregunta');
    }
};
