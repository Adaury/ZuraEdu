<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\FaltaDisciplinaria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Services\AcademicRiskScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * AcademicRiskScoreService — 2 hallazgos "Medio" de la ronda de auditoría
 * de módulos grandes del 2026-09-03: N+1 real en calcularTodos() (4-5
 * queries por estudiante dentro del loop, síncrono vía HTTP) y una 5ª
 * reimplementación independiente de la regla de prioridad académica que
 * ya se había consolidado en PromedioEstudianteService.
 */
class AcademicRiskScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private function crearEstudianteConRiesgo(SchoolYear $sy, Grupo $grupo, Asignacion $asignacion): array
    {
        $estudiante = Estudiante::factory()->create();
        $matricula = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => random_int(1, 999), 'estado' => 'activa',
        ]);

        CalificacionAcademica::create([
            'matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $sy->id,
            'nota_final' => 55, // < 70, cuenta como materia en riesgo
        ]);

        FaltaDisciplinaria::create([
            'estudiante_id' => $estudiante->id, 'tipo' => 'falta_leve',
            'descripcion' => 'Test', 'fecha' => now()->subDays(5),
        ]);

        Asistencia::create([
            'matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id,
            'fecha' => now()->subDays(3), 'estado' => 'ausente',
            'registrado_por' => \App\Models\User::factory()->create()->id,
        ]);

        return compact('estudiante', 'matricula');
    }

    public function test_calcular_todos_produce_el_mismo_resultado_que_calcular_individual(): void
    {
        $sy = SchoolYear::create(['nombre' => '2026-Riesgo', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado R', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'RS1', 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);

        $e1 = $this->crearEstudianteConRiesgo($sy, $grupo, $asignacion);
        $e2 = $this->crearEstudianteConRiesgo($sy, $grupo, $asignacion);

        $service = new AcademicRiskScoreService();

        // Referencia: cálculo individual (no tocado por el fix de bulk)
        $esperado1 = $service->calcularParaEstudiante($e1['estudiante']->id, $sy->id);
        $esperado2 = $service->calcularParaEstudiante($e2['estudiante']->id, $sy->id);

        $count = $service->calcularTodos($sy->id);
        $this->assertEquals(2, $count);

        $real1 = \App\Models\AcademicRiskScore::where('estudiante_id', $e1['estudiante']->id)->first();
        $real2 = \App\Models\AcademicRiskScore::where('estudiante_id', $e2['estudiante']->id)->first();

        $this->assertEquals($esperado1['score'], $real1->score);
        $this->assertEquals($esperado1['dim_academico'], $real1->dim_academico);
        $this->assertEquals($esperado1['materias_en_riesgo'], $real1->materias_en_riesgo);
        $this->assertEquals($esperado1['promedio_general'], $real1->promedio_general);
        $this->assertEquals($esperado1['faltas_leves'], $real1->faltas_leves);

        $this->assertEquals($esperado2['score'], $real2->score);
        $this->assertEquals($esperado2['dim_academico'], $real2->dim_academico);
    }

    public function test_calcular_todos_no_crece_en_numero_de_queries_por_estudiante(): void
    {
        $sy = SchoolYear::create(['nombre' => '2026-RiesgoN1', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado N', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'RS2', 'nombre' => 'Matemática', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);

        $totalEstudiantes = 8;
        for ($i = 0; $i < $totalEstudiantes; $i++) {
            $this->crearEstudianteConRiesgo($sy, $grupo, $asignacion);
        }

        DB::enableQueryLog();
        (new AcademicRiskScoreService())->calcularTodos($sy->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // ~15 queries fijas de lectura en bulk (school year, matrículas + eager
        // loads, calificaciones académicas/técnicas, asistencia, faltas) +
        // máx. 3 queries de escritura por estudiante (updateOrCreate: select +
        // insert/update). Antes del fix eran 4-5 queries de LECTURA por
        // estudiante dentro del loop — con 8 estudiantes eso habría dado 50+.
        $limite = 15 + ($totalEstudiantes * 3);
        $this->assertLessThan(
            $limite,
            $queries,
            "calcularTodos() con {$totalEstudiantes} estudiantes ejecutó {$queries} queries (límite {$limite}) — las lecturas no deben escalar por estudiante, solo la escritura final."
        );
    }
}
