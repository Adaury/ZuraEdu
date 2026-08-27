<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Promocion;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recomendación 8 de docs/AUDITORIA_DON_BOSCO_ZURAEDU.md: test de regresión
 * para el cierre de año escolar (CierreAnoController), en particular para
 * cubrir el escenario que motivó la recomendación #22 — que `matriculas.estado`
 * acepte 'promovida'/'no_promovida' sin fallar (migración
 * 2026_08_26_000001_add_promocion_states_to_matriculas_enum) — y el traslado
 * masivo de fin de año con lockForUpdate (recomendación #7 de la auditoría).
 */
class CierreAnoRegressionTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        // Las rutas de cierre de año son todas POST; sin esto cada request
        // fallaría la verificación CSRF y Laravel redirigiría silenciosamente
        // a "back" (sin Referer, a la raíz) en vez de ejecutar el controlador.
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');
        return $user;
    }

    /** Crea un año escolar activo con un grupo, una asignación académica y un estudiante matriculado. */
    private function crearEscenario(): array
    {
        $schoolYear = SchoolYear::create([
            'nombre'       => '20' . random_int(26, 99) . '-A',
            'fecha_inicio' => '2025-08-01',
            'fecha_fin'    => '2026-06-30',
            'activo'       => true,
        ]);

        self::$nivel++;
        $grado = Grado::create([
            'nombre' => "Grado " . self::$nivel, 'nivel' => self::$nivel, 'orden' => self::$nivel, 'ciclo' => 'primer_ciclo', 'activo' => true,
        ]);
        // La migración 2026_03_19_000008_seed_secciones_a_to_z ya crea las secciones A-Z.
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);

        $grupo = Grupo::create([
            'school_year_id' => $schoolYear->id,
            'grado_id'       => $grado->id,
            'seccion_id'     => $seccion->id,
            'activo'         => true,
        ]);

        $estudiante = Estudiante::factory()->create();

        $matricula = Matricula::create([
            'school_year_id'  => $schoolYear->id,
            'estudiante_id'   => $estudiante->id,
            'grupo_id'        => $grupo->id,
            'fecha_matricula' => '2025-08-15',
            'numero_orden'    => 1,
            'estado'          => 'activa',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'AS' . $grupo->id, 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);

        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id,
            'grupo_id'       => $grupo->id,
            'asignatura_id'  => $asignatura->id,
            'activo'         => true,
            'area'           => 'academica',
        ]);

        return compact('schoolYear', 'grado', 'seccion', 'grupo', 'estudiante', 'matricula', 'asignatura', 'asignacion');
    }

    // =========================================================================
    //  EJECUTAR CIERRE — cálculo de promoción y persistencia del estado
    // =========================================================================

    /** Regresión clave: matricula.estado debe aceptar 'promovida' sin error de ENUM. */
    public function test_ejecutar_marca_promovido_y_actualiza_estado_de_matricula(): void
    {
        $e = $this->crearEscenario();
        CalificacionAcademica::create([
            'matricula_id'   => $e['matricula']->id,
            'asignacion_id'  => $e['asignacion']->id,
            'school_year_id' => $e['schoolYear']->id,
            'nota_final'     => 85,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id])
            ->assertRedirect(route('admin.cierre-ano.index'))
            ->assertSessionHas('success');

        $promocion = Promocion::where('matricula_id', $e['matricula']->id)->first();
        $this->assertNotNull($promocion);
        $this->assertSame('promovido', $promocion->estado);
        $this->assertEquals(85.0, $promocion->promedio_final);

        // La regresión que motivó esta prueba: esto fallaba con SQLSTATE 22001 /
        // "Data truncated" antes de ampliar el ENUM de matriculas.estado.
        $this->assertSame('promovida', $e['matricula']->fresh()->estado);
    }

    /** Regresión clave: matricula.estado debe aceptar 'no_promovida' sin error de ENUM. */
    public function test_ejecutar_marca_no_promovido_cuando_promedio_es_menor_a_60(): void
    {
        $e = $this->crearEscenario();
        CalificacionAcademica::create([
            'matricula_id'   => $e['matricula']->id,
            'asignacion_id'  => $e['asignacion']->id,
            'school_year_id' => $e['schoolYear']->id,
            'nota_final'     => 45,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id])
            ->assertRedirect();

        $promocion = Promocion::where('matricula_id', $e['matricula']->id)->first();
        $this->assertSame('no_promovido', $promocion->estado);
        $this->assertSame('no_promovida', $e['matricula']->fresh()->estado);
    }

    public function test_ejecutar_marca_pendiente_cuando_no_hay_notas(): void
    {
        $e = $this->crearEscenario();
        // Sin ninguna CalificacionAcademica ni Calificacion creada.

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id])
            ->assertRedirect();

        $promocion = Promocion::where('matricula_id', $e['matricula']->id)->first();
        $this->assertSame('pendiente', $promocion->estado);
        $this->assertNull($promocion->promedio_final);
    }

    /** Cuando hay notas académicas y técnicas para la misma matrícula, la académica gana (no se mezclan). */
    public function test_ejecutar_prioriza_calificacion_academica_sobre_tecnica(): void
    {
        $e = $this->crearEscenario();
        CalificacionAcademica::create([
            'matricula_id'   => $e['matricula']->id,
            'asignacion_id'  => $e['asignacion']->id,
            'school_year_id' => $e['schoolYear']->id,
            'nota_final'     => 90,
        ]);

        $periodo = Periodo::create([
            'school_year_id' => $e['schoolYear']->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => true,
        ]);
        Calificacion::create([
            'matricula_id'  => $e['matricula']->id,
            'asignacion_id' => $e['asignacion']->id,
            'periodo_id'    => $periodo->id,
            'nota_final'    => 40,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id]);

        $promocion = Promocion::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(90.0, $promocion->promedio_final, 'Debe usar la nota académica (90), no mezclarla con la técnica (40).');
        $this->assertSame('promovido', $promocion->estado);
    }

    public function test_ejecutar_desactiva_el_school_year(): void
    {
        $e = $this->crearEscenario();

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id]);

        $this->assertFalse($e['schoolYear']->fresh()->activo);
    }

    public function test_ejecutar_falla_si_el_school_year_ya_no_esta_activo(): void
    {
        $e = $this->crearEscenario();
        $e['schoolYear']->update(['activo' => false]);

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Promocion::count(), 'No debe crear promociones si el año ya estaba cerrado.');
        $this->assertSame('activa', $e['matricula']->fresh()->estado, 'La matrícula no debe modificarse si el cierre no se ejecuta.');
    }

    public function test_usuario_sin_rol_autorizado_no_puede_ejecutar_cierre(): void
    {
        $e = $this->crearEscenario();
        // Secretaría sí entra al panel admin (EnsureAdminAccess) pero no tiene
        // acceso a Dirección (gate 'acceso-direccion' de la ruta), a diferencia
        // de un Docente, a quien EnsureAdminAccess redirige a su portal antes
        // de llegar siquiera a esa verificación.
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Secretaría');

        $this->actingAs($user)
            ->post(route('admin.cierre-ano.ejecutar'), ['school_year_id' => $e['schoolYear']->id])
            ->assertForbidden();

        $this->assertSame(0, Promocion::count());
        $this->assertTrue($e['schoolYear']->fresh()->activo, 'El año no debe cerrarse si quien lo intenta no está autorizado.');
    }

    // =========================================================================
    //  TRASLADO MASIVO DE FIN DE AÑO
    // =========================================================================

    public function test_traslado_crea_matriculas_en_el_ano_nuevo_con_orden_secuencial(): void
    {
        $base = $this->crearEscenario();
        $anoNuevo = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-B', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => false,
        ]);
        $grupoNuevo = Grupo::create([
            'school_year_id' => $anoNuevo->id, 'grado_id' => $base['grado']->id, 'seccion_id' => $base['seccion']->id, 'activo' => true,
        ]);

        $estudiante2 = Estudiante::factory()->create();
        $matricula2  = Matricula::create([
            'school_year_id' => $base['schoolYear']->id, 'estudiante_id' => $estudiante2->id, 'grupo_id' => $base['grupo']->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 2, 'estado' => 'promovida',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar-traslado'), [
                'ano_nuevo_id' => $anoNuevo->id,
                'traslados'    => [
                    ['estudiante_id' => $base['estudiante']->id, 'grupo_id' => $grupoNuevo->id],
                    ['estudiante_id' => $estudiante2->id,        'grupo_id' => $grupoNuevo->id],
                ],
            ])
            ->assertRedirect(route('admin.cierre-ano.index'))
            ->assertSessionHas('success');

        $nuevas = Matricula::where('school_year_id', $anoNuevo->id)->orderBy('numero_orden')->get();
        $this->assertCount(2, $nuevas);
        $this->assertEqualsCanonicalizing([1, 2], $nuevas->pluck('numero_orden')->all());
        $this->assertEqualsCanonicalizing(
            [$base['estudiante']->id, $estudiante2->id],
            $nuevas->pluck('estudiante_id')->all()
        );
    }

    /** Un estudiante ya trasladado (matriculado en el año nuevo) no debe duplicarse. */
    public function test_traslado_omite_estudiante_ya_matriculado_en_el_ano_nuevo(): void
    {
        $base = $this->crearEscenario();
        $anoNuevo = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-C', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => false,
        ]);
        $grupoNuevo = Grupo::create([
            'school_year_id' => $anoNuevo->id, 'grado_id' => $base['grado']->id, 'seccion_id' => $base['seccion']->id, 'activo' => true,
        ]);

        // Ya fue trasladado manualmente antes.
        Matricula::create([
            'school_year_id' => $anoNuevo->id, 'estudiante_id' => $base['estudiante']->id, 'grupo_id' => $grupoNuevo->id,
            'fecha_matricula' => now()->toDateString(), 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.cierre-ano.ejecutar-traslado'), [
                'ano_nuevo_id' => $anoNuevo->id,
                'traslados'    => [
                    ['estudiante_id' => $base['estudiante']->id, 'grupo_id' => $grupoNuevo->id],
                ],
            ])
            ->assertRedirect(route('admin.cierre-ano.index'))
            ->assertSessionHas('success');

        $this->assertSame(
            1,
            Matricula::where('school_year_id', $anoNuevo->id)->where('estudiante_id', $base['estudiante']->id)->count(),
            'No debe crear una segunda matrícula para un estudiante ya trasladado.'
        );
    }
}
