<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GAP 08 del roadmap (docs/MATRIZ_GAPS_PRODUCTO_ZURAEDU.md): las
 * solicitudes tipo 'cita_docente' no tenían ningún destinatario real, solo
 * texto libre. Se agrega docente_id (nullable, solo se usa cuando
 * tipo='cita_docente') en vez de crear un modelo paralelo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_representante', function (Blueprint $table) {
            $table->foreignId('docente_id')->nullable()->after('estudiante_id')
                ->constrained('docentes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_representante', function (Blueprint $table) {
            $table->dropConstrainedForeignId('docente_id');
        });
    }
};
