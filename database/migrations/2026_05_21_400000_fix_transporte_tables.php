<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // rutas_transporte — tenant_id + campos operativos
        Schema::table('rutas_transporte', function (Blueprint $table) {
            if (! Schema::hasColumn('rutas_transporte', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
            if (! Schema::hasColumn('rutas_transporte', 'horario_salida')) {
                $table->time('horario_salida')->nullable()->after('activo');
            }
            if (! Schema::hasColumn('rutas_transporte', 'horario_regreso')) {
                $table->time('horario_regreso')->nullable()->after('horario_salida');
            }
            if (! Schema::hasColumn('rutas_transporte', 'telefono_conductor')) {
                $table->string('telefono_conductor', 30)->nullable()->after('conductor');
            }
        });

        // paradas_ruta — tenant_id
        if (! Schema::hasColumn('paradas_ruta', 'tenant_id')) {
            Schema::table('paradas_ruta', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        // estudiantes_ruta — tenant_id
        if (! Schema::hasColumn('estudiantes_ruta', 'tenant_id')) {
            Schema::table('estudiantes_ruta', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('rutas_transporte', function (Blueprint $table) {
            if (Schema::hasColumn('rutas_transporte', 'tenant_id')) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
            if (Schema::hasColumn('rutas_transporte', 'horario_salida'))   $table->dropColumn('horario_salida');
            if (Schema::hasColumn('rutas_transporte', 'horario_regreso'))  $table->dropColumn('horario_regreso');
            if (Schema::hasColumn('rutas_transporte', 'telefono_conductor')) $table->dropColumn('telefono_conductor');
        });

        if (Schema::hasColumn('paradas_ruta', 'tenant_id')) {
            Schema::table('paradas_ruta', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']); $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('estudiantes_ruta', 'tenant_id')) {
            Schema::table('estudiantes_ruta', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']); $table->dropColumn('tenant_id');
            });
        }
    }
};
