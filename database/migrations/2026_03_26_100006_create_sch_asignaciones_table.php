<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Qué debe enseñar cada profesor: materia + curso + horas/semana
// Esto es lo que el generador necesita distribuir en el horario

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id') ->constrained('sch_materias') ->cascadeOnDelete();
            $table->foreignId('profesor_id')->constrained('sch_profesores')->cascadeOnDelete();
            $table->foreignId('curso_id')   ->constrained('sch_cursos')    ->cascadeOnDelete();
            $table->unsignedTinyInteger('horas_semana'); // cuántas clases/semana asignar
            $table->timestamps();

            $table->unique(['materia_id', 'profesor_id', 'curso_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('sch_asignaciones'); }
};
