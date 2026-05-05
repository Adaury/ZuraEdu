<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suplencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('horario_detalle_id');
            $table->unsignedBigInteger('docente_original_id');
            $table->unsignedBigInteger('docente_suplente_id')->nullable();
            $table->date('fecha');
            $table->enum('estado', ['pendiente', 'cubierta', 'sin_cubrir'])->default('pendiente');
            $table->text('motivo')->nullable();
            $table->text('notas_suplente')->nullable();
            $table->unsignedBigInteger('registrado_por');
            $table->timestamps();
            $table->foreign('horario_detalle_id')->references('id')->on('horario_detalles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suplencias');
    }
};
