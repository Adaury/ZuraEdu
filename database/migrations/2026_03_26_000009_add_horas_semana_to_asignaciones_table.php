<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('asignaciones', 'horas_semana')) {
                $table->integer('horas_semana')->default(4)->after('tipo_evaluacion');
            }
        });
    }
    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropColumn('horas_semana');
        });
    }
};
