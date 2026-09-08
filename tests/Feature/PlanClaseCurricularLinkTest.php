<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\PlanClase;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\Planificacion;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cobertura de la referencia curricular opcional agregada a PlanClase
 * (planif_unidad_id -> PlanifUnidad para área académica, planificacion_id ->
 * Planificacion tipo RA para área técnica), tanto en el panel Admin como en
 * el Portal Docente, incluyendo los límites de seguridad multi-tenant y de
 * autorización por docente que motivaron el endurecimiento de
 * resolverReferenciaCurricular() en PlanClaseDocenteController.
 */
class PlanClaseCurricularLinkTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        // El driver de caché de test (array) vive todo el proceso de la suite;
        // sin esto, SchoolYear::actual() de un test anterior puede filtrarse.
        Cache::flush();
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
    }

    private function crearTenant(): Tenant
    {
        self::$seq++;
        return Tenant::create([
            'nombre_institucion' => 'Institucion QA ' . self::$seq,
            'dominio'            => 'qatenant' . self::$seq . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'pro',
        ]);
    }

    /** Escenario académico completo (año, grado, sección, grupo, asignatura, docente+usuario, asignación) para un tenant. */
    private function crearEscenario(Tenant $tenant, string $area = 'academica'): array
    {
        self::$seq++;
        $n = self::$seq;

        app()->instance('tenant', $tenant);

        $schoolYear = SchoolYear::create([
            'nombre' => 'SY-QA-' . $n, 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $grado   = Grado::create(['nombre' => 'Grado QA ' . $n, 'nivel' => 100 + $n, 'ciclo' => 'primer_ciclo', 'orden' => 100 + $n, 'activo' => true]);
        $seccion = Seccion::create(['nombre' => 'QS' . $n, 'orden' => $n]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'QA' . $n, 'nombre' => 'Asignatura QA ' . $n, 'area' => $area, 'activo' => true]);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $user->id, 'area' => $area]);

        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => $docente->id, 'activo' => true, 'area' => $area,
        ]);

        return compact('schoolYear', 'grado', 'seccion', 'grupo', 'asignatura', 'user', 'docente', 'asignacion');
    }

    private function crearPlanifUnidad(Tenant $tenant, Asignacion $asignacion): PlanifUnidad
    {
        app()->instance('tenant', $tenant);
        $anual = PlanifAnual::create([
            'tenant_id' => $tenant->id, 'docente_id' => $asignacion->docente_id, 'asignacion_id' => $asignacion->id,
            'school_year_id' => $asignacion->school_year_id, 'titulo' => 'Anual QA',
        ]);
        return PlanifUnidad::create([
            'tenant_id' => $tenant->id, 'planif_anual_id' => $anual->id, 'numero' => 1,
            'titulo' => 'Unidad QA', 'periodo' => 'P1', 'semanas' => 4,
        ]);
    }

    private function crearPlanificacionRa(Tenant $tenant, Asignacion $asignacion): Planificacion
    {
        app()->instance('tenant', $tenant);
        return Planificacion::create([
            'tenant_id' => $tenant->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $asignacion->school_year_id,
            'tipo' => 'ra', 'denominacion' => 'RA QA', 'mf_codigo' => 'MF1', 'uc_codigo' => 'UC1',
        ]);
    }

    private function crearAdmin(Tenant $tenant): User
    {
        app()->instance('tenant', $tenant);
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Administrador');
        return $admin;
    }

    // ============================================================
    //  ADMIN — PlanClaseController
    // ============================================================

    public function test_admin_academica_vincula_planif_unidad_del_mismo_tenant(): void
    {
        $tenant   = $this->crearTenant();
        $e        = $this->crearEscenario($tenant, 'academica');
        $unidad   = $this->crearPlanifUnidad($tenant, $e['asignacion']);
        $admin    = $this->crearAdmin($tenant);

        $resp = $this->actingAs($admin)->post(route('admin.planes-clase.store'), [
            'titulo' => 'Plan Admin Academica', 'area' => 'academica', 'tipo_plan' => 'semanal',
            'asignacion_id' => $e['asignacion']->id, 'planif_unidad_id' => $unidad->id,
        ]);

        $plan = PlanClase::where('titulo', 'Plan Admin Academica')->first();
        $this->assertNotNull($plan, 'El plan debe crearse.');
        $resp->assertRedirect(route('admin.planes-clase.show', $plan));
        $this->assertSame($unidad->id, $plan->planif_unidad_id);
        $this->assertNull($plan->planificacion_id);
    }

    public function test_admin_tecnica_vincula_planificacion_ra_del_mismo_tenant(): void
    {
        $tenant = $this->crearTenant();
        $e      = $this->crearEscenario($tenant, 'tecnica');
        $ra     = $this->crearPlanificacionRa($tenant, $e['asignacion']);
        $admin  = $this->crearAdmin($tenant);

        $resp = $this->actingAs($admin)->post(route('admin.planes-clase.store'), [
            'titulo' => 'Plan Admin Tecnica', 'area' => 'tecnica', 'tipo_plan' => 'semanal',
            'asignacion_id' => $e['asignacion']->id, 'planificacion_id' => $ra->id,
        ]);

        $plan = PlanClase::where('titulo', 'Plan Admin Tecnica')->first();
        $this->assertNotNull($plan);
        $resp->assertRedirect(route('admin.planes-clase.show', $plan));
        $this->assertSame($ra->id, $plan->planificacion_id);
        $this->assertNull($plan->planif_unidad_id);
    }

    /** Seguridad multi-tenant: una PlanifUnidad de OTRO tenant nunca debe poder vincularse. */
    public function test_admin_no_puede_vincular_planif_unidad_de_otro_tenant(): void
    {
        $tenantA = $this->crearTenant();
        $tenantB = $this->crearTenant();
        $eA      = $this->crearEscenario($tenantA, 'academica');
        $eB      = $this->crearEscenario($tenantB, 'academica');
        $unidadB = $this->crearPlanifUnidad($tenantB, $eB['asignacion']); // pertenece al tenant B
        $adminA  = $this->crearAdmin($tenantA);
        app()->forgetInstance('tenant');

        $this->actingAs($adminA)->post(route('admin.planes-clase.store'), [
            'titulo' => 'Plan Cross Tenant', 'area' => 'academica', 'tipo_plan' => 'semanal',
            'asignacion_id' => $eA['asignacion']->id, 'planif_unidad_id' => $unidadB->id,
        ]);

        $plan = PlanClase::where('titulo', 'Plan Cross Tenant')->first();
        $this->assertNotNull($plan);
        $this->assertNull($plan->planif_unidad_id, 'Una unidad de OTRO tenant jamas debe quedar vinculada.');
    }

    /** Seguridad multi-tenant, lado técnico: idem para Planificacion RA de otro tenant. */
    public function test_admin_no_puede_vincular_planificacion_ra_de_otro_tenant(): void
    {
        $tenantA = $this->crearTenant();
        $tenantB = $this->crearTenant();
        $eA      = $this->crearEscenario($tenantA, 'tecnica');
        $eB      = $this->crearEscenario($tenantB, 'tecnica');
        $raB     = $this->crearPlanificacionRa($tenantB, $eB['asignacion']);
        $adminA  = $this->crearAdmin($tenantA);
        app()->forgetInstance('tenant');

        $this->actingAs($adminA)->post(route('admin.planes-clase.store'), [
            'titulo' => 'Plan Cross Tenant Tecnica', 'area' => 'tecnica', 'tipo_plan' => 'semanal',
            'asignacion_id' => $eA['asignacion']->id, 'planificacion_id' => $raB->id,
        ]);

        $plan = PlanClase::where('titulo', 'Plan Cross Tenant Tecnica')->first();
        $this->assertNotNull($plan);
        $this->assertNull($plan->planificacion_id, 'Un RA de OTRO tenant jamas debe quedar vinculado.');
    }

    /** Regresión: create/edit/show/index no deben 500 (bug real de eager-loading grupo.grado/seccion corregido en esta misma sesión). */
    public function test_admin_create_edit_show_index_responden_200_con_asignacion_vinculada(): void
    {
        $tenant = $this->crearTenant();
        $e      = $this->crearEscenario($tenant, 'academica');
        $unidad = $this->crearPlanifUnidad($tenant, $e['asignacion']);
        $admin  = $this->crearAdmin($tenant);

        $this->actingAs($admin)->get(route('admin.planes-clase.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.planes-clase.store'), [
            'titulo' => 'Plan Regresion 500', 'area' => 'academica', 'tipo_plan' => 'semanal',
            'asignacion_id' => $e['asignacion']->id, 'planif_unidad_id' => $unidad->id,
        ]);
        $plan = PlanClase::where('titulo', 'Plan Regresion 500')->first();

        $this->actingAs($admin)->get(route('admin.planes-clase.show', $plan))->assertOk();
        $this->actingAs($admin)->get(route('admin.planes-clase.edit', $plan))->assertOk();
        $this->actingAs($admin)->get(route('admin.planes-clase.index'))->assertOk();
    }

    /** Guardia anti-N+1: listar 6 planes con asignación no debe multiplicar las queries por fila. */
    public function test_admin_index_no_produce_n_mas_1_al_listar_varios_planes(): void
    {
        $tenant = $this->crearTenant();
        $e      = $this->crearEscenario($tenant, 'academica');
        $admin  = $this->crearAdmin($tenant);

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($admin)->post(route('admin.planes-clase.store'), [
                'titulo' => 'Plan N+1 #' . $i, 'area' => 'academica', 'tipo_plan' => 'semanal',
                'asignacion_id' => $e['asignacion']->id,
            ]);
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.planes-clase.index'))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Con eager-loading correcto el conteo de queries es constante (no escala con 6 filas);
        // un N+1 real por fila hubiera producido >6 queries adicionales solo para esta tabla.
        $this->assertLessThan(25, $queries, "Se esperaban pocas queries fijas, se ejecutaron {$queries} (posible N+1).");
    }

    // ============================================================
    //  PORTAL DOCENTE — PlanClaseDocenteController
    // ============================================================

    public function test_portal_docente_academica_vincula_planif_unidad_de_su_propia_asignacion(): void
    {
        $tenant = $this->crearTenant();
        $e      = $this->crearEscenario($tenant, 'academica');
        $unidad = $this->crearPlanifUnidad($tenant, $e['asignacion']);

        $resp = $this->actingAs($e['user'])->post(
            route('portal.docente.planes-clase.store', $e['asignacion']),
            ['titulo' => 'Plan Portal Academica', 'tipo_plan' => 'semanal', 'planif_unidad_id' => $unidad->id]
        );

        $plan = PlanClase::where('titulo', 'Plan Portal Academica')->first();
        $this->assertNotNull($plan);
        $resp->assertRedirect(route('portal.docente.planes-clase.show', [$e['asignacion'], $plan]));
        $this->assertSame($unidad->id, $plan->planif_unidad_id);
    }

    public function test_portal_docente_tecnica_vincula_planificacion_ra_de_su_propia_asignacion(): void
    {
        $tenant = $this->crearTenant();
        $e      = $this->crearEscenario($tenant, 'tecnica');
        $ra     = $this->crearPlanificacionRa($tenant, $e['asignacion']);

        $resp = $this->actingAs($e['user'])->post(
            route('portal.docente.planes-clase.store', $e['asignacion']),
            ['titulo' => 'Plan Portal Tecnica', 'tipo_plan' => 'semanal', 'planificacion_id' => $ra->id]
        );

        $plan = PlanClase::where('titulo', 'Plan Portal Tecnica')->first();
        $this->assertNotNull($plan);
        $resp->assertRedirect(route('portal.docente.planes-clase.show', [$e['asignacion'], $plan]));
        $this->assertSame($ra->id, $plan->planificacion_id);
    }

    /**
     * Gap real encontrado y corregido en esta sesión: un docente con DOS
     * asignaciones académicas propias no debe poder vincular el plan de la
     * asignación X a una PlanifUnidad que pertenece a su OTRA asignación Y.
     */
    public function test_portal_docente_no_puede_vincular_unidad_de_otra_asignacion_propia(): void
    {
        $tenant = $this->crearTenant();
        $eX     = $this->crearEscenario($tenant, 'academica');
        $eY     = $this->crearEscenario($tenant, 'academica');
        $unidadY = $this->crearPlanifUnidad($tenant, $eY['asignacion']); // pertenece a la asignación Y, no X

        // Mismo docente en ambas asignaciones para aislar la variable bajo prueba (asignación, no autoría).
        Asignacion::where('id', $eY['asignacion']->id)->update(['docente_id' => $eX['docente']->id]);

        $this->actingAs($eX['user'])->post(
            route('portal.docente.planes-clase.store', $eX['asignacion']),
            ['titulo' => 'Plan Cross Asignacion', 'tipo_plan' => 'semanal', 'planif_unidad_id' => $unidadY->id]
        );

        $plan = PlanClase::where('titulo', 'Plan Cross Asignacion')->first();
        $this->assertNotNull($plan);
        $this->assertNull($plan->planif_unidad_id, 'Una unidad de OTRA asignacion (aunque del mismo docente) no debe quedar vinculada.');
    }

    /** Seguridad multi-tenant desde el portal docente: RA de otro tenant nunca debe vincularse. */
    public function test_portal_docente_no_puede_vincular_referencia_de_otro_tenant(): void
    {
        $tenantA = $this->crearTenant();
        $tenantB = $this->crearTenant();
        $eA      = $this->crearEscenario($tenantA, 'tecnica');
        $eB      = $this->crearEscenario($tenantB, 'tecnica');
        $raB     = $this->crearPlanificacionRa($tenantB, $eB['asignacion']);

        // Simula una petición HTTP genuina: nada debe quedar vinculado en el
        // contenedor de una llamada de setup anterior (así arranca cada
        // request real en producción, sin Octane).
        app()->forgetInstance('tenant');

        $resp = $this->actingAs($eA['user'])->post(
            route('portal.docente.planes-clase.store', $eA['asignacion']),
            ['titulo' => 'Plan Portal Cross Tenant', 'tipo_plan' => 'semanal', 'planificacion_id' => $raB->id]
        );
        $resp->assertRedirect();

        $plan = PlanClase::where('titulo', 'Plan Portal Cross Tenant')->first();
        $this->assertNotNull($plan);
        $this->assertNull($plan->planificacion_id);
    }

    /**
     * Regresión CRÍTICA: SubstituteBindings corría antes que ResolveTenant en
     * Kernel.php, por lo que el binding implícito de {planesClase} se
     * resolvía SIN scope de tenant activo — un admin del tenant A podía
     * potencialmente recibir un PlanClase del tenant B, ya que
     * Admin\PlanClaseController::show() no hace ninguna verificación de
     * propiedad más allá de confiar en el modelo inyectado por la ruta.
     */
    public function test_admin_no_puede_ver_plan_de_otro_tenant_via_binding_de_ruta(): void
    {
        $tenantA = $this->crearTenant();
        $tenantB = $this->crearTenant();
        $eB      = $this->crearEscenario($tenantB, 'academica');

        app()->instance('tenant', $tenantB);
        $adminB = User::factory()->create(['activo' => true]);
        $adminB->assignRole('Administrador');
        $this->actingAs($adminB)->post(route('admin.planes-clase.store'), [
            'titulo' => 'Plan Exclusivo Tenant B', 'area' => 'academica', 'tipo_plan' => 'semanal',
            'asignacion_id' => $eB['asignacion']->id,
        ]);
        $planB = PlanClase::where('titulo', 'Plan Exclusivo Tenant B')->first();
        $this->assertNotNull($planB);

        $adminA = $this->crearAdmin($tenantA);
        // Nada debe quedar vinculado en el contenedor antes de esta petición —
        // así arranca toda petición real en producción.
        app()->forgetInstance('tenant');

        $this->actingAs($adminA)->get(route('admin.planes-clase.show', $planB))->assertNotFound();
    }

    /** Autorización: un docente no puede ver el plan de una asignación que no es suya. */
    public function test_portal_docente_no_puede_ver_plan_de_otro_docente(): void
    {
        $tenant = $this->crearTenant();
        $eDueno = $this->crearEscenario($tenant, 'academica');
        $eOtro  = $this->crearEscenario($tenant, 'academica');

        $this->actingAs($eDueno['user'])->post(
            route('portal.docente.planes-clase.store', $eDueno['asignacion']),
            ['titulo' => 'Plan Privado Docente', 'tipo_plan' => 'semanal']
        );
        $plan = PlanClase::where('titulo', 'Plan Privado Docente')->first();

        $this->actingAs($eOtro['user'])
            ->get(route('portal.docente.planes-clase.show', [$eDueno['asignacion'], $plan]))
            ->assertForbidden();
    }

    /** Autorización: un docente no puede crear un plan para una asignación ajena, ni siquiera manipulando la URL. */
    public function test_portal_docente_no_puede_crear_plan_para_asignacion_ajena(): void
    {
        $tenant = $this->crearTenant();
        $eDueno = $this->crearEscenario($tenant, 'academica');
        $eOtro  = $this->crearEscenario($tenant, 'academica');

        $this->actingAs($eOtro['user'])
            ->post(route('portal.docente.planes-clase.store', $eDueno['asignacion']), [
                'titulo' => 'Plan Intruso', 'tipo_plan' => 'semanal',
            ])
            ->assertForbidden();

        $this->assertNull(PlanClase::where('titulo', 'Plan Intruso')->first(), 'No debe haberse creado nada.');
    }

    public function test_portal_docente_create_show_index_responden_200(): void
    {
        $tenant = $this->crearTenant();
        $e      = $this->crearEscenario($tenant, 'academica');
        $unidad = $this->crearPlanifUnidad($tenant, $e['asignacion']);

        $this->actingAs($e['user'])->get(route('portal.docente.planes-clase.create', $e['asignacion']))->assertOk();

        $this->actingAs($e['user'])->post(
            route('portal.docente.planes-clase.store', $e['asignacion']),
            ['titulo' => 'Plan Portal 200', 'tipo_plan' => 'semanal', 'planif_unidad_id' => $unidad->id]
        );
        $plan = PlanClase::where('titulo', 'Plan Portal 200')->first();

        $this->actingAs($e['user'])->get(route('portal.docente.planes-clase.show', [$e['asignacion'], $plan]))->assertOk();
        $this->actingAs($e['user'])->get(route('portal.docente.planes-clase.index', $e['asignacion']))->assertOk();
    }

    // ============================================================
    //  SUPERADMIN — TenantController (regresión withTrashed)
    // ============================================================

    /** Regresión: instituciones eliminadas (soft-delete) no deben listarse como activas. */
    public function test_superadmin_index_no_lista_tenants_eliminados(): void
    {
        $superAdminTenant = $this->crearTenant();
        $tenantEliminado  = $this->crearTenant();
        $tenantEliminado->delete(); // soft delete

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        app()->instance('tenant', $superAdminTenant);
        $superAdmin = User::factory()->create(['activo' => true]);
        $superAdmin->assignRole('super_admin');

        $resp = $this->actingAs($superAdmin)->get(route('superadmin.tenants.index'));
        $resp->assertOk();
        $resp->assertDontSee($tenantEliminado->nombre_institucion);

        $stats = $resp->viewData('stats');
        $tenants = $resp->viewData('tenants');
        $this->assertSame($stats['total'], $tenants->total(), 'El stat "Total" debe coincidir con las filas realmente listadas.');
    }
}
