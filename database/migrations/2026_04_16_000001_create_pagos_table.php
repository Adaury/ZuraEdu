<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->string('concepto');                          // "Cuota Enero 2026", "Matrícula", etc.
            $table->decimal('monto', 10, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'vencido', 'cancelado'])->default('pendiente');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta', 'stripe', 'otro'])->nullable();
            $table->string('referencia')->nullable();            // No. recibo / session_id Stripe
            $table->text('notas')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['matricula_id', 'estado']);
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
