<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_materias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('horas_semana')->default(4);
            $table->string('color', 7)->default('#3b82f6'); // hex color para la vista
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sch_materias'); }
};
