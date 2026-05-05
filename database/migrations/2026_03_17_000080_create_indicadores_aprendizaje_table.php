<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicadores_aprendizaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->text('descripcion')->notNull();
            $table->tinyInteger('periodo_numero')->unsigned()->notNull();
            $table->tinyInteger('orden')->unsigned()->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['asignatura_id', 'grado_id', 'periodo_numero'], 'idx_ind_asig_grado_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores_aprendizaje');
    }
};
