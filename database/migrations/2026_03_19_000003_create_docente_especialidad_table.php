<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades_tecnicas')->cascadeOnDelete();
            $table->boolean('es_coordinador')->default(false);
            $table->date('fecha_asignacion')->nullable();
            $table->timestamps();
            $table->unique(['docente_id', 'especialidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_especialidad');
    }
};
