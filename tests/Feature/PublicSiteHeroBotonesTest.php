<?php

namespace Tests\Feature;

use App\Models\PaginaSeccion;
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

    private function crearHero(Tenant $tenant, array $contenido): void
    {
        app()->instance('tenant', $tenant);
        PaginaSeccion::create([
            'tenant_id' => $tenant->id, 'tipo' => 'hero', 'orden' => 1,
            'activo' => true, 'contenido' => $contenido,
        ]);
        app()->forgetInstance('tenant');
    }

    public function test_el_boton_1_usa_la_url_configurada_por_el_admin(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Uno');
        $this->crearHero($tenant, [
            'btn_texto' => 'Portal de Padres',
            'btn_url' => 'https://portal.externo.test/padres',
        ]);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="https://portal.externo.test/padres"', false);
        $response->assertSee('Portal de Padres');
    }

    public function test_el_boton_1_usa_login_por_defecto_si_no_hay_url_configurada(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Defecto');
        $this->crearHero($tenant, ['btn_texto' => 'Iniciar Sesión']);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="' . route('login') . '"', false);
    }

    public function test_el_boton_2_usa_inscripcion_por_defecto_si_no_hay_url_configurada(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Dos Defecto');
        $this->crearHero($tenant, ['btn2_texto' => 'Solicitar Admisión']);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="' . route('inscripcion') . '"', false);
    }

    public function test_el_boton_2_usa_la_url_configurada_por_el_admin(): void
    {
        $tenant = $this->crearTenant('Colegio Boton Dos');
        $this->crearHero($tenant, [
            'btn2_texto' => 'Ver Redes',
            'btn2_url' => '#contacto',
        ]);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('href="#contacto"', false);
    }

    public function test_la_url_configurada_de_un_tenant_no_afecta_a_otro(): void
    {
        $tenantA = $this->crearTenant('Colegio Boton A');
        $this->crearHero($tenantA, ['btn_texto' => 'Entrar', 'btn_url' => 'https://a.test/entrar']);

        $tenantB = $this->crearTenant('Colegio Boton B');
        $this->crearHero($tenantB, ['btn_texto' => 'Entrar']);

        $responseB = $this->get($this->urlSitio($tenantB));

        $responseB->assertOk();
        $responseB->assertDontSee('https://a.test/entrar');
        $responseB->assertSee('href="' . route('login') . '"', false);
    }
}
