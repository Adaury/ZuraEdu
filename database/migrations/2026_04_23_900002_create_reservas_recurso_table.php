<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos_fisicos')->cascadeOnDelete();
            $table->foreignId('solicitante_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('motivo');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índice para agilizar la validación de conflictos
            $table->index(['recurso_id', 'fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas_recurso');
    }
};
