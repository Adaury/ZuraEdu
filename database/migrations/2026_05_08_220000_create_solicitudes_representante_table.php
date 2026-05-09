<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_representante', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('representante_id')->constrained('representantes')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->string('tipo', 60); // justificacion_ausencia | cita_docente | cita_direccion | solicitar_documento | actualizar_datos | otro
            $table->string('asunto', 200);
            $table->text('descripcion');
            $table->date('fecha_evento')->nullable();  // para justificaciones: fecha de ausencia
            $table->string('adjunto', 500)->nullable(); // ruta de archivo
            $table->enum('estado', ['pendiente', 'en_proceso', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('respuesta')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('respondido_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_representante');
    }
};
