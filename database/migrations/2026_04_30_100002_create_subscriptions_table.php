<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->enum('estado', ['prueba', 'activa', 'vencida', 'cancelada', 'suspendida'])
                  ->default('prueba');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('monto_pagado', 10, 2)->nullable();
            $table->string('moneda', 3)->default('USD');
            $table->enum('ciclo', ['mensual', 'anual'])->default('mensual');
            $table->string('metodo_pago', 40)->nullable()->comment('stripe|paypal|transferencia|efectivo');
            $table->string('referencia_pago', 100)->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'estado']);
            $table->index('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
