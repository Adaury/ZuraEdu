<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // fichas_salud — tenant_id
        if (! Schema::hasColumn('fichas_salud', 'tenant_id')) {
            Schema::table('fichas_salud', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        // incidentes_medicos — tenant_id + hora + notificado_representante
        Schema::table('incidentes_medicos', function (Blueprint $table) {
            if (! Schema::hasColumn('incidentes_medicos', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
            if (! Schema::hasColumn('incidentes_medicos', 'hora')) {
                $table->time('hora')->nullable()->after('fecha');
            }
            if (! Schema::hasColumn('incidentes_medicos', 'notificado_representante')) {
                $table->boolean('notificado_representante')->default(false)->after('remitido_a');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('fichas_salud', 'tenant_id')) {
            Schema::table('fichas_salud', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        Schema::table('incidentes_medicos', function (Blueprint $table) {
            if (Schema::hasColumn('incidentes_medicos', 'tenant_id')) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
            if (Schema::hasColumn('incidentes_medicos', 'hora')) {
                $table->dropColumn('hora');
            }
            if (Schema::hasColumn('incidentes_medicos', 'notificado_representante')) {
                $table->dropColumn('notificado_representante');
            }
        });
    }
};
