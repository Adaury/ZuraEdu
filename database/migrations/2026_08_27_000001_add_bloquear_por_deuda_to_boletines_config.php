<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recomendación 20 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md: bloqueo real
 * (opcional, por institución) de impresión/exportación de boletines cuando
 * el estudiante tiene pagos vencidos. Antes solo existía una alerta
 * informativa que nunca bloqueaba nada — ahora es una decisión que cada
 * institución activa desde Config. Boletín.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->boolean('bloquear_por_deuda')->default(false)->after('mostrar_asistencia');
        });
    }

    public function down(): void
    {
        Schema::table('boletines_config', function (Blueprint $table) {
            $table->dropColumn('bloquear_por_deuda');
        });
    }
};
