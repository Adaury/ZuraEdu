<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_year_id');
            $table->string('nombre')->default('Horario Principal');
            $table->enum('estado', ['borrador', 'publicado', 'archivado'])->default('borrador');
            $table->boolean('es_activo')->default(false);
            $table->timestamp('generado_en')->nullable();
            $table->integer('iteraciones')->nullable(); // cuántas iteraciones usó el algoritmo
            $table->decimal('score', 5, 2)->nullable(); // calidad del horario (0-100)
            $table->json('conflictos')->nullable(); // array de conflictos no resueltos
            $table->unsignedBigInteger('centro_id')->nullable()->index();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
