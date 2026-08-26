<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CierreAnoController asigna matricula.estado = 'promovida'/'no_promovida' en 10 sitios
 * (ver docs/AUDITORIA_DON_BOSCO_ZURAEDU.md §5, hallazgo crítico), pero el ENUM original
 * de matriculas.estado (create_matriculas_table.php) solo tiene 'activa','retirada',
 * 'transferida'. Con la conexión en modo strict, el cierre de año no puede persistir su
 * propio resultado. Esta migración es aditiva: solo agrega los 2 valores que faltan,
 * no quita ni modifica ninguno de los 3 existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE matriculas MODIFY COLUMN estado
             ENUM('activa','retirada','transferida','promovida','no_promovida')
             NOT NULL DEFAULT 'activa'"
        );
    }

    public function down(): void
    {
        // Revertir requeriría decidir qué hacer con filas ya marcadas 'promovida'/
        // 'no_promovida' (perderían el estado real de promoción). No se asume una
        // reasignación automática — si se necesita revertir, decidir primero cómo
        // remapear esas filas y hacerlo explícito antes de correr este down().
        DB::statement(
            "ALTER TABLE matriculas MODIFY COLUMN estado
             ENUM('activa','retirada','transferida')
             NOT NULL DEFAULT 'activa'"
        );
    }
};
