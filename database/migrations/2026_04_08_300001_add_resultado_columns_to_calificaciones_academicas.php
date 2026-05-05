<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columnas Resultado (R) a calificaciones_academicas.
     *
     * Estructura MINERD 4 bloques de competencias:
     *   comp{C}_p{P}  → Proceso    (ya existe)
     *   comp{C}_r{P}  → Resultado  (nuevo)
     *   prom_comp{C}  → Promedio competencia (ya existe, se recalcula)
     *
     * Por cada competencia C (1-4) y período P (1-4):
     *   Promedio período = avg(compC_pP, compC_rP)
     *   prom_compC      = avg de los 4 promedios de período
     *   nota_final      = avg de los 4 prom_compC
     */
    public function up(): void
    {
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            // comp{C}_r{P} — Resultado por competencia y período
            foreach ([1, 2, 3, 4] as $c) {
                foreach ([1, 2, 3, 4] as $p) {
                    // Añadir después de la columna p equivalente
                    $table->decimal("comp{$c}_r{$p}", 5, 2)
                          ->nullable()
                          ->after("comp{$c}_p{$p}");
                }
            }

            // Columnas de promedio por período por competencia (para caché de cálculo)
            foreach ([1, 2, 3, 4] as $c) {
                foreach ([1, 2, 3, 4] as $p) {
                    $table->decimal("avg_comp{$c}_p{$p}", 5, 2)
                          ->nullable()
                          ->after("comp{$c}_r{$p}");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones_academicas', function (Blueprint $table) {
            foreach ([1, 2, 3, 4] as $c) {
                foreach ([1, 2, 3, 4] as $p) {
                    $table->dropColumnIfExists("comp{$c}_r{$p}");
                    $table->dropColumnIfExists("avg_comp{$c}_p{$p}");
                }
            }
        });
    }
};
