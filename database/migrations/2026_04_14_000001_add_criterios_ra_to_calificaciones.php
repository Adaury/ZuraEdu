<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            // Criterios por RA: {ra_num: {tp, ex, cc, oh, pd, ec, cf}}
            // tp=Trabajos Prácticos(30), ex=Exposición(15), cc=Corrección Cuadernos(10),
            // oh=Organización e Higiene(20), pd=Participación Diaria(15), ec=Evaluación Conceptual(10)
            $table->json('criterios_ra')->nullable()->after('recuperaciones_ra');
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn('criterios_ra');
        });
    }
};
