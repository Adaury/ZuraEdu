<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recomendación 7 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md (#31): trackea qué
 * guías de la sección de Capacitación (/admin/ayuda/capacitacion) ya vio
 * cada usuario, para mostrar su progreso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacitaciones_vistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('contenido_id');
            $table->timestamp('visto_at');
            $table->timestamps();

            $table->unique(['user_id', 'contenido_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacitaciones_vistas');
    }
};
