<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franjas_horarias', function (Blueprint $table) {
            $table->id();
            $table->integer('numero'); // 1, 2, 3... (orden del día)
            $table->time('hora_inicio'); // "07:30"
            $table->time('hora_fin');    // "08:15"
            $table->string('nombre')->nullable(); // "1ra Hora", "Recreo"
            $table->boolean('es_recreo')->default(false);
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('centro_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['numero', 'centro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franjas_horarias');
    }
};
