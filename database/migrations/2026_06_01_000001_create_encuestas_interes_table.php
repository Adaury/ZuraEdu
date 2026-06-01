<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas_interes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['docente', 'administrativo']);
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono', 30);
            $table->tinyInteger('nivel_interes')->unsigned()->default(0); // 1-5
            $table->json('respuestas');          // todas las respuestas del formulario
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas_interes');
    }
};
