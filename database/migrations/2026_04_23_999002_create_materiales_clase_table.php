<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_virtual_id')->constrained('clases_virtuales')->cascadeOnDelete();
            $table->string('titulo');
            $table->enum('tipo', ['anuncio', 'material', 'tarea', 'evaluacion']);
            $table->text('contenido')->nullable();
            $table->string('url_externo')->nullable();
            $table->dateTime('fecha_limite')->nullable();
            $table->integer('puntos')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_clase');
    }
};
