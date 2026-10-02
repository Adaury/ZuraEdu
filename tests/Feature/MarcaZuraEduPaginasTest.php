<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\Estudiante;
use App\Models\User;
use App\Support\Marca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marca ZuraEdu — páginas REALES renderizadas: el logo y el pie «Todos los derechos reservados» aparecen donde deben, y el colegio que sube su
 * propio logo lo conserva (ZuraEdu es la marca de la plataforma, no reemplaza a la del colegio).
 */
class MarcaZuraEduPaginasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function usuario(string $rol): User
    {
        // El rol super_admin no lo crea RolesSeeder (lo crea el comando CrearSuperAdmin): se asegura aquí, como hacen otras pruebas.
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole($rol);

        return $u;
    }

    public function test_el_acceso_muestra_el_logo_de_la_plataforma_y_el_texto_legal(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString(Marca::copyright(), $html);
        $this->assertStringContainsString('brand/zuraedu-logo-blanco.svg', $html);
        $this->assertStringContainsString('brand/favicon.svg', $html, 'ícono de pestaña de la marca');
    }

    public function test_la_pagina_de_error_lleva_el_pie_de_marca(): void
    {
        $html = $this->get('/esta-ruta-no-existe-zuraedu')->assertNotFound()->getContent();

        $this->assertStringContainsString('data-marca-pie', $html);
        $this->assertStringContainsString(Marca::copyright(), $html);
    }

    public function test_el_panel_de_administracion_lleva_logo_en_la_barra_lateral_y_pie(): void
    {
        $html = $this->actingAs($this->usuario('Administrador'))->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-marca-pie', $html);
        $this->assertStringContainsString(Marca::copyright(), $html);
        $this->assertStringContainsString('brand/zuraedu-logo-blanco.svg', $html, 'logo de la plataforma al pie de la barra lateral');
        $this->assertStringNotContainsString('AprendeTicPaulino', $html, 'el pie ya no lleva un nombre de colegio escrito a mano');
    }

    public function test_sin_logo_propio_el_colegio_muestra_la_insignia_de_zuraedu_y_con_logo_propio_conserva_el_suyo(): void
    {
        $admin = $this->usuario('Administrador');

        $sinLogo = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();
        $this->assertStringContainsString('brand/zuraedu-icono.svg', $sinLogo, 'sin logo del colegio: la insignia de ZuraEdu en lugar de las siglas «SGE»');

        Setting::set('system_logo', 'logos/mi-colegio.png');
        Setting::flush();
        $conLogo = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();
        $this->assertStringContainsString('logos/mi-colegio.png', $conLogo, 'el logo del colegio sigue siendo el principal');
        $this->assertStringNotContainsString('brand/zuraedu-icono.svg', $conLogo, 'la insignia de ZuraEdu no reemplaza al logo del colegio');
        $this->assertStringContainsString('brand/zuraedu-logo-blanco.svg', $conLogo, 'ZuraEdu sigue apareciendo como la plataforma (al pie de la barra lateral)');
    }

    public function test_el_portal_del_estudiante_lleva_el_pie(): void
    {
        $u = $this->usuario('Estudiante');
        Estudiante::factory()->create(['user_id' => $u->id]);

        $html = $this->actingAs($u)->get(route('portal.estudiante.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-marca-pie', $html);
        $this->assertStringContainsString(Marca::copyright(), $html);
        // El portal instalable conserva SU ícono por colegio (con el color del centro): no se duplica el de la marca
        $this->assertSame(1, substr_count($html, 'rel="apple-touch-icon"'));
        $this->assertSame(1, substr_count($html, 'name="theme-color"'));
    }

    public function test_el_panel_de_superadministrador_usa_el_logo_real_en_vez_de_las_letras_ze(): void
    {
        $html = $this->actingAs($this->usuario('super_admin'))->get(route('superadmin.tenants.index'))->assertOk()->getContent();

        $this->assertStringContainsString('brand/zuraedu-icono.svg', $html);
        $this->assertStringNotContainsString('>ZE<', $html);
        $this->assertStringContainsString(Marca::copyright(), $html);
    }

    public function test_el_correo_base_lleva_el_pie_y_el_logo(): void
    {
        $html = view('emails.plantilla', ['centro' => 'Colegio de Prueba', 'cuerpo' => '<p>Hola</p>'])->render();

        $this->assertStringContainsString(Marca::copyright(), $html);
        $this->assertStringContainsString('brand/zuraedu-logo-300.png', $html);
        $this->assertStringContainsString('Colegio de Prueba', $html, 'el correo sigue mostrando el nombre del colegio');
    }
}
