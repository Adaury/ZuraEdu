<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar el default de la columna al color correcto
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->string('color_secundario', 7)->default('#c0392b')->change();
        });

        // Actualizar los registros que todavía tienen el default incorrecto del migration anterior
        DB::table('boletines_config')
            ->where('color_secundario', '#2563eb')
            ->update(['color_secundario' => '#c0392b']);
    }

    public function down(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->string('color_secundario', 7)->default('#2563eb')->change();
        });
    }
};
