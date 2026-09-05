<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mejora de UX pedida por el usuario (2026-09-05): el menú admin se volvió
 * muy grande (39 secciones "nav-section-title" en layouts/admin.blade.php).
 * Se agregaron dos mecanismos:
 * 1. Cada sección se colapsa/expande al hacer clic (acordeón), aplicado por
 *    JS a la estructura existente sin tocar el HTML de cada bloque — solo
 *    la sección con la página activa empieza expandida.
 * 2. Un botón en el topbar (#sidebarCollapseBtn) oculta/muestra el sidebar
 *    completo en desktop, para más espacio de pantalla.
 *
 * No hay navegador disponible en esta sesión para ejecutar el JS/CSS real
 * (ver mismo caveat en SuperAdminSidebarToggleTest) — este test verifica lo
 * que sí se puede confirmar por HTTP: que el HTML renderizado tiene los
 * elementos e ids que el script busca, que la regla CSS que realmente
 * oculta el sidebar existe, y que las 39 secciones siguen el patrón
 * estructural exacto (nav-section-title seguido de <ul>) del que depende
 * el acordeón — si alguien rompe ese patrón en el futuro, este test lo
 * detecta antes de que el menú deje de colapsar en producción.
 */
class AdminSidebarCollapseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');
        return $user;
    }

    private function renderizarDashboard(): string
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $response->assertOk();
        return $response->getContent();
    }

    public function test_el_boton_de_colapsar_sidebar_y_su_icono_estan_presentes_y_enlazados(): void
    {
        $html = $this->renderizarDashboard();

        $this->assertStringContainsString('id="sidebarCollapseBtn"', $html);
        $this->assertStringContainsString('id="sidebarCollapseIcon"', $html);
        $this->assertStringContainsString('aria-controls="sidebar"', $html);

        // Si alguien renombra un id sin actualizar el otro lado, el toggle
        // queda roto en silencio (getElementById devuelve null).
        $this->assertStringContainsString("getElementById('sidebarCollapseBtn')", $html);
        $this->assertStringContainsString("getElementById('sidebarCollapseIcon')", $html);
    }

    public function test_la_regla_css_que_oculta_el_sidebar_completo_existe(): void
    {
        $html = $this->renderizarDashboard();

        // Sin esta regla, la clase que el JS agrega a <html> no tendría
        // ningún efecto visual y el botón parecería no hacer nada.
        $this->assertStringContainsString('html.sidebar-collapsed .sidebar', $html);
    }

    public function test_el_script_anti_parpadeo_de_sidebar_colapsado_esta_presente(): void
    {
        $html = $this->renderizarDashboard();

        $this->assertStringContainsString("localStorage.getItem('sidebarCollapsed')", $html);
    }

    public function test_el_script_del_acordeon_de_secciones_esta_presente(): void
    {
        $html = $this->renderizarDashboard();

        $this->assertStringContainsString(".sidebar-nav .nav-section-title", $html);
        $this->assertStringContainsString("classList.add('sidebar-submenu')", $html);
    }

    public function test_el_script_asigna_un_icono_a_cada_seccion(): void
    {
        $html = $this->renderizarDashboard();

        $this->assertStringContainsString('ICONOS_SECCION', $html);
        $this->assertStringContainsString("label.className = 'nav-section-label'", $html);
        // El respaldo genérico debe existir para cualquier sección nueva
        // que no esté todavía en el mapa de iconos.
        $this->assertStringContainsString("|| 'bi-collection'", $html);
    }

    public function test_el_sidebar_se_colapsa_automaticamente_al_hacer_clic_en_un_item(): void
    {
        $html = $this->renderizarDashboard();

        $this->assertStringContainsString("localStorage.setItem('sidebarCollapsed', '1')", $html);
    }

    public function test_todas_las_secciones_del_menu_siguen_el_patron_que_el_acordeon_necesita(): void
    {
        // El acordeón se aplica con title.nextElementSibling — si una
        // sección nueva no queda seguida inmediatamente de un <ul>, esa
        // sección específica no se volverá colapsable (fallo silencioso).
        $html = $this->renderizarDashboard();

        $total = preg_match_all('/<div class="nav-section-title"[^>]*>.*?<\/div>\s*<ul class="list-unstyled/s', $html, $matches);
        $totalTitles = substr_count($html, 'class="nav-section-title"');

        $this->assertGreaterThan(0, $totalTitles, 'No se encontró ninguna sección en el dashboard de Administrador.');
        $this->assertSame(
            $totalTitles,
            $total,
            'Al menos una sección del menú no está seguida inmediatamente de un <ul> — el acordeón no se aplicará ahí.'
        );
    }
}
