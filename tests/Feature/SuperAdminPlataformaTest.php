<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Panel de plataforma (SuperAdmin): editar una institución queda auditado (plan, estado,
 * límites, módulos), el tenant principal no se suspende por la ruta de edición, el dominio
 * anterior deja de resolver, una institución activa no se elimina, el pago registra
 * constancia sin apagar los módulos que el plan no gobierna y el "destino" de enter-panel
 * no es un redirect abierto.
 */
class SuperAdminPlataformaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function tenant(array $extra = []): Tenant
    {
        return Tenant::create(array_merge([
            'nombre_institucion' => 'Colegio Plataforma',
            'dominio'            => 'plat' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
            'max_estudiantes'    => 100, 'max_docentes' => 5,
        ], $extra));
    }

    private function datosUpdate(Tenant $t, array $extra = []): array
    {
        return array_merge([
            'nombre_institucion' => $t->nombre_institucion, 'dominio' => $t->dominio,
            'tipo' => $t->tipo, 'estado' => $t->estado, 'plan' => $t->plan,
            'max_estudiantes' => $t->max_estudiantes, 'max_docentes' => $t->max_docentes,
        ], $extra);
    }

    public function test_editar_una_institucion_deja_el_detalle_del_cambio(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();

        $this->actingAs($sa)
            ->put(route('superadmin.tenants.update', $t), $this->datosUpdate($t, [
                'plan' => 'pro', 'max_estudiantes' => 900, 'features' => ['pagos', 'classroom'],
            ]))
            ->assertRedirect();

        $log = ActivityLog::where('accion', 'tenant_actualizado')->where('modelo_id', $t->id)->firstOrFail();
        $this->assertStringContainsString('plan: free → pro', $log->descripcion);
        $this->assertStringContainsString('max_estudiantes: 100 → 900', $log->descripcion);
        $this->assertStringContainsString('pagos', $log->descripcion);
    }

    public function test_el_tenant_principal_no_se_suspende_por_la_edicion(): void
    {
        $sa = $this->superAdmin();
        $principal = Tenant::find(1) ?? Tenant::create([
            'id' => 1, 'nombre_institucion' => 'Principal', 'dominio' => 'principal' . random_int(1000, 9999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'pro', 'max_estudiantes' => 9999, 'max_docentes' => 9999,
        ]);
        if ($principal->id !== 1) {
            $this->markTestSkipped('No se pudo crear un tenant con id 1 en esta base.');
        }

        $this->actingAs($sa)
            ->put(route('superadmin.tenants.update', $principal), $this->datosUpdate($principal, ['estado' => 'suspendido']))
            ->assertSessionHasErrors('estado');

        $this->assertSame('activo', $principal->fresh()->estado);
    }

    public function test_cambiar_el_dominio_invalida_la_cache_del_host_anterior(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();
        $viejo = $t->dominio;
        Cache::put("tenant_host_{$viejo}", $t->id, 600);

        $this->actingAs($sa)
            ->put(route('superadmin.tenants.update', $t), $this->datosUpdate($t, ['dominio' => 'nuevo' . random_int(10000, 99999)]))
            ->assertRedirect();

        $this->assertNull(Cache::get("tenant_host_{$viejo}"));
    }

    public function test_colores_invalidos_se_rechazan(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();

        $this->actingAs($sa)
            ->put(route('superadmin.tenants.update', $t), $this->datosUpdate($t, ['color_primario' => 'red;x}']))
            ->assertSessionHasErrors('color_primario');
    }

    public function test_una_institucion_activa_no_se_elimina_directamente(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();

        $this->actingAs($sa)->delete(route('superadmin.tenants.destroy', $t))->assertSessionHasErrors('error');
        $this->assertNotNull(Tenant::find($t->id));

        $t->update(['estado' => 'cancelado']);
        $this->actingAs($sa)->delete(route('superadmin.tenants.destroy', $t))->assertRedirect();
        $this->assertNull(Tenant::find($t->id));
    }

    public function test_registrar_pago_deja_constancia_y_conserva_los_modulos_que_el_plan_no_gobierna(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();
        TenantFeature::create(['tenant_id' => $t->id, 'feature' => 'area_tecnica', 'activo' => true]);
        TenantFeature::create(['tenant_id' => $t->id, 'feature' => 'modo_publico', 'activo' => true]);

        $this->actingAs($sa)
            ->post(route('superadmin.tenants.subscriptions.store', $t), [
                'plan' => 'basico', 'ciclo' => 'mensual', 'monto_pagado' => 49.5, 'meses' => 3, 'referencia_pago' => 'TRF-001',
            ])
            ->assertRedirect();

        $this->assertTrue((bool) TenantFeature::where('tenant_id', $t->id)->where('feature', 'area_tecnica')->value('activo'));
        $this->assertTrue((bool) TenantFeature::where('tenant_id', $t->id)->where('feature', 'modo_publico')->value('activo'));
        $this->assertTrue((bool) TenantFeature::where('tenant_id', $t->id)->where('feature', 'classroom')->value('activo'), 'El plan básico activa classroom');

        $log = ActivityLog::where('accion', 'tenant_pago_registrado')->where('modelo_id', $t->id)->firstOrFail();
        $this->assertStringContainsString('49.5', $log->descripcion);
        $this->assertStringContainsString('TRF-001', $log->descripcion);
    }

    public function test_toggle_feature_solo_acepta_modulos_conocidos_y_audita(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();

        $this->actingAs($sa)
            ->post(route('superadmin.tenants.toggle-feature', $t), ['feature' => 'inventado_xyz'])
            ->assertSessionHasErrors('feature');
        $this->assertSame(0, TenantFeature::where('tenant_id', $t->id)->where('feature', 'inventado_xyz')->count());

        $this->actingAs($sa)
            ->post(route('superadmin.tenants.toggle-feature', $t), ['feature' => 'gamificacion'])
            ->assertRedirect();
        $this->assertDatabaseHas('activity_logs', ['accion' => 'tenant_modulo_cambiado', 'modelo_id' => $t->id]);
    }

    public function test_enter_panel_no_redirige_fuera_del_sitio(): void
    {
        $sa = $this->superAdmin();
        $t  = $this->tenant();

        $r = $this->actingAs($sa)->post(route('superadmin.tenants.enter-panel', $t), ['destino' => 'https://evil.example/robo']);
        $r->assertRedirect('/admin/dashboard');

        $r = $this->actingAs($sa)->post(route('superadmin.tenants.enter-panel', $t), ['destino' => '//evil.example']);
        $r->assertRedirect('/admin/dashboard');

        $r = $this->actingAs($sa)->post(route('superadmin.tenants.enter-panel', $t), ['destino' => '/admin/estudiantes']);
        $r->assertRedirect('/admin/estudiantes');
    }
}
