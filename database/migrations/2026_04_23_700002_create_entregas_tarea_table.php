<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'entregada', 'revisada'])->default('pendiente');
            $table->text('notas_docente')->nullable();
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->timestamps();

            $table->unique(['tarea_id', 'estudiante_id']);
            $table->index(['estudiante_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas_tarea');
    }
};
