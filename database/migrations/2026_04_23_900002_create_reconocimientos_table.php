<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconocimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('tipo_id')->constrained('tipos_reconocimiento');
            $table->string('titulo', 160);
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->foreignId('emitido_por_id')->constrained('users');
            $table->boolean('entregado')->default(false);
            $table->date('fecha_entrega')->nullable();
            $table->timestamps();

            $table->index('estudiante_id');
            $table->index('tipo_id');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconocimientos');
    }
};
