<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('onboarding_completado')->default(false)->after('metadatos');
            $table->unsignedTinyInteger('onboarding_paso')->default(0)->after('onboarding_completado');
        });

        // Los tenants ya existentes no necesitan el wizard
        DB::table('tenants')->update(['onboarding_completado' => true, 'onboarding_paso' => 4]);

        if (! Schema::hasColumn('grados', 'activo')) {
            Schema::table('grados', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('orden');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['onboarding_completado', 'onboarding_paso']);
        });

        if (Schema::hasColumn('grados', 'activo')) {
            Schema::table('grados', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }
    }
};
