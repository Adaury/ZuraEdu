<?php
namespace Database\Seeders;

use App\Models\SchoolYear;
use App\Models\Periodo;
use App\Models\ConfigCalificacion;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder
{
    public function run(): void
    {
        $year = SchoolYear::firstOrCreate(
            ['nombre' => '2024-2025'],
            [
                'fecha_inicio' => '2024-09-02',
                'fecha_fin'    => '2025-06-27',
                'activo'       => true,
            ]
        );

        $periodos = [
            ['numero'=>1,'nombre'=>'Primer Período',   'fecha_inicio'=>'2024-09-02','fecha_fin'=>'2024-11-22','activo'=>false,'cerrado'=>true],
            ['numero'=>2,'nombre'=>'Segundo Período',  'fecha_inicio'=>'2024-11-25','fecha_fin'=>'2025-02-07','activo'=>false,'cerrado'=>true],
            ['numero'=>3,'nombre'=>'Tercer Período',   'fecha_inicio'=>'2025-02-10','fecha_fin'=>'2025-04-25','activo'=>false,'cerrado'=>false],
            ['numero'=>4,'nombre'=>'Cuarto Período',   'fecha_inicio'=>'2025-04-28','fecha_fin'=>'2025-06-27','activo'=>true, 'cerrado'=>false],
        ];

        foreach ($periodos as $p) {
            Periodo::firstOrCreate(
                ['school_year_id' => $year->id, 'numero' => $p['numero']],
                array_merge($p, ['school_year_id' => $year->id])
            );
        }

        $pesos = [
            'tareas'        => 20,
            'practicas'     => 15,
            'participacion' => 10,
            'proyecto'      => 20,
            'examen'        => 35,
        ];

        foreach ($pesos as $componente => $peso) {
            ConfigCalificacion::firstOrCreate(
                ['school_year_id' => $year->id, 'componente' => $componente],
                ['peso' => $peso, 'activo' => true]
            );
        }
    }
}
