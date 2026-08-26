<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recomendación 6 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md (#9/#10): mueve la
 * carga masiva de calificaciones a un Job en cola. Esta tabla trackea cada
 * lote (estado, contadores, errores) para poder mostrarle progreso al
 * usuario ya que el procesamiento deja de ser síncrono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->string('archivo_original');
            $table->string('archivo_path');
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])->default('pendiente');
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('importados')->default(0);
            $table->unsignedInteger('omitidos')->default(0);
            $table->json('errores')->nullable();
            $table->text('error_fatal')->nullable();
            $table->timestamp('iniciado_at')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_calificaciones');
    }
};
