<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // config_institucional: unique global en 'clave' bloquea segundo tenant
        if (Schema::hasTable('config_institucional')) {
            $this->dropUniqueIfExists('config_institucional', 'config_institucional_clave_unique');
            Schema::table('config_institucional', function (Blueprint $table) {
                $existing = collect(DB::select("SHOW INDEX FROM config_institucional WHERE Key_name = 'config_tenant_clave_unique'"));
                if ($existing->isEmpty()) {
                    $table->unique(['tenant_id', 'clave'], 'config_tenant_clave_unique');
                }
            });
        }

        // grupos: unique sin tenant_id
        if (Schema::hasTable('grupos')) {
            $this->dropUniqueIfExists('grupos', 'grupos_school_year_id_grado_id_seccion_id_unique');
            Schema::table('grupos', function (Blueprint $table) {
                $existing = collect(DB::select("SHOW INDEX FROM grupos WHERE Key_name = 'grupos_tenant_unique'"));
                if ($existing->isEmpty()) {
                    $table->unique(['tenant_id', 'school_year_id', 'grado_id', 'seccion_id'], 'grupos_tenant_unique');
                }
            });
        }

        // matriculas: unique sin tenant_id
        if (Schema::hasTable('matriculas')) {
            $this->dropUniqueIfExists('matriculas', 'matriculas_school_year_id_estudiante_id_unique');
            Schema::table('matriculas', function (Blueprint $table) {
                $existing = collect(DB::select("SHOW INDEX FROM matriculas WHERE Key_name = 'matriculas_tenant_unique'"));
                if ($existing->isEmpty()) {
                    $table->unique(['tenant_id', 'school_year_id', 'estudiante_id'], 'matriculas_tenant_unique');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('config_institucional')) {
            $this->dropUniqueIfExists('config_institucional', 'config_tenant_clave_unique');
            Schema::table('config_institucional', fn (Blueprint $t) => $t->unique('clave'));
        }

        if (Schema::hasTable('grupos')) {
            $this->dropUniqueIfExists('grupos', 'grupos_tenant_unique');
            Schema::table('grupos', fn (Blueprint $t) =>
                $t->unique(['school_year_id', 'grado_id', 'seccion_id']));
        }

        if (Schema::hasTable('matriculas')) {
            $this->dropUniqueIfExists('matriculas', 'matriculas_tenant_unique');
            Schema::table('matriculas', fn (Blueprint $t) =>
                $t->unique(['school_year_id', 'estudiante_id']));
        }
    }

    private function dropUniqueIfExists(string $table, string $index): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
        if ($exists) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique($index));
        }
    }
};
