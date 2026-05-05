<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletines_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->unique()->constrained('school_years')->cascadeOnDelete();
            $table->string('nombre_centro', 200)->nullable();
            $table->string('subtitulo', 200)->nullable();
            $table->string('director_nombre', 150)->nullable();
            $table->string('director_firma', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->text('pie_pagina')->nullable();
            $table->boolean('mostrar_indicadores')->default(true);
            $table->boolean('mostrar_asistencia')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletines_config');
    }
};
