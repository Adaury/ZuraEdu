<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_evaluacion_periodos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->unsignedTinyInteger('tareas')->default(0);
            $table->unsignedTinyInteger('practicas')->default(0);
            $table->unsignedTinyInteger('participacion')->default(0);
            $table->unsignedTinyInteger('proyecto')->default(0);
            $table->unsignedTinyInteger('examen')->default(0);
            $table->text('observaciones')->nullable();
            $table->boolean('publicado')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'asignacion_id', 'periodo_id'], 'pep_tenant_asig_per_unique');
        });

        Schema::table('instrumentos_evaluacion', function (Blueprint $table) {
            $table->foreignId('periodo_id')->nullable()->after('school_year_id')
                  ->constrained('periodos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instrumentos_evaluacion', function (Blueprint $table) {
            $table->dropForeign(['periodo_id']);
            $table->dropColumn('periodo_id');
        });
        Schema::dropIfExists('plan_evaluacion_periodos');
    }
};
