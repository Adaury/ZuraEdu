<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Un docente solo puede ser tutor de un grupo por año escolar
            $table->unique(['grupo_id', 'school_year_id'], 'tutorias_grupo_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorias');
    }
};
