<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos_inventario')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida', 'ajuste']);
            $table->unsignedInteger('cantidad');
            $table->string('motivo');
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
