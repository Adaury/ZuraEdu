<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hallazgo al investigar la unificación de colores de marca: layouts/portal.blade.php
 * (docente/padre/estudiante) nunca renderizaba el logo subido por el tenant en
 * /admin/sistema — solo mostraba la abreviatura en texto (Setting::get('system_abbr')),
 * a diferencia de layouts/admin.blade.php, que sí muestra la imagen cuando existe.
 * No es un problema de "global vs. por tenant" (Setting ya es tenant-scoped,
 * confirmado en app/Helpers/Setting.php) — es que el <img> simplemente no existía
 * en este layout.
 */
class PortalLogoTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearRepresentanteConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Logo Portal',
            'dominio'            => 'colegiologoportal' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-LP',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Representante');
        Representante::create([
            'user_id' => $user->id, 'cedula' => (string) random_int(100000000, 999999999),
            'nombres' => 'Rep', 'apellidos' => 'Logo', 'telefono' => '8090000000',
        ]);

        return [$user, $tenant];
    }

    public function test_el_portal_muestra_el_logo_del_tenant_cuando_esta_configurado(): void
    {
        [$user, $tenant] = $this->crearRepresentanteConTenant();

        Storage::fake('public');
        $path = 'branding/logo-test.png';
        Storage::disk('public')->put($path, 'contenido-fake');
        Setting::set('system_logo', $path);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertSee(Storage::url($path), false);
    }

    public function test_el_portal_muestra_la_abreviatura_como_respaldo_sin_logo_configurado(): void
    {
        [$user, $tenant] = $this->crearRepresentanteConTenant();
        app()->instance('tenant', $tenant);
        Setting::set('system_abbr', 'CLP');
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertSee('CLP');

        $html = $response->getContent();
        $inicio = strpos($html, 'class="prt-logo"');
        $fin    = strpos($html, '</a>', $inicio);
        $bloqueLogo = substr($html, $inicio, $fin - $inicio);

        $this->assertStringNotContainsString('<img', $bloqueLogo, 'Sin logo configurado, el badge del portal no debe renderizar una <img>.');
    }
}
