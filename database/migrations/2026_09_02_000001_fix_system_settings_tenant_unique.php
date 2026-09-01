<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `system_settings.key` tenía un UNIQUE global (no compuesto con tenant_id),
 * heredado de cuando la tabla era de un solo tenant. tenant_id se agregó
 * después "para consistencia" (migración 2026_04_29_200001) pero nunca se
 * usó en las queries ni en la unicidad — dos tenants con la misma clave de
 * branding (system_name, system_logo, login_color_bg1...) literalmente no
 * podían coexistir sin pisarse. Ver auditoría del proyecto — hallazgo
 * crítico de aislamiento de datos entre tenants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function ($table) {
            $table->dropUnique('system_settings_key_unique');
            $table->unique(['tenant_id', 'key'], 'system_settings_tenant_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function ($table) {
            $table->dropUnique('system_settings_tenant_key_unique');
            $table->unique('key');
        });
    }
};
