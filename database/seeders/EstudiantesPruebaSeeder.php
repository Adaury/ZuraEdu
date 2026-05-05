<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;

/**
 * Crea grados 1ro–6to, secciones A–C, grupos y un estudiante de prueba por grupo.
 *
 * USO:
 *   php artisan db:seed --class=EstudiantesPruebaSeeder
 */
class EstudiantesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('Iniciando EstudiantesPruebaSeeder...');

        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            $this->command->error('No hay año escolar activo. Ejecuta SchoolYearSeeder primero.');
            return;
        }
        $this->command->line("  Año escolar: {$schoolYear->nombre}");

        // ── 1. Grados 1ro–6to ─────────────────────────────────────────────
        $gradosData = [
            ['nivel' => 1, 'nombre' => '1ro de Secundaria', 'orden' => 1, 'ciclo' => 'primer_ciclo'],
            ['nivel' => 2, 'nombre' => '2do de Secundaria', 'orden' => 2, 'ciclo' => 'primer_ciclo'],
            ['nivel' => 3, 'nombre' => '3ro de Secundaria', 'orden' => 3, 'ciclo' => 'primer_ciclo'],
            ['nivel' => 4, 'nombre' => '4to de Secundaria', 'orden' => 4, 'ciclo' => 'segundo_ciclo'],
            ['nivel' => 5, 'nombre' => '5to de Secundaria', 'orden' => 5, 'ciclo' => 'segundo_ciclo'],
            ['nivel' => 6, 'nombre' => '6to de Secundaria', 'orden' => 6, 'ciclo' => 'segundo_ciclo'],
        ];

        $grados = [];
        foreach ($gradosData as $data) {
            $grados[$data['nivel']] = Grado::firstOrCreate(
                ['nivel' => $data['nivel']],
                ['nombre' => $data['nombre'], 'orden' => $data['orden'], 'ciclo' => $data['ciclo']]
            );
        }
        $this->command->line('  Grados: OK (1ro–6to)');

        // ── 2. Secciones A, B, C ──────────────────────────────────────────
        $secciones = [];
        foreach (['A' => 1, 'B' => 2, 'C' => 3] as $letra => $orden) {
            $secciones[$letra] = Seccion::firstOrCreate(
                ['nombre' => $letra],
                ['orden'  => $orden]
            );
        }
        $this->command->line('  Secciones: OK (A, B, C)');

        // ── 3. Grupos: 6 grados × 3 secciones = 18 grupos ────────────────
        $grupos = [];
        foreach ($grados as $nivel => $grado) {
            foreach ($secciones as $letra => $seccion) {
                $grupos[$nivel][$letra] = Grupo::firstOrCreate(
                    [
                        'school_year_id' => $schoolYear->id,
                        'grado_id'       => $grado->id,
                        'seccion_id'     => $seccion->id,
                    ],
                    [
                        'activo'    => true,
                        'capacidad' => 30,
                    ]
                );
            }
        }
        $this->command->line('  Grupos: OK (18 grupos — 6 grados × 3 secciones)');

        // ── 4. Estudiantes de prueba ──────────────────────────────────────
        $nivelLabel = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
        $creados = 0;

        foreach ($grupos as $nivel => $secGrupos) {
            foreach ($secGrupos as $letra => $grupo) {
                $numMatricula = sprintf('PRUEBA-%d%s-%d', $nivel, $letra, $schoolYear->id);

                // Evitar duplicar si ya existe
                if (Matricula::where('numero_orden', 0)
                    ->whereHas('estudiante', fn($q) => $q->where('numero_matricula', $numMatricula))
                    ->exists()
                ) {
                    continue;
                }

                $estudiante = Estudiante::firstOrCreate(
                    ['numero_matricula' => $numMatricula],
                    [
                        'nombres'          => 'Prueba ' . $nivelLabel[$nivel] . $letra,
                        'apellidos'        => 'Estudiante Test',
                        'cedula'           => null,
                        'fecha_nacimiento' => '2010-01-01',
                        'sexo'             => 'M',
                        'nacionalidad'     => 'Dominicana',
                        'estado'           => 'activo',
                    ]
                );

                Matricula::firstOrCreate(
                    [
                        'school_year_id' => $schoolYear->id,
                        'estudiante_id'  => $estudiante->id,
                        'grupo_id'       => $grupo->id,
                    ],
                    [
                        'fecha_matricula' => now()->toDateString(),
                        'numero_orden'    => 0,
                        'estado'          => 'activa',
                    ]
                );

                $creados++;
            }
        }

        $this->command->line("  Estudiantes creados/encontrados: {$creados}");
        $this->command->info('');
        $this->command->info('EstudiantesPruebaSeeder completado.');
        $this->command->info('  18 grupos disponibles (1ro A–C, 2do A–C, 3ro A–C, 4to A–C, 5to A–C, 6to A–C)');
        $this->command->info('  1 estudiante de prueba por grupo.');
        $this->command->info('');
    }
}
