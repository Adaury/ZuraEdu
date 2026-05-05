<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recursos_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['enlace', 'video', 'documento', 'imagen', 'otro'])->default('enlace');
            $table->string('url')->nullable();           // para enlaces / YouTube / drives
            $table->string('archivo_path')->nullable();  // para archivos subidos
            $table->string('archivo_nombre')->nullable();// nombre original del archivo
            $table->boolean('publicado')->default(true);
            $table->integer('orden')->default(0);

            $table->timestamps();

            $table->index(['asignacion_id', 'publicado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos_materia');
    }
};
