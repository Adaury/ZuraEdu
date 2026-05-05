<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales_clase')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('tipo_mime');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_material');
    }
};
