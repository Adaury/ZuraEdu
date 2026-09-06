<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\ConfigInstitucional;
use App\Models\FotoAlbum;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo al auditar el carrusel: /galeria (GaleriaController::galeriaPublica())
 * ya existía como página pública, pero usaba branding genérico de la
 * plataforma (env('APP_PRODUCT_NAME')) en vez del nombre/logo del tenant, y
 * no respetaba el gate de modo_publico. Se corrigió y se conectó al mismo
 * sistema del portal público.
 */
class GaleriaPublicaBrandingTest extends TestCase
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

    private function url(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/galeria';
    }

    public function test_la_galeria_publica_usa_el_nombre_del_tenant_no_el_generico(): void
    {
        $tenant = $this->crearTenant('Colegio Galeria Branding');

        $response = $this->get($this->url($tenant));

        $response->assertOk();
        $response->assertSee('Colegio Galeria Branding');
    }

    public function test_la_galeria_publica_muestra_los_albumes_activos_del_tenant(): void
    {
        $tenant = $this->crearTenant('Colegio Galeria Albumes');
        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Graduación 2026', 'activo' => true, 'orden' => 0]);
        FotoAlbum::create(['album_id' => $album->id, 'ruta' => 'galeria/1/foto1.jpg', 'orden' => 1]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant));

        $response->assertOk();
        $response->assertSee('Graduación 2026');
    }

    public function test_sin_modo_publico_la_galeria_muestra_no_disponible(): void
    {
        $tenant = $this->crearTenant('Colegio Galeria Apagada', modoPublico: false);

        $response = $this->get($this->url($tenant));

        $response->assertOk();
        $response->assertSee('no tiene su portal público activado');
    }

    public function test_los_albumes_de_un_tenant_no_aparecen_en_la_galeria_de_otro(): void
    {
        $tenantA = $this->crearTenant('Colegio Galeria A');
        app()->instance('tenant', $tenantA);
        $albumA = Album::create(['titulo' => 'Álbum Exclusivo A', 'activo' => true, 'orden' => 0]);
        FotoAlbum::create(['album_id' => $albumA->id, 'ruta' => 'galeria/1/foto1.jpg', 'orden' => 1]);
        app()->forgetInstance('tenant');

        $tenantB = $this->crearTenant('Colegio Galeria B');

        $response = $this->get($this->url($tenantB));

        $response->assertOk();
        $response->assertDontSee('Álbum Exclusivo A');
    }
}
