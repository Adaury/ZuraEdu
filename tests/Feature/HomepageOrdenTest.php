<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto — Fase 2 del portal público: control de orden de las
 * 5 secciones (hp_orden, string CSV en ConfigInstitucional) vía botones
 * subir/bajar en el editor de Homepage. Sin drag-and-drop, sin tabla nueva.
 */
class HomepageOrdenTest extends TestCase
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

    private function urlSitio(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio';
    }

    public function test_orden_por_defecto_es_hero_about_stats_features_contacto(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Orden Default');

        app()->instance('tenant', $tenant);
        $orden = \App\Http\Controllers\Admin\HomepageController::ordenActual();
        app()->forgetInstance('tenant');

        $this->assertSame(['hero', 'carrusel', 'about', 'stats', 'features', 'noticias', 'contacto'], $orden);
    }

    public function test_mover_una_seccion_arriba_actualiza_hp_orden(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Subir');

        $response = $this->actingAs($user)->post(route('admin.homepage.orden', ['stats', 'arriba']));

        $response->assertRedirect(route('admin.homepage.edit'));

        app()->instance('tenant', $tenant);
        $this->assertSame('hero,carrusel,stats,about,features,noticias,contacto', ConfigInstitucional::get('hp_orden'));
    }

    public function test_mover_una_seccion_abajo_actualiza_hp_orden(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Bajar');

        $response = $this->actingAs($user)->post(route('admin.homepage.orden', ['about', 'abajo']));

        $response->assertRedirect(route('admin.homepage.edit'));

        app()->instance('tenant', $tenant);
        $this->assertSame('hero,carrusel,stats,about,features,noticias,contacto', ConfigInstitucional::get('hp_orden'));
    }

    public function test_mover_la_primera_seccion_hacia_arriba_no_cambia_nada(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Limite Arriba');

        $this->actingAs($user)->post(route('admin.homepage.orden', ['hero', 'arriba']));

        app()->instance('tenant', $tenant);
        $orden = \App\Http\Controllers\Admin\HomepageController::ordenActual();
        $this->assertSame(['hero', 'carrusel', 'about', 'stats', 'features', 'noticias', 'contacto'], $orden);
    }

    public function test_mover_la_ultima_seccion_hacia_abajo_no_cambia_nada(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Limite Abajo');

        $this->actingAs($user)->post(route('admin.homepage.orden', ['contacto', 'abajo']));

        app()->instance('tenant', $tenant);
        $orden = \App\Http\Controllers\Admin\HomepageController::ordenActual();
        $this->assertSame(['hero', 'carrusel', 'about', 'stats', 'features', 'noticias', 'contacto'], $orden);
    }

    public function test_seccion_invalida_responde_404(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Seccion Invalida');

        $response = $this->actingAs($user)->post(route('admin.homepage.orden', ['no-existe', 'arriba']));

        $response->assertNotFound();
    }

    public function test_direccion_invalida_responde_404(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Direccion Invalida');

        $response = $this->actingAs($user)->post(route('admin.homepage.orden', ['hero', 'izquierda']));

        $response->assertNotFound();
    }

    public function test_un_usuario_sin_permiso_no_puede_reordenar(): void
    {
        // Coordinador Académico sí entra al panel admin (EnsureAdminAccess lo
        // permite) pero no tiene 'gestionar-configuracion' — a diferencia de
        // Docente/Estudiante/Representante, que ni siquiera llegan a esta
        // ruta porque EnsureAdminAccess los redirige antes (302) a su portal.
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Sin Permiso',
            'dominio'            => 'colegiosinpermiso' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Coordinador Académico');

        $response = $this->actingAs($user)->post(route('admin.homepage.orden', ['hero', 'arriba']));

        $response->assertForbidden();
    }

    public function test_el_orden_guardado_se_refleja_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Orden Sitio');

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_contacto_direccion', 'Calle Primero 1');
        ConfigInstitucional::set('hp_about_titulo', 'Sección Segunda');
        app()->forgetInstance('tenant');

        // Mover "contacto" hasta el principio (4 pasos arriba).
        $this->actingAs($user)->post(route('admin.homepage.orden', ['contacto', 'arriba']));
        $this->actingAs($user)->post(route('admin.homepage.orden', ['contacto', 'arriba']));
        $this->actingAs($user)->post(route('admin.homepage.orden', ['contacto', 'arriba']));
        $this->actingAs($user)->post(route('admin.homepage.orden', ['contacto', 'arriba']));

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $html = $response->getContent();
        $posContacto = strpos($html, 'Calle Primero 1');
        $posAbout    = strpos($html, 'Sección Segunda');

        $this->assertNotFalse($posContacto);
        $this->assertNotFalse($posAbout);
        $this->assertLessThan($posAbout, $posContacto, 'Contacto debía aparecer antes que About tras reordenar.');
    }

    public function test_reordenar_un_tenant_no_afecta_el_orden_de_otro(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Orden A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Orden B');

        $this->actingAs($userA)->post(route('admin.homepage.orden', ['stats', 'arriba']));

        app()->instance('tenant', $tenantA);
        $ordenA = \App\Http\Controllers\Admin\HomepageController::ordenActual();
        app()->forgetInstance('tenant');

        app()->instance('tenant', $tenantB);
        $ordenB = \App\Http\Controllers\Admin\HomepageController::ordenActual();
        app()->forgetInstance('tenant');

        $this->assertSame(['hero', 'carrusel', 'stats', 'about', 'features', 'noticias', 'contacto'], $ordenA);
        $this->assertSame(['hero', 'carrusel', 'about', 'stats', 'features', 'noticias', 'contacto'], $ordenB);
    }
}
