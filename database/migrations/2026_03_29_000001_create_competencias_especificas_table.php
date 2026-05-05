<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencias_especificas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained()->cascadeOnDelete();
            $table->enum('ciclo', ['primer_ciclo', 'segundo_ciclo'])->default('primer_ciclo');
            $table->string('codigo', 10);        // CE1, CE2, CE3…
            $table->string('nombre', 250);
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['asignatura_id', 'ciclo', 'codigo'], 'ce_unique');
            $table->index(['asignatura_id', 'ciclo', 'activo'], 'ce_busqueda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencias_especificas');
    }
};
