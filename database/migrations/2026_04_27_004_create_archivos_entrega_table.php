<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_entrega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_classroom')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('tipo_mime');
            $table->unsignedBigInteger('tamanio')->default(0); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_entrega');
    }
};
