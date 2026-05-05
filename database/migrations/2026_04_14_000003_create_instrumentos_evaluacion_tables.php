<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrumentos_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->nullable()->constrained('asignaciones')->nullOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->string('titulo', 200);
            $table->enum('tipo', ['lista_cotejo', 'rubrica', 'escala_estimacion'])->default('lista_cotejo');
            $table->string('competencia', 200)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('indicadores_logro')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('valoracion_global')->nullable();        // excelente/bueno/regular/en_proceso
            $table->json('niveles_desempeno')->nullable();          // definición de niveles por tipo rúbrica
            $table->boolean('publicado')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_year_id', 'tipo']);
            $table->index(['docente_id', 'school_year_id']);
        });

        Schema::create('instrumento_criterios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos_evaluacion')->cascadeOnDelete();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->decimal('peso_max', 5, 2)->default(1.00);
            $table->timestamps();
        });

        Schema::create('instrumento_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos_evaluacion')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->json('puntajes')->nullable();                   // {criterio_id: valor, ...}
            $table->decimal('ponderacion', 5, 2)->nullable();
            $table->string('nivel_desempeno', 50)->nullable();      // excelente/bueno/regular/en_proceso
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['instrumento_id', 'matricula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrumento_evaluaciones');
        Schema::dropIfExists('instrumento_criterios');
        Schema::dropIfExists('instrumentos_evaluacion');
    }
};
