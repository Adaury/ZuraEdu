<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Grado;
use App\Models\Seccion;
use App\Models\Grupo;
use App\Models\Asignatura;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\User;

class DemoResetSeeder extends Seeder
{
    // ── Tablas a limpiar (orden no importa con FK_CHECKS=0) ───────────────
    private array $tablasALimpiar = [
        'evaluaciones_indicadores',
        'calificaciones_academicas',
        'calificaciones',
        'asistencias',
        'observaciones',
        'indicadores_aprendizaje',
        'resultados_aprendizaje',
        'horario_detalles',
        'horarios',
        'sch_horario_detalles',
        'sch_horarios',
        'sch_asignaciones',
        'sch_materias',
        'sch_cursos',
        'suplencias',
        'matriculas',
        'estudiante_representante',
        'asignaciones',
        'grupos',
        'secciones',
        'asignaturas',
    ];

    public function run(): void
    {
        $this->limpiarDatos();
        $this->crearAsignaturas();
        $this->crearGruposYEstudiantes();

        $this->command->info('');
        $this->command->info('✅ Demo completo. Sistema listo para presentar.');
        $this->command->info('');
    }

    // ────────────────────────────────────────────────────────────────────────
    //  1. LIMPIEZA
    // ────────────────────────────────────────────────────────────────────────
    private function limpiarDatos(): void
    {
        $this->command->info('🗑  Eliminando datos existentes...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->tablasALimpiar as $tabla) {
            if (Schema::hasTable($tabla)) {
                DB::table($tabla)->truncate();
            }
        }

        // Eliminar usuarios con rol Estudiante ligados a registros de estudiantes
        $estudianteUserIds = DB::table('estudiantes')
            ->whereNotNull('user_id')
            ->pluck('user_id');

        if ($estudianteUserIds->isNotEmpty()) {
            User::whereIn('id', $estudianteUserIds->toArray())->delete();
        }

        DB::table('estudiantes')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('   ✓ Tablas vaciadas correctamente.');
    }

    // ────────────────────────────────────────────────────────────────────────
    //  2. ASIGNATURAS (una por área académica)
    // ────────────────────────────────────────────────────────────────────────
    private function crearAsignaturas(): void
    {
        $this->command->info('📚 Creando asignaturas...');

        $asignaturas = [
            ['codigo' => 'LEN',  'nombre' => 'Lengua Española',         'area' => 'Humanidades',        'horas_semanales' => 5, 'color' => '#dc3545'],
            ['codigo' => 'MAT',  'nombre' => 'Matemáticas',              'area' => 'Ciencias Exactas',   'horas_semanales' => 5, 'color' => '#1d4ed8'],
            ['codigo' => 'CN',   'nombre' => 'Ciencias Naturales',       'area' => 'Ciencias Naturales', 'horas_semanales' => 4, 'color' => '#198754'],
            ['codigo' => 'CS',   'nombre' => 'Ciencias Sociales',        'area' => 'Ciencias Sociales',  'horas_semanales' => 4, 'color' => '#fd7e14'],
            ['codigo' => 'ING',  'nombre' => 'Inglés',                   'area' => 'Idiomas',             'horas_semanales' => 3, 'color' => '#6f42c1'],
            ['codigo' => 'EDUF', 'nombre' => 'Educación Física',         'area' => 'Educación Física',   'horas_semanales' => 2, 'color' => '#20c997'],
            ['codigo' => 'ARTE', 'nombre' => 'Educación Artística',      'area' => 'Artes',               'horas_semanales' => 2, 'color' => '#e83e8c'],
            ['codigo' => 'TIC',  'nombre' => 'Tecnología e Informática', 'area' => 'Tecnología',          'horas_semanales' => 2, 'color' => '#0dcaf0'],
            ['codigo' => 'FORM', 'nombre' => 'Formación Integral',       'area' => 'Formación Humana',   'horas_semanales' => 2, 'color' => '#6610f2'],
            ['codigo' => 'HIST', 'nombre' => 'Historia y Geografía',     'area' => 'Humanidades',         'horas_semanales' => 3, 'color' => '#795548'],
        ];

        foreach ($asignaturas as $data) {
            Asignatura::create(array_merge($data, ['activo' => true]));
        }

        $this->command->info('   ✓ ' . count($asignaturas) . ' asignaturas creadas (una por área).');
    }

    // ────────────────────────────────────────────────────────────────────────
    //  3. SECCIÓN A + GRUPOS + ESTUDIANTES + MATRÍCULAS
    // ────────────────────────────────────────────────────────────────────────
    private function crearGruposYEstudiantes(): void
    {
        $this->command->info('🏫 Creando grupos y estudiantes...');

        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            $this->command->error('No hay año escolar activo. Ejecuta SchoolYearSeeder primero.');
            return;
        }

        // Una sola sección para todos los grados
        $seccion = Seccion::create(['nombre' => 'A', 'orden' => 1]);

        $grados = Grado::where('ciclo', 'primer_ciclo')->orderBy('nivel')->get();

        $nombresMasculinos = ['Carlos', 'José', 'Miguel', 'Luis', 'Rafael', 'Juan', 'Pedro', 'Diego', 'Andrés', 'Fernando'];
        $nombresFemeninos  = ['María', 'Ana', 'Carmen', 'Rosa', 'Isabel', 'Patricia', 'Sandra', 'Diana', 'Laura', 'Sofía'];
        $apellidos1        = ['García', 'Martínez', 'Rodríguez', 'López', 'González', 'Hernández', 'Pérez', 'Sánchez', 'Ramírez', 'Torres'];
        $apellidos2        = ['Cruz', 'Reyes', 'Morales', 'Jiménez', 'Castillo', 'Vargas', 'Ramos', 'Medina', 'Guerrero', 'Flores'];

        $contador = 1; // Para número de matrícula global

        foreach ($grados as $grado) {
            // Crear grupo: Grado + Sección A
            $grupo = Grupo::create([
                'school_year_id' => $schoolYear->id,
                'grado_id'       => $grado->id,
                'seccion_id'     => $seccion->id,
                'capacidad'      => 35,
                'activo'         => true,
            ]);

            $this->command->line("   • {$grado->nombre} — Sección A");

            // Año de nacimiento aproximado según grado
            $anioNac = 2025 - (12 + $grado->nivel);

            // 10 estudiantes por grupo, alternando M/F
            for ($i = 0; $i < 10; $i++) {
                $sexo    = ($i % 2 === 0) ? 'M' : 'F';
                $nombres = $sexo === 'M' ? $nombresMasculinos[$i] : $nombresFemeninos[$i];
                $numMat  = str_pad($contador, 4, '0', STR_PAD_LEFT);

                $estudiante = Estudiante::create([
                    'numero_matricula' => "2025-{$numMat}",
                    'nombres'          => $nombres,
                    'apellidos'        => "{$apellidos1[$i]} {$apellidos2[$i]}",
                    'fecha_nacimiento' => "{$anioNac}-06-15",
                    'sexo'             => $sexo,
                    'estado'           => 'activo',
                    'nacionalidad'     => 'Dominicana',
                ]);

                Matricula::create([
                    'school_year_id'  => $schoolYear->id,
                    'estudiante_id'   => $estudiante->id,
                    'grupo_id'        => $grupo->id,
                    'fecha_matricula' => now()->toDateString(),
                    'numero_orden'    => $i + 1,
                    'estado'          => 'activa',
                ]);

                $contador++;
            }
        }

        $total = ($grados->count() * 10);
        $this->command->info("   ✓ {$grados->count()} grupos creados con 10 estudiantes cada uno ({$total} total).");
    }
}
