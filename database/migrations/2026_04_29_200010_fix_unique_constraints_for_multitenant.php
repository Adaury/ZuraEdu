<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los índices únicos en grados.nivel, secciones.nombre y asignaturas.codigo
     * son globales. En multi-tenant deben ser únicos por (tenant_id, columna).
     */
    public function up(): void
    {
        // grados: nivel único global → único por tenant
        Schema::table('grados', function (Blueprint $table) {
            $table->dropUnique(['nivel']);
            $table->unique(['tenant_id', 'nivel'], 'grados_tenant_nivel_unique');
        });

        // secciones: nombre único global → único por tenant
        Schema::table('secciones', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->unique(['tenant_id', 'nombre'], 'secciones_tenant_nombre_unique');
        });

        // asignaturas: codigo único global → único por tenant
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->unique(['tenant_id', 'codigo'], 'asignaturas_tenant_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('grados', function (Blueprint $table) {
            $table->dropUnique('grados_tenant_nivel_unique');
            $table->unique(['nivel']);
        });

        Schema::table('secciones', function (Blueprint $table) {
            $table->dropUnique('secciones_tenant_nombre_unique');
            $table->unique(['nombre']);
        });

        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropUnique('asignaturas_tenant_codigo_unique');
            $table->unique(['codigo']);
        });
    }
};
