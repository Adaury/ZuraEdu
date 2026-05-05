<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_horario_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')   ->constrained('sch_horarios')    ->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('sch_asignaciones')->cascadeOnDelete();
            $table->foreignId('aula_id')      ->nullable()->constrained('sch_aulas')->nullOnDelete();
            $table->foreignId('franja_id')    ->constrained('sch_franjas')     ->cascadeOnDelete();
            $table->enum('dia', ['lunes','martes','miercoles','jueves','viernes']);
            $table->timestamps();

            // Un profesor / curso / aula solo puede estar en 1 slot simultáneo
            $table->index(['horario_id', 'dia', 'franja_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('sch_horario_detalles'); }
};
