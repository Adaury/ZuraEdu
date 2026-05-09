<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Si system_name sigue siendo el valor PSAC sembrado en la migración original,
        // lo reemplazamos por ZuraEdu (branding de la plataforma).
        // Cuando el centro cree su institución podrá cambiarlo desde Configuración → Sistema.
        DB::table('system_settings')
            ->where('key', 'system_name')
            ->where('value', 'Politécnico Salesiano Arquides Calderón (PSAC)')
            ->update(['value' => 'ZuraEdu']);

        DB::table('system_settings')
            ->where('key', 'system_abbr')
            ->where('value', 'PSAC')
            ->update(['value' => 'ZE']);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'system_name')
            ->where('value', 'ZuraEdu')
            ->update(['value' => 'Politécnico Salesiano Arquides Calderón (PSAC)']);

        DB::table('system_settings')
            ->where('key', 'system_abbr')
            ->where('value', 'ZE')
            ->update(['value' => 'PSAC']);
    }
};
