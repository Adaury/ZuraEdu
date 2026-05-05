<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $claves = [
            'duracion_bloque'       => ['valor' => '45',  'tipo' => 'integer', 'grupo' => 'horarios',  'descripcion' => 'Duración de cada bloque horario en minutos'],
            'max_misma_materia_dia' => ['valor' => '1',   'tipo' => 'integer', 'grupo' => 'horarios',  'descripcion' => 'Máximo de veces que la misma materia puede aparecer por día por grupo'],
            'modulo_pagos_activo'   => ['valor' => '0',   'tipo' => 'boolean', 'grupo' => 'pagos',     'descripcion' => 'Activar módulo de pagos (solo centros privados)'],
        ];

        foreach ($claves as $clave => $datos) {
            DB::table('config_institucional')->updateOrInsert(
                ['clave' => $clave],
                array_merge($datos, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('config_institucional')
            ->whereIn('clave', ['duracion_bloque', 'max_misma_materia_dia'])
            ->delete();
    }
};
