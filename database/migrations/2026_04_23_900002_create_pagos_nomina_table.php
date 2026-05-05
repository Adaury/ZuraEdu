<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_nomina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomina_empleado_id')
                  ->constrained('nomina_empleados')
                  ->onDelete('cascade');
            $table->string('mes', 7);          // formato YYYY-MM
            $table->decimal('salario_bruto', 12, 2);
            $table->decimal('deducciones', 12, 2)->default(0);
            $table->decimal('salario_neto', 12, 2);
            $table->boolean('pagado')->default(false);
            $table->date('fecha_pago')->nullable();
            $table->timestamps();

            $table->unique(['nomina_empleado_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_nomina');
    }
};
