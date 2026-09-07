<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_clase', function (Blueprint $table) {
            // Referencia opcional a la capa curricular según el área del plan:
            // académica → PlanifUnidad (planif_unidades), técnica → Planificacion RA/MF/UC (planificaciones).
            $table->foreignId('planif_unidad_id')->nullable()->after('area')
                ->constrained('planif_unidades')->nullOnDelete();
            $table->foreignId('planificacion_id')->nullable()->after('planif_unidad_id')
                ->constrained('planificaciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('planes_clase', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planif_unidad_id');
            $table->dropConstrainedForeignId('planificacion_id');
        });
    }
};
