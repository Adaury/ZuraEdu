<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->date('fecha_baja')->nullable()->after('observaciones');
            $table->text('motivo_baja')->nullable()->after('fecha_baja');
            $table->string('institucion_traslado')->nullable()->after('motivo_baja');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['fecha_baja', 'motivo_baja', 'institucion_traslado']);
        });
    }
};
