<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evolución del portal público: espacios de anuncios (Google Ads u otro) en
 * los extremos de /sitio, configurables por cada centro desde su Homepage.
 * El código se guarda tal cual (SanitizeInput::$allowedEmbedFields) porque
 * depende de <script>, que se elimina en cualquier otro campo — es una
 * excepción consciente y aceptada: el admin del centro controla su propio
 * sitio público, el riesgo no cruza a otros tenants.
 */
class PublicSitioAdsTest extends TestCase
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

    public function test_el_codigo_de_anuncios_sobrevive_al_guardado_pese_a_tener_script(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Ads Guardar');

        $codigo = '<script async src="https://pagead2.googlesyndication.com/x.js"></script><ins class="adsbygoogle"></ins>';

        $this->actingAs($user)->post(route('admin.homepage.update'), [
            'hp_ads_izquierda' => $codigo,
        ]);

        app()->instance('tenant', $tenant);
        $this->assertSame($codigo, ConfigInstitucional::get('hp_ads_izquierda'));
        app()->forgetInstance('tenant');
    }

    public function test_el_anuncio_configurado_se_muestra_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Ads Mostrar');

        $this->actingAs($user)->post(route('admin.homepage.update'), [
            'hp_ads_derecha' => '<ins class="adsbygoogle" data-ad-slot="1234"></ins>',
        ]);

        $response = $this->get('http://' . $tenant->dominio . '.zuraedu.test/sitio');

        $response->assertOk();
        $response->assertSee('data-ad-slot="1234"', false);
    }

    public function test_sin_codigo_configurado_no_se_muestra_el_espacio_de_anuncios(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Ads Vacio',
            'dominio'            => 'colegioadsvacio' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => true]);

        $response = $this->get('http://' . $tenant->dominio . '.zuraedu.test/sitio');

        $response->assertOk();
        // Busca el atributo HTML, no el selector CSS del <style> (que
        // siempre está presente aunque no haya ningún anuncio configurado).
        $response->assertDontSee('class="ads-lateral', false);
    }

    public function test_el_anuncio_de_un_tenant_no_aparece_en_el_sitio_de_otro(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Ads A');
        $this->actingAs($userA)->post(route('admin.homepage.update'), [
            'hp_ads_izquierda' => '<ins data-ad-slot="solo-de-a"></ins>',
        ]);

        $tenantB = Tenant::create([
            'nombre_institucion' => 'Colegio Ads B',
            'dominio'            => 'colegioadsb' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenantB->id, 'feature' => 'modo_publico', 'activo' => true]);

        $response = $this->get('http://' . $tenantB->dominio . '.zuraedu.test/sitio');

        $response->assertOk();
        $response->assertDontSee('solo-de-a');
    }
}
