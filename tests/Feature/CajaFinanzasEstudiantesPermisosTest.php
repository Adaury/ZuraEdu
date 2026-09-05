<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Warning del Gate de Producción (2026-09-04, fila #3 RBAC): Caja/Finanzas
 * tenía el permiso completo `gestionar-estudiantes` (incluye crear/editar/
 * eliminar) pese a que RolesSeeder documentaba la intención como "solo
 * lectura en práctica" — routes/admin/personas.php:40 gateaba
 * Route::resource('estudiantes', ...) completo (incluido destroy) con ese
 * único permiso. Se introdujo `ver-estudiantes` (solo lectura) separado de
 * `gestionar-estudiantes` (mutación), y Caja/Finanzas ahora solo tiene el
 * primero.
 */
class CajaFinanzasEstudiantesPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Tenant} */
    private function cajaConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Caja',
            'dominio'            => 'colegiocaja' . random_int(10000, 99999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Caja / Finanzas');

        return [$user, $tenant];
    }

    public function test_caja_finanzas_puede_ver_el_listado_de_estudiantes(): void
    {
        [$user] = $this->cajaConTenant();

        $this->actingAs($user)
            ->get(route('admin.estudiantes.index'))
            ->assertOk();
    }

    public function test_caja_finanzas_no_puede_crear_estudiantes(): void
    {
        [$user] = $this->cajaConTenant();

        $this->actingAs($user)
            ->get(route('admin.estudiantes.create'))
            ->assertForbidden();
    }

    public function test_caja_finanzas_no_puede_guardar_un_estudiante_nuevo(): void
    {
        [$user] = $this->cajaConTenant();

        $this->actingAs($user)
            ->post(route('admin.estudiantes.store'), [])
            ->assertForbidden();
    }

    public function test_caja_finanzas_no_puede_eliminar_un_estudiante(): void
    {
        [$user, $tenant] = $this->cajaConTenant();

        app()->instance('tenant', $tenant);
        $estudiante = Estudiante::factory()->create();
        app()->forgetInstance('tenant');

        $this->actingAs($user)
            ->delete(route('admin.estudiantes.destroy', $estudiante))
            ->assertForbidden();

        // Estudiante usa SoftDeletes: verificar deleted_at directamente en
        // vez de assertDatabaseMissing, que no detectaría un soft-delete.
        $this->assertNull(
            \App\Models\Estudiante::withTrashed()->find($estudiante->id)->deleted_at
        );
    }

    public function test_administrador_si_puede_eliminar_un_estudiante(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Admin',
            'dominio'            => 'colegioadmin' . random_int(10000, 99999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);

        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $estudiante = Estudiante::factory()->create();
        app()->forgetInstance('tenant');

        $this->actingAs($admin)
            ->delete(route('admin.estudiantes.destroy', $estudiante))
            ->assertRedirect();

        $this->assertNotNull(
            \App\Models\Estudiante::withTrashed()->find($estudiante->id)->deleted_at
        );
    }
}
