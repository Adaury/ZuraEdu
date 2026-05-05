<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Asignatura;
use App\Models\SchoolYear;

class AsignacionesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = SchoolYear::actual();

        if (! $schoolYear) {
            $this->command->error('No hay año escolar activo.');
            return;
        }

        // ── 1. Limpiar asignaciones existentes ────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('asignaciones')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info('🗑  Asignaciones anteriores eliminadas.');

        // ── 2. Actualizar áreas de docentes ───────────────────────────────
        $docentes = Docente::all();

        if ($docentes->count() === 0) {
            $this->command->error('No hay docentes registrados.');
            return;
        }

        // Asignar área a cada docente en rotación: academica, tecnica, ambas
        $areas = ['academica', 'tecnica', 'ambas'];
        foreach ($docentes as $i => $docente) {
            $docente->update([
                'area'   => $areas[$i % 3],
                'estado' => 'activo',
            ]);
        }
        $this->command->info('👤 Áreas de docentes actualizadas.');

        // ── 3. Obtener grupos y asignaturas ───────────────────────────────
        $grupos     = Grupo::where('school_year_id', $schoolYear->id)
                          ->where('activo', true)
                          ->with('grado')
                          ->get();
        $asignaturas = Asignatura::where('activo', true)->get();

        if ($grupos->isEmpty()) {
            $this->command->error('No hay grupos activos para este año escolar.');
            return;
        }

        $this->command->info("📚 Creando asignaciones para {$grupos->count()} grupos...");

        $contador = 0;

        foreach ($grupos as $grupo) {
            $nivel = $grupo->grado?->nivel ?? 1;
            // Grados 1-3 → académica, grados 4-6 → puede ser técnica o académica
            $areaGrupo = $nivel <= 3 ? 'academica' : 'ambas';

            // Docente principal del grupo (rotación)
            $docenteAcad  = $docentes->where('area', 'academica')->first()
                         ?? $docentes->where('area', 'ambas')->first()
                         ?? $docentes->first();
            $docenteTecn  = $docentes->where('area', 'tecnica')->first()
                         ?? $docentes->where('area', 'ambas')->first()
                         ?? $docentes->first();

            // Asignaturas académicas: LEN, MAT, CN, CS, ING, EDUF, ARTE, FORM, HIST
            $asignaturasAcad = $asignaturas->whereNotIn('codigo', ['TIC'])->values();
            // Asignaturas técnicas: TIC + algunas más
            $asignaturasTecn = $asignaturas->whereIn('codigo', ['TIC', 'MAT', 'CN'])->values();

            // Todas las asignaturas son académicas primero
            foreach ($asignaturas as $j => $asignatura) {
                $doc = $docentes->values()->get($j % $docentes->count());
                $area = $nivel <= 3 ? 'academica' : (in_array($asignatura->codigo, ['TIC','FORM']) ? 'tecnica' : 'academica');
                $tipo = $area === 'tecnica' ? 'ra' : 'componentes';

                [$created] = [Asignacion::firstOrCreate(
                    [
                        'school_year_id' => $schoolYear->id,
                        'grupo_id'       => $grupo->id,
                        'asignatura_id'  => $asignatura->id,
                    ],
                    [
                        'docente_id'      => $doc->id,
                        'activo'          => true,
                        'area'            => $area,
                        'tipo_evaluacion' => $tipo,
                        'horas_semana'    => $asignatura->horas_semanales ?? 3,
                    ]
                ), true];
                $contador++;
            }

            $gradoNombre   = $grupo->grado?->nombre ?? '';
            $seccionNombre = $grupo->seccion?->nombre ?? '';
            $this->command->line("   • {$gradoNombre} {$seccionNombre} → asignaciones creadas");
        }

        $this->command->info("✅ {$contador} asignaciones creadas correctamente.");
        $this->command->info('');
        $this->command->info('Ahora puede usar:');
        $this->command->info('  → Registro de Notas (Primer Ciclo / Segundo Ciclo)');
        $this->command->info('  → Área Académica');
        $this->command->info('  → Área Técnica');
    }
}
