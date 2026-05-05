<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zc_rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales_clase')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('zc_rubric_criterios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('zc_rubrics')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('puntaje_max', 5, 2)->default(10);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('zc_rubric_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas_classroom')->cascadeOnDelete();
            $table->foreignId('criterio_id')->constrained('zc_rubric_criterios')->cascadeOnDelete();
            $table->decimal('puntaje', 5, 2)->default(0);
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->unique(['entrega_id', 'criterio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zc_rubric_calificaciones');
        Schema::dropIfExists('zc_rubric_criterios');
        Schema::dropIfExists('zc_rubrics');
    }
};
