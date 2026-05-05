<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sch_disponibilidad_profesor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesor_id')->constrained('sch_profesores')->cascadeOnDelete();
            $table->foreignId('franja_id')  ->constrained('sch_franjas')   ->cascadeOnDelete();
            $table->enum('dia', ['lunes','martes','miercoles','jueves','viernes']);
            $table->boolean('disponible')->default(true);
            $table->timestamps();

            $table->unique(['profesor_id', 'franja_id', 'dia']);
        });
    }

    public function down(): void { Schema::dropIfExists('sch_disponibilidad_profesor'); }
};
