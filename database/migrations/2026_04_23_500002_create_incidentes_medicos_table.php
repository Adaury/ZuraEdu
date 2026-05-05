<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentes_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['accidente', 'enfermedad', 'alergia', 'otro']);
            $table->text('descripcion');
            $table->text('accion_tomada');
            $table->string('remitido_a')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentes_medicos');
    }
};
