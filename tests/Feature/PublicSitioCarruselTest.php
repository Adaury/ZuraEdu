<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\FotoAlbum;
use App\Models\PaginaSeccion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evolución del portal público: carrusel principal en /sitio — reutiliza
 * Album/FotoAlbum (Galería, ya existente) en vez de un sistema de subida
 * de fotos nuevo. Un único álbum marcado con mostrar_en_sitio=true.
 */
class PublicSitioCarruselTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

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

    private function url(Tenant $tenant, string $path): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test' . $path;
    }

    private function crearFotos(Album $album, int $cantidad = 5): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            FotoAlbum::create(['album_id' => $album->id, 'ruta' => "galeria/{$album->id}/foto{$i}.jpg", 'orden' => $i]);
        }
    }

    public function test_el_album_marcado_como_carrusel_aparece_en_el_sitio(): void
    {
        $tenant = $this->crearTenant('Colegio Carrusel Uno');
        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Carrusel Principal', 'descripcion' => 'Nuestra historia en imágenes.', 'activo' => true, 'mostrar_en_sitio' => true, 'orden' => 0]);
        $this->crearFotos($album, 4);
        FotoAlbum::create(['album_id' => $album->id, 'ruta' => 'galeria/1/foto5.jpg', 'titulo' => 'Nuestra Fachada', 'orden' => 5]);
        // El centro debe haber agregado el bloque "Carrusel" en el constructor
        // visual (sin album_id explícito, cae al único álbum marcado en Galería).
        PaginaSeccion::create(['tenant_id' => $tenant->id, 'tipo' => 'carrusel', 'orden' => 1, 'activo' => true, 'contenido' => []]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertSee('Nuestra Fachada');
        $response->assertSee('Nuestra historia en imágenes.');
    }

    public function test_un_album_con_menos_de_5_fotos_no_aparece_como_carrusel_aunque_este_marcado(): void
    {
        $tenant = $this->crearTenant('Colegio Carrusel Pocas Fotos');
        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Carrusel Incompleto', 'activo' => true, 'mostrar_en_sitio' => true, 'orden' => 0]);
        FotoAlbum::create(['album_id' => $album->id, 'ruta' => 'galeria/1/foto1.jpg', 'titulo' => 'Foto Suelta', 'orden' => 1]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertDontSee('Foto Suelta');
    }

    public function test_un_album_no_marcado_no_aparece_como_carrusel(): void
    {
        $tenant = $this->crearTenant('Colegio Carrusel Sin Marcar');
        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Álbum Normal', 'activo' => true, 'mostrar_en_sitio' => false, 'orden' => 0]);
        FotoAlbum::create(['album_id' => $album->id, 'ruta' => 'galeria/1/foto1.jpg', 'titulo' => 'No Debe Salir', 'orden' => 1]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertDontSee('No Debe Salir');
    }

    public function test_marcar_un_nuevo_album_desmarca_el_anterior(): void
    {
        $tenant = $this->crearTenant('Colegio Carrusel Exclusivo');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $albumA = Album::create(['titulo' => 'Álbum A', 'activo' => true, 'mostrar_en_sitio' => true, 'orden' => 0]);
        $albumB = Album::create(['titulo' => 'Álbum B', 'activo' => true, 'mostrar_en_sitio' => false, 'orden' => 1]);
        $this->crearFotos($albumB, 5);
        app()->forgetInstance('tenant');

        $this->actingAs($user)->put(route('admin.galeria.update', $albumB), [
            'titulo' => 'Álbum B', 'activo' => '1', 'mostrar_en_sitio' => '1',
        ]);

        $this->assertFalse($albumA->fresh()->mostrar_en_sitio);
        $this->assertTrue($albumB->fresh()->mostrar_en_sitio);
    }

    public function test_no_se_puede_marcar_como_carrusel_con_menos_de_5_fotos(): void
    {
        $tenant = $this->crearTenant('Colegio Carrusel Insuficiente');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Álbum Nuevo', 'activo' => true, 'mostrar_en_sitio' => false, 'orden' => 0]);
        $this->crearFotos($album, 2);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->put(route('admin.galeria.update', $album), [
            'titulo' => 'Álbum Nuevo', 'activo' => '1', 'mostrar_en_sitio' => '1',
        ]);

        $response->assertSessionHasErrors('mostrar_en_sitio');
        $this->assertFalse($album->fresh()->mostrar_en_sitio);
    }

    public function test_toggle_carrusel_marca_el_album_desde_la_pantalla_de_fotos(): void
    {
        $tenant = $this->crearTenant('Colegio Toggle Carrusel');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Álbum Fotos Listas', 'activo' => true, 'mostrar_en_sitio' => false, 'orden' => 0]);
        $this->crearFotos($album, 5);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.galeria.toggleCarrusel', $album), [
            'mostrar_en_sitio' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue($album->fresh()->mostrar_en_sitio);
    }

    public function test_toggle_carrusel_rechaza_con_menos_de_5_fotos(): void
    {
        $tenant = $this->crearTenant('Colegio Toggle Insuficiente');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Álbum Pocas Fotos', 'activo' => true, 'mostrar_en_sitio' => false, 'orden' => 0]);
        $this->crearFotos($album, 3);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.galeria.toggleCarrusel', $album), [
            'mostrar_en_sitio' => '1',
        ]);

        $response->assertSessionHasErrors('mostrar_en_sitio');
        $this->assertFalse($album->fresh()->mostrar_en_sitio);
    }

    public function test_toggle_carrusel_puede_quitar_el_album_del_carrusel(): void
    {
        $tenant = $this->crearTenant('Colegio Toggle Quitar');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $album = Album::create(['titulo' => 'Álbum Marcado', 'activo' => true, 'mostrar_en_sitio' => true, 'orden' => 0]);
        $this->crearFotos($album, 5);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.galeria.toggleCarrusel', $album), [
            'mostrar_en_sitio' => '0',
        ]);

        $response->assertRedirect();
        $this->assertFalse($album->fresh()->mostrar_en_sitio);
    }

    public function test_el_carrusel_de_un_tenant_no_afecta_a_otro(): void
    {
        $tenantA = $this->crearTenant('Colegio Carrusel A');
        app()->instance('tenant', $tenantA);
        $albumA = Album::create(['titulo' => 'Carrusel A', 'activo' => true, 'mostrar_en_sitio' => true, 'orden' => 0]);
        $this->crearFotos($albumA, 4);
        FotoAlbum::create(['album_id' => $albumA->id, 'ruta' => 'galeria/1/a.jpg', 'titulo' => 'Foto Exclusiva A', 'orden' => 5]);
        app()->forgetInstance('tenant');

        $tenantB = $this->crearTenant('Colegio Carrusel B');

        $response = $this->get($this->url($tenantB, '/sitio'));

        $response->assertOk();
        $response->assertDontSee('Foto Exclusiva A');
    }
}
