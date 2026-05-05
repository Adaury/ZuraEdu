<?php

namespace Database\Seeders;

use App\Models\FamiliaProfesional;
use Illuminate\Database\Seeder;

class FamiliaProfesionalSeeder extends Seeder
{
    public function run(): void
    {
        $familias = [
            ['nombre' => 'Turismo',                    'color' => '#e67e22', 'icono' => 'bi-airplane',      'descripcion' => 'Hotelería, gastronomía, guías turísticas y servicios de viaje.'],
            ['nombre' => 'Comercio y Mercado',         'color' => '#27ae60', 'icono' => 'bi-shop',           'descripcion' => 'Gestión comercial, ventas, marketing y administración de negocios.'],
            ['nombre' => 'Logística y Transporte',     'color' => '#c0392b', 'icono' => 'bi-truck',          'descripcion' => 'Cadena de suministro, distribución, almacenamiento y transporte.'],
            ['nombre' => 'Acondicionamiento Físico',   'color' => '#8e44ad', 'icono' => 'bi-heart-pulse',    'descripcion' => 'Educación física, entrenamiento deportivo y salud corporal.'],
            ['nombre' => 'Desarrollo de Aplicaciones', 'color' => '#2980b9', 'icono' => 'bi-code-slash',     'descripcion' => 'Programación, diseño web, bases de datos y tecnología de la información.'],
        ];

        foreach ($familias as $data) {
            FamiliaProfesional::firstOrCreate(
                ['nombre' => $data['nombre']],
                $data + ['activo' => true]
            );
        }
    }
}
