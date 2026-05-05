<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Asignacion;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\User;

/**
 * Seeder de datos demo completos.
 *
 * Rellena calificaciones académicas, asistencias, observaciones y
 * vincula los usuarios demo (estudiante / padre / docente) a datos reales.
 *
 * USO:
 *   php artisan db:seed --class=DatosDemoCompleteSeeder
 */
class DatosDemoCompleteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 Iniciando DatosDemoCompleteSeeder...');
        $this->command->info('');

        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            $this->command->error('❌ No hay año escolar activo. Ejecuta SchoolYearSeeder primero.');
            return;
        }
        $this->command->line("   📅 Año escolar: {$schoolYear->nombre}");

        $periodos = Periodo::where('school_year_id', $schoolYear->id)->orderBy('numero')->get();
        if ($periodos->isEmpty()) {
            $this->command->error('❌ No hay períodos en el año escolar activo.');
            return;
        }
        $this->command->line("   📆 Períodos: {$periodos->count()}");

        $docente = Docente::first();
        if (! $docente) {
            $this->command->error('❌ No hay docentes registrados. Ejecuta DemoSeeder primero.');
            return;
        }

        $this->command->info('');

        // ── 1. Calificaciones académicas ──────────────────────────────────
        $this->seedCalificacionesAcademicas($schoolYear, $periodos);

        // ── 2. Asistencias ────────────────────────────────────────────────
        $this->seedAsistencias($periodos, $docente);

        // ── 3. Observaciones ──────────────────────────────────────────────
        $this->seedObservaciones($schoolYear, $periodos, $docente);

        // ── 4. Vincular usuarios demo a datos reales ──────────────────────
        $this->vincularUsuariosDemo($schoolYear);

        $this->command->info('');
        $this->command->info('✅ DatosDemoCompleteSeeder completado.');
        $this->command->info('');
        $this->command->table(
            ['Usuario', 'Contraseña', 'Rol'],
            [
                ['admin@demo.com',      '123456', 'Administrador'],
                ['docente@demo.com',    '123456', 'Docente'],
                ['padre@demo.com',      '123456', 'Representante'],
                ['estudiante@demo.com', '123456', 'Estudiante'],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  1. CALIFICACIONES ACADÉMICAS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedCalificacionesAcademicas(SchoolYear $schoolYear, $periodos): void
    {
        $this->command->info('📝 Generando calificaciones académicas...');

        // Cargar todas las matrículas activas con su grupo
        $matriculas = Matricula::where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->get();

        // Cargar asignaciones activas indexadas por grupo_id
        $asignacionesPorGrupo = Asignacion::where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->get()
            ->groupBy('grupo_id');

        $creadas    = 0;
        $existentes = 0;

        foreach ($matriculas as $matricula) {
            $asigs = $asignacionesPorGrupo->get($matricula->grupo_id, collect());

            foreach ($asigs as $asignacion) {
                $existe = CalificacionAcademica::where('matricula_id', $matricula->id)
                    ->where('asignacion_id', $asignacion->id)
                    ->exists();

                if ($existe) {
                    $existentes++;
                    continue;
                }

                // Generar notas realistas por período (entre 65 y 98)
                $notas = [];
                foreach ($periodos as $p) {
                    $n = $p->numero;
                    // 4 competencias por período
                    $notas["comp1_p{$n}"] = rand(65, 98);
                    $notas["comp2_p{$n}"] = rand(65, 98);
                    $notas["comp3_p{$n}"] = rand(65, 98);
                    $notas["comp4_p{$n}"] = rand(65, 98);
                }

                // Promedios por competencia (avg de los 4 periodos)
                foreach ([1, 2, 3, 4] as $c) {
                    $vals = array_filter(
                        array_map(fn($p) => $notas["comp{$c}_p{$p->numero}"] ?? null, $periodos->all()),
                        fn($v) => $v !== null
                    );
                    $notas["prom_comp{$c}"] = count($vals) ? round(array_sum($vals) / count($vals), 1) : null;
                }

                // Nota final = promedio de los 4 promedios de competencias
                $promedios = array_filter([$notas['prom_comp1'], $notas['prom_comp2'], $notas['prom_comp3'], $notas['prom_comp4']]);
                $notaFinal = count($promedios) ? round(array_sum($promedios) / count($promedios), 1) : null;

                CalificacionAcademica::create(array_merge($notas, [
                    'matricula_id'   => $matricula->id,
                    'asignacion_id'  => $asignacion->id,
                    'school_year_id' => $schoolYear->id,
                    'nota_final'     => $notaFinal,
                    'situacion'      => $notaFinal !== null && $notaFinal >= 65 ? 'A' : 'R',
                    'publicado'      => true,
                ]));

                $creadas++;
            }
        }

        $this->command->line("   ✓ {$creadas} calificaciones creadas, {$existentes} ya existían.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  2. ASISTENCIAS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedAsistencias($periodos, Docente $docente): void
    {
        $this->command->info('📋 Generando asistencias...');

        $matriculas = Matricula::where('estado', 'activa')->get();
        $asignacionesPorGrupo = Asignacion::where('activo', true)
            ->get()
            ->groupBy('grupo_id');

        $estados  = ['presente', 'presente', 'presente', 'presente', 'tarde', 'ausente'];
        $creadas  = 0;

        foreach ($periodos as $periodo) {
            if (! $periodo->fecha_inicio || ! $periodo->fecha_fin) {
                continue;
            }

            // 3 fechas de clase por período
            $fechas = $this->generarFechasClase($periodo->fecha_inicio, $periodo->fecha_fin, 3);

            foreach ($matriculas as $matricula) {
                $asigs = $asignacionesPorGrupo->get($matricula->grupo_id, collect());

                foreach ($asigs->take(4) as $asignacion) {
                    foreach ($fechas as $fecha) {
                        $existe = Asistencia::where('matricula_id', $matricula->id)
                            ->where('asignacion_id', $asignacion->id)
                            ->where('fecha', $fecha)
                            ->exists();

                        if ($existe) continue;

                        Asistencia::create([
                            'matricula_id'    => $matricula->id,
                            'asignacion_id'   => $asignacion->id,
                            'fecha'           => $fecha,
                            'estado'          => $estados[array_rand($estados)],
                            'registrado_por'  => $docente->user_id,
                        ]);
                        $creadas++;
                    }
                }
            }
        }

        $this->command->line("   ✓ {$creadas} registros de asistencia creados.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  3. OBSERVACIONES
    // ─────────────────────────────────────────────────────────────────────────
    private function seedObservaciones(SchoolYear $schoolYear, $periodos, Docente $docente): void
    {
        $this->command->info('💬 Generando observaciones...');

        $matriculas = Matricula::where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->with('estudiante')
            ->take(15) // Solo los primeros 15 estudiantes
            ->get();

        $asignacionesPorGrupo = Asignacion::where('school_year_id', $schoolYear->id)
            ->where('activo', true)
            ->where('docente_id', $docente->id)
            ->get()
            ->groupBy('grupo_id');

        $textosPositivos = [
            'Excelente desempeño en clase. Demuestra gran interés y dedicación.',
            'Estudiante destacado. Siempre cumple con las tareas asignadas.',
            'Ha mejorado notablemente su rendimiento académico este período.',
            'Participación activa y aportes valiosos a las discusiones en clase.',
        ];
        $textosAcademicos = [
            'Necesita reforzar los temas vistos en las últimas semanas.',
            'Se recomienda practicar más ejercicios de matemáticas en casa.',
            'Debe prestar mayor atención durante las explicaciones en clase.',
        ];

        $creadas = 0;
        $periodo = $periodos->first();

        foreach ($matriculas as $matricula) {
            $asigs = $asignacionesPorGrupo->get($matricula->grupo_id, collect());
            if ($asigs->isEmpty()) continue;
            $asignacion = $asigs->first();

            // Una positiva + una académica por estudiante
            Observacion::create([
                'docente_id'    => $docente->id,
                'estudiante_id' => $matricula->estudiante_id,
                'asignacion_id' => $asignacion->id,
                'periodo_id'    => $periodo?->id,
                'tipo'          => 'positiva',
                'texto'         => $textosPositivos[array_rand($textosPositivos)],
                'privada'       => false,
            ]);

            Observacion::create([
                'docente_id'    => $docente->id,
                'estudiante_id' => $matricula->estudiante_id,
                'asignacion_id' => $asignacion->id,
                'periodo_id'    => $periodo?->id,
                'tipo'          => 'academica',
                'texto'         => $textosAcademicos[array_rand($textosAcademicos)],
                'privada'       => false,
            ]);

            $creadas += 2;
        }

        $this->command->line("   ✓ {$creadas} observaciones creadas.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  4. VINCULAR USUARIOS DEMO
    // ─────────────────────────────────────────────────────────────────────────
    private function vincularUsuariosDemo(SchoolYear $schoolYear): void
    {
        $this->command->info('👤 Vinculando usuarios demo...');

        // Primer estudiante matriculado activo
        $matricula = Matricula::where('school_year_id', $schoolYear->id)
            ->where('estado', 'activa')
            ->with('estudiante')
            ->first();

        if (! $matricula || ! $matricula->estudiante) {
            $this->command->warn('   ⚠ No se encontró estudiante activo para vincular.');
            return;
        }

        $estudianteReal = $matricula->estudiante;

        // ── Usuario Estudiante demo ───────────────────────────────────────
        $estudianteUser = User::updateOrCreate(
            ['email' => 'estudiante@demo.com'],
            [
                'name'                 => $estudianteReal->nombres,
                'apellidos'            => $estudianteReal->apellidos,
                'password'             => Hash::make('123456'),
                'activo'               => true,
                'pendiente_aprobacion' => false,
            ]
        );
        if (! $estudianteUser->hasRole('Estudiante')) {
            $estudianteUser->syncRoles(['Estudiante']);
        }
        $estudianteReal->update(['user_id' => $estudianteUser->id]);
        $this->command->line("   ✓ estudiante@demo.com → {$estudianteReal->apellidos}, {$estudianteReal->nombres}");

        // ── Usuario Padre demo ────────────────────────────────────────────
        $padreUser = User::updateOrCreate(
            ['email' => 'padre@demo.com'],
            [
                'name'                 => 'Representante',
                'apellidos'            => $estudianteReal->apellidos,
                'password'             => Hash::make('123456'),
                'activo'               => true,
                'pendiente_aprobacion' => false,
            ]
        );
        if (! $padreUser->hasRole('Representante')) {
            $padreUser->syncRoles(['Representante']);
        }

        $rep = Representante::updateOrCreate(
            ['user_id' => $padreUser->id],
            [
                'nombres'   => 'Representante',
                'apellidos' => $estudianteReal->apellidos,
                'cedula'    => '000-0000099-9',
                'telefono'  => '809-555-0001',
                'email'     => 'padre@demo.com',
                'ocupacion' => 'Empleado',
            ]
        );

        // Vincular al estudiante (pivot)
        $yaVinculado = DB::table('estudiante_representante')
            ->where('estudiante_id', $estudianteReal->id)
            ->where('representante_id', $rep->id)
            ->exists();

        if (! $yaVinculado) {
            DB::table('estudiante_representante')->insert([
                'estudiante_id'    => $estudianteReal->id,
                'representante_id' => $rep->id,
                'parentesco'       => 'padre',
                'es_principal'     => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
        $this->command->line("   ✓ padre@demo.com → representante de {$estudianteReal->apellidos}");

        // ── Usuario Docente demo ──────────────────────────────────────────
        $docenteUser = User::updateOrCreate(
            ['email' => 'docente@demo.com'],
            [
                'name'                 => 'Profesor',
                'apellidos'            => 'Demo',
                'password'             => Hash::make('123456'),
                'activo'               => true,
                'pendiente_aprobacion' => false,
            ]
        );
        if (! $docenteUser->hasRole('Docente')) {
            $docenteUser->syncRoles(['Docente']);
        }

        $docente = Docente::updateOrCreate(
            ['user_id' => $docenteUser->id],
            [
                'nombres'          => 'Profesor',
                'apellidos'        => 'Demo',
                'cedula'           => '000-0000088-8',
                'email'            => 'docente@demo.com',
                'especialidad'     => 'Educación General',
                'titulo_academico' => 'Licenciado en Educación',
                'estado'           => 'activo',
            ]
        );

        // Asignar el docente demo a todas las asignaciones sin docente
        Asignacion::where('school_year_id', $schoolYear->id)
            ->whereNull('docente_id')
            ->update(['docente_id' => $docente->id]);

        // Si el docente no tiene ninguna asignación, asignarle las del grupo 1
        $tieneAsignaciones = Asignacion::where('docente_id', $docente->id)
            ->where('school_year_id', $schoolYear->id)
            ->exists();

        if (! $tieneAsignaciones) {
            $grupoId = $matricula->grupo_id;
            Asignacion::where('school_year_id', $schoolYear->id)
                ->where('grupo_id', $grupoId)
                ->update(['docente_id' => $docente->id]);
        }

        $this->command->line("   ✓ docente@demo.com → {$docente->apellidos}, {$docente->nombres}");

        // ── Admin demo ────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name'                 => 'Administrador',
                'apellidos'            => 'Demo',
                'password'             => Hash::make('123456'),
                'activo'               => true,
                'pendiente_aprobacion' => false,
            ]
        )->syncRoles(['Administrador']);
        $this->command->line('   ✓ admin@demo.com → Administrador');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPER: generar fechas de clase entre dos fechas (días hábiles)
    // ─────────────────────────────────────────────────────────────────────────
    private function generarFechasClase(string $inicio, string $fin, int $cantidad): array
    {
        $fechas = [];
        $current = \Carbon\Carbon::parse($inicio);
        $limite  = \Carbon\Carbon::parse($fin);

        while ($current->lte($limite) && count($fechas) < $cantidad) {
            if ($current->isWeekday()) {
                $fechas[] = $current->toDateString();
            }
            $current->addDays(7); // Una vez por semana
        }

        return $fechas;
    }
}
