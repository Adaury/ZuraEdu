<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->notNull();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->enum('estado', ['presente', 'ausente', 'tardanza', 'justificado'])->notNull();
            $table->text('justificacion')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['fecha', 'matricula_id', 'asignacion_id']);
            $table->index('fecha');
            $table->index(['matricula_id', 'fecha']);
            $table->index(['asignacion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
