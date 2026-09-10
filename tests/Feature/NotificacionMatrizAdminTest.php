<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\Notificacion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notificaciones Configurables (Fase 5, pieza 3) -- CRUD de la matriz de
 * institución (/admin/sistema/notificaciones).
 */
class NotificacionMatrizAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearAdmin(string $nombreTenant): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombreTenant,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombreTenant)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => true]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    public function test_admin_ve_la_matriz(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Matriz Ver');

        $response = $this->actingAs($user)->get(route('admin.sistema.notificaciones'));

        $response->assertOk();
        foreach (Notificacion::CATEGORIAS as $meta) {
            $response->assertSee($meta['label']);
        }
    }

    public function test_admin_guarda_la_matriz(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Matriz Guardar');

        $this->actingAs($user)->post(route('admin.sistema.notificaciones.update'), [
            'inapp_academico' => '0', // se apaga
            'push_academico'  => '1',
            'push_pagos'      => '0', // se apaga
        ]);

        app()->instance('tenant', $tenant);
        $this->assertSame('0', Setting::get('notif_inapp_academico'));
        $this->assertSame('1', Setting::get('notif_push_academico'));
        $this->assertSame('0', Setting::get('notif_push_pagos'));
        app()->forgetInstance('tenant');
    }

    public function test_la_matriz_nunca_escribe_notif_inapp_sistema(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Matriz Sistema');

        $this->actingAs($user)->post(route('admin.sistema.notificaciones.update'), []);

        $this->assertDatabaseMissing('system_settings', ['tenant_id' => $tenant->id, 'key' => 'notif_inapp_sistema']);
    }

    public function test_aislamiento_cross_tenant(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Matriz Aislar A');
        ['tenant' => $tenantB, 'user' => $userB] = $this->crearAdmin('Colegio Matriz Aislar B');

        $this->actingAs($userA)->post(route('admin.sistema.notificaciones.update'), ['inapp_pagos' => '0']);

        app()->instance('tenant', $tenantB);
        $this->assertSame('1', Setting::get('notif_inapp_pagos', '1'));
        app()->forgetInstance('tenant');

        app()->instance('tenant', $tenantA);
        $this->assertSame('0', Setting::get('notif_inapp_pagos'));
        app()->forgetInstance('tenant');
    }

    public function test_un_representante_no_accede_a_la_matriz(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Matriz Representante',
            'dominio'            => 'colegiomatrizrep' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Representante');

        $response = $this->actingAs($user)->get(route('admin.sistema.notificaciones'));

        $response->assertRedirect(route('portal.padre.dashboard'));
    }
}
