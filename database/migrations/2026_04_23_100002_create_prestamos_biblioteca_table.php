<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_biblioteca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->date('fecha_prestamo');
            $table->date('fecha_vencimiento');
            $table->date('fecha_devolucion')->nullable();
            $table->enum('estado', ['activo', 'devuelto', 'vencido'])->default('activo');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_vencimiento']);
            $table->index('estudiante_id');
            $table->index('libro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_biblioteca');
    }
};
