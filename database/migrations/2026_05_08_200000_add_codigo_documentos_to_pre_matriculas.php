<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_matriculas', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->unique()->after('id');
            $table->string('genero', 10)->nullable()->after('fecha_nacimiento');
            $table->string('lugar_nacimiento', 150)->nullable()->after('genero');
            $table->string('cedula_estudiante', 20)->nullable()->after('lugar_nacimiento');
            $table->string('relacion_representante', 50)->nullable()->after('telefono');
            $table->json('documentos')->nullable()->after('notas_admin');
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pre_matriculas', function (Blueprint $table) {
            $table->dropIndex(['codigo']);
            $table->dropColumn(['codigo', 'genero', 'lugar_nacimiento', 'cedula_estudiante', 'relacion_representante', 'documentos']);
        });
    }
};
