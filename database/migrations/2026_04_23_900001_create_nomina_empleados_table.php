<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('cargo', 120);
            $table->decimal('salario_base', 12, 2);
            $table->enum('tipo_contrato', ['fijo', 'temporal', 'hora'])->default('fijo');
            $table->unsignedSmallInteger('horas_semana')->nullable();
            $table->date('fecha_ingreso');
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_empleados');
    }
};
