<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Fase 5, pieza 4 (Branding fino, alcance acotado): self-service de
 * Tenant.color_primario/color_secundario -- antes solo se podían fijar en
 * el onboarding o desde SuperAdmin. No toca el sistema de colores-por-rol
 * del panel admin (layouts/admin.blade.php), que queda fuera de alcance.
 */
class BrandingColoresTest extends TestCase
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
            'color_primario'     => '#1d4ed8',
            'color_secundario'   => '#10b981',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    public function test_admin_ve_los_colores_actuales_en_el_tab_apariencia(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Colores Ver');

        $response = $this->actingAs($user)->get(route('admin.sistema.index'));

        $response->assertOk();
        $response->assertSee('#1d4ed8');
        $response->assertSee('#10b981');
    }

    public function test_admin_guarda_colores_validos(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Colores Guardar');

        $response = $this->actingAs($user)->post(route('admin.sistema.colores'), [
            'color_primario'   => '#ff5733',
            'color_secundario' => '#33ff57',
        ]);

        $response->assertRedirect();
        $tenant->refresh();
        $this->assertSame('#ff5733', $tenant->color_primario);
        $this->assertSame('#33ff57', $tenant->color_secundario);
    }

    public function test_rechaza_un_valor_que_no_es_hexadecimal(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Colores Invalido');

        $response = $this->actingAs($user)->post(route('admin.sistema.colores'), [
            'color_primario'   => 'javascript:alert(1)',
            'color_secundario' => '#10b981',
        ]);

        $response->assertSessionHasErrors('color_primario');
        $tenant->refresh();
        $this->assertSame('#1d4ed8', $tenant->color_primario, 'Un valor inválido no debe pisar el color guardado.');
    }

    public function test_guardar_invalida_la_cache_de_iconos_pwa(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Colores PWA');

        foreach ([96, 192, 512] as $size) {
            foreach (['a', 'm'] as $variante) {
                Cache::put("pwa_icon_{$tenant->id}_{$size}_{$variante}", 'icono-viejo', 3600);
            }
        }

        $this->actingAs($user)->post(route('admin.sistema.colores'), [
            'color_primario'   => '#ff5733',
            'color_secundario' => '#10b981',
        ]);

        foreach ([96, 192, 512] as $size) {
            foreach (['a', 'm'] as $variante) {
                $this->assertFalse(
                    Cache::has("pwa_icon_{$tenant->id}_{$size}_{$variante}"),
                    "La cache del icono PWA {$size}{$variante} debe invalidarse al cambiar el color."
                );
            }
        }
    }

    public function test_aislamiento_cross_tenant(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Colores Aislar A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Colores Aislar B');

        $this->actingAs($userA)->post(route('admin.sistema.colores'), [
            'color_primario'   => '#000000',
            'color_secundario' => '#111111',
        ]);

        $tenantB->refresh();
        $this->assertSame('#1d4ed8', $tenantB->color_primario);
        $this->assertSame('#10b981', $tenantB->color_secundario);
    }

    public function test_un_rol_sin_solo_administrador_recibe_403(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Colores SinPermiso');
        $coordinador = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $coordinador->assignRole('Coordinador Académico'); // Gate 'solo-administrador' exige exactamente rol Administrador.

        $response = $this->actingAs($coordinador)->post(route('admin.sistema.colores'), [
            'color_primario'   => '#000000',
            'color_secundario' => '#111111',
        ]);

        $response->assertForbidden();
        $tenant->refresh();
        $this->assertSame('#1d4ed8', $tenant->color_primario);
    }
}
