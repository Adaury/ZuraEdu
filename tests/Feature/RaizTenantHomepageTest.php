<?php

namespace Tests\Feature;

use App\Models\PaginaSeccion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Roadmap de producto: la raíz "/" mostraba SIEMPRE el landing genérico de
 * marketing del SaaS, sin importar el subdominio visitado — una institución
 * nunca veía su propio sitio en su propio dominio, solo en /sitio.
 * ResolveTenant ahora expone tenant.dominio_propio (true solo cuando el
 * host identificó a ESA institución específica, no un fallback genérico) y
 * PublicSiteController::raiz() decide entre landing y el sitio propio.
 */
class RaizTenantHomepageTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre, bool $modoPublico = true): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => $modoPublico]);

        return $tenant;
    }

    private function urlRaiz(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/';
    }

    private function crearHero(Tenant $tenant, string $titulo): void
    {
        app()->instance('tenant', $tenant);
        PaginaSeccion::create([
            'tenant_id' => $tenant->id, 'tipo' => 'hero', 'orden' => 1,
            'activo' => true, 'contenido' => ['titulo' => $titulo],
        ]);
        app()->forgetInstance('tenant');
    }

    public function test_un_host_generico_no_reconocido_muestra_el_landing_de_marketing(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('ZuraEdu — Sistema educativo inteligente');
    }

    public function test_el_subdominio_de_una_institucion_muestra_su_propio_sitio_en_la_raiz(): void
    {
        $tenant = $this->crearTenant('Colegio Raiz Uno');
        $this->crearHero($tenant, 'Bienvenidos a Colegio Raiz Uno');

        $response = $this->get($this->urlRaiz($tenant));

        $response->assertOk();
        $response->assertSee('Bienvenidos a Colegio Raiz Uno');
        $response->assertDontSee('ZuraEdu — Sistema educativo inteligente');
    }

    public function test_el_subdominio_sin_modo_publico_muestra_no_disponible_en_la_raiz(): void
    {
        $tenant = $this->crearTenant('Colegio Raiz Apagado', modoPublico: false);

        $response = $this->get($this->urlRaiz($tenant));

        $response->assertOk();
        $response->assertSee('no tiene su portal público activado');
    }

    public function test_dos_instituciones_ven_contenido_distinto_en_su_propia_raiz(): void
    {
        $tenantA = $this->crearTenant('Colegio Raiz A');
        $this->crearHero($tenantA, 'Exclusivo Raiz A');

        $tenantB = $this->crearTenant('Colegio Raiz B');
        $this->crearHero($tenantB, 'Exclusivo Raiz B');

        $responseA = $this->get($this->urlRaiz($tenantA));
        $responseB = $this->get($this->urlRaiz($tenantB));

        $responseA->assertSee('Exclusivo Raiz A');
        $responseA->assertDontSee('Exclusivo Raiz B');
        $responseB->assertSee('Exclusivo Raiz B');
        $responseB->assertDontSee('Exclusivo Raiz A');
    }

    public function test_superadmin_impersonando_ve_el_sitio_propio_del_tenant_en_la_raiz(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $tenantPlataforma = Tenant::create([
            'nombre_institucion' => 'ZuraEdu Plataforma',
            'dominio'            => 'plataformaraiz' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);
        $superAdmin = User::factory()->create(['activo' => true, 'tenant_id' => $tenantPlataforma->id]);
        $superAdmin->assignRole('super_admin');

        $tenant = $this->crearTenant('Colegio Impersonado');
        $this->crearHero($tenant, 'Vista Impersonada');

        $response = $this->actingAs($superAdmin)
            ->withSession(['sa_tenant_id' => $tenant->id])
            ->get('/');

        $response->assertOk();
        $response->assertSee('Vista Impersonada');
    }
}
