<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\PlanClase;
use App\Models\Planificacion;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Roadmap de producto: conexión de solo lectura Planificación → ZuraClass
 * (ClassroomDocenteController::show(), tab Muro). Muestra la unidad de
 * PlanifUnidad activa hoy (mismo query que Vista Hoy Docente) y conteos de
 * PlanClase / Planificación técnica de la asignación, sin inferir fechas
 * para estas dos últimas (sus fechas son nullable, sin mapeo confiable) y
 * sin crear ninguna tabla ni FK nueva.
 */
class ClassroomPlanificacionConexionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearTenant(string $nombre): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    /** Crea tenant + año + docente + asignación + aula virtual, dentro de ese tenant. */
    private function crearAula(string $codigoAsig): array
    {
        $tenant = $this->crearTenant('Colegio ZC ' . $codigoAsig);
        app()->instance('tenant', $tenant);

        $sy = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-ZC', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado ' . $codigoAsig, 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $docenteUser = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $docenteUser->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $docenteUser->id]);

        $asignatura = Asignatura::create(['codigo' => $codigoAsig, 'nombre' => 'Materia ' . $codigoAsig, 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica', 'docente_id' => $docente->id,
        ]);
        $clase = ClaseVirtual::create(['asignacion_id' => $asignacion->id, 'nombre' => 'Aula ' . $codigoAsig, 'activo' => true]);

        return compact('tenant', 'sy', 'docenteUser', 'docente', 'asignacion', 'clase');
    }

    // ── 1. Unidad activa hoy ─────────────────────────────────────────────

    public function test_muestra_la_unidad_activa_hoy_de_planif_unidad(): void
    {
        $ctx = $this->crearAula('PZ1');

        $planifAnual = PlanifAnual::create(['asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id, 'titulo' => 'Plan Anual PZ1']);
        PlanifUnidad::create([
            'planif_anual_id' => $planifAnual->id, 'numero' => 1, 'titulo' => 'Unidad Vigente',
            'fecha_inicio' => today()->subDays(2), 'fecha_fin' => today()->addDays(5),
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertViewHas('planificacion', fn($p) => $p['unidad_hoy'] !== null && $p['unidad_hoy']->titulo === 'Unidad Vigente');
        $response->assertSee('Unidad Vigente');
        $response->assertSee('Disponible');
    }

    // ── 2. Ausencia de planificación ─────────────────────────────────────

    public function test_muestra_sin_planificacion_registrada_cuando_no_hay_unidad_vigente(): void
    {
        $ctx = $this->crearAula('PZ2');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertViewHas('planificacion', fn($p) => $p['unidad_hoy'] === null);
        $response->assertSee('Sin planificación registrada');
    }

    public function test_no_muestra_una_unidad_fuera_del_rango_de_fechas_vigente(): void
    {
        $ctx = $this->crearAula('PZ2B');

        $planifAnual = PlanifAnual::create(['asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id, 'titulo' => 'Plan Anual PZ2B']);
        PlanifUnidad::create([
            'planif_anual_id' => $planifAnual->id, 'numero' => 1, 'titulo' => 'Unidad Pasada',
            'fecha_inicio' => today()->subDays(30), 'fecha_fin' => today()->subDays(20),
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertViewHas('planificacion', fn($p) => $p['unidad_hoy'] === null);
        $response->assertSee('Sin planificación registrada');
    }

    // ── 3. Conteo de Plan de Clase ───────────────────────────────────────

    public function test_muestra_el_conteo_correcto_de_planes_de_clase(): void
    {
        $ctx = $this->crearAula('PZ3');

        foreach (range(1, 3) as $i) {
            PlanClase::create([
                'asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id,
                'titulo' => "Plan $i", 'area' => 'academica', 'tipo_plan' => 'diaria', 'publicado' => true, 'creado_por' => $ctx['docenteUser']->id,
            ]);
        }
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertViewHas('planificacion', fn($p) => $p['plan_clase_count'] === 3);
        $response->assertSee('3 registros');
    }

    // ── 4. Conteo de Planificación Técnica ───────────────────────────────

    public function test_muestra_el_conteo_correcto_de_planificacion_tecnica(): void
    {
        $ctx = $this->crearAula('PZ4');

        Planificacion::create([
            'asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'tipo' => 'ra',
            'denominacion' => 'RA 1', 'publicado' => true, 'creado_por' => $ctx['docenteUser']->id,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertViewHas('planificacion', fn($p) => $p['planificacion_count'] === 1);
        $response->assertSee('1 registro');
    }

    // ── 5/6. Autorización ────────────────────────────────────────────────

    public function test_el_docente_dueño_de_la_asignacion_puede_ver_la_tarjeta(): void
    {
        $ctx = $this->crearAula('PZ5');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertSee('Planificación de esta asignatura');
    }

    public function test_un_docente_que_no_es_dueño_de_la_asignacion_no_puede_acceder(): void
    {
        $ctx = $this->crearAula('PZ6');

        $otroDocenteUser = User::factory()->create(['activo' => true, 'tenant_id' => $ctx['tenant']->id]);
        $otroDocenteUser->assignRole('Docente');
        Docente::factory()->create(['user_id' => $otroDocenteUser->id]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($otroDocenteUser)->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertForbidden();
    }

    // ── 7. Aislamiento entre tenants ─────────────────────────────────────

    public function test_un_docente_de_otro_tenant_no_puede_acceder_al_aula(): void
    {
        $ctxA = $this->crearAula('PZ7A');
        $ctxB = $this->crearAula('PZ7B');

        $response = $this->actingAs($ctxB['docenteUser'])->get(route('portal.docente.classroom.show', $ctxA['clase']));

        $response->assertNotFound();
    }

    // ── 8. Asignación correcta — cada aula ve solo su propia planificación ──

    public function test_el_conteo_no_incluye_planes_de_clase_de_otra_asignacion_del_mismo_tenant(): void
    {
        $ctx = $this->crearAula('PZ8A');

        // Otra asignación del MISMO tenant y docente — sus planes NO deben contarse.
        $otraAsignatura = Asignatura::create(['codigo' => 'PZ8X', 'nombre' => 'Materia PZ8X', 'area' => 'academica', 'activo' => true]);
        $otraAsignacion = Asignacion::create([
            'school_year_id' => $ctx['sy']->id, 'grupo_id' => $ctx['asignacion']->grupo_id, 'asignatura_id' => $otraAsignatura->id,
            'activo' => true, 'area' => 'academica', 'docente_id' => $ctx['docente']->id,
        ]);
        foreach (range(1, 2) as $i) {
            PlanClase::create([
                'asignacion_id' => $otraAsignacion->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id,
                'titulo' => "Plan Otra $i", 'area' => 'academica', 'tipo_plan' => 'diaria', 'publicado' => true, 'creado_por' => $ctx['docenteUser']->id,
            ]);
        }

        // La asignación de ESTA aula tiene un solo plan.
        PlanClase::create([
            'asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id,
            'titulo' => 'Plan Propio', 'area' => 'academica', 'tipo_plan' => 'diaria', 'publicado' => true, 'creado_por' => $ctx['docenteUser']->id,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));

        $response->assertOk();
        $response->assertViewHas('planificacion', fn($p) => $p['plan_clase_count'] === 1);
    }

    // ── 9. Acceso mediante URL directa ───────────────────────────────────

    public function test_la_tarjeta_es_visible_al_acceder_mediante_url_directa(): void
    {
        $ctx = $this->crearAula('PZ9');
        app()->forgetInstance('tenant');

        $url = route('portal.docente.classroom.show', $ctx['clase']);
        $response = $this->actingAs($ctx['docenteUser'])->get($url);

        $response->assertOk();
        $response->assertSee('Esta planificación sirve como referencia para preparar tu clase.');
    }

    // ── 10. Sin N+1 ───────────────────────────────────────────────────────

    public function test_la_conexion_no_agrega_consultas_n_mas_1(): void
    {
        $ctx = $this->crearAula('PZ10');

        $planifAnual = PlanifAnual::create(['asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id, 'titulo' => 'Plan Anual PZ10']);
        PlanifUnidad::create([
            'planif_anual_id' => $planifAnual->id, 'numero' => 1, 'titulo' => 'Unidad X',
            'fecha_inicio' => today()->subDay(), 'fecha_fin' => today()->addDay(),
        ]);
        PlanClase::create([
            'asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'docente_id' => $ctx['docente']->id,
            'titulo' => 'Plan X', 'area' => 'academica', 'tipo_plan' => 'diaria', 'publicado' => true, 'creado_por' => $ctx['docenteUser']->id,
        ]);
        Planificacion::create([
            'asignacion_id' => $ctx['asignacion']->id, 'school_year_id' => $ctx['sy']->id, 'tipo' => 'ra',
            'denominacion' => 'RA X', 'publicado' => true, 'creado_por' => $ctx['docenteUser']->id,
        ]);
        app()->forgetInstance('tenant');

        DB::enableQueryLog();
        $response = $this->actingAs($ctx['docenteUser'])->get(route('portal.docente.classroom.show', $ctx['clase']));
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk();
        // La conexión agrega solo 3 queries fijas (unidad hoy + 2 counts),
        // sin importar cuántos materiales/estudiantes tenga el aula.
        $this->assertLessThan(40, $queryCount, "Se ejecutaron $queryCount queries — posible N+1 introducido por la conexión de planificación.");
    }
}
