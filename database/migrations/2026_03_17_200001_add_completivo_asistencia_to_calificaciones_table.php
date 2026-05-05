<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->decimal('nota_cc', 5, 2)->nullable()->after('nota_final');           // C.C  Calificación Complementaria
            $table->decimal('nota_completiva', 5, 2)->nullable()->after('nota_cc');      // 50% nota_final + 50% CC
            $table->decimal('nota_ce', 5, 2)->nullable()->after('nota_completiva');      // C.E  Calificación Extraordinaria
            $table->decimal('nota_extraordinaria', 5, 2)->nullable()->after('nota_ce'); // 30% nota_final + 70% CE
            $table->unsignedSmallInteger('asistencia_clases')->nullable()->after('nota_extraordinaria');
            $table->unsignedSmallInteger('asistencia_total')->nullable()->after('asistencia_clases');
        });
    }

    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn([
                'nota_cc', 'nota_completiva',
                'nota_ce', 'nota_extraordinaria',
                'asistencia_clases', 'asistencia_total',
            ]);
        });
    }
};
