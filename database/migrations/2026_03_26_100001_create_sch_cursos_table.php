<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);          // "1ro A", "2do B"
            $table->string('grado', 40);            // "Primer Año"
            $table->string('seccion', 10)->nullable();
            $table->unsignedSmallInteger('capacidad')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sch_cursos'); }
};
