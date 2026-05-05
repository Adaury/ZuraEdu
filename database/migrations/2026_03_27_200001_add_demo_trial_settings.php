<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // Demo mode
            ['key' => 'demo_activo',          'value' => '1'],
            ['key' => 'demo_roles',            'value' => 'docente,estudiante,padre'],
            // Trial period
            ['key' => 'trial_activo',          'value' => '0'],
            ['key' => 'trial_inicio',          'value' => ''],
            ['key' => 'trial_dias',            'value' => '30'],
            ['key' => 'trial_mensaje',         'value' => 'Estás usando una versión de prueba del sistema.'],
        ];

        foreach ($settings as $s) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $s['key']],
                ['value' => $s['value'], 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'demo_activo','demo_roles','trial_activo','trial_inicio','trial_dias','trial_mensaje',
        ])->delete();
    }
};
