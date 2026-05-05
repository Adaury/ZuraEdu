<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EspecialidadTecnica;

class EspecialidadTecnicaSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            [
                'nombre'      => 'Turismo y Hotelería',
                'codigo'      => 'TUR',
                'descripcion' => 'Formación en alojamiento, alimentos y bebidas, agencias de viaje y gestión hotelera.',
                'color'       => '#e67e22',
                'icono'       => 'bi-airplane',
                'activo'      => true,
                'orden'       => 1,
            ],
            [
                'nombre'      => 'Informática',
                'codigo'      => 'INF',
                'descripcion' => 'Formación en programación, redes, sistemas operativos y soporte técnico.',
                'color'       => '#2980b9',
                'icono'       => 'bi-laptop',
                'activo'      => true,
                'orden'       => 2,
            ],
            [
                'nombre'      => 'Mercadeo',
                'codigo'      => 'MER',
                'descripcion' => 'Formación en ventas, marketing digital, administración comercial y contabilidad.',
                'color'       => '#27ae60',
                'icono'       => 'bi-graph-up',
                'activo'      => true,
                'orden'       => 3,
            ],
            [
                'nombre'      => 'Acondicionamiento Físico',
                'codigo'      => 'ACF',
                'descripcion' => 'Formación en entrenamiento deportivo, nutrición, anatomía y gestión de instalaciones.',
                'color'       => '#8e44ad',
                'icono'       => 'bi-heart-pulse',
                'activo'      => true,
                'orden'       => 4,
            ],
            [
                'nombre'      => 'Logística y Transporte',
                'codigo'      => 'LYT',
                'descripcion' => 'Formación en cadena de suministro, almacenes, transporte y comercio internacional.',
                'color'       => '#c0392b',
                'icono'       => 'bi-truck',
                'activo'      => true,
                'orden'       => 5,
            ],
        ];

        foreach ($especialidades as $data) {
            EspecialidadTecnica::firstOrCreate(['codigo' => $data['codigo']], $data);
        }
    }
}
