<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->string('titulo_director', 30)->nullable()->after('director');
            $table->string('encargado_academico', 150)->nullable()->after('titulo_director');
            $table->string('titulo_encargado', 30)->nullable()->after('encargado_academico');
            $table->string('nivel_educativo', 100)->nullable()->after('titulo_encargado');
            $table->string('regional', 100)->nullable()->after('nivel_educativo');
            $table->string('distrito', 100)->nullable()->after('regional');
            $table->string('municipio', 100)->nullable()->after('distrito');
            $table->string('direccion', 255)->nullable()->after('municipio');
            $table->string('telefono', 50)->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->dropColumn([
                'titulo_director', 'encargado_academico', 'titulo_encargado',
                'nivel_educativo', 'regional', 'distrito', 'municipio',
                'direccion', 'telefono',
            ]);
        });
    }
};
