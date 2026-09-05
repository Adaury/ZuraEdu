<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registra el NCF/e-CF que el centro educativo obtuvo por su cuenta (su
 * propio software de facturación, el Facturador Gratuito de la DGII, o un
 * proveedor certificado) y lo asocia a un pago para mostrarlo en el recibo.
 *
 * ZuraEdu NO emite ni valida el comprobante fiscal — eso requiere software
 * homologado por la DGII (Ley 32-23) y no es responsabilidad de este
 * sistema. Por eso es un campo de texto libre, sin validar formato NCF/e-CF
 * ni garantizar unicidad contra la DGII.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('numero_comprobante_fiscal', 20)->nullable()->after('referencia');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('numero_comprobante_fiscal');
        });
    }
};
