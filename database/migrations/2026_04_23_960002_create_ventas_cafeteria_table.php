<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_cafeteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos_cafeteria')->nullOnDelete();
            $table->string('descripcion', 200)->nullable();
            $table->enum('tipo', ['venta', 'recarga', 'ajuste'])->default('venta');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_anterior', 10, 2)->default(0);
            $table->decimal('saldo_nuevo', 10, 2)->default(0);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_cafeteria');
    }
};
