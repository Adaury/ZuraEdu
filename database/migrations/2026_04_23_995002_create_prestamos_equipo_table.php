<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_equipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_prestamo');
            $table->date('fecha_vencimiento');
            $table->date('fecha_devolucion')->nullable();
            $table->string('motivo', 500)->nullable();
            $table->enum('estado', ['activo', 'devuelto', 'vencido'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_equipo');
    }
};
