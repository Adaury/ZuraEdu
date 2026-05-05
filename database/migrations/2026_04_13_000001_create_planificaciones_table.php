<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Planificación principal (cabecera) ────────────────────────────
        Schema::create('planificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->enum('tipo', ['ra', 'actividad'])->default('ra');
            $table->string('familia_profesional')->nullable();
            $table->string('denominacion')->nullable();
            $table->string('modulo_nombre')->nullable();
            $table->string('mf_codigo')->nullable();      // MF_060_3
            $table->text('uc_codigo')->nullable();         // UC_060_... (puede ser largo)
            $table->string('sesion')->nullable();           // "6to A"
            $table->string('nivel')->nullable();            // "3"
            $table->decimal('horas', 5, 1)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('publicado')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Items por RA (para tipo = 'ra') ───────────────────────────────
        Schema::create('planificacion_ra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planificacion_id')->constrained('planificaciones')->cascadeOnDelete();
            $table->integer('orden')->default(1);
            $table->string('ra_codigo')->nullable();        // "RA8.1"
            $table->text('ra_descripcion')->nullable();
            $table->string('nivel_taxonomico')->nullable(); // "Aplicación 3"
            $table->json('elementos_capacidad')->nullable(); // [{descripcion, nivel}]
            $table->json('fechas')->nullable();              // [{desde, hasta}]
            $table->text('actividades')->nullable();
            $table->text('instrumentos_evaluacion')->nullable();
            $table->text('contenidos')->nullable();
            $table->timestamps();
        });

        // ── Actividades de aprendizaje (para tipo = 'actividad') ──────────
        Schema::create('planificacion_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planificacion_id')->constrained('planificaciones')->cascadeOnDelete();
            $table->string('ra_codigo')->nullable();
            $table->text('ra_descripcion')->nullable();
            $table->integer('actividad_numero')->nullable();
            $table->text('objetivo')->nullable();
            $table->text('act_inicio')->nullable();
            $table->text('act_desarrollo')->nullable();
            $table->text('act_cierre')->nullable();
            $table->text('estrategias')->nullable();
            $table->text('recursos')->nullable();
            $table->text('instrumentos_evaluacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planificacion_actividades');
        Schema::dropIfExists('planificacion_ra_items');
        Schema::dropIfExists('planificaciones');
    }
};
