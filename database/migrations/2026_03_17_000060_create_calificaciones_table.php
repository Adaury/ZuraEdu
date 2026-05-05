<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->decimal('tareas', 5, 2)->nullable();
            $table->decimal('practicas', 5, 2)->nullable();
            $table->decimal('participacion', 5, 2)->nullable();
            $table->decimal('proyecto', 5, 2)->nullable();
            $table->decimal('examen', 5, 2)->nullable();
            $table->decimal('nota_final', 5, 2)->nullable();
            $table->enum('indicador', ['Excelente', 'Bueno', 'En proceso', 'Insuficiente'])->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('publicado')->default(false);
            $table->unsignedBigInteger('modificado_por')->nullable();
            $table->foreign('modificado_por')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['matricula_id', 'asignacion_id', 'periodo_id']);
            $table->index('periodo_id');
            $table->index('asignacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
