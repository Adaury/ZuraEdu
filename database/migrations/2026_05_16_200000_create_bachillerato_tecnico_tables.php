<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Áreas Técnicas (ej: Informática, Electricidad)
        Schema::create('areas_tecnicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#1e3a6e');
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        // Cursos Técnicos (ej: Técnico en Redes, Técnico en Programación)
        Schema::create('cursos_tecnicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('area_tecnica_id')->constrained('areas_tecnicas')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->string('codigo', 30)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('duracion_horas')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        // Módulos Formativos (ej: Módulo 1: Fundamentos de redes)
        Schema::create('modulos_formativos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('curso_tecnico_id')->constrained('cursos_tecnicos')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->string('codigo', 30)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('duracion_horas')->nullable();
            $table->decimal('creditos', 4, 1)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos_formativos');
        Schema::dropIfExists('cursos_tecnicos');
        Schema::dropIfExists('areas_tecnicas');
    }
};
