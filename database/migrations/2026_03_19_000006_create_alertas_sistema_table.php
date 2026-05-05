<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_sistema', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', [
                'riesgo_academico',
                'entrega_notas',
                'baja_asistencia',
                'periodo_cierre',
                'evento_calendario',
                'otro',
            ])->default('otro');
            $table->string('titulo', 200);
            $table->text('mensaje');
            $table->enum('nivel', ['info', 'warning', 'danger', 'success'])->default('info');
            $table->foreignId('destinatario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('destinatario_rol', 80)->nullable();
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->boolean('leida')->default(false);
            $table->datetime('fecha_leida')->nullable();
            $table->datetime('expira_en')->nullable();
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['destinatario_id', 'leida']);
            $table->index(['destinatario_rol', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_sistema');
    }
};
