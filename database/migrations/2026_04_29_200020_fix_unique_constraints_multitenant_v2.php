<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas con unique constraints globales que deben ser únicos por (tenant_id, columna).
     * Se aplica la misma corrección del archivo anterior pero para tablas faltantes.
     */
    public function up(): void
    {
        // school_years: nombre único global → único por tenant
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unique(['tenant_id', 'nombre'], 'sy_tenant_nombre_unique');
        });

        // estudiantes: numero_matricula y cedula únicos global → por tenant
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropUnique(['numero_matricula']);
            $table->unique(['tenant_id', 'numero_matricula'], 'est_tenant_matricula_unique');

            // cedula puede ser null, el unique incluye NULLs distintos en MySQL
            $table->dropUnique(['cedula']);
            $table->unique(['tenant_id', 'cedula'], 'est_tenant_cedula_unique');
        });

        // docentes: cedula única global → por tenant
        Schema::table('docentes', function (Blueprint $table) {
            $table->dropUnique(['cedula']);
            $table->unique(['tenant_id', 'cedula'], 'doc_tenant_cedula_unique');
        });
    }

    public function down(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropUnique('sy_tenant_nombre_unique');
            $table->unique(['nombre']);
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropUnique('est_tenant_matricula_unique');
            $table->unique(['numero_matricula']);
            $table->dropUnique('est_tenant_cedula_unique');
            $table->unique(['cedula']);
        });

        Schema::table('docentes', function (Blueprint $table) {
            $table->dropUnique('doc_tenant_cedula_unique');
            $table->unique(['cedula']);
        });
    }
};
