<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_docente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->string('tipo', 60);
            $table->string('asunto', 200);
            $table->text('descripcion');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('adjunto')->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('respuesta')->nullable();
            $table->unsignedBigInteger('respondido_por')->nullable();
            $table->timestamp('respondido_en')->nullable();
            $table->timestamps();

            $table->foreign('respondido_por')->references('id')->on('users')->nullOnDelete();
            $table->index(['docente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_docente');
    }
};
