<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega FK area_id a asignaturas para normalizar el campo área.
 * El varchar 'area' se mantiene por compatibilidad con módulos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->foreignId('area_id')
                  ->nullable()
                  ->after('area')
                  ->constrained('areas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');
        });
    }
};
