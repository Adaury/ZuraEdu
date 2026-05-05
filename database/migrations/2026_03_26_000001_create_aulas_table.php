<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // "Aula 101", "Lab. Informática"
            $table->string('codigo')->nullable();
            $table->integer('capacidad')->default(30);
            $table->enum('tipo', ['aula', 'laboratorio', 'taller', 'gimnasio', 'biblioteca'])->default('aula');
            $table->boolean('disponible')->default(true);
            $table->string('piso')->nullable();
            $table->json('equipamiento')->nullable(); // ["proyector","pizarra","computadoras"]
            $table->unsignedBigInteger('centro_id')->nullable()->index(); // multi-tenant
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
