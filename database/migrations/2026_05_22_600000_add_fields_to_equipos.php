<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (! Schema::hasColumn('equipos', 'marca')) {
                $table->string('marca', 100)->nullable()->after('tipo');
            }
            if (! Schema::hasColumn('equipos', 'modelo')) {
                $table->string('modelo', 100)->nullable()->after('marca');
            }
            if (! Schema::hasColumn('equipos', 'ubicacion')) {
                $table->string('ubicacion', 150)->nullable()->after('descripcion');
            }
            if (! Schema::hasColumn('equipos', 'anio_adquisicion')) {
                $table->smallInteger('anio_adquisicion')->unsigned()->nullable()->after('ubicacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            foreach (['marca', 'modelo', 'ubicacion', 'anio_adquisicion'] as $col) {
                if (Schema::hasColumn('equipos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
