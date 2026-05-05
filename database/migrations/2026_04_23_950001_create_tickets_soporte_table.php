<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets_soporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitante_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asignado_a_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->enum('categoria', ['tecnico', 'academico', 'administrativo', 'otro'])->default('otro');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->enum('estado', ['abierto', 'en_proceso', 'resuelto', 'cerrado'])->default('abierto');
            $table->timestamps();

            $table->index('solicitante_id');
            $table->index('asignado_a_id');
            $table->index('estado');
            $table->index('prioridad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_soporte');
    }
};
