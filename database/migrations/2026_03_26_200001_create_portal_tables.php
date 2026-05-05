<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal Multi-Rol: representantes, pivot, notificaciones, observaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Representantes (padres/tutores con login propio) ──────────────
        Schema::create('representantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('cedula', 20)->nullable();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_trabajo', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('ocupacion', 100)->nullable();
            $table->string('direccion')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // ── 2. Pivot estudiante ↔ representante ─────────────────────────────
        Schema::create('estudiante_representante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('representante_id')->constrained('representantes')->cascadeOnDelete();
            $table->string('parentesco', 50)->default('padre/madre'); // padre, madre, tutor, abuelo, etc.
            $table->boolean('es_principal')->default(false);          // representante principal
            $table->timestamps();

            $table->unique(['estudiante_id', 'representante_id']);
            $table->index('representante_id');
        });

        // ── 3. Notificaciones in-app ─────────────────────────────────────────
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 50);   // nueva_nota, ausencia, comunicado, observacion, alerta
            $table->string('titulo', 200);
            $table->text('mensaje');
            $table->json('datos')->nullable();   // datos extra: estudiante_id, materia, etc.
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'leida']);
            $table->index('created_at');
        });

        // ── 4. Observaciones del docente ────────────────────────────────────
        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('asignacion_id')->nullable()->constrained('asignaciones')->nullOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->enum('tipo', ['academica', 'conductual', 'positiva', 'general'])->default('general');
            $table->text('texto');
            $table->boolean('privada')->default(false); // si true: solo admin y docente la ven
            $table->timestamps();

            $table->index(['estudiante_id', 'periodo_id']);
            $table->index('docente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('estudiante_representante');
        Schema::dropIfExists('representantes');
    }
};
