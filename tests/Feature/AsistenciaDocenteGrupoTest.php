<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las matrículas de la asistencia llegan del navegador. El docente solo puede marcar a los
 * estudiantes del grupo de SU asignación: antes se aceptaba cualquier matrícula del colegio (y,
 * si era "ausente", se avisaba por WhatsApp/notificación al representante de ese otro estudiante).
 */
class AsistenciaDocenteGrupoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{user: User, asig: Asignacion, propia: Matricula, ajena: Matricula} */
    private function escenario(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Grupo Asistencia',
            'dominio'            => 'colegiogrupo' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy   = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $user->id]);

        $grupos = [];
        foreach (['A', 'B'] as $i => $letra) {
            $grado   = Grado::create(['nombre' => 'Grado GA' . random_int(1, 99999), 'nivel' => 101 + $i, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
            $seccion = Seccion::firstOrCreate(['nombre' => $letra], ['orden' => $i + 1]);
            $grupos[] = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        }

        $asignatura = Asignatura::create(['codigo' => 'GA' . random_int(10000, 99999), 'nombre' => 'Materia GA', 'activo' => true]);
        $asig = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupos[0]->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $docente->id, 'activo' => true, 'tipo_evaluacion' => 'componentes',
        ]);

        $matricular = fn (Grupo $g, int $orden) => Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $g->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => $orden, 'estado' => 'activa',
        ]);

        return ['user' => $user, 'asig' => $asig, 'propia' => $matricular($grupos[0], 1), 'ajena' => $matricular($grupos[1], 1)];
    }

    public function test_guardar_asistencia_rechaza_todo_el_lote_si_una_matricula_es_de_otro_grupo(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['user'])
            ->post(route('portal.docente.asistencia.guardar', $e['asig']), [
                'fecha'   => '2025-09-10',
                'estados' => [$e['propia']->id => 'presente', $e['ajena']->id => 'ausente'],
            ])
            ->assertSessionHasErrors('estados');

        // No se escribió NADA, ni siquiera la matrícula legítima del mismo lote.
        $this->assertSame(0, Asistencia::count());
    }

    public function test_guardar_asistencia_acepta_matriculas_del_propio_grupo(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['user'])
            ->post(route('portal.docente.asistencia.guardar', $e['asig']), [
                'fecha'   => '2025-09-10',
                'estados' => [$e['propia']->id => 'presente'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('presente', Asistencia::where('matricula_id', $e['propia']->id)->value('estado'));
    }

    public function test_asistencia_rapida_rechaza_matricula_de_otro_grupo(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['user'])
            ->postJson(route('portal.docente.asistencia-rapida.guardar'), [
                'asignacion_id' => $e['asig']->id,
                'matricula_id'  => $e['ajena']->id,
                'estado'        => 'ausente',
                'fecha'         => '2025-09-10',
            ])
            ->assertStatus(422);

        $this->assertSame(0, Asistencia::count());
    }

    public function test_asistencia_rapida_acepta_matricula_del_propio_grupo(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['user'])
            ->postJson(route('portal.docente.asistencia-rapida.guardar'), [
                'asignacion_id' => $e['asig']->id,
                'matricula_id'  => $e['propia']->id,
                'estado'        => 'tarde',
                'fecha'         => '2025-09-10',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('tarde', Asistencia::where('matricula_id', $e['propia']->id)->value('estado'));
    }
}
