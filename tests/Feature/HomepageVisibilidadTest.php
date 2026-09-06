<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug preexistente encontrado al construir el portal público (Fase 1,
 * roadmap): un checkbox de visibilidad desmarcado NO se envía en el POST
 * del formulario, así que HomepageController::update() (que usaba
 * $request->has($key) para decidir si guardar) nunca llegaba a persistir
 * "apagado" — el bloque quedaba con el último valor guardado ('1') para
 * siempre. Ahora se guarda explícitamente '1'/'0' en cada submit para las
 * 5 claves *_visible reales del editor (hp_hero_visible, hp_about_visible,
 * hp_stats_visible, hp_features_visible, hp_contacto_visible — no existe
 * un toggle independiente de redes sociales, están dentro de "contacto").
 */
class HomepageVisibilidadTest extends TestCase
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

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    private const CLAVES_VISIBILIDAD = [
        'hp_hero_visible', 'hp_about_visible', 'hp_stats_visible',
        'hp_features_visible', 'hp_contacto_visible',
    ];

    public function test_marcar_cada_checkbox_de_visibilidad_y_guardar_lo_deja_en_1(): void
    {
        foreach (self::CLAVES_VISIBILIDAD as $clave) {
            ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Vis ' . $clave);

            $response = $this->actingAs($user)->post(route('admin.homepage.update'), [
                $clave => '1',
            ]);

            $response->assertRedirect(route('admin.homepage.edit'));

            app()->instance('tenant', $tenant);
            $this->assertSame('1', ConfigInstitucional::get($clave), "La clave $clave debía quedar en '1' al marcarse.");
            app()->forgetInstance('tenant');
        }
    }

    public function test_desmarcar_cada_checkbox_de_visibilidad_y_guardar_lo_deja_en_0(): void
    {
        foreach (self::CLAVES_VISIBILIDAD as $clave) {
            ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Ocultar ' . $clave);

            // Estado inicial: visible (como viene por defecto en un editor nuevo).
            $this->actingAs($user)->post(route('admin.homepage.update'), [$clave => '1']);

            // Simula un checkbox desmarcado: la clave simplemente no se envía.
            $response = $this->actingAs($user)->post(route('admin.homepage.update'), []);

            $response->assertRedirect(route('admin.homepage.edit'));

            app()->instance('tenant', $tenant);
            $this->assertSame('0', ConfigInstitucional::get($clave), "La clave $clave debía quedar en '0' al desmarcarse.");
            app()->forgetInstance('tenant');
        }
    }

    public function test_desmarcar_hero_lo_oculta_realmente_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Sitio Vis');

        $this->actingAs($user)->post(route('admin.homepage.update'), [
            'hp_hero_visible' => '1',
            'hp_hero_titulo'  => 'Título Que Debe Desaparecer',
        ]);

        // Desmarcar (no se envía la clave) — el título queda guardado pero
        // el bloque debe dejar de mostrarse.
        $this->actingAs($user)->post(route('admin.homepage.update'), []);

        $response = $this->get('http://' . $tenant->dominio . '.zuraedu.test/sitio');

        $response->assertOk();
        $response->assertDontSee('Título Que Debe Desaparecer');
    }

    public function test_marcar_hero_lo_muestra_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Sitio Vis Marcado');

        $this->actingAs($user)->post(route('admin.homepage.update'), [
            'hp_hero_visible' => '1',
            'hp_hero_titulo'  => 'Título Que Debe Aparecer',
        ]);

        $response = $this->get('http://' . $tenant->dominio . '.zuraedu.test/sitio');

        $response->assertOk();
        $response->assertSee('Título Que Debe Aparecer');
    }

    public function test_no_afecta_el_guardado_de_los_campos_de_texto(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Texto');

        $response = $this->actingAs($user)->post(route('admin.homepage.update'), [
            'hp_hero_titulo'       => 'Bienvenidos',
            'hp_hero_subtitulo'    => 'Excelencia académica',
            'hp_about_titulo'      => 'Nuestra Historia',
            'hp_contacto_email'    => 'info@colegio.test',
            'hp_color_primario'    => '#ff0000',
        ]);

        $response->assertRedirect(route('admin.homepage.edit'));

        app()->instance('tenant', $tenant);
        $this->assertSame('Bienvenidos', ConfigInstitucional::get('hp_hero_titulo'));
        $this->assertSame('Excelencia académica', ConfigInstitucional::get('hp_hero_subtitulo'));
        $this->assertSame('Nuestra Historia', ConfigInstitucional::get('hp_about_titulo'));
        $this->assertSame('info@colegio.test', ConfigInstitucional::get('hp_contacto_email'));
        $this->assertSame('#ff0000', ConfigInstitucional::get('hp_color_primario'));
    }

    public function test_actualizar_visibilidad_de_un_tenant_no_afecta_a_otro(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Vis A');
        ['tenant' => $tenantB, 'user' => $userB] = $this->crearAdmin('Colegio Vis B');

        // A oculta su hero.
        $this->actingAs($userA)->post(route('admin.homepage.update'), ['hp_hero_visible' => '1']);
        $this->actingAs($userA)->post(route('admin.homepage.update'), []);

        // B nunca lo toca — debe seguir en su default (sin registro => '1' al leer con default).
        app()->instance('tenant', $tenantA);
        $this->assertSame('0', ConfigInstitucional::get('hp_hero_visible'));
        app()->forgetInstance('tenant');

        app()->instance('tenant', $tenantB);
        $this->assertNull(ConfigInstitucional::get('hp_hero_visible'), 'El tenant B no debe tener ningún registro afectado por A.');
    }
}
