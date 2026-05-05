<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->enum('componente', ['tareas', 'practicas', 'participacion', 'proyecto', 'examen'])->notNull();
            $table->decimal('peso', 5, 2)->notNull();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['school_year_id', 'componente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_calificaciones');
    }
};
