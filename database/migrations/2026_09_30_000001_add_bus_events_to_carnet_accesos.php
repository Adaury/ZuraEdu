<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GAP 06 (docs/MATRIZ_GAPS_PRODUCTO_ZURAEDU.md): control de abordaje del
 * transporte escolar reutilizando Carnet+. Aditiva: agrega 'bus_subida' y
 * 'bus_bajada' al ENUM tipo_evento (sin quitar ninguno) y una ruta_id
 * nullable para saber en qué ruta se registró el abordaje.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE carnet_accesos MODIFY COLUMN tipo_evento
             ENUM('entrada','salida','biblioteca','comedor','laboratorio','evento','prestamo','bus_subida','bus_bajada')
             NOT NULL DEFAULT 'entrada'"
        );

        Schema::table('carnet_accesos', function (Blueprint $table) {
            $table->foreignId('ruta_id')->nullable()->after('zona_id')
                ->constrained('rutas_transporte')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carnet_accesos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ruta_id');
        });
        // No se revierte el ENUM: filas 'bus_*' existentes quedarían inválidas.
    }
};
