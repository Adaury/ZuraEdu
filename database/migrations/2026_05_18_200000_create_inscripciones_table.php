<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'asignada', 'cancelada'])->default('pendiente');
            $table->enum('origen', ['continuidad', 'nueva', 'traslado'])->default('continuidad');
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->date('fecha_inscripcion');
            $table->text('observaciones')->nullable();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'school_year_id', 'estudiante_id'], 'inscripciones_unica');
            $table->index(['tenant_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
