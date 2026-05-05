<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();

            $table->enum('estado', ['promovido', 'no_promovido', 'condicionado', 'pendiente'])
                  ->default('pendiente');

            $table->decimal('promedio_final', 5, 2)->nullable();
            $table->decimal('pct_asistencia', 5, 2)->nullable();

            // Detalles de materias reprobadas
            $table->unsignedTinyInteger('materias_reprobadas')->default(0);
            $table->json('materias_reprobadas_detalle')->nullable();

            $table->text('observacion')->nullable();
            $table->foreignId('decidido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_decision')->nullable();

            $table->timestamps();

            $table->unique(['matricula_id', 'school_year_id'], 'promo_unique');
            $table->index(['school_year_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
