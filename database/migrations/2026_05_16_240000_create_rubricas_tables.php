<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rúbrica (definición)
        Schema::create('rubricas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            // JSON: [{nombre, pct, color}] — niveles de logro de peor a mejor
            $table->json('niveles');
            // JSON: [{nombre, puntos, descriptores: [texto por nivel]}]
            $table->json('criterios');
            $table->timestamps();
        });

        // Aplicación de rúbrica a un estudiante
        Schema::create('rubrica_aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('rubrica_id')->constrained('rubricas')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            // JSON: {criterio_idx: nivel_idx}
            $table->json('resultados')->nullable();
            $table->decimal('puntaje', 6, 2)->default(0);
            $table->decimal('puntaje_max', 6, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('aplicado_en')->nullable();
            $table->timestamps();

            $table->unique(['rubrica_id', 'asignacion_id', 'matricula_id'], 'rubrica_aplic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrica_aplicaciones');
        Schema::dropIfExists('rubricas');
    }
};
