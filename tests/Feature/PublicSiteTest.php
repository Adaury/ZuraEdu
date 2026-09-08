<?php

namespace Tests\Feature;

use App\Models\PaginaSeccion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto: "portal público por centro". PublicSiteController::show()
 * renderiza en /sitio los bloques que el propio centro arma en el constructor
 * visual (tabla pagina_secciones), sin autenticación.
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
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
            'color_primario' => '#123456', 'color_secundario' => '#654321',
        ]);

        // Fase 2: /sitio ahora exige el feature modo_publico activo. Estos
        // tests verifican el CONTENIDO renderizado, no el gate en sí (que
        // tiene su propio archivo de tests) — se activa aquí para no
        // duplicar esa cobertura en cada test de contenido.
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => true]);

        return $tenant;
    }

    private function urlSitio(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio';
    }

    private function crearSeccion(Tenant $tenant, string $tipo, array $contenido, bool $activo = true): void
    {
        app()->instance('tenant', $tenant);
        PaginaSeccion::create([
            'tenant_id' => $tenant->id, 'tipo' => $tipo, 'orden' => 1,
            'activo' => $activo, 'contenido' => $contenido,
        ]);
        app()->forgetInstance('tenant');
    }

    public function test_muestra_el_bloque_hero_cuando_esta_visible_y_configurado(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Uno');
        $this->crearSeccion($tenant, 'hero', [
            'titulo' => 'Bienvenidos a Colegio Sitio Uno',
            'subtitulo' => 'Formando líderes del mañana',
        ]);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('Bienvenidos a Colegio Sitio Uno');
        $response->assertSee('Formando líderes del mañana');
    }

    public function test_oculta_el_bloque_hero_cuando_no_esta_visible(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Dos');
        $this->crearSeccion($tenant, 'hero', ['titulo' => 'Título Oculto XYZ'], activo: false);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertDontSee('Título Oculto XYZ');
    }

    public function test_muestra_estadisticas_configuradas(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Tres');
        $this->crearSeccion($tenant, 'stats', [
            'items' => [['numero' => '500+', 'label' => 'Estudiantes']],
        ]);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('500+');
        $response->assertSee('Estudiantes');
    }

    public function test_muestra_datos_de_contacto_configurados(): void
    {
        $tenant = $this->crearTenant('Colegio Sitio Cuatro');
        $this->crearSeccion($tenant, 'contacto', [
            'direccion' => 'Av. Siempre Viva 123',
            'telefono' => '809-000-0000',
        ]);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('Av. Siempre Viva 123');
        $response->assertSee('809-000-0000');
    }

    public function test_tenant_sin_configuracion_muestra_estado_vacio_sin_error(): void
    {
        // Sin ningún bloque en pagina_secciones (tenant recién creado, nunca
        // pasó por el constructor visual) debe caer directo al empty-state.
        $tenant = $this->crearTenant('Colegio Sitio Vacio');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('aún no ha publicado contenido');
    }

    public function test_un_tenant_no_ve_el_contenido_configurado_por_otro_tenant(): void
    {
        $tenantA = $this->crearTenant('Colegio Sitio A');
        $this->crearSeccion($tenantA, 'hero', ['titulo' => 'Exclusivo de A']);

        $tenantB = $this->crearTenant('Colegio Sitio B');
        $this->crearSeccion($tenantB, 'hero', ['titulo' => 'Exclusivo de B']);

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
