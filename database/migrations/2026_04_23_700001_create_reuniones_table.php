<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reuniones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->enum('tipo', [
                'consejo_directivo',
                'reunion_padres',
                'reunion_docentes',
                'comite',
                'otra',
            ])->default('otra');
            $table->dateTime('fecha');
            $table->string('lugar')->nullable();
            $table->foreignId('convocante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('agenda')->nullable();
            $table->text('participantes')->nullable();
            $table->enum('estado', ['programada', 'realizada', 'cancelada'])->default('programada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reuniones');
    }
};
