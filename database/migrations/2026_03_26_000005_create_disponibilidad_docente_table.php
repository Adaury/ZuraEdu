<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidad_docente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('docente_id');
            $table->enum('dia', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes']);
            $table->unsignedBigInteger('franja_id');
            $table->boolean('disponible')->default(true);
            $table->string('motivo')->nullable(); // "Reunión administrativa"
            $table->unsignedBigInteger('school_year_id');
            $table->timestamps();
            $table->foreign('docente_id')->references('id')->on('docentes')->onDelete('cascade');
            $table->foreign('franja_id')->references('id')->on('franjas_horarias')->onDelete('cascade');
            $table->unique(['docente_id', 'dia', 'franja_id', 'school_year_id'], 'uq_disp_docente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidad_docente');
    }
};
