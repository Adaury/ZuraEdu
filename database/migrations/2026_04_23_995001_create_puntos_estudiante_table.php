<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->string('concepto');
            $table->enum('categoria', ['academico', 'asistencia', 'conducta', 'participacion', 'extra'])
                  ->default('extra');
            $table->integer('puntos');
            $table->date('fecha');
            $table->timestamps();

            $table->index('matricula_id');
            $table->index(['matricula_id', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_estudiante');
    }
};
