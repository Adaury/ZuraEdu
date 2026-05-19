<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concepto_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('nombre');
            $table->decimal('monto_defecto', 10, 2)->nullable();
            $table->enum('tipo', ['mensualidad', 'inscripcion', 'otro'])->default('otro');
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concepto_pagos');
    }
};
