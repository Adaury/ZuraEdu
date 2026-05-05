<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['academico', 'deportivo', 'cultural', 'social', 'otro'])
                  ->default('academico');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('lugar')->nullable();
            $table->unsignedInteger('cupo_maximo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
