<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('tipo', ['noticia', 'aviso', 'comunicado', 'actividad', 'logro', 'convocatoria'])
                ->default('noticia');
            $table->string('titulo', 200);
            $table->text('contenido');
            $table->string('imagen_destacada')->nullable();
            $table->date('fecha');
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador');
            $table->boolean('visible')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'estado', 'visible', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
