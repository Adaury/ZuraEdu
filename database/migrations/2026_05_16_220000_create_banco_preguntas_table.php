<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banco_preguntas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas')->nullOnDelete();
            $table->text('enunciado');
            $table->enum('tipo', ['multiple', 'verdadero_falso', 'abierta'])->default('multiple');
            $table->json('opciones')->nullable();
            $table->decimal('puntos_default', 5, 2)->default(1);
            $table->text('explicacion')->nullable();
            $table->string('categoria', 100)->nullable();
            $table->unsignedInteger('usos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banco_preguntas');
    }
};
