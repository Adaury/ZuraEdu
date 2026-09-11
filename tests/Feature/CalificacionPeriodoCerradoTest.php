<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría Don Bosco (Sección 4): cerrar un período era solo una etiqueta
 * visual -- ningún guardado de calificaciones comprobaba periodos.cerrado,
 * así que cualquiera con ingresar-calificaciones podía seguir modificando
 * notas de un período ya cerrado. Cubre técnica (CalificacionController::
 * guardar(), acotada a un solo periodo_id) y académica
 * (CalificacionAcademicaController::guardarAcademica(), guarda el año
 * completo en una fila -- un período cerrado congela solo sus columnas,
 * no bloquea el guardado de los períodos que sí siguen abiertos).
 */
class CalificacionPeriodoCerradoTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(Tenant $tenant): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');
        return $user;
    }

    /** @return array{tenant: Tenant, sy: SchoolYear, asignacion: Asignacion, matricula: Matricula} */
    private function crearEscenario(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Periodo Cerrado',
            'dominio'            => 'colegioperiodocerrado' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        self::$nivel++;
        $sy = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado PC' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'PC' . random_int(1000, 9999), 'nombre' => 'Materia PC', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => null, 'activo' => true, 'tipo_evaluacion' => 'componentes',
        ]);
        $estudiante = \App\Models\Estudiante::factory()->create();
        $matricula = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        app()->forgetInstance('tenant');

        return compact('tenant', 'sy', 'asignacion', 'matricula');
    }

    private function crearPeriodo(Tenant $tenant, SchoolYear $sy, int $numero, bool $cerrado): Periodo
    {
        app()->instance('tenant', $tenant);
        $periodo = Periodo::create([
            'school_year_id' => $sy->id, 'numero' => $numero, 'nombre' => "Período {$numero}",
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31',
            'activo' => ! $cerrado, 'cerrado' => $cerrado,
        ]);
        app()->forgetInstance('tenant');

        return $periodo;
    }

    // ── Técnica ──────────────────────────────────────────────────────────

    public function test_guardar_tecnica_rechaza_si_el_periodo_esta_cerrado(): void
    {
        $e = $this->crearEscenario();
        $periodo = $this->crearPeriodo($e['tenant'], $e['sy'], 1, cerrado: true);
        $admin = $this->admin($e['tenant']);

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar'), [
            'asignacion_id' => $e['asignacion']->id,
            'periodo_id'    => $periodo->id,
            'notas'         => [$e['matricula']->id => ['examen' => 90]],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('calificaciones', ['matricula_id' => $e['matricula']->id]);
    }

    public function test_guardar_tecnica_funciona_normalmente_si_el_periodo_esta_abierto(): void
    {
        $e = $this->crearEscenario();
        $periodo = $this->crearPeriodo($e['tenant'], $e['sy'], 1, cerrado: false);
        $admin = $this->admin($e['tenant']);

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar'), [
            'asignacion_id' => $e['asignacion']->id,
            'periodo_id'    => $periodo->id,
            'notas'         => [$e['matricula']->id => ['examen' => 90]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('calificaciones', ['matricula_id' => $e['matricula']->id, 'periodo_id' => $periodo->id]);
    }

    // ── Académica (guarda el año completo, congela solo columnas del período cerrado) ──

    public function test_guardar_academica_congela_las_columnas_del_periodo_cerrado_sin_bloquear_los_abiertos(): void
    {
        $e = $this->crearEscenario();
        $p1 = $this->crearPeriodo($e['tenant'], $e['sy'], 1, cerrado: true);
        $p2 = $this->crearPeriodo($e['tenant'], $e['sy'], 2, cerrado: false);
        $admin = $this->admin($e['tenant']);

        // Nota original de P1 ya guardada antes de que el período se cerrara.
        app()->instance('tenant', $e['tenant']);
        CalificacionAcademica::create([
            'matricula_id' => $e['matricula']->id, 'asignacion_id' => $e['asignacion']->id,
            'school_year_id' => $e['sy']->id, 'comp1_p1' => 80,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar-academica'), [
            'asignacion_id'  => $e['asignacion']->id,
            'school_year_id' => $e['sy']->id,
            'notas'          => [
                $e['matricula']->id => [
                    'comp1_p1' => 99, // P1 cerrado -- este intento de cambio debe ignorarse
                    'comp1_p2' => 70, // P2 abierto -- este sí debe guardarse
                ],
            ],
        ]);

        $response->assertOk();
        $reg = CalificacionAcademica::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(80.0, (float) $reg->comp1_p1, 'La columna del período cerrado no debe cambiar.');
        $this->assertEquals(70.0, (float) $reg->comp1_p2, 'La columna del período abierto sí debe guardarse.');
    }

    public function test_guardar_academica_funciona_normalmente_sin_periodos_cerrados(): void
    {
        $e = $this->crearEscenario();
        $this->crearPeriodo($e['tenant'], $e['sy'], 1, cerrado: false);
        $admin = $this->admin($e['tenant']);

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar-academica'), [
            'asignacion_id'  => $e['asignacion']->id,
            'school_year_id' => $e['sy']->id,
            'notas'          => [$e['matricula']->id => ['comp1_p1' => 85]],
        ]);

        $response->assertOk();
        $reg = CalificacionAcademica::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(85.0, (float) $reg->comp1_p1);
    }

    public function test_reabrir_el_periodo_permite_editar_de_nuevo(): void
    {
        $e = $this->crearEscenario();
        $periodo = $this->crearPeriodo($e['tenant'], $e['sy'], 1, cerrado: true);
        $admin = $this->admin($e['tenant']);

        app()->instance('tenant', $e['tenant']);
        $periodo->update(['cerrado' => false, 'activo' => true]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->postJson(route('admin.calificaciones.guardar'), [
            'asignacion_id' => $e['asignacion']->id,
            'periodo_id'    => $periodo->id,
            'notas'         => [$e['matricula']->id => ['examen' => 90]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('calificaciones', ['matricula_id' => $e['matricula']->id]);
    }
}
