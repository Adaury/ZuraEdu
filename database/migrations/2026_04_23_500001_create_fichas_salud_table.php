<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas_salud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->unique()->constrained('estudiantes')->cascadeOnDelete();
            $table->string('tipo_sangre', 5)->nullable();
            $table->text('alergias')->nullable();
            $table->text('condiciones_medicas')->nullable();
            $table->text('medicamentos')->nullable();
            $table->string('contacto_emergencia')->nullable();
            $table->string('telefono_emergencia')->nullable();
            $table->string('seguro_medico')->nullable();
            $table->string('num_seguro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas_salud');
    }
};
