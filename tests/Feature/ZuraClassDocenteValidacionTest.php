<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\ZcRubric;
use App\Models\ZcRubricCriterio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ZuraClass docente: la nota no puede superar los puntos de la actividad, los criterios de
 * rúbrica deben ser los de esa actividad, el período debe ser del año del aula, los puntos no
 * bajan de una nota ya puesta y al duplicar un aula el período viejo no se arrastra.
 */
class ZuraClassDocenteValidacionTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $sy;
    private Periodo $periodo;
    private Docente $docente;
    private Matricula $matricula;
    private Asignacion $asignacion;
    private ClaseVirtual $clase;
    private Grupo $grupo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->sy = SchoolYear::create(['nombre' => '2026-DocVal', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $this->periodo = Periodo::create([
            'school_year_id' => $this->sy->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);

        $grado = Grado::create(['nombre' => 'Grado DV', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $this->grupo = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $this->docente = Docente::factory()->create();
        $this->docente->user->assignRole('Docente');
        $this->docente->user->update(['activo' => true]);

        $est = Estudiante::factory()->create();
        $this->matricula = Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $this->grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $this->asignacion = $this->nuevaAsignacion($this->sy, 'DV1');
        $this->clase = ClaseVirtual::create(['asignacion_id' => $this->asignacion->id, 'nombre' => 'Aula DV', 'activo' => true]);
    }

    private function nuevaAsignacion(SchoolYear $sy, string $codigo): Asignacion
    {
        $asignatura = Asignatura::create(['codigo' => $codigo . random_int(100, 999), 'nombre' => 'Materia ' . $codigo, 'area' => 'academica', 'activo' => true]);

        return Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $this->grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);
    }

    private function tareaConEntrega(int $puntos = 20): array
    {
        $material = MaterialClase::create([
            'clase_virtual_id' => $this->clase->id, 'titulo' => 'Tarea ' . random_int(1, 99999), 'tipo' => 'tarea',
            'puntos' => $puntos, 'periodo_id' => $this->periodo->id, 'publicado' => true,
        ]);
        $entrega = EntregaClassroom::create([
            'material_id' => $material->id, 'matricula_id' => $this->matricula->id,
            'estado' => 'entregado', 'contenido' => 'x', 'fecha_entrega' => now(),
        ]);

        return [$material, $entrega];
    }

    private function calificar(EntregaClassroom $entrega, array $extra)
    {
        return $this->actingAs($this->docente->user)
            ->patch(route('portal.docente.classroom.calificar_entrega', [$this->clase, $entrega]), array_merge([
                'calificacion' => 10, 'sincronizar_notas' => 1,
            ], $extra));
    }

    public function test_la_nota_no_puede_superar_los_puntos_de_la_actividad(): void
    {
        [, $entrega] = $this->tareaConEntrega(20);

        $this->calificar($entrega, ['calificacion' => 80])->assertSessionHasErrors('calificacion');

        $this->assertNull($entrega->fresh()->calificacion);
        $this->assertNull(Calificacion::where('matricula_id', $this->matricula->id)->value('tareas'));

        $this->calificar($entrega, ['calificacion' => 18])->assertSessionDoesntHaveErrors();
        $this->assertEquals(90.0, (float) Calificacion::where('matricula_id', $this->matricula->id)->value('tareas'));
    }

    public function test_los_criterios_de_rubrica_deben_ser_de_la_actividad_y_no_pasar_su_maximo(): void
    {
        [$material, $entrega] = $this->tareaConEntrega(20);
        [$otroMaterial] = $this->tareaConEntrega(20);

        $rubrica = ZcRubric::create(['material_id' => $material->id, 'nombre' => 'R1']);
        $c1 = ZcRubricCriterio::create(['rubric_id' => $rubrica->id, 'nombre' => 'Ortografía', 'puntaje_max' => 10, 'orden' => 0]);
        $c2 = ZcRubricCriterio::create(['rubric_id' => $rubrica->id, 'nombre' => 'Contenido', 'puntaje_max' => 10, 'orden' => 1]);

        $rubricaAjena = ZcRubric::create(['material_id' => $otroMaterial->id, 'nombre' => 'R2']);
        $cAjeno = ZcRubricCriterio::create(['rubric_id' => $rubricaAjena->id, 'nombre' => 'Ajeno', 'puntaje_max' => 100, 'orden' => 0]);

        // Criterio de otra actividad
        $this->calificar($entrega, ['rubrica' => [['criterio_id' => $cAjeno->id, 'puntaje' => 50]]])
            ->assertSessionHasErrors('rubrica.0.criterio_id');

        // Puntaje por encima del máximo del criterio
        $this->calificar($entrega, ['rubrica' => [['criterio_id' => $c1->id, 'puntaje' => 15]]])
            ->assertSessionHasErrors('rubrica.0.puntaje');

        $this->assertNull($entrega->fresh()->calificacion);

        // Rúbrica válida: 8 + 9 de 20 = 85 % de 20 pts = 17
        $this->calificar($entrega, ['rubrica' => [
            ['criterio_id' => $c1->id, 'puntaje' => 8],
            ['criterio_id' => $c2->id, 'puntaje' => 9],
        ]])->assertSessionDoesntHaveErrors();
        $this->assertEquals(17.0, (float) $entrega->fresh()->calificacion);
    }

    public function test_el_periodo_de_un_material_debe_ser_del_ano_del_aula(): void
    {
        $syViejo = SchoolYear::create(['nombre' => '2019-2020', 'fecha_inicio' => '2019-08-01', 'fecha_fin' => '2020-06-30', 'activo' => false]);
        $periodoViejo = Periodo::create([
            'school_year_id' => $syViejo->id, 'numero' => 1, 'nombre' => 'P1 viejo',
            'fecha_inicio' => '2019-08-01', 'fecha_fin' => '2019-10-31', 'activo' => false, 'cerrado' => false,
        ]);

        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.guardar_material', $this->clase), [
                'titulo' => 'Con período ajeno', 'tipo' => 'tarea', 'puntos' => 20, 'periodo_id' => $periodoViejo->id, 'publicado' => 1,
            ])
            ->assertSessionHasErrors('periodo_id');

        $this->assertSame(0, MaterialClase::where('titulo', 'Con período ajeno')->count());

        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.guardar_material', $this->clase), [
                'titulo' => 'Con período propio', 'tipo' => 'tarea', 'puntos' => 20, 'periodo_id' => $this->periodo->id, 'publicado' => 1,
            ])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(1, MaterialClase::where('titulo', 'Con período propio')->count());
    }

    public function test_no_se_pueden_bajar_los_puntos_por_debajo_de_una_nota_ya_puesta(): void
    {
        [$material, $entrega] = $this->tareaConEntrega(20);
        $entrega->update(['calificacion' => 18, 'estado' => 'calificado']);

        $this->actingAs($this->docente->user)
            ->put(route('portal.docente.classroom.actualizar_material', [$this->clase, $material]), [
                'titulo' => $material->titulo, 'tipo' => 'tarea', 'puntos' => 10, 'publicado' => 1,
            ])
            ->assertSessionHasErrors('puntos');

        $this->assertSame(20, (int) $material->fresh()->puntos);

        $this->actingAs($this->docente->user)
            ->put(route('portal.docente.classroom.actualizar_material', [$this->clase, $material]), [
                'titulo' => $material->titulo, 'tipo' => 'tarea', 'puntos' => 25, 'publicado' => 1,
            ])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(25, (int) $material->fresh()->puntos);
    }

    public function test_duplicar_un_aula_no_arrastra_el_periodo_de_otro_ano(): void
    {
        // Aula "vieja": asignación de otro año escolar con una tarea en un período de ese año.
        $syViejo = SchoolYear::create(['nombre' => '2018-2019', 'fecha_inicio' => '2018-08-01', 'fecha_fin' => '2019-06-30', 'activo' => false]);
        $periodoViejo = Periodo::create([
            'school_year_id' => $syViejo->id, 'numero' => 1, 'nombre' => 'P1 2018',
            'fecha_inicio' => '2018-08-01', 'fecha_fin' => '2018-10-31', 'activo' => false, 'cerrado' => false,
        ]);
        $asigVieja = $this->nuevaAsignacion($syViejo, 'DV0');
        $claseVieja = ClaseVirtual::create(['asignacion_id' => $asigVieja->id, 'nombre' => 'Aula 2018', 'activo' => true]);
        MaterialClase::create([
            'clase_virtual_id' => $claseVieja->id, 'titulo' => 'Tarea 2018', 'tipo' => 'tarea',
            'puntos' => 20, 'periodo_id' => $periodoViejo->id, 'publicado' => true,
        ]);
        // Material del año actual en el aula vigente (para comprobar que ese período SÍ se conserva)
        MaterialClase::create([
            'clase_virtual_id' => $this->clase->id, 'titulo' => 'Tarea vigente', 'tipo' => 'tarea',
            'puntos' => 10, 'periodo_id' => $this->periodo->id, 'publicado' => true,
        ]);

        $destino = $this->nuevaAsignacion($this->sy, 'DV2');

        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.duplicar', $claseVieja), ['nombre' => 'Copia', 'asignacion_id' => $destino->id])
            ->assertRedirect();
        $copia = ClaseVirtual::where('nombre', 'Copia')->firstOrFail();
        $this->assertNull(MaterialClase::where('clase_virtual_id', $copia->id)->where('titulo', 'Tarea 2018')->value('periodo_id'));

        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.duplicar', $this->clase), ['nombre' => 'Copia vigente', 'asignacion_id' => $destino->id])
            ->assertRedirect();
        $copia2 = ClaseVirtual::where('nombre', 'Copia vigente')->firstOrFail();
        $this->assertSame(
            $this->periodo->id,
            (int) MaterialClase::where('clase_virtual_id', $copia2->id)->where('titulo', 'Tarea vigente')->value('periodo_id')
        );
    }
}
