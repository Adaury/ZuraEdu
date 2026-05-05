<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas_classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales_clase')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->text('contenido')->nullable();
            $table->string('url_entrega')->nullable();
            $table->enum('estado', ['pendiente', 'entregado', 'calificado'])->default('pendiente');
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->text('comentario_docente')->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->timestamps();

            $table->unique(['material_id', 'matricula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas_classroom');
    }
};
