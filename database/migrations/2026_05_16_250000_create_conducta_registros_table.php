<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conducta_registros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            // Indicadores: 1=Deficiente, 2=Regular, 3=Bueno, 4=Muy Bueno, 5=Excelente
            $table->tinyInteger('puntualidad')->nullable();
            $table->tinyInteger('participacion')->nullable();
            $table->tinyInteger('respeto')->nullable();
            $table->tinyInteger('trabajo_equipo')->nullable();
            $table->tinyInteger('responsabilidad')->nullable();
            $table->tinyInteger('orden')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['matricula_id', 'asignacion_id', 'periodo_id'], 'conducta_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conducta_registros');
    }
};
