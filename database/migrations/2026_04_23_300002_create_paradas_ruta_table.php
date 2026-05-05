<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paradas_ruta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')->constrained('rutas_transporte')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(1);
            $table->time('hora_estimada')->nullable();
            $table->timestamps();

            $table->index(['ruta_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paradas_ruta');
    }
};
