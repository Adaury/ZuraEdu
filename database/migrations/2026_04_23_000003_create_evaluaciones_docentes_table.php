<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('evaluador_id')->constrained('users')->cascadeOnDelete();
            $table->string('periodo_evaluado', 100); // Ej: "2024-2025 · 1er Trimestre"
            $table->unsignedTinyInteger('puntualidad');         // 1-5
            $table->unsignedTinyInteger('dominio_contenido');   // 1-5
            $table->unsignedTinyInteger('metodologia');         // 1-5
            $table->unsignedTinyInteger('relacion_estudiantes');// 1-5
            $table->unsignedTinyInteger('planificacion');       // 1-5
            $table->decimal('promedio', 4, 2)->nullable();      // calculado al guardar
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['docente_id', 'periodo_evaluado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_docentes');
    }
};
