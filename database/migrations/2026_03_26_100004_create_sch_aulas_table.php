<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_aulas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->unsignedSmallInteger('capacidad')->default(30);
            $table->enum('tipo', ['aula', 'laboratorio', 'taller', 'gimnasio'])->default('aula');
            $table->boolean('disponible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sch_aulas'); }
};
