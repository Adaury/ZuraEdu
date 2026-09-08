<?php

namespace Tests\Feature;

use App\Models\PaginaSeccion;
use App\Models\Publicacion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evolución del portal público: enlace "Acceder al panel" y el logo
 * llevando al inicio en las 4 páginas públicas del tenant (/sitio, /galeria,
 * /sitio/noticias, /sitio/noticias/{id}); y el lápiz de edición sobre cada
 * publicación, visible solo para quien tiene el permiso de administrarlas.
 */
class PublicSitioNavegacionTest extends TestCase
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

    public function test_el_enlace_acceder_al_panel_aparece_en_las_4_paginas_publicas(): void
    {
        $tenant = $this->crearTenant('Colegio Nav Acceder');
        app()->instance('tenant', $tenant);
        $publicacion = Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Nota', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        foreach (['/sitio', '/galeria', '/sitio/noticias', "/sitio/noticias/{$publicacion->id}"] as $path) {
            $response = $this->get($this->url($tenant, $path));
            $response->assertOk();
            $response->assertSee(route('login'), false);
        }
    }

    public function test_el_logo_del_navbar_enlaza_al_inicio_del_sitio(): void
    {
        $tenant = $this->crearTenant('Colegio Nav Logo');

        $response = $this->get($this->url($tenant, '/sitio/noticias'));

        $response->assertOk();
        $response->assertSee('href="' . route('sitio.show') . '" class="brand-link"', false);
    }

    public function test_un_admin_con_permiso_ve_el_lapiz_de_editar_en_la_tarjeta(): void
    {
        $tenant = $this->crearTenant('Colegio Nav Lapiz Admin');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        app()->instance('tenant', $tenant);
        $publicacion = Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Editable', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        PaginaSeccion::create(['tenant_id' => $tenant->id, 'tipo' => 'noticias', 'orden' => 1, 'activo' => true, 'contenido' => ['limite' => 6]]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        $response->assertSee(route('admin.publicaciones.edit', $publicacion), false);
    }

    public function test_un_visitante_anonimo_no_ve_el_lapiz_de_editar(): void
    {
        $tenant = $this->crearTenant('Colegio Nav Lapiz Anonimo');
        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'No Editable Para Mi', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        // Busca el atributo HTML, no el selector CSS del <style> (que
        // siempre está presente aunque nadie vea el ícono de editar).
        $response->assertDontSee('class="noticia-editar"', false);
    }

    public function test_un_rol_sin_el_permiso_no_ve_el_lapiz_de_editar(): void
    {
        $tenant = $this->crearTenant('Colegio Nav Lapiz Director');
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Director'); // Director NO tiene gestionar-configuracion (ver RolesSeeder).

        app()->instance('tenant', $tenant);
        Publicacion::create([
            'tipo' => 'noticia', 'titulo' => 'Sin Permiso Director', 'contenido' => 'x',
            'fecha' => now(), 'estado' => 'publicado', 'visible' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get($this->url($tenant, '/sitio'));

        $response->assertOk();
        // Busca el atributo HTML, no el selector CSS del <style> (que
        // siempre está presente aunque nadie vea el ícono de editar).
        $response->assertDontSee('class="noticia-editar"', false);
    }
}
