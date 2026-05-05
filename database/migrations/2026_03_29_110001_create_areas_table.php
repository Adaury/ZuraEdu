<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla normalizada de Áreas académicas/técnicas.
 * Reemplaza el campo varchar 'area' en asignaturas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->enum('tipo', ['academica', 'tecnica', 'ambas'])->default('academica');
            $table->enum('ciclo', ['primer_ciclo', 'segundo_ciclo', 'ambos'])->default('ambos');
            $table->string('color', 7)->default('#3b82f6'); // hex color para UI
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
