<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('horario_id');
            $table->unsignedBigInteger('asignacion_id'); // docente+materia+grupo
            $table->unsignedBigInteger('aula_id')->nullable();
            $table->unsignedBigInteger('franja_id');
            $table->enum('dia', ['lunes', 'martes', 'miercoles', 'jueves', 'viernes']);
            $table->boolean('es_suplencia')->default(false);
            $table->timestamps();
            $table->foreign('horario_id')->references('id')->on('horarios')->onDelete('cascade');
            $table->foreign('franja_id')->references('id')->on('franjas_horarias');
            // Un grupo no puede estar en 2 lugares al mismo tiempo
            $table->unique(['horario_id', 'franja_id', 'dia', 'asignacion_id'], 'uq_horario_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_detalles');
    }
};
