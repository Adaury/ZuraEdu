<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicadores_logro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competencia_id')
                  ->constrained('competencias_especificas')
                  ->cascadeOnDelete();
            $table->string('codigo', 10);        // IL1, IL2, IL3…
            $table->text('descripcion');
            $table->unsignedTinyInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['competencia_id', 'codigo'], 'il_unique');
            $table->index(['competencia_id', 'activo'], 'il_busqueda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores_logro');
    }
};
