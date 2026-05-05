<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Asignatura;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'nombre' => 'Lengua y Literatura',
                'tipo'   => 'academica',
                'ciclo'  => 'ambos',
                'color'  => '#3b82f6',
            ],
            [
                'nombre' => 'Matemáticas',
                'tipo'   => 'academica',
                'ciclo'  => 'ambos',
                'color'  => '#8b5cf6',
            ],
            [
                'nombre' => 'Ciencias de la Naturaleza',
                'tipo'   => 'academica',
                'ciclo'  => 'ambos',
                'color'  => '#10b981',
            ],
            [
                'nombre' => 'Ciencias Sociales',
                'tipo'   => 'academica',
                'ciclo'  => 'ambos',
                'color'  => '#f59e0b',
            ],
            [
                'nombre' => 'Formación Integral, Humana y Religiosa',
                'tipo'   => 'academica',
                'ciclo'  => 'primer_ciclo',
                'color'  => '#ec4899',
            ],
            [
                'nombre' => 'Educación Física',
                'tipo'   => 'academica',
                'ciclo'  => 'ambos',
                'color'  => '#f97316',
            ],
            [
                'nombre' => 'Lengua Extranjera (Inglés)',
                'tipo'   => 'academica',
                'ciclo'  => 'ambos',
                'color'  => '#06b6d4',
            ],
            [
                'nombre' => 'Arte y Expresión',
                'tipo'   => 'academica',
                'ciclo'  => 'primer_ciclo',
                'color'  => '#d946ef',
            ],
            [
                'nombre' => 'Área Técnico-Profesional',
                'tipo'   => 'tecnica',
                'ciclo'  => 'segundo_ciclo',
                'color'  => '#ef4444',
            ],
            [
                'nombre' => 'Tecnología e Informática',
                'tipo'   => 'tecnica',
                'ciclo'  => 'ambos',
                'color'  => '#64748b',
            ],
        ];

        foreach ($areas as $data) {
            Area::firstOrCreate(['nombre' => $data['nombre']], $data);
        }

        // Vincular asignaturas existentes a sus áreas por nombre parcial
        $this->vincularAsignaturas();

        $this->command->info('AreasSeeder: ' . Area::count() . ' áreas creadas.');
    }

    private function vincularAsignaturas(): void
    {
        $mapa = [
            'Matemáticas'       => 'Matemáticas',
            'Lengua Española'   => 'Lengua y Literatura',
            'Ciencias Naturales'=> 'Ciencias de la Naturaleza',
            'Ciencias Sociales' => 'Ciencias Sociales',
            'Inglés'            => 'Lengua Extranjera (Inglés)',
            'Educación Física'  => 'Educación Física',
            'Informática'       => 'Tecnología e Informática',
        ];

        foreach ($mapa as $nombreAsig => $nombreArea) {
            $area = Area::where('nombre', $nombreArea)->first();
            if (!$area) continue;

            Asignatura::where('nombre', 'like', "%{$nombreAsig}%")
                ->whereNull('area_id')
                ->update(['area_id' => $area->id]);
        }
    }
}
