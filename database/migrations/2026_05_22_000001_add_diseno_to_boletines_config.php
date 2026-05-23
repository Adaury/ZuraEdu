<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->string('color_primario', 7)->default('#1e3a6e')->after('logo');
            $table->string('color_secundario', 7)->default('#2563eb')->after('color_primario');
            $table->unsignedSmallInteger('logo_ancho')->default(68)->after('color_secundario');
            $table->unsignedSmallInteger('logo_alto')->default(58)->after('logo_ancho');
            $table->string('tamano_fuente', 5)->default('9pt')->after('logo_alto');
            $table->boolean('mostrar_foto_estudiante')->default(false)->after('tamano_fuente');
        });
    }

    public function down(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->dropColumn([
                'color_primario', 'color_secundario',
                'logo_ancho', 'logo_alto',
                'tamano_fuente', 'mostrar_foto_estudiante',
            ]);
        });
    }
};
