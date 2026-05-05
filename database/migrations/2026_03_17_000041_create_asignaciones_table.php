<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['school_year_id', 'grupo_id', 'asignatura_id']);
            $table->index('docente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
    }
};
