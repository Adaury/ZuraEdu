<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clases_virtuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('portada_color', 7)->default('#3B82F6');
            $table->boolean('activo')->default(true);
            $table->boolean('permite_comentarios')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases_virtuales');
    }
};
