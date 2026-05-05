<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes_ruta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas_transporte')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('tipo', ['ida', 'vuelta', 'ambos'])->default('ambos');
            $table->foreignId('parada_id')->nullable()->constrained('paradas_ruta')->nullOnDelete();
            $table->timestamps();

            $table->unique(['ruta_id', 'estudiante_id']);
            $table->index('estudiante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes_ruta');
    }
};
