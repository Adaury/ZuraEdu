<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insignias_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->enum('tipo', [
                'asistencia_perfecta',
                'top_estudiante',
                'mejora_continua',
                'cien_puntos',
                'quinientos_puntos',
                'sin_faltas',
            ]);
            $table->date('fecha_obtencion');
            $table->timestamps();

            $table->unique(['matricula_id', 'tipo']);
            $table->index('matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insignias_estudiante');
    }
};
