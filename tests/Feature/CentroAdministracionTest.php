<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto: "centro de administración unificado tipo Moodle"
 * (docs/ZURAEDU_ADMIN_ARCHITECTURE.md). Página de aterrizaje que agrupa por
 * categoría enlaces a páginas de administración YA EXISTENTES — no crea
 * ninguna funcionalidad nueva, solo filtra qué mostrar según los permisos
 * Spatie que ya protegen cada ruta destino.
 */
class CentroAdministracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearUsuarioConRol(string $rol): User
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Centro Admin',
            'dominio'            => 'colegiocentroadmin' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);

        return $user;
    }

    public function test_un_administrador_ve_todas_las_categorias(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertSee('Personas');
        $response->assertSee('Académico');
        $response->assertSee('Comunicación');
        $response->assertSee('Finanzas');
        $response->assertSee('Configuración');
        $response->assertSee('Página Web');
        $response->assertSee('Galería');
        $response->assertSee('Noticias y Publicaciones');
        $response->assertSee('Docentes');
        $response->assertSee('Editor de Página Principal');
    }

    public function test_un_docente_es_redirigido_a_su_propio_portal(): void
    {
        // EnsureAdminAccess redirige a Docente a su portal antes de llegar
        // siquiera a esta página — el hub es exclusivamente para roles
        // administrativos, igual que el resto del panel /admin.
        $user = $this->crearUsuarioConRol('Docente');

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertRedirect(route('portal.docente.dashboard'));
    }

    public function test_un_rol_con_permisos_limitados_no_ve_tarjetas_fuera_de_su_alcance(): void
    {
        // Biblioteca solo tiene 'ver-dashboard' y 'gestionar-biblioteca' —
        // no debe ver, por ejemplo, "Facturación" (acceso-billing) ni
        // "Nómina" (gestionar-pagos).
        $user = $this->crearUsuarioConRol('Biblioteca');

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertDontSee('Facturación');
        $response->assertDontSee('Nómina');
        $response->assertDontSee('Docentes');
    }

    public function test_las_tarjetas_visibles_apuntan_a_rutas_reales(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));
        $categorias = $response->viewData('categorias');

        $this->assertTrue($categorias->isNotEmpty());
        foreach ($categorias as $items) {
            foreach ($items as $item) {
                $this->assertArrayHasKey('url', $item);
                $this->assertNotEmpty($item['url']);
            }
        }
    }

    public function test_el_link_del_sidebar_aparece_para_un_administrador(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Centro de Administración');
    }
}
