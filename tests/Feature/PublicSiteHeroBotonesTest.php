<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug real reportado por el usuario: el Botón 1 del Hero en /sitio estaba
 * hardcodeado a route('inscripcion') sin importar el texto que el admin
 * escribiera — un admin que puso "Iniciar Sesión" esperando el login real
 * terminaba en el formulario de pre-matrícula. Se agregan hp_hero_btn_url /
 * hp_hero_btn2_url para que el destino de cada botón sea configurable,
 * con /login y /inscripcion como fallback si se dejan vacíos (mismo
 * comportamiento que tenían antes los tenants que nunca configuraron esto).
 */
class PublicSiteHeroBotonesTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => true]);

        return $tenant;
    }

    private function urlSitio(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio';
    }

    public function test_el_boton_1_usa_la_url_configurada_por_el_admin(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Uno');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_btn_texto', 'Portal de Padres');
        ConfigInstitucional::set('hp_hero_btn_url', 'https://portal.externo.test/padres');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="https://portal.externo.test/padres"', false);
        $response->assertSee('Portal de Padres');
    }

    public function test_el_boton_1_usa_login_por_defecto_si_no_hay_url_configurada(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Defecto');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_btn_texto', 'Iniciar Sesión');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="' . route('login') . '"', false);
    }

    public function test_el_boton_2_usa_inscripcion_por_defecto_si_no_hay_url_configurada(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Dos Defecto');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_btn2_texto', 'Solicitar Admisión');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="' . route('inscripcion') . '"', false);
    }

    public function test_el_boton_2_usa_la_url_configurada_por_el_admin(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Dos');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_btn2_texto', 'Ver Redes');
        ConfigInstitucional::set('hp_hero_btn2_url', '#contacto');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="#contacto"', false);
    }

    public function test_la_url_configurada_de_un_tenant_no_afecta_a_otro(): void
    {
        $tenantA = $this->crearTenant('Colegio Boton A');
        app()->instance('tenant', $tenantA);
        ConfigInstitucional::set('hp_hero_btn_texto', 'Entrar');
        ConfigInstitucional::set('hp_hero_btn_url', 'https://a.test/entrar');
        app()->forgetInstance('tenant');

        $tenantB = $this->crearTenant('Colegio Boton B');
        app()->instance('tenant', $tenantB);
        ConfigInstitucional::set('hp_hero_btn_texto', 'Entrar');
        app()->forgetInstance('tenant');

        $responseB = $this->get($this->urlSitio($tenantB));

        $responseB->assertOk();
        $responseB->assertDontSee('https://a.test/entrar');
        $responseB->assertSee('href="' . route('login') . '"', false);
    }
}
