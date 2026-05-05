<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faltas_disciplinarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->enum('tipo', ['tardanza', 'falta_leve', 'falta_grave', 'suspension']);
            $table->text('descripcion');
            $table->date('fecha');
            $table->boolean('resuelto')->default(false);
            $table->text('notas_resolucion')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'fecha']);
            $table->index(['tipo', 'resuelto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faltas_disciplinarias');
    }
};
