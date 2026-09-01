<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * `horario_detalles.aula_id` era la única columna *_id sin índice de todas
 * las tablas centrales auditadas (horario_id, franja_id, asignacion_id,
 * dia y tenant_id ya lo tienen). Se consulta por aula al detectar choques
 * de horario (HorarioIntegrityChecker) y al filtrar la vista maestra por
 * aula — full scan en cada consulta sin este índice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horario_detalles', function ($table) {
            $table->index('aula_id', 'idx_hd_aula');
        });
    }

    public function down(): void
    {
        Schema::table('horario_detalles', function ($table) {
            $table->dropIndex('idx_hd_aula');
        });
    }
};
