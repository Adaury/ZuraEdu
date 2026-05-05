<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaturas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique()->notNull();
            $table->string('nombre', 100)->notNull();
            $table->text('descripcion')->nullable();
            $table->string('area', 80)->nullable();
            $table->tinyInteger('horas_semanales')->unsigned()->default(4);
            $table->string('color', 7)->default('#1e3a6e');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaturas');
    }
};
