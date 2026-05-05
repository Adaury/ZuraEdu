<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            // Rename to match model & views
            $table->renameColumn('nombre_centro',   'nombre_institucion');
            $table->renameColumn('director_nombre', 'director');
            $table->renameColumn('subtitulo',       'lema');
            $table->renameColumn('logo_path',       'logo');
        });

        Schema::table('boletines_config', function (Blueprint $table) {
            // Drop unused column
            $table->dropColumn('director_firma');
            // Add missing columns
            $table->string('codigo', 50)->nullable()->after('lema');
            $table->text('observaciones_generales')->nullable()->after('pie_pagina');
        });
    }

    public function down(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->renameColumn('nombre_institucion', 'nombre_centro');
            $table->renameColumn('director',           'director_nombre');
            $table->renameColumn('lema',               'subtitulo');
            $table->renameColumn('logo',               'logo_path');
        });
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->string('director_firma')->nullable();
            $table->dropColumn(['codigo', 'observaciones_generales']);
        });
    }
};
