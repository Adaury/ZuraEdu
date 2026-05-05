<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->enum('area',            ['academica','tecnica'])->default('academica')->after('activo');
            $table->enum('tipo_evaluacion', ['componentes','ra'])->default('componentes')->after('area');
        });

        Schema::table('asignaturas', function (Blueprint $table) {
            $table->unsignedTinyInteger('num_ra')->default(0)->after('nombre')
                  ->comment('0 = componentes tradicionales, >0 = cantidad de RA para área técnica');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropColumn(['area','tipo_evaluacion']);
        });
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropColumn('num_ra');
        });
    }
};
