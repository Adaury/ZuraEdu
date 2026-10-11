<?php

namespace Tests\Feature;

use App\Models\AcademicRiskScore;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Acceso al Risk Score por portales web y API móvil: cada quien ve solo lo
 * suyo (estudiante → su score; representante → solo sus hijos).
 */
class AcademicRiskPortalesApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private SchoolYear $sy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Riesgo Portales',
            'dominio'            => 'rp' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $this->tenant);

        $this->sy = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-P', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
    }

    /** Estudiante matriculado con score calculado. @return array{0: Estudiante, 1: AcademicRiskScore} */
    private function estudianteConScore(int $score, ?User $user = null): array
    {
        static $n = 0;
        $n++;
        $grado   = Grado::create(['nombre' => 'Grado RP' . random_int(1, 99999) . $n, 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $est = Estudiante::factory()->create($user ? ['user_id' => $user->id] : []);
        Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => $n, 'estado' => 'activa',
        ]);

        $ars = AcademicRiskScore::create([
            'tenant_id' => $this->tenant->id, 'estudiante_id' => $est->id, 'school_year_id' => $this->sy->id,
            'score' => $score, 'nivel' => AcademicRiskScore::nivelDesdeScore($score),
            'dim_academico' => 50, 'dim_asistencia' => 20, 'dim_disciplina' => 0, 'dim_tendencia' => 0,
            'materias_en_riesgo' => 2, 'total_materias' => 5, 'promedio_general' => 68.5, 'pct_asistencia' => 91.0,
            'tardanzas' => 0, 'faltas_leves' => 0, 'faltas_graves' => 0, 'suspensiones' => 0,
            'calculado_en' => now(),
        ]);

        return [$est, $ars];
    }

    private function user(string $rol): User
    {
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id]);
        $u->assignRole($rol);

        return $u;
    }

    // ── API estudiante ────────────────────────────────────────────────────

    public function test_api_estudiante_ve_su_propio_score(): void
    {
        $user = $this->user('Estudiante');
        [, $ars] = $this->estudianteConScore(55, $user);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/riesgo/mi-score')
            ->assertOk()
            ->assertJson(['calculado' => true, 'score' => 55, 'nivel' => 'moderado']);
    }

    public function test_api_mi_score_rechaza_a_quien_no_es_estudiante(): void
    {
        Sanctum::actingAs($this->user('Representante'));
        $this->getJson('/api/v1/riesgo/mi-score')->assertForbidden();
    }

    public function test_api_estudiante_sin_calcular_responde_calculado_false(): void
    {
        $user = $this->user('Estudiante');
        Estudiante::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/riesgo/mi-score')
            ->assertOk()
            ->assertJson(['calculado' => false]);
    }

    // ── API representante ────────────────────────────────────────────────

    public function test_api_representante_ve_a_su_hijo_pero_no_a_un_ajeno(): void
    {
        $user = $this->user('Representante');
        $rep  = Representante::factory()->create(['user_id' => $user->id]);

        [$hijo]  = $this->estudianteConScore(75);
        [$ajeno] = $this->estudianteConScore(90);
        $rep->estudiantes()->attach($hijo->id, ['parentesco' => 'Madre', 'es_principal' => true]);

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/riesgo/hijo/{$hijo->id}")
            ->assertOk()
            ->assertJson(['score' => 75, 'nivel' => 'alto']);

        $this->getJson("/api/v1/riesgo/hijo/{$ajeno->id}")->assertForbidden();
    }

    // ── Portal web ────────────────────────────────────────────────────────

    public function test_portal_padre_ve_riesgo_del_hijo_y_recibe_403_con_ajeno(): void
    {
        $user = $this->user('Representante');
        $rep  = Representante::factory()->create(['user_id' => $user->id]);

        [$hijo]  = $this->estudianteConScore(62);
        [$ajeno] = $this->estudianteConScore(85);
        $rep->estudiantes()->attach($hijo->id, ['parentesco' => 'Padre', 'es_principal' => true]);

        $this->actingAs($user)
            ->get(route('portal.padre.hijo.riesgo', $hijo))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('portal.padre.hijo.riesgo', $ajeno))
            ->assertForbidden();
    }

    public function test_portal_estudiante_ve_su_riesgo(): void
    {
        $user = $this->user('Estudiante');
        $this->estudianteConScore(45, $user);

        $this->actingAs($user)
            ->get(route('portal.estudiante.mi-riesgo'))
            ->assertOk();
    }

    public function test_portal_estudiante_sin_perfil_recibe_403(): void
    {
        $user = $this->user('Estudiante');   // sin registro Estudiante vinculado

        $this->actingAs($user)
            ->get(route('portal.estudiante.mi-riesgo'))
            ->assertForbidden();
    }

    public function test_ruta_admin_de_riesgo_no_es_accesible_para_estudiante_ni_representante(): void
    {
        foreach (['Estudiante', 'Representante'] as $rol) {
            $this->actingAs($this->user($rol))
                ->get(route('admin.riesgo.index'))
                ->assertRedirect();   // EnsureAdminAccess los saca de /admin antes de llegar al controlador
        }
    }
}
