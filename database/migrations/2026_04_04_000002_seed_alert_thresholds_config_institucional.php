<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $thresholds = [
            [
                'clave'       => 'alerta_nota_minima',
                'valor'       => '60',
                'tipo'        => 'integer',
                'grupo'       => 'alertas',
                'descripcion' => 'Nota mínima para generar alerta de riesgo académico (0–100)',
            ],
            [
                'clave'       => 'alerta_asistencia_minima',
                'valor'       => '75',
                'tipo'        => 'integer',
                'grupo'       => 'alertas',
                'descripcion' => 'Porcentaje mínimo de asistencia antes de generar alerta (0–100)',
            ],
        ];

        foreach ($thresholds as $item) {
            DB::table('config_institucional')->updateOrInsert(
                ['clave' => $item['clave']],
                array_merge($item, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('config_institucional')
            ->whereIn('clave', ['alerta_nota_minima', 'alerta_asistencia_minima'])
            ->delete();
    }
};
