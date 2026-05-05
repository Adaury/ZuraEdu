<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grado;

class GradosSeeder extends Seeder
{
    public function run(): void
    {
        // Solo Primer Ciclo (la institución opera únicamente 1ro–3ro de Secundaria)
        $grados = [
            ['nivel' => 1, 'nombre' => '1ro de Secundaria', 'orden' => 1, 'ciclo' => 'primer_ciclo'],
            ['nivel' => 2, 'nombre' => '2do de Secundaria', 'orden' => 2, 'ciclo' => 'primer_ciclo'],
            ['nivel' => 3, 'nombre' => '3ro de Secundaria', 'orden' => 3, 'ciclo' => 'primer_ciclo'],
        ];

        foreach ($grados as $grado) {
            Grado::firstOrCreate(
                ['nivel' => $grado['nivel']],
                ['nombre' => $grado['nombre'], 'orden' => $grado['orden']]
            );
        }
    }
}
