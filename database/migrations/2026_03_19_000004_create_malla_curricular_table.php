<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('malla_curricular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->enum('area', ['academica', 'tecnica'])->default('academica');
            $table->foreignId('especialidad_id')->nullable()->constrained('especialidades_tecnicas')->nullOnDelete();
            $table->unsignedTinyInteger('horas_semanales')->default(0);
            $table->unsignedSmallInteger('horas_anuales')->nullable();
            $table->boolean('es_obligatoria')->default(true);
            $table->tinyInteger('orden_display')->default(0);
            $table->text('notas_curriculo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['grado_id', 'asignatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('malla_curricular');
    }
};
