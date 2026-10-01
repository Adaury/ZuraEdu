<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El paginador del listado de estudiantes ejecuta `count(*) where tenant_id = ? and deleted_at is null`.
 * El optimizador de MySQL prefiere el índice único (tenant_id, cedula) y lee cada fila (~12 ms con 4.950
 * estudiantes); forzando est_tenant_listado_idx es index-only (~2 ms), con el total exacto.
 *
 * Solo sin filtros, y solo si el índice existe (FORCE INDEX sobre un índice inexistente es un 500).
 */
class EstudianteListadoIndiceTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE_CACHE = 'db_schema_idx_est_tenant_listado_idx';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Cache::forget(self::CLAVE_CACHE);
    }

    /** @return array{admin: User, tenant: Tenant, sy: SchoolYear} */
    private function contexto(int $estudiantes): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Listado Indice',
            'dominio'            => 'colegiolistidx' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');
        Estudiante::factory()->count($estudiantes)->create();

        return compact('admin', 'tenant', 'sy');
    }

    private function matricular(Estudiante $e, SchoolYear $sy): void
    {
        static $nivel = 100;
        $nivel++;
        $grado   = Grado::create(['nombre' => 'Grado LI' . $nivel, 'nivel' => $nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $e->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
    }

    /** SQL de los count(*) sobre estudiantes ejecutados durante la petición. */
    private function conteosDeEstudiantes(User $admin, string $url): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get($url)->assertOk();
        $sqls = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn ($q) => str_contains($q, 'count(*)') && str_contains($q, 'from `estudiantes`')
                && ! str_contains($q, 'not exists'))
            ->values()->all();
        DB::disableQueryLog();

        return $sqls;
    }

    public function test_sin_filtros_el_conteo_del_paginador_fuerza_el_indice_del_listado(): void
    {
        ['admin' => $admin] = $this->contexto(3);

        $conteos = $this->conteosDeEstudiantes($admin, route('admin.estudiantes.index'));

        $this->assertNotEmpty($conteos);
        $this->assertStringContainsString('force index (est_tenant_listado_idx)', $conteos[0]);
    }

    public function test_el_total_con_el_indice_forzado_es_exacto_y_solo_del_tenant(): void
    {
        ['admin' => $admin] = $this->contexto(3);

        // Estudiantes de OTRO colegio y uno borrado lógicamente: no deben contar.
        $otro = Tenant::create([
            'nombre_institucion' => 'Otro Colegio', 'dominio' => 'otrolistidx' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $otro);
        Estudiante::factory()->count(5)->create();
        app()->instance('tenant', Tenant::find($admin->tenant_id));
        Estudiante::first()->delete(); // soft delete

        $total = $this->actingAs($admin)->get(route('admin.estudiantes.index'))
            ->assertOk()->viewData('estudiantes')->total();

        $this->assertSame(2, $total); // 3 - 1 borrado, sin los 5 del otro tenant
    }

    public function test_con_busqueda_de_texto_no_se_fuerza_el_indice(): void
    {
        ['admin' => $admin] = $this->contexto(3);

        $conteos = $this->conteosDeEstudiantes($admin, route('admin.estudiantes.index', ['buscar' => 'a']));

        $this->assertNotEmpty($conteos);
        $this->assertStringNotContainsString('force index', $conteos[0]);
    }

    public function test_con_filtro_de_letra_no_se_fuerza_el_indice(): void
    {
        ['admin' => $admin] = $this->contexto(3);

        $conteos = $this->conteosDeEstudiantes($admin, route('admin.estudiantes.index', ['letra' => 'A']));

        $this->assertNotEmpty($conteos);
        $this->assertStringNotContainsString('force index', $conteos[0]);
    }

    public function test_si_el_indice_no_existe_la_pagina_sigue_funcionando_sin_forzarlo(): void
    {
        ['admin' => $admin] = $this->contexto(3);

        // Simula un entorno con el código nuevo y la migración sin aplicar: la salvaguarda lo detecta.
        Cache::put(self::CLAVE_CACHE, false, 3600);

        $conteos = $this->conteosDeEstudiantes($admin, route('admin.estudiantes.index'));

        $this->assertNotEmpty($conteos);
        $this->assertStringNotContainsString('force index', $conteos[0]);
    }

    public function test_el_aviso_de_sin_matricula_cuenta_exactamente_a_quienes_no_estan_matriculados_este_ano(): void
    {
        ['admin' => $admin, 'tenant' => $tenant, 'sy' => $sy] = $this->contexto(0);

        $matriculado = Estudiante::factory()->create();
        $this->matricular($matriculado, $sy);

        // Solo tuvo matrícula en un año ANTERIOR: sigue contando como "sin matrícula" en el actual.
        $anterior = SchoolYear::create(['nombre' => '2019-A', 'fecha_inicio' => '2018-08-01', 'fecha_fin' => '2019-06-30', 'activo' => false]);
        $soloAnterior = Estudiante::factory()->create();
        $this->matricular($soloAnterior, $anterior);

        Estudiante::factory()->count(2)->create();            // nunca matriculados
        Estudiante::factory()->create()->delete();            // borrado lógico: no cuenta

        // Estudiantes sin matrícula de OTRO colegio: no deben sumarse.
        $otro = Tenant::create([
            'nombre_institucion' => 'Otro Colegio SM', 'dominio' => 'otrosm' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $otro);
        Estudiante::factory()->count(4)->create();
        app()->instance('tenant', $tenant);

        $sinMatricula = $this->actingAs($admin)->get(route('admin.estudiantes.index'))
            ->assertOk()->viewData('sinMatricula');

        $this->assertSame(3, $sinMatricula); // soloAnterior + 2 nunca matriculados
    }

    public function test_el_aviso_de_sin_matricula_usa_el_indice_del_listado(): void
    {
        ['admin' => $admin] = $this->contexto(2);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.estudiantes.index'))->assertOk();
        $antiJoin = collect(DB::getQueryLog())->pluck('query')->first(fn ($q) => str_contains($q, 'not exists'));
        DB::disableQueryLog();

        $this->assertNotNull($antiJoin);
        $this->assertStringContainsString('force index (est_tenant_listado_idx)', $antiJoin);
    }

    public function test_con_filtro_de_ciclo_no_se_calcula_el_aviso(): void
    {
        ['admin' => $admin] = $this->contexto(2);

        $this->actingAs($admin)->get(route('admin.estudiantes.index', ['ciclo' => 1]))
            ->assertOk()->assertViewHas('sinMatricula', 0);
    }

    public function test_el_banner_se_muestra_cuando_hay_estudiantes_sin_matricula(): void
    {
        ['admin' => $admin] = $this->contexto(2);

        $this->actingAs($admin)->get(route('admin.estudiantes.index'))
            ->assertOk()->assertSee('2 estudiante(s)', false)->assertSee('sin matrícula en el año escolar actual');
    }

    public function test_la_comprobacion_de_existencia_del_indice_se_cachea(): void
    {
        ['admin' => $admin] = $this->contexto(1);

        $this->assertFalse(Cache::has(self::CLAVE_CACHE));
        $this->actingAs($admin)->get(route('admin.estudiantes.index'))->assertOk();

        $this->assertTrue(Cache::has(self::CLAVE_CACHE));
        $this->assertTrue(Cache::get(self::CLAVE_CACHE)); // en este entorno el índice existe
    }
}
