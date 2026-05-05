<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_matriculas', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento');
            $table->string('grado_solicitado', 80);
            $table->string('nombre_representante', 150);
            $table->string('cedula_representante', 20);
            $table->string('telefono', 30);
            $table->string('email', 150);
            $table->string('direccion', 300);
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('notas_admin')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('grado_solicitado');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_matriculas');
    }
};
