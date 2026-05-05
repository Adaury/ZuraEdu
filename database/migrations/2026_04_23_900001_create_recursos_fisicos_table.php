<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recursos_fisicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', [
                'aula',
                'laboratorio',
                'sala_computadoras',
                'cancha',
                'auditorio',
                'proyector',
                'otro',
            ])->default('aula');
            $table->unsignedSmallInteger('capacidad')->nullable();
            $table->string('ubicacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos_fisicos');
    }
};
