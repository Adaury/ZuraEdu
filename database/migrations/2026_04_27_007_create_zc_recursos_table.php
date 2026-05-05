<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zc_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_virtual_id')->constrained('clases_virtuales')->cascadeOnDelete();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->string('titulo');
            $table->enum('tipo', ['pdf', 'video', 'enlace', 'imagen', 'presentacion', 'otro']);
            $table->text('descripcion')->nullable();
            $table->string('url')->nullable();
            $table->string('ruta_archivo')->nullable();
            $table->string('nombre_archivo')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('publico')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zc_recursos');
    }
};
