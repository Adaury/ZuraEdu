<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificacion_audits', function (Blueprint $table) {
            $table->id();
            $table->string('modelo', 30);           // 'Calificacion' | 'CalificacionAcademica'
            $table->unsignedBigInteger('registro_id');
            $table->unsignedBigInteger('matricula_id')->index();
            $table->unsignedBigInteger('asignacion_id')->index();
            $table->string('campo', 60);            // 'nota_final', 'comp1_p1', etc.
            $table->decimal('valor_anterior', 6, 2)->nullable();
            $table->decimal('valor_nuevo',    6, 2)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['modelo', 'registro_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion_audits');
    }
};
