<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recomendación 5 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md (#26 SLA, #29 causa raíz).
 * Extiende el módulo de Mesa de Ayuda existente — sin tabla ni módulo nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_soporte', function (Blueprint $table) {
            $table->timestamp('sla_vencimiento_at')->nullable()->after('prioridad');
            $table->boolean('sla_incumplido')->default(false)->after('sla_vencimiento_at');
            $table->text('causa_raiz')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_soporte', function (Blueprint $table) {
            $table->dropColumn(['sla_vencimiento_at', 'sla_incumplido', 'causa_raiz']);
        });
    }
};
