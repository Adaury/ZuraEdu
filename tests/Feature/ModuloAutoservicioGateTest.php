<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 0 del roadmap (docs/ZURAEDU_PRODUCT_GAPS.md): CheckTenantFeature
 * ahora exige TenantFeature (plan) Y ConfigInstitucional::moduloActivo()
 * (autoservicio del centro) -- antes moduloActivo() era puramente
 * cosmético y no bloqueaba ninguna ruta. moduloActivo() por defecto es
 * true (opt-out) precisamente para que este cambio no bloquee de golpe a
 * ningún tenant real que nunca tocó la pestaña Módulos.
 */
class ModuloAutoservicioGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearAdmin(string $nombreTenant, array $features = []): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombreTenant,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombreTenant)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        foreach ($features as $feature) {
            TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => $feature, 'activo' => true]);
        }
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    public function test_modulo_nunca_tocado_por_el_centro_no_bloquea_si_el_plan_lo_incluye(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Gate Default', ['pagos']);

        $response = $this->actingAs($user)->get(route('admin.pagos.index'));

        $response->assertOk();
    }

    public function test_el_centro_apaga_el_modulo_y_la_ruta_queda_bloqueada(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Gate Apagado', ['pagos']);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('modulo_pagos_activo', '0');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.pagos.index'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('warning');
        $this->assertStringContainsString('configuración de tu institución', session('warning'));
    }

    public function test_el_centro_enciende_el_modulo_explicitamente_no_bloquea(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Gate Encendido', ['pagos']);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('modulo_pagos_activo', '1');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.pagos.index'));

        $response->assertOk();
    }

    public function test_plan_sin_el_modulo_bloquea_sin_importar_el_autoservicio(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Gate SinPlan'); // sin TenantFeature 'pagos'

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('modulo_pagos_activo', '1'); // encendido en autoservicio, pero el plan no lo trae
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.pagos.index'));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertStringContainsString('no está disponible en tu plan', session('warning'));
    }

    public function test_alias_classroom_zuraclass(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Gate Classroom', ['classroom']);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('modulo_zuraclass_activo', '0');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.classroom.index'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_alias_seguimiento_social_seguimiento(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Gate Seguimiento', ['seguimiento_social']);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('modulo_seguimiento_activo', '0');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.seguimiento-social.dashboard'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_super_admin_ignora_el_gate_de_autoservicio(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Gate SuperAdmin', ['pagos']);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('modulo_pagos_activo', '0');
        app()->forgetInstance('tenant');

        $plataforma = Tenant::create([
            'nombre_institucion' => 'Plataforma ZuraEdu',
            'dominio'            => 'plataformagateautoservicio' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);
        $superAdmin = User::factory()->create(['activo' => true, 'tenant_id' => $plataforma->id]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)
            ->withSession(['sa_tenant_id' => $tenant->id])
            ->get(route('admin.pagos.index'));

        $response->assertOk();
    }

    public function test_aislamiento_cross_tenant(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Gate Aislar A', ['pagos']);
        ['tenant' => $tenantB, 'user' => $userB] = $this->crearAdmin('Colegio Gate Aislar B', ['pagos']);

        app()->instance('tenant', $tenantA);
        ConfigInstitucional::set('modulo_pagos_activo', '0');
        app()->forgetInstance('tenant');

        $responseA = $this->actingAs($userA)->get(route('admin.pagos.index'));
        $responseB = $this->actingAs($userB)->get(route('admin.pagos.index'));

        $responseA->assertRedirect(route('admin.dashboard'));
        $responseB->assertOk();
    }
}
