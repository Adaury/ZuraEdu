<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->date('fecha_matricula');
            $table->smallInteger('numero_orden')->unsigned()->nullable();
            $table->enum('estado', ['activa', 'retirada', 'transferida'])->default('activa');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['school_year_id', 'estudiante_id']);
            $table->index('grupo_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
