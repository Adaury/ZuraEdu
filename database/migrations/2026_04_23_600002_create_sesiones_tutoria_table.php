<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_tutoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoria_id')->constrained('tutorias')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tema');
            $table->text('descripcion')->nullable();
            $table->text('estudiantes_atendidos')->nullable();
            $table->text('acuerdos')->nullable();
            $table->date('proxima_sesion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_tutoria');
    }
};
