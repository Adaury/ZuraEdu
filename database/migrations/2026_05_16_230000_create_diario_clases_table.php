<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diario_clases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tema', 300);
            $table->text('actividades')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('incidencias')->nullable();
            $table->unsignedTinyInteger('asistentes')->nullable();
            $table->timestamps();

            $table->unique(['asignacion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diario_clases');
    }
};
