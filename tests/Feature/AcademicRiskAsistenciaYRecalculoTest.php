<?php

namespace Tests\Feature;

use App\Models\AcademicRiskScore;
use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AcademicRiskScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Risk Score: (1) la asistencia debe contar 'tarde' y 'excusa' (enum real de
 * asistencias.estado) como asistido; antes solo contaba 'presente'/'tardanza'
 * y 'tardanza' no existe en el enum. (2) recalcularUno no debe crear filas
 * para estudiantes ajenos al tenant ni sin matrícula activa.
 */
class AcademicRiskAsistenciaYRecalculoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function tenant(string $nombre): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => 'riesgo' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    /** @return array{sy: SchoolYear, grupo: Grupo, asig: Asignacion} */
    private function aula(): array
    {
        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-R', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado RK' . random_int(1, 99999), 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asig    = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id,
            'asignatura_id'  => Asignatura::create(['codigo' => 'RK' . random_int(10000, 99999), 'nombre' => 'Materia RK', 'activo' => true])->id,
            'docente_id' => null, 'activo' => true, 'tipo_evaluacion' => 'componentes',
        ]);

        return compact('sy', 'grupo', 'asig');
    }

    private function matricular(SchoolYear $sy, Grupo $grupo, int $orden = 1): Matricula
    {
        return Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => $orden, 'estado' => 'activa',
        ]);
    }

    public function test_tarde_y_excusa_cuentan_como_asistido(): void
    {
        $this->tenant('Colegio Riesgo Asistencia');
        $a = $this->aula();
        $m = $this->matricular($a['sy'], $a['grupo']);
        $user = User::factory()->create();

        foreach (['presente', 'tarde', 'excusa', 'ausente'] as $i => $estado) {
            Asistencia::create([
                'matricula_id' => $m->id, 'asignacion_id' => $a['asig']->id,
                'fecha' => now()->subDays($i + 1), 'estado' => $estado, 'registrado_por' => $user->id,
            ]);
        }

        // 3 de 4 asistidos = 75 %. Con el bug eran 1 de 4 = 25 %.
        $individual = (new AcademicRiskScoreService())->calcularParaEstudiante($m->estudiante_id, $a['sy']->id);
        $this->assertEquals(75.0, $individual['pct_asistencia']);

        (new AcademicRiskScoreService())->calcularTodos($a['sy']->id);
        $masivo = AcademicRiskScore::where('estudiante_id', $m->estudiante_id)->first();
        $this->assertEquals(75.0, $masivo->pct_asistencia);
    }

    public function test_calculo_individual_ignora_matriculas_no_activas_como_el_masivo(): void
    {
        $this->tenant('Colegio Riesgo Activa');
        $a = $this->aula();
        $m = $this->matricular($a['sy'], $a['grupo']);
        $m->update(['estado' => 'retirada']);

        \App\Models\CalificacionAcademica::create([
            'matricula_id' => $m->id, 'asignacion_id' => $a['asig']->id, 'school_year_id' => $a['sy']->id,
            'nota_final' => 40, 'pct_asistencia' => 50,
        ]);

        $r = (new AcademicRiskScoreService())->calcularParaEstudiante($m->estudiante_id, $a['sy']->id);

        $this->assertNull($r['pct_asistencia']);     // antes tomaba el 50 de la matrícula retirada
        $this->assertSame(0, $r['total_materias']);
    }

    public function test_sin_datos_de_periodos_la_tendencia_no_suma_riesgo(): void
    {
        $this->tenant('Colegio Riesgo Tendencia');
        $a = $this->aula();
        $m = $this->matricular($a['sy'], $a['grupo']);

        $r = (new AcademicRiskScoreService())->calcularParaEstudiante($m->estudiante_id, $a['sy']->id);

        $this->assertEquals(0, $r['dim_tendencia']);
        $this->assertSame(0, $r['score']);
        $this->assertSame('sin_riesgo', $r['nivel']);
    }

    public function test_recalcular_uno_rechaza_estudiante_inexistente_o_de_otro_tenant(): void
    {
        $tenantA = $this->tenant('Colegio Riesgo A');
        $a = $this->aula();
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenantA->id]);
        $admin->assignRole('Administrador');

        // Estudiante matriculado en otro tenant
        $this->tenant('Colegio Riesgo B');
        $b = $this->aula();
        $ajeno = $this->matricular($b['sy'], $b['grupo']);

        // Volver al tenant A
        app()->instance('tenant', $tenantA);

        $this->actingAs($admin)
            ->postJson(route('admin.riesgo.recalcular-uno', $ajeno->estudiante_id))
            ->assertNotFound();

        $this->actingAs($admin)
            ->postJson(route('admin.riesgo.recalcular-uno', 99999999))
            ->assertNotFound();

        $this->assertSame(0, AcademicRiskScore::withoutGlobalScopes()->count());
    }

    public function test_recalcular_uno_rechaza_estudiante_sin_matricula_activa(): void
    {
        $tenant = $this->tenant('Colegio Riesgo C');
        $this->aula();
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');
        $sinMatricula = Estudiante::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.riesgo.recalcular-uno', $sinMatricula->id))
            ->assertNotFound();
    }

    public function test_rol_sin_acceso_recibe_403_y_no_calcula(): void
    {
        $tenant = $this->tenant('Colegio Riesgo E');
        $a = $this->aula();
        $m = $this->matricular($a['sy'], $a['grupo']);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Secretaría');

        $this->actingAs($user)
            ->postJson(route('admin.riesgo.recalcular-uno', $m->estudiante_id))
            ->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('admin.riesgo.calcular'))
            ->assertForbidden();

        $this->assertSame(0, AcademicRiskScore::count());
    }

    public function test_recalculos_manuales_quedan_en_el_registro_de_actividad(): void
    {
        $tenant = $this->tenant('Colegio Riesgo F');
        $a = $this->aula();
        $m = $this->matricular($a['sy'], $a['grupo']);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)->postJson(route('admin.riesgo.recalcular-uno', $m->estudiante_id))->assertOk();
        $this->actingAs($admin)->postJson(route('admin.riesgo.calcular'))->assertOk();

        $this->assertDatabaseHas('activity_logs', ['accion' => 'riesgo.recalculado', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('activity_logs', ['accion' => 'riesgo.recalculado_todos', 'user_id' => $admin->id]);
    }

    public function test_recalcular_uno_funciona_con_estudiante_matriculado(): void
    {
        $tenant = $this->tenant('Colegio Riesgo D');
        $a = $this->aula();
        $m = $this->matricular($a['sy'], $a['grupo']);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->postJson(route('admin.riesgo.recalcular-uno', $m->estudiante_id))
            ->assertOk()
            ->assertJsonStructure(['score', 'nivel', 'data']);

        $this->assertSame(1, AcademicRiskScore::where('estudiante_id', $m->estudiante_id)->count());
    }
}
