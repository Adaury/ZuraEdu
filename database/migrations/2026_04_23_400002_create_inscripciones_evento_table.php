<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')
                  ->constrained('eventos')
                  ->cascadeOnDelete();
            $table->foreignId('estudiante_id')
                  ->constrained('estudiantes')
                  ->cascadeOnDelete();
            $table->date('fecha_inscripcion');
            $table->boolean('asistio')->default(false);
            $table->timestamps();

            $table->unique(['evento_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_evento');
    }
};
