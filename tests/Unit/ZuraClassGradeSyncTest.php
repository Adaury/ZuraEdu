<?php

namespace Tests\Unit;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\ClaseVirtual;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Services\ZuraClassGradeSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ZuraClassGradeSync — hallazgo Alto de la auditoría del módulo ZuraClass
 * (ver [[project_auditoria_sigerd_whatsapp_2026_09_02.md]] y la ronda de
 * módulos grandes del 2026-09-03): sincronizar() sobrescribía
 * calificaciones.tareas con la nota de UNA sola entrega en vez de
 * promediar todas las tareas del período — si un docente creaba 2+
 * tareas en el mismo período, cada entrega calificada perdía
 * silenciosamente la contribución de la anterior.
 */
class ZuraClassGradeSyncTest extends TestCase
{
    use RefreshDatabase;

    private function crearEscenario(): array
    {
        $sy = SchoolYear::create(['nombre' => '2026-Test', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Z', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create();
        $matricula = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        $asignatura = Asignatura::create(['codigo' => 'ZC1', 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);
        $periodo = Periodo::create([
            'school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);
        $clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula Virtual', 'activo' => true]);

        return compact('matricula', 'asignacion', 'periodo', 'clase');
    }

    private function tareaCalificada(array $e, int $puntos, float $calificacion): EntregaClassroom
    {
        $material = MaterialClase::create([
            'clase_virtual_id' => $e['clase']->id, 'periodo_id' => $e['periodo']->id,
            'titulo' => 'Tarea', 'tipo' => 'tarea', 'puntos' => $puntos, 'publicado' => true,
        ]);

        return EntregaClassroom::create([
            'material_id' => $material->id, 'matricula_id' => $e['matricula']->id,
            'estado' => 'calificado', 'calificacion' => $calificacion,
        ]);
    }

    public function test_dos_tareas_en_el_mismo_periodo_se_promedian_no_se_sobrescriben(): void
    {
        $e = $this->crearEscenario();
        $sync = new ZuraClassGradeSync();

        // Tarea 1: 8/10 = 80 sobre 100
        $entrega1 = $this->tareaCalificada($e, 10, 8);
        $sync->sincronizar($entrega1);

        $cal = Calificacion::where('matricula_id', $e['matricula']->id)
            ->where('asignacion_id', $e['asignacion']->id)
            ->where('periodo_id', $e['periodo']->id)
            ->first();
        $this->assertEquals(80.0, $cal->tareas, 'Con una sola tarea, tareas debe ser esa nota.');

        // Tarea 2: 20/20 = 100 sobre 100 — promedio esperado: (80+100)/2 = 90
        $entrega2 = $this->tareaCalificada($e, 20, 20);
        $sync->sincronizar($entrega2);

        $cal->refresh();
        $this->assertEquals(
            90.0,
            $cal->tareas,
            'Con 2 tareas calificadas, tareas debe ser el promedio (90), no la nota de la última tarea sola (100).'
        );
    }

    public function test_recalcular_promedio_grupo_actualiza_a_todos_los_estudiantes_del_material(): void
    {
        $e = $this->crearEscenario();
        $sync = new ZuraClassGradeSync();

        $this->tareaCalificada($e, 10, 5); // 50
        $entrega2 = $this->tareaCalificada($e, 10, 9); // 90 — mismo estudiante, material distinto
        $sync->sincronizar($entrega2->fresh());

        // recalcularPromedioGrupo sobre el material de la segunda entrega no debe
        // perder la contribución de la primera tarea del mismo período.
        $sync->recalcularPromedioGrupo($entrega2->material);

        $cal = Calificacion::where('matricula_id', $e['matricula']->id)
            ->where('asignacion_id', $e['asignacion']->id)
            ->where('periodo_id', $e['periodo']->id)
            ->first();
        $this->assertEquals(70.0, $cal->tareas, '(50+90)/2 = 70');
    }
}
