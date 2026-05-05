<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asignatura;

class AsignaturasSeeder extends Seeder
{
    public function run(): void
    {
        $asignaturas = [
            [
                'codigo'         => 'LEN',
                'nombre'         => 'Lengua Española',
                'area'           => 'Humanidades',
                'horas_semanales'=> 5,
                'color'          => '#dc3545',
                'activo'         => true,
            ],
            [
                'codigo'         => 'MAT',
                'nombre'         => 'Matemáticas',
                'area'           => 'Ciencias Exactas',
                'horas_semanales'=> 5,
                'color'          => '#0d6efd',
                'activo'         => true,
            ],
            [
                'codigo'         => 'CN',
                'nombre'         => 'Ciencias Naturales',
                'area'           => 'Ciencias Naturales',
                'horas_semanales'=> 4,
                'color'          => '#198754',
                'activo'         => true,
            ],
            [
                'codigo'         => 'CS',
                'nombre'         => 'Ciencias Sociales',
                'area'           => 'Ciencias Sociales',
                'horas_semanales'=> 4,
                'color'          => '#fd7e14',
                'activo'         => true,
            ],
            [
                'codigo'         => 'ING',
                'nombre'         => 'Inglés',
                'area'           => 'Idiomas',
                'horas_semanales'=> 3,
                'color'          => '#6f42c1',
                'activo'         => true,
            ],
            [
                'codigo'         => 'EDUF',
                'nombre'         => 'Educación Física',
                'area'           => 'Educación Física',
                'horas_semanales'=> 2,
                'color'          => '#20c997',
                'activo'         => true,
            ],
            [
                'codigo'         => 'ARTE',
                'nombre'         => 'Educación Artística',
                'area'           => 'Artes',
                'horas_semanales'=> 2,
                'color'          => '#e83e8c',
                'activo'         => true,
            ],
            [
                'codigo'         => 'TIC',
                'nombre'         => 'Tecnología e Informática',
                'area'           => 'Tecnología',
                'horas_semanales'=> 2,
                'color'          => '#0dcaf0',
                'activo'         => true,
            ],
            [
                'codigo'         => 'FORM',
                'nombre'         => 'Formación Integral',
                'area'           => 'Formación Humana',
                'horas_semanales'=> 2,
                'color'          => '#6610f2',
                'activo'         => true,
            ],
            [
                'codigo'         => 'HIST',
                'nombre'         => 'Historia y Geografía',
                'area'           => 'Humanidades',
                'horas_semanales'=> 3,
                'color'          => '#795548',
                'activo'         => true,
            ],
        ];

        foreach ($asignaturas as $data) {
            Asignatura::firstOrCreate(
                ['codigo' => $data['codigo']],
                array_diff_key($data, ['codigo' => ''])
            );
        }
    }
}
