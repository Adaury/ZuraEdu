<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * `asistencias` (la tabla de mayor volumen de escritura hoy) tenía 2 índices
 * que son prefijos redundantes de otros compuestos ya existentes — MySQL usa
 * el prefijo izquierdo de un índice compuesto para servir las mismas
 * consultas, así que el índice individual no aporta cobertura extra, solo
 * overhead de escritura/espacio:
 *   - asistencias_fecha_index (fecha) — cubierto por el prefijo de
 *     asistencias_fecha_matricula_id_asignacion_id_unique (fecha, ...)
 *     y de asistencias_fecha_asignacion_index (fecha, asignacion_id).
 *   - idx_asist_matricula_id (matricula_id) — cubierto por el prefijo de
 *     asistencias_matricula_id_fecha_index (matricula_id, fecha).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function ($table) {
            $table->dropIndex('asistencias_fecha_index');
            $table->dropIndex('idx_asist_matricula_id');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function ($table) {
            $table->index('fecha', 'asistencias_fecha_index');
            $table->index('matricula_id', 'idx_asist_matricula_id');
        });
    }
};
