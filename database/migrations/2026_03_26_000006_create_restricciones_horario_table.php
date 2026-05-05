<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restricciones_horario', function (Blueprint $table) {
            $table->id();
            $table->string('clave'); // 'max_clases_dia_docente', 'max_clases_dia_grupo', 'horas_libres_min'
            $table->string('valor');
            $table->string('descripcion')->nullable();
            $table->unsignedBigInteger('school_year_id')->nullable();
            $table->unsignedBigInteger('centro_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['clave', 'school_year_id', 'centro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restricciones_horario');
    }
};
