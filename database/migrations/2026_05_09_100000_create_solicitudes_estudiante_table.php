<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_estudiante', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->string('tipo', 60);
            $table->string('asunto', 200);
            $table->text('descripcion');
            $table->date('fecha_evento')->nullable();
            $table->string('adjunto', 500)->nullable();
            $table->enum('estado', ['pendiente','en_proceso','aprobada','rechazada'])->default('pendiente');
            $table->text('respuesta')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('respondido_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_estudiante');
    }
};
