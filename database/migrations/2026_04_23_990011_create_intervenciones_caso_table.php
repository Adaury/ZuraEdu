<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervenciones_caso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->constrained('casos_seguimiento')->cascadeOnDelete();
            $table->text('descripcion');
            $table->enum('tipo_intervencion', ['reunion', 'llamada', 'visita', 'derivacion', 'otro'])->default('otro');
            $table->date('fecha');
            $table->text('resultado')->nullable();
            $table->text('siguiente_accion')->nullable();
            $table->timestamps();

            $table->index(['caso_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenciones_caso');
    }
};
