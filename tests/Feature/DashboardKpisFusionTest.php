<?php

namespace Tests\Feature;

use App\Models\SchoolYear;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto (docs/ZURAEDU_IMPLEMENTATION_ROADMAP.md, punto 3):
 * fusión de /admin/kpis dentro del dashboard principal para Administrador
 * y Director (ambos comparten rolDashboard === 'admin'). Reutiliza
 * KpiDashboardService — mismo cálculo que ya usaba KpiController, sin
 * duplicar consultas. pagos_mes se omite a propósito (ya cubierto por
 * $statsPagos, mismos números).
 */
class DashboardKpisFusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearUsuarioConRol(string $rol): User
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio KPI',
            'dominio'            => 'colegiokpi' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        app()->forgetInstance('tenant');

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);

        return $user;
    }

    public function test_administrador_ve_los_kpis_institucionales_en_el_dashboard(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpisHoy', fn($k) => $k !== null);
        $response->assertSee('Hoy en el Centro');
        $response->assertSee('Rendimiento Institucional');
    }

    public function test_director_tambien_ve_los_kpis_institucionales(): void
    {
        // Director no tiene rama propia en $rolDashboard — cae en el mismo
        // bucket 'admin' que Administrador (confirmado en la auditoría).
        $user = $this->crearUsuarioConRol('Director');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpisHoy', fn($k) => $k !== null);
        $response->assertSee('Hoy en el Centro');
    }

    public function test_coordinador_no_ve_los_kpis_institucionales(): void
    {
        $user = $this->crearUsuarioConRol('Coordinador Académico');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpisHoy', fn($k) => $k === null);
        $response->assertDontSee('Hoy en el Centro');
    }

    public function test_secretaria_no_ve_los_kpis_institucionales(): void
    {
        $user = $this->crearUsuarioConRol('Secretaría');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpisHoy', fn($k) => $k === null);
    }

    public function test_admin_kpis_index_sigue_funcionando_tras_la_fusion(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');

        $response = $this->actingAs($user)->get(route('admin.kpis.index'));

        $response->assertOk();
        $response->assertViewHas('kpis');
    }

    public function test_los_datos_del_dashboard_coinciden_con_admin_kpis_mismo_servicio(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');

        $dashboard = $this->actingAs($user)->get(route('admin.dashboard'));
        $kpisPage  = $this->actingAs($user)->get(route('admin.kpis.index'));

        $dashboard->assertOk();
        $kpisPage->assertOk();

        $kpisHoy = $dashboard->viewData('kpisHoy');
        $kpis    = $kpisPage->viewData('kpis');

        // Ambos reflejan el mismo estado (0 asistencias registradas en este
        // escenario) porque comparten el mismo KpiDashboardService.
        $this->assertSame($kpis['asistencia_hoy']['total'], $kpisHoy['asistencia_hoy']['total']);
        $this->assertSame($kpis['situacion_estudiantes']['total'], $kpisHoy['situacion_estudiantes']['total']);
    }
}
