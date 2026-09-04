<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * TenantController (SuperAdmin) — hallazgo Medio de la ronda de auditoría
 * de módulos grandes del 2026-09-03: eliminar/suspender/impersonar un
 * tenant no dejaba ningún rastro de auditoría. Se agregó
 * ActivityLog::registrar() en destroy/toggleEstado/enterPanel/exitPanel.
 */
class SuperAdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function superAdmin(): User
    {
        // super_admin no forma parte de RolesSeeder — solo lo crea el comando
        // artisan superadmin:crear (App\Console\Commands\CrearSuperAdmin).
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('super_admin');
        return $user;
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => 'Colegio Auditado',
            'dominio'            => 'colegioauditado' . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);
    }

    public function test_suspender_un_tenant_deja_rastro_en_activity_log(): void
    {
        $sa = $this->superAdmin();
        $tenant = $this->tenant();

        $this->actingAs($sa)
            ->post(route('superadmin.tenants.toggle-estado', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id'   => $sa->id,
            'accion'    => 'tenant_suspendido',
            'modelo_id' => $tenant->id,
        ]);
    }

    public function test_eliminar_un_tenant_deja_rastro_en_activity_log(): void
    {
        $sa = $this->superAdmin();
        $tenant = $this->tenant();
        $tenantId = $tenant->id;

        $this->actingAs($sa)
            ->delete(route('superadmin.tenants.destroy', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id'   => $sa->id,
            'accion'    => 'tenant_eliminado',
            'modelo_id' => $tenantId,
        ]);
    }

    public function test_entrar_y_salir_del_panel_de_un_tenant_deja_rastro(): void
    {
        $sa = $this->superAdmin();
        $tenant = $this->tenant();

        $this->actingAs($sa)
            ->post(route('superadmin.tenants.enter-panel', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $sa->id, 'accion' => 'tenant_impersonar_entrar', 'modelo_id' => $tenant->id,
        ]);

        $this->actingAs($sa)
            ->post(route('superadmin.tenants.exit-panel'))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $sa->id, 'accion' => 'tenant_impersonar_salir', 'modelo_id' => $tenant->id,
        ]);
    }
}
