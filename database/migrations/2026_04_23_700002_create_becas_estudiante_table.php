<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('becas_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beca_id')->constrained('becas')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['beca_id', 'matricula_id']);
            $table->index('matricula_id');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('becas_estudiante');
    }
};
