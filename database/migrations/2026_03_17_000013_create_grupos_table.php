<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aula', 20)->nullable();
            $table->tinyInteger('capacidad')->unsigned()->default(35);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['school_year_id', 'grado_id', 'seccion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
