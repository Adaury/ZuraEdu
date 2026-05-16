<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planif_anuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('planif_unidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('planif_anual_id')->constrained('planif_anuales')->cascadeOnDelete();
            $table->tinyInteger('numero')->default(1);
            $table->string('titulo', 200);
            $table->string('periodo', 10)->nullable();   // P1 / P2 / P3 / P4
            $table->tinyInteger('semanas')->nullable();
            $table->text('objetivos')->nullable();
            $table->json('competencias')->nullable();    // array de nombres
            $table->text('indicadores')->nullable();
            $table->text('contenidos')->nullable();
            $table->text('estrategias')->nullable();
            $table->text('recursos')->nullable();
            $table->text('evaluacion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planif_unidades');
        Schema::dropIfExists('planif_anuales');
    }
};
