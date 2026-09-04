<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla a nivel de plataforma (NO tenant-scoped): el backup respalda la
     * base de datos compartida completa (todos los tenants a la vez), así
     * que el historial de corridas tampoco pertenece a un tenant particular.
     */
    public function up(): void
    {
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('iniciado_en');
            $table->timestamp('finalizado_en')->nullable();
            $table->unsignedInteger('duracion_segundos')->nullable();
            $table->enum('estado', ['exitoso', 'fallido'])->default('fallido');
            $table->string('etapa_fallo')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->string('bd_archivo')->nullable();
            $table->unsignedBigInteger('bd_tamano_bytes')->nullable();
            $table->string('archivos_archivo')->nullable();
            $table->unsignedBigInteger('archivos_tamano_bytes')->nullable();
            $table->unsignedInteger('eliminados_retencion')->default(0);
            $table->timestamps();

            $table->index(['estado', 'iniciado_en'], 'idx_backup_runs_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
