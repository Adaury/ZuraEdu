<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->nullable()->constrained('asignaciones')->nullOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->string('titulo', 200);
            $table->enum('area', ['academica', 'tecnica'])->default('academica');
            $table->enum('tipo_plan', ['diaria', 'semanal', 'quincenal', 'mensual'])->default('semanal');
            $table->string('semana', 100)->nullable();            // "Del 6 al 10 de enero"
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('grado_seccion', 100)->nullable();     // "4to A-B-C"
            $table->text('intencion_pedagogica')->nullable();
            $table->json('estrategias')->nullable();               // array de claves de estrategias
            $table->text('observacion')->nullable();
            $table->string('archivo_path')->nullable();            // ruta del archivo subido
            $table->string('archivo_nombre')->nullable();          // nombre original
            $table->string('archivo_tipo')->nullable();            // mime type
            $table->boolean('publicado')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_year_id', 'area']);
            $table->index(['docente_id', 'school_year_id']);
        });

        Schema::create('plan_clase_momentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_clase_id')->constrained('planes_clase')->cascadeOnDelete();
            $table->enum('tipo', ['inicio', 'desarrollo', 'cierre']);
            $table->integer('orden')->default(0);
            $table->integer('duracion_minutos')->nullable();
            $table->text('area_curricular')->nullable();           // competencia/área
            $table->text('competencias_especificas')->nullable();
            $table->text('contenidos')->nullable();
            $table->text('actividades')->nullable();
            $table->text('indicador_logro')->nullable();
            $table->text('recursos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_clase_momentos');
        Schema::dropIfExists('planes_clase');
    }
};
