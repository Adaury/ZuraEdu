<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_franjas', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero');      // orden visual
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('nombre', 50)->nullable();   // "1ra hora", "Recreo"
            $table->boolean('es_recreo')->default(false);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique('numero');
        });
    }

    public function down(): void { Schema::dropIfExists('sch_franjas'); }
};
