<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // articulos_inventario — tenant_id + estado reparacion
        if (! Schema::hasColumn('articulos_inventario', 'tenant_id')) {
            Schema::table('articulos_inventario', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
        DB::statement("ALTER TABLE articulos_inventario
            MODIFY COLUMN estado ENUM('bueno','regular','malo','reparacion') NOT NULL DEFAULT 'bueno'");

        // movimientos_inventario — tenant_id
        if (! Schema::hasColumn('movimientos_inventario', 'tenant_id')) {
            Schema::table('movimientos_inventario', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        // equipos — tenant_id
        if (! Schema::hasColumn('equipos', 'tenant_id')) {
            Schema::table('equipos', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        // prestamos_equipo — tenant_id
        if (! Schema::hasColumn('prestamos_equipo', 'tenant_id')) {
            Schema::table('prestamos_equipo', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['articulos_inventario', 'movimientos_inventario', 'equipos', 'prestamos_equipo'] as $t) {
            if (Schema::hasColumn($t, 'tenant_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }

        DB::statement("ALTER TABLE articulos_inventario
            MODIFY COLUMN estado ENUM('bueno','regular','malo') NOT NULL DEFAULT 'bueno'");
    }
};
