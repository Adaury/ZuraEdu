<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recomendación 2 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md: la columna
 * `grados.ciclo` era un ENUM de MySQL, frágil ante cambios — agregar un
 * ciclo nuevo requiere un ALTER TABLE, y la migración
 * 2026_05_15_100000_add_inicial_to_grados_ciclo_enum tuvo que borrar filas
 * existentes antes de poder aplicarlo. Se convierte a VARCHAR y la
 * validación de valores permitidos pasa a vivir en `Grado::CICLOS`
 * (aplicación), no en el esquema — agregar un ciclo nuevo ya no requiere
 * ninguna migración.
 *
 * No se normaliza la relación nivel↔ciclo en sí (nivel es un ordinal propio
 * de cada institución/convención de nombres de grado, no una función fija
 * de ciclo — confirmado con los datos reales: el mismo nivel=4 es
 * 'primer_ciclo' bajo la convención "Básica" y 'segundo_ciclo' bajo la
 * convención "Secundaria" en tenants distintos).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE grados MODIFY COLUMN ciclo VARCHAR(30) NOT NULL DEFAULT 'primer_ciclo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE grados MODIFY COLUMN ciclo ENUM('inicial','primer_ciclo','segundo_ciclo','bachillerato') NOT NULL DEFAULT 'primer_ciclo'");
    }
};
