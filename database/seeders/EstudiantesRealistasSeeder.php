<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EstudiantesRealistasSeeder extends Seeder
{
    // Nombres dominicanos masculinos
    private array $nombresM = [
        'Juan Carlos', 'Miguel Ángel', 'Carlos Alberto', 'José Manuel', 'Luis Antonio',
        'Pedro Pablo', 'Roberto Emilio', 'Eduardo José', 'Francisco Antonio', 'Rafael Ángel',
        'Nelson Ramón', 'Alexis Daniel', 'Henry Omar', 'César Augusto', 'Ramón Antonio',
        'Julio César', 'Daniel Enrique', 'Cristian David', 'Ángel Gabriel', 'Javier Andrés',
        'Kelvin Omar', 'Yohan Mateo', 'Brandon Enrique', 'Wilmer José', 'Samuel Antonio',
    ];

    // Nombres dominicanos femeninos
    private array $nombresF = [
        'María del Carmen', 'Ana Luisa', 'Rosa Isabel', 'Carmen Altagracia', 'Luisa Fernanda',
        'Patricia Alicia', 'Yanira Esmeralda', 'Karina Lisbet', 'Jennifer Altagracia', 'Yaritza del Carmen',
        'Dulce María', 'Sandra Milena', 'Mariely Cristina', 'Yuliana Beatriz', 'Estefanía del Carmen',
        'Paola Alejandra', 'Génesis Valentina', 'Camila Sofía', 'Natalia Isabel', 'Andrea Milagros',
        'Valeria de los Ángeles', 'Stephanie Berenice', 'Kenia Margarita', 'Lorena Massiel', 'Yeimy Carolina',
    ];

    // Apellidos dominicanos
    private array $apellidos = [
        'García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Torres',
        'Ramírez', 'Díaz', 'Reyes', 'Morales', 'Cruz', 'Núñez', 'Álvarez', 'Jiménez',
        'Vargas', 'Castillo', 'Ramos', 'Flores', 'Herrera', 'Guzmán', 'Medina', 'Taveras',
        'Bautista', 'Marte', 'Féliz', 'Rosario', 'Suriel', 'De la Rosa', 'Reyna', 'Polanco',
        'Ceballos', 'Pimentel', 'Hiraldo', 'Villanueva', 'Almonte', 'Peña', 'Valdez', 'Acosta',
    ];

    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) { $this->command->error('No hay tenant.'); return; }
        app()->instance('tenant', $tenant);

        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) { $this->command->error('No hay año escolar activo.'); return; }

        $this->command->info('🎓 Creando grupos y estudiantes realistas...');

        // Usuario compartido para representantes de demo (sin acceso al portal)
        $userRepDemo = User::firstOrCreate(
            ['email' => 'representantes.demo@sge.test'],
            [
                'name'                 => 'Representante',
                'apellidos'            => 'Demo Masivo',
                'password'             => Hash::make('Rep2030!'),
                'activo'               => false,
                'pendiente_aprobacion' => false,
            ]
        );
        if (! $userRepDemo->hasRole('Representante')) {
            $userRepDemo->assignRole('Representante');
        }

        // ── Grados ────────────────────────────────────────────────────────
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

        // ── Secciones ─────────────────────────────────────────────────────
        $secciones = [];
        foreach (['A' => 1, 'B' => 2, 'C' => 3] as $letra => $orden) {
            $secciones[$letra] = Seccion::firstOrCreate(['nombre' => $letra], ['orden' => $orden]);
        }

        // ── Grupos: 18 (6 grados × 3 secciones) ──────────────────────────
        $totalEstudiantes = 0;
        $totalReps        = 0;
        $contadorCedula   = 10000;

        foreach ($grados as $nivel => $grado) {
            foreach ($secciones as $letra => $seccion) {
                $grupo = Grupo::firstOrCreate(
                    [
                        'school_year_id' => $schoolYear->id,
                        'grado_id'       => $grado->id,
                        'seccion_id'     => $seccion->id,
                    ],
                    ['activo' => true, 'capacidad' => 30, 'tenant_id' => $tenant->id]
                );

                // 20 estudiantes por grupo (10 M + 10 F)
                $yaMatriculados = Matricula::withoutGlobalScope('tenant')
                    ->where('school_year_id', $schoolYear->id)
                    ->where('grupo_id', $grupo->id)
                    ->count();

                if ($yaMatriculados >= 20) continue;

                $porCrear = 20 - $yaMatriculados;
                $numero   = 1;

                for ($i = 0; $i < $porCrear; $i++) {
                    $esMasculino = $i % 2 === 0;
                    $nombres     = $esMasculino
                        ? $this->nombresM[$i % count($this->nombresM)]
                        : $this->nombresF[$i % count($this->nombresF)];
                    $letraOrd = ord($letra[0]);
                    $ap1 = $this->apellidos[($i * $nivel + $letraOrd) % count($this->apellidos)];
                    $ap2 = $this->apellidos[($i * 3 + $nivel)         % count($this->apellidos)];

                    $numMatricula = sprintf('%d%s%04d', $nivel, $letra, $contadorCedula);
                    $contadorCedula++;

                    $estudiante = Estudiante::withoutGlobalScope('tenant')
                        ->firstOrCreate(
                            ['numero_matricula' => $numMatricula],
                            [
                                'nombres'          => $nombres,
                                'apellidos'        => "{$ap1} {$ap2}",
                                'fecha_nacimiento' => now()->subYears(11 + $nivel)->subDays(rand(0, 365))->toDateString(),
                                'sexo'             => $esMasculino ? 'M' : 'F',
                                'nacionalidad'     => 'Dominicana',
                                'municipio'        => $this->municipioAleatorio(),
                                'estado'           => 'activo',
                                'tenant_id'        => $tenant->id,
                            ]
                        );

                    Matricula::withoutGlobalScope('tenant')->firstOrCreate(
                        [
                            'school_year_id' => $schoolYear->id,
                            'estudiante_id'  => $estudiante->id,
                            'grupo_id'       => $grupo->id,
                        ],
                        [
                            'fecha_matricula' => now()->subDays(rand(0, 30))->toDateString(),
                            'numero_orden'    => $numero++,
                            'estado'          => 'activa',
                            'tenant_id'       => $tenant->id,
                        ]
                    );

                    // Representante (comparte usuario demo para no crear 360 logins)
                    $repCedula = sprintf('001-%07d-%d', $contadorCedula, $contadorCedula % 9 + 1);
                    $repExiste = Representante::withoutGlobalScope('tenant')
                        ->where('cedula', $repCedula)
                        ->first();

                    if (! $repExiste) {
                        $rep = Representante::create([
                            'user_id'   => $userRepDemo->id,
                            'nombres'   => 'Representante',
                            'apellidos' => "{$ap1} {$ap2}",
                            'cedula'    => $repCedula,
                            'telefono'  => '809-' . rand(200, 999) . '-' . rand(1000, 9999),
                            'email'     => strtolower(str_replace(' ', '.', $ap1)) . $contadorCedula . '@gmail.com',
                            'ocupacion' => $this->ocupacionAleatoria(),
                            'tenant_id' => $tenant->id,
                        ]);

                        $yaVinculado = DB::table('estudiante_representante')
                            ->where('estudiante_id', $estudiante->id)
                            ->where('representante_id', $rep->id)
                            ->exists();

                        if (! $yaVinculado) {
                            DB::table('estudiante_representante')->insert([
                                'estudiante_id'    => $estudiante->id,
                                'representante_id' => $rep->id,
                                'parentesco'       => $esMasculino ? 'padre' : 'madre',
                                'es_principal'     => true,
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ]);
                        }
                        $totalReps++;
                    }

                    $totalEstudiantes++;
                }
            }
        }

        $this->command->line("   ✓ {$totalEstudiantes} estudiantes matriculados en 18 grupos.");
        $this->command->line("   ✓ {$totalReps} representantes creados y vinculados.");
    }

    private function municipioAleatorio(): string
    {
        $municipios = [
            'Santo Domingo', 'Santiago', 'La Vega', 'San Pedro de Macorís',
            'Puerto Plata', 'San Francisco de Macorís', 'Higüey', 'Barahona',
            'Bonao', 'Moca', 'San Cristóbal', 'Azua',
        ];
        return $municipios[array_rand($municipios)];
    }

    private function ocupacionAleatoria(): string
    {
        $ocupaciones = [
            'Empleado', 'Comerciante', 'Médico', 'Abogado', 'Ingeniero',
            'Maestro', 'Enfermero/a', 'Contador', 'Agricultor', 'Policía',
        ];
        return $ocupaciones[array_rand($ocupaciones)];
    }
}
