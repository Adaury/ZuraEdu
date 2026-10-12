<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ZuraClass estudiante/padre: un material en borrador o programado a futuro, o un aula
 * desactivada, no se puede usar por URL directa; una entrega tardía cuenta como entregada
 * y no se reenvía sin límite; el padre no ve tareas en borrador como "pendientes".
 */
class ZuraClassEstudiantePadreTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $sy;
    private Estudiante $estudiante;
    private Matricula $matricula;
    private ClaseVirtual $clase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->sy = SchoolYear::create(['nombre' => '2026-EstPad', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado EP', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $docente = Docente::factory()->create();
        $this->estudiante = Estudiante::factory()->create();
        $this->estudiante->user->assignRole('Estudiante');
        $this->estudiante->user->update(['activo' => true]);
        $this->matricula = Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $this->estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'EP' . random_int(1000, 9999), 'nombre' => 'Sociales', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $this->clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula EP', 'activo' => true]);
    }

    private function tarea(array $extra = []): MaterialClase
    {
        return MaterialClase::create(array_merge([
            'clase_virtual_id' => $this->clase->id, 'titulo' => 'Tarea ' . random_int(1, 99999),
            'tipo' => 'tarea', 'puntos' => 10, 'publicado' => true,
        ], $extra));
    }

    private function entregar(MaterialClase $m)
    {
        return $this->actingAs($this->estudiante->user)
            ->post(route('portal.estudiante.classroom.entregar', [$this->clase, $m]), ['contenido' => 'Mi trabajo']);
    }

    public function test_no_se_entrega_ni_comenta_en_un_material_en_borrador_o_programado(): void
    {
        $borrador   = $this->tarea(['publicado' => false]);
        $programada = $this->tarea(['publicado' => true, 'publicar_en' => now()->addDays(3)]);

        foreach ([$borrador, $programada] as $m) {
            $this->entregar($m)->assertNotFound();
            $this->actingAs($this->estudiante->user)
                ->post(route('portal.estudiante.classroom.comentar', [$this->clase, $m]), ['contenido' => 'hola'])
                ->assertNotFound();
        }

        $this->assertSame(0, EntregaClassroom::count());
    }

    public function test_quiz_programado_a_futuro_no_se_puede_comenzar(): void
    {
        $m = $this->tarea(['tipo' => 'evaluacion', 'publicado' => true, 'publicar_en' => now()->addDay()]);

        $this->actingAs($this->estudiante->user)
            ->post(route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $m]))
            ->assertNotFound();
    }

    public function test_aula_desactivada_no_se_abre_ni_se_entrega_por_url(): void
    {
        $m = $this->tarea();
        $this->clase->update(['activo' => false]);

        $this->actingAs($this->estudiante->user)
            ->get(route('portal.estudiante.classroom.show', $this->clase))
            ->assertNotFound();
        $this->entregar($m)->assertNotFound();
    }

    public function test_una_entrega_tardia_no_se_reenvia_sin_limite_y_cuenta_como_entregada(): void
    {
        $m = $this->tarea(['fecha_limite' => now()->subDay(), 'permite_reentrega' => false]);

        $this->entregar($m);
        $entrega = EntregaClassroom::where('material_id', $m->id)->firstOrFail();
        $this->assertSame('atrasado', $entrega->estado);

        // Segundo intento: debe rechazarse (antes se aceptaba indefinidamente)
        $this->entregar($m)->assertSessionHasErrors('error');
        $this->assertSame(1, (int) $entrega->fresh()->intentos);

        // Ya no figura como pendiente en el dashboard
        $r = $this->actingAs($this->estudiante->user)
            ->getJson(route('portal.estudiante.classroom.pendientes'));
        $r->assertOk()->assertJson(['count' => 0, 'vencidas' => 0]);
    }

    public function test_el_padre_no_cuenta_tareas_en_borrador_como_pendientes(): void
    {
        $this->tarea(['titulo' => 'Visible']);
        $this->tarea(['titulo' => 'Borrador', 'publicado' => false]);

        $padreUser = \App\Models\User::factory()->create(['activo' => true]);
        $padreUser->assignRole('Representante');
        $rep = Representante::factory()->create(['user_id' => $padreUser->id]);
        $rep->estudiantes()->attach($this->estudiante->id, ['parentesco' => 'Madre', 'es_principal' => true]);

        $r = $this->actingAs($padreUser)
            ->get(route('portal.padre.hijo.classroom.index', $this->estudiante));
        $r->assertOk();

        $clase = $r->viewData('clases')->firstWhere('id', $this->clase->id);
        $this->assertSame(1, $clase->_tareas_total);
        $this->assertSame(1, $clase->_pendientes);

        // Aula desactivada: el padre tampoco la abre por URL
        $this->clase->update(['activo' => false]);
        $this->actingAs($padreUser)
            ->get(route('portal.padre.hijo.classroom.show', [$this->estudiante, $this->clase]))
            ->assertNotFound();
    }
}
