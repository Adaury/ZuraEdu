<?php

namespace Tests\Feature;

use App\Models\Publicacion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evolución del portal público: sección "Noticias y Publicaciones" en /sitio,
 * listado completo en /sitio/noticias y detalle en /sitio/noticias/{id}.
 * Publicacion es un modelo nuevo, separado de Comunicado (interno).
 */
class PublicSitioNoticiasTest extends TestCase
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

    private function url(Tenant $tenant, string $path = '/'): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test' . $path;
    }

    public function test_una_publicacion_publicada_y_vigente_aparece_en_la_portada(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Uno');
        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Inicio de Clases', 'contenido' => 'Contenido',
            'fecha' => now()->subDay(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertSee('Inicio de Clases');
    }

    public function test_una_publicacion_en_borrador_no_aparece_en_la_portada(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Borrador');
        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Aun No Publicar', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'borrador', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertDontSee('Aun No Publicar');
    }

    public function test_una_publicacion_no_visible_no_aparece_aunque_este_publicada(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Oculta');
        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Oculta A Proposito', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => false,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertDontSee('Oculta A Proposito');
    }

    public function test_una_publicacion_con_fecha_futura_no_aparece_todavia(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Futura');
        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'convocatoria', 'titulo' => 'Evento Futuro', 'contenido' => 'x',
            'fecha' => now()->addDays(5), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertDontSee('Evento Futuro');
    }

    public function test_el_listado_completo_de_noticias_muestra_las_publicadas(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Listado');
        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Noticia Uno', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio/noticias'));

        $response->assertOk();
        $response->assertSee('Noticia Uno');
    }

    public function test_el_detalle_de_una_publicacion_publicada_es_visible(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Detalle');
        app()->instance('tenant', $tenant);
        $publicacion = Publicacion::create([
            'tipo' => 'logro', 'titulo' => 'Logro Destacado', 'contenido' => 'Contenido completo aquí.',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, "/sitio/noticias/{$publicacion->id}"));

        $response->assertOk();
        $response->assertSee('Logro Destacado');
        $response->assertSee('Contenido completo aquí');
    }

    public function test_el_detalle_de_una_publicacion_en_borrador_da_404(): void
    {
        $tenant = $this->crearTenant('Colegio Noticias Detalle Borrador');
        app()->instance('tenant', $tenant);
        $publicacion = Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'No Debe Verse', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'borrador', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, "/sitio/noticias/{$publicacion->id}"));

        $response->assertNotFound();
    }

    public function test_una_publicacion_de_otro_tenant_da_404(): void
    {
        $tenantA = $this->crearTenant('Colegio Noticias A');
        app()->instance('tenant', $tenantA);
        $publicacionA = Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'De A', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $tenantB = $this->crearTenant('Colegio Noticias B');

        $response = $this->get($this->url($tenantB, "/sitio/noticias/{$publicacionA->id}"));

        $response->assertNotFound();
    }
}
