<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_nomina', function (Blueprint $table) {
            // Desglose de deducciones
            $table->decimal('desc_tss', 10, 2)->default(0)->after('salario_bruto');
            $table->decimal('desc_isr', 10, 2)->default(0)->after('desc_tss');
            $table->decimal('desc_otros', 10, 2)->default(0)->after('desc_isr');
            $table->text('notas_deducciones')->nullable()->after('desc_otros');

            // Ingresos adicionales
            $table->decimal('horas_extra', 10, 2)->default(0)->after('notas_deducciones');
            $table->decimal('bonificacion', 10, 2)->default(0)->after('horas_extra');
            $table->decimal('otros_ingresos', 10, 2)->default(0)->after('bonificacion');

            // Pagado por
            $table->foreignId('pagado_por')->nullable()->after('fecha_pago')
                  ->constrained('users')->nullOnDelete();
            $table->string('metodo_pago', 50)->nullable()->after('pagado_por');
            $table->string('referencia_pago', 100)->nullable()->after('metodo_pago');
        });

        // Añadir campos extra al empleado
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->string('cedula', 20)->nullable()->after('cargo');
            $table->string('cuenta_bancaria', 30)->nullable()->after('cedula');
            $table->string('banco', 60)->nullable()->after('cuenta_bancaria');
            $table->decimal('tss_porcentaje', 5, 2)->default(3.04)->after('salario_base'); // descuento TSS trabajador RD
            $table->boolean('exento_isr')->default(false)->after('tss_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_nomina', function (Blueprint $table) {
            $table->dropForeign(['pagado_por']);
            $table->dropColumn(['desc_tss','desc_isr','desc_otros','notas_deducciones',
                               'horas_extra','bonificacion','otros_ingresos',
                               'pagado_por','metodo_pago','referencia_pago']);
        });
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->dropColumn(['cedula','cuenta_bancaria','banco','tss_porcentaje','exento_isr']);
        });
    }
};
