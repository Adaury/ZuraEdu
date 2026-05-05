<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casos_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('tipo', ['academico', 'social', 'familiar', 'conductual', 'otro'])->default('otro');
            $table->text('descripcion');
            $table->enum('nivel_riesgo', ['bajo', 'medio', 'alto', 'critico'])->default('bajo');
            $table->enum('estado', ['abierto', 'en_seguimiento', 'cerrado'])->default('abierto');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->timestamps();

            $table->index(['estado', 'nivel_riesgo']);
            $table->index('estudiante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos_seguimiento');
    }
};
