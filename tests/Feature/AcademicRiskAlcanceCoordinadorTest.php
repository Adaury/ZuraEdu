<?php

namespace Tests\Feature;

use App\Models\AcademicRiskScore;
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
 * Risk Score admin: los coordinadores de ciclo solo ven estudiantes, grupos y
 * resumen de su ciclo; Administrador/Director/Coordinador Académico ven todo.
 */
class AcademicRiskAlcanceCoordinadorTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private SchoolYear $sy;
    private Estudiante $estPrimer;
    private Estudiante $estSegundo;
    private AcademicRiskScore $scorePrimer;
    private AcademicRiskScore $scoreSegundo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Riesgo Alcance',
            'dominio'            => 'ra' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $this->tenant);

        $this->sy = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);

        [$this->estPrimer,  $this->scorePrimer]  = $this->estudiante('primer_ciclo', 1);
        [$this->estSegundo, $this->scoreSegundo] = $this->estudiante('segundo_ciclo', 2);
    }

    private function estudiante(string $ciclo, int $orden): array
    {
        $grado   = Grado::create(['nombre' => 'Grado RA' . random_int(1, 99999), 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => $orden, 'ciclo' => $ciclo, 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $est     = Estudiante::factory()->create();
        Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => $orden, 'estado' => 'activa',
        ]);
        $score = AcademicRiskScore::create([
            'tenant_id' => $this->tenant->id, 'estudiante_id' => $est->id, 'school_year_id' => $this->sy->id,
            'score' => 70, 'nivel' => 'alto',
            'dim_academico' => 50, 'dim_asistencia' => 20, 'dim_disciplina' => 0, 'dim_tendencia' => 0,
            'materias_en_riesgo' => 1, 'total_materias' => 3, 'promedio_general' => 66, 'pct_asistencia' => 90,
            'tardanzas' => 0, 'faltas_leves' => 0, 'faltas_graves' => 0, 'suspensiones' => 0,
            'calculado_en' => now(),
        ]);

        return [$est, $score];
    }

    private function user(string $rol): User
    {
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id]);
        $u->assignRole($rol);

        return $u;
    }

    public function test_coordinador_primer_ciclo_solo_ve_su_ciclo_en_el_listado(): void
    {
        $r = $this->actingAs($this->user('Coordinador Primer Ciclo'))->get(route('admin.riesgo.index'));

        $r->assertOk();
        $ids = $r->viewData('scores')->pluck('estudiante_id')->all();
        $this->assertSame([$this->estPrimer->id], $ids);
        $this->assertSame(1, (int) $r->viewData('totalEst'));
        $this->assertCount(1, $r->viewData('grupos'));
    }

    public function test_coordinador_segundo_ciclo_solo_ve_su_ciclo_en_el_listado(): void
    {
        $r = $this->actingAs($this->user('Coordinador Segundo Ciclo'))->get(route('admin.riesgo.index'));

        $this->assertSame([$this->estSegundo->id], $r->viewData('scores')->pluck('estudiante_id')->all());
    }

    public function test_coordinador_no_puede_abrir_ni_recalcular_estudiante_de_otro_ciclo(): void
    {
        $coord = $this->user('Coordinador Primer Ciclo');

        $this->actingAs($coord)->get(route('admin.riesgo.show', $this->scorePrimer))->assertOk();
        $this->actingAs($coord)->get(route('admin.riesgo.show', $this->scoreSegundo))->assertForbidden();

        $this->actingAs($coord)
            ->postJson(route('admin.riesgo.recalcular-uno', $this->estSegundo->id))
            ->assertForbidden();
        $this->actingAs($coord)
            ->postJson(route('admin.riesgo.recalcular-uno', $this->estPrimer->id))
            ->assertOk();
    }

    public function test_filtro_por_grupo_ajeno_no_filtra_a_otro_ciclo(): void
    {
        $grupoSegundo = Matricula::where('estudiante_id', $this->estSegundo->id)->first()->grupo_id;

        $r = $this->actingAs($this->user('Coordinador Primer Ciclo'))
            ->get(route('admin.riesgo.index', ['grupo_id' => $grupoSegundo]));

        $this->assertCount(0, $r->viewData('scores'));
    }

    public function test_inicial_va_con_primer_ciclo_y_bachillerato_con_segundo(): void
    {
        [$estInicial]    = $this->estudiante('inicial', 3);
        [$estBachiller]  = $this->estudiante('bachillerato', 4);

        $primer = $this->actingAs($this->user('Coordinador Primer Ciclo'))->get(route('admin.riesgo.index'));
        $this->assertEqualsCanonicalizing(
            [$this->estPrimer->id, $estInicial->id],
            $primer->viewData('scores')->pluck('estudiante_id')->all()
        );

        $segundo = $this->actingAs($this->user('Coordinador Segundo Ciclo'))->get(route('admin.riesgo.index'));
        $this->assertEqualsCanonicalizing(
            [$this->estSegundo->id, $estBachiller->id],
            $segundo->viewData('scores')->pluck('estudiante_id')->all()
        );
    }

    public function test_roles_de_alcance_total_ven_ambos_ciclos(): void
    {
        foreach (['Administrador', 'Director', 'Coordinador Académico'] as $rol) {
            $r = $this->actingAs($this->user($rol))->get(route('admin.riesgo.index'));

            $this->assertEqualsCanonicalizing(
                [$this->estPrimer->id, $this->estSegundo->id],
                $r->viewData('scores')->pluck('estudiante_id')->all(),
                "El rol {$rol} debe ver ambos ciclos"
            );
        }
    }
}
