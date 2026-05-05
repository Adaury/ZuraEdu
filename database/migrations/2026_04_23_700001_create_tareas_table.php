<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_limite');
            $table->enum('tipo', ['tarea', 'actividad', 'proyecto', 'evaluacion'])->default('tarea');
            $table->unsignedSmallInteger('puntos_valor')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['asignacion_id', 'fecha_limite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
