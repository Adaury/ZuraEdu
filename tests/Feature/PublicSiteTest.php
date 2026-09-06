<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto: "portal público por centro" — Fase 1. Reutiliza el
 * contenido que Admin\HomepageController ya permite configurar en
 * ConfigInstitucional (claves hp_*); PublicSiteController::show() solo lo
 * renderiza en /sitio, sin autenticación, sin migración nueva y sin tocar
 * el editor existente.
 *
 * La ruta es pública (sin login), así que ResolveTenant no puede resolver
 * el tenant vía auth()->id() como en el resto de la suite — hay que
 * resolverlo igual que en producción: por subdominio (dominio.zuraedu.com).
 */
class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
            'color_primario' => '#123456', 'color_secundario' => '#654321',
        ]);
    }

    private function urlSitio(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio';
    }

    public function test_muestra_el_bloque_hero_cuando_esta_visible_y_configurado(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Uno');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_titulo', 'Bienvenidos a Colegio Sitio Uno');
        ConfigInstitucional::set('hp_hero_subtitulo', 'Formando líderes del mañana');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('Bienvenidos a Colegio Sitio Uno');
        $response->assertSee('Formando líderes del mañana');
    }

    public function test_oculta_el_bloque_hero_cuando_no_esta_visible(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Dos');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_titulo', 'Título Oculto XYZ');
        ConfigInstitucional::set('hp_hero_visible', '0');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertDontSee('Título Oculto XYZ');
    }

    public function test_muestra_estadisticas_configuradas(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Tres');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_stat1_numero', '500+');
        ConfigInstitucional::set('hp_stat1_label', 'Estudiantes');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('500+');
        $response->assertSee('Estudiantes');
    }

    public function test_muestra_datos_de_contacto_configurados(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Cuatro');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_contacto_direccion', 'Av. Siempre Viva 123');
        ConfigInstitucional::set('hp_contacto_telefono', '809-000-0000');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('Av. Siempre Viva 123');
        $response->assertSee('809-000-0000');
    }

    public function test_tenant_sin_configuracion_muestra_estado_vacio_sin_error(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Vacio');
        app()->instance('tenant', $tenant);
        // Todos los bloques con visible=1 por defecto no muestran nada si no
        // tienen contenido (about/features/contacto), salvo Hero — lo apagamos
        // explícitamente para forzar el estado "sin nada que mostrar".
        ConfigInstitucional::set('hp_hero_visible', '0');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('aún no ha publicado contenido');
    }

    public function test_un_tenant_no_ve_el_contenido_configurado_por_otro_tenant(): void
    {
        $tenantA = $this->crearTenant('Colegio Sitio A');
        app()->instance('tenant', $tenantA);
        ConfigInstitucional::set('hp_hero_titulo', 'Exclusivo de A');
        app()->forgetInstance('tenant');

        $tenantB = $this->crearTenant('Colegio Sitio B');
        app()->instance('tenant', $tenantB);
        ConfigInstitucional::set('hp_hero_titulo', 'Exclusivo de B');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenantB));

        $response->assertOk();
        $response->assertSee('Exclusivo de B');
        $response->assertDontSee('Exclusivo de A');
    }

    public function test_usa_el_color_primario_del_tenant_como_variable_css_por_defecto(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Color');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('#123456');
    }

    public function test_la_ruta_sitio_esta_en_las_rutas_publicas_del_middleware(): void
    {
        $ref = new \ReflectionClass(\App\Http\Middleware\ResolveTenant::class);
        $rutas = $ref->getConstant('RUTAS_PUBLICAS');

        $this->assertContains('sitio', $rutas);
        $this->assertContains('sitio/*', $rutas);
    }
}
