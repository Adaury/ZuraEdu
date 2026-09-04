<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hallazgo Bajo de la auditoría completa 2026-09-04: layouts/superadmin.blade.php
 * ocultaba el sidebar por debajo de 768px (@media max-width:768px) sin
 * ningún botón para volver a abrirlo — un SuperAdmin en celular se quedaba
 * sin forma de navegar el panel. Se agregó un botón hamburguesa + overlay +
 * toggle JS, con el mismo patrón que layouts/admin.blade.php.
 *
 * No hay navegador disponible en esta sesión para ejecutar el JS/CSS real,
 * así que este test verifica lo que SÍ se puede verificar por HTTP: que el
 * HTML renderizado tiene los tres IDs que el toggle necesita
 * (saSidebar/saOverlay/saHamburger) presentes y correctamente enlazados —
 * el botón referencia el sidebar vía aria-controls, y el script de toggle
 * los busca a los tres por su id. Si alguien renombra uno de los tres sin
 * actualizar los otros, este test lo detecta.
 */
class SuperAdminSidebarToggleTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('super_admin');
        return $user;
    }

    public function test_el_panel_superadmin_incluye_el_boton_hamburguesa_y_el_overlay_del_sidebar(): void
    {
        $sa = $this->superAdmin();

        $response = $this->actingAs($sa)->get(route('superadmin.tenants.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="saSidebar"', $html, 'Falta el id del sidebar.');
        $this->assertStringContainsString('id="saOverlay"', $html, 'Falta el overlay para cerrar el sidebar en móvil.');
        $this->assertStringContainsString('id="saHamburger"', $html, 'Falta el botón hamburguesa.');
        $this->assertStringContainsString('aria-controls="saSidebar"', $html, 'El botón hamburguesa no referencia el sidebar correcto.');

        // El script de toggle debe buscar exactamente esos tres ids —
        // si alguien renombra uno sin actualizar el otro lado, el toggle
        // queda roto en silencio (getElementById devuelve null).
        $this->assertStringContainsString("getElementById('saSidebar')", $html);
        $this->assertStringContainsString("getElementById('saOverlay')", $html);
        $this->assertStringContainsString("getElementById('saHamburger')", $html);
    }

    public function test_el_css_responsive_define_la_clase_open_para_el_sidebar_movil(): void
    {
        $sa = $this->superAdmin();

        $response = $this->actingAs($sa)->get(route('superadmin.tenants.index'));
        $html = $response->getContent();

        // Sin esta regla, aunque el JS agregue la clase "open" al hacer
        // click, no habría ningún CSS que la interprete y el sidebar
        // seguiría fuera de pantalla.
        $this->assertStringContainsString('.sa-sidebar.open', $html);
    }
}
