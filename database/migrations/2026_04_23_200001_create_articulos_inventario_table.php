<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('categoria', [
                'mobiliario',
                'tecnologia',
                'material_didactico',
                'deportivo',
                'limpieza',
                'otro',
            ])->default('otro');
            $table->unsignedInteger('cantidad_total')->default(0);
            $table->unsignedInteger('cantidad_disponible')->default(0);
            $table->string('ubicacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['bueno', 'regular', 'malo'])->default('bueno');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos_inventario');
    }
};
