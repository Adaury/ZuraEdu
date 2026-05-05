<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiales_clase', function (Blueprint $table) {
            // Vincular con período evaluativo y competencia
            $table->foreignId('periodo_id')->nullable()->after('clase_virtual_id')
                  ->constrained('periodos')->nullOnDelete();
            $table->foreignId('competencia_id')->nullable()->after('periodo_id')
                  ->constrained('competencias_especificas')->nullOnDelete();

            // Control de entregas
            $table->boolean('permite_reentrega')->default(false)->after('publicado');
            $table->integer('limite_tiempo')->nullable()->after('permite_reentrega');   // minutos
            $table->dateTime('publicar_en')->nullable()->after('limite_tiempo');         // publicación programada

            // Subtipo para tareas/evaluaciones
            $table->string('subtipo')->nullable()->after('tipo'); // proyecto|foro|cuestionario|examen|practica
        });
    }

    public function down(): void
    {
        Schema::table('materiales_clase', function (Blueprint $table) {
            $table->dropForeign(['periodo_id']);
            $table->dropForeign(['competencia_id']);
            $table->dropColumn(['periodo_id', 'competencia_id', 'permite_reentrega',
                                'limite_tiempo', 'publicar_en', 'subtipo']);
        });
    }
};
