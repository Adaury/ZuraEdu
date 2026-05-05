<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->enum('tipo', ['laptop', 'tablet', 'proyector', 'camara', 'otro'])->default('otro');
            $table->string('codigo', 60)->nullable()->unique();
            $table->enum('estado', ['disponible', 'prestado', 'mantenimiento', 'baja'])->default('disponible');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
