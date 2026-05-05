<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrantes_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos_escolares')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('rol', ['lider', 'integrante'])->default('integrante');
            $table->timestamps();

            $table->unique(['proyecto_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrantes_proyecto');
    }
};
