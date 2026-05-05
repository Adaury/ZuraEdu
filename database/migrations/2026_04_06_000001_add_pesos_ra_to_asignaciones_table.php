<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            // JSON con pesos personalizados por el docente: {"1":40,"2":30,"3":30}
            $table->json('pesos_ra')->nullable()->after('tipo_evaluacion');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropColumn('pesos_ra');
        });
    }
};
