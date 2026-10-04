<?php

namespace Tests\Feature;

use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Los indicadores del dashboard del administrador (ranking de grupos incluido, ~85 ms) se calculaban en cada carga.
 * Ahora van con caché de 2 minutos por colegio y año; el módulo «KPIs» sigue calculándolos al momento.
 */
class DashboardKpiCacheTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);

        return User::factory()->create(['activo' => true])->assignRole('Administrador');
    }

    private function consultasDelRanking(callable $accion): int
    {
        $n = 0;
        DB::listen(function ($q) use (&$n) {
            if (str_contains($q->sql, 'AVG(ca.nota_final)') && str_contains($q->sql, 'COUNT(DISTINCT ca.matricula_id)')) {
                $n++;
            }
        });
        $accion();

        return $n;
    }

    public function test_el_dashboard_calcula_los_kpis_una_sola_vez_en_cargas_seguidas(): void
    {
        SchoolYear::create(['nombre' => '2096-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin = $this->admin();
        Cache::flush();

        $consultas = $this->consultasDelRanking(function () use ($admin) {
            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        });

        $this->assertSame(1, $consultas, 'tres cargas del dashboard, una sola vez el ranking de grupos');
    }

    public function test_el_modulo_kpis_sigue_calculando_en_vivo(): void
    {
        SchoolYear::create(['nombre' => '2096-B', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin = $this->admin();
        Cache::flush();

        $consultas = $this->consultasDelRanking(function () use ($admin) {
            $this->actingAs($admin)->getJson(route('admin.kpis.data'))->assertOk();
            $this->actingAs($admin)->getJson(route('admin.kpis.data'))->assertOk();
        });

        $this->assertSame(2, $consultas, 'el botón Actualizar de KPIs no usa la caché');
    }
}
