<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->default('Horario Principal');
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador');
            $table->unsignedTinyInteger('score')->default(0);      // 0–100%
            $table->unsignedInteger('iteraciones')->default(0);
            $table->json('conflictos')->nullable();                  // clases sin asignar
            $table->timestamp('generado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sch_horarios'); }
};
