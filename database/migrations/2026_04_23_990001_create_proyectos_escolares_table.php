<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos_escolares', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('area', ['ciencias', 'matematica', 'humanidades', 'tecnologia', 'arte', 'otro'])
                  ->default('otro');
            $table->foreignId('tutor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->enum('estado', ['planificacion', 'desarrollo', 'finalizado', 'presentado'])
                  ->default('planificacion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos_escolares');
    }
};
