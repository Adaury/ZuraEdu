<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\User;
use App\Support\GuiasRol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guías rápidas («flyers») por rol y página de bienvenida:
 * - cada rol del sistema tiene su guía (pantalla, impresión y PDF de una sola página);
 * - la landing explica el sistema por persona y ya no muestra cifras que el sistema no puede demostrar.
 */
class GuiasRolYLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_el_indice_lista_todas_las_guias(): void
    {
        $r = $this->get(route('guias.index'))->assertOk();

        foreach (GuiasRol::GUIAS as $slug => $g) {
            $r->assertSee($g['nombre']);
            $r->assertSee(route('guias.show', $slug), false);
            $r->assertSee(route('guias.pdf', $slug), false);
        }
        $r->assertSee('Todos los derechos reservados');
    }

    public function test_cada_guia_se_ve_en_pantalla_con_imprimir_y_pdf(): void
    {
        foreach (GuiasRol::GUIAS as $slug => $g) {
            $r = $this->get(route('guias.show', $slug))->assertOk();
            $r->assertSee($g['nombre'])->assertSee($g['lema'])->assertSee('Descargar PDF')->assertSee('Imprimir');
            $r->assertSee($g['pasos'][0][0]);
            $r->assertSee('Alt + Q');
        }
    }

    public function test_una_guia_inexistente_da_404(): void
    {
        $this->get('/guias/inventada')->assertNotFound();
        $this->get('/guias/inventada/pdf')->assertNotFound();
        $this->get('/guias/..%2F..%2Fenv')->assertNotFound();
    }

    public function test_cada_guia_en_pdf_cabe_en_una_sola_pagina_a4(): void
    {
        foreach (GuiasRol::GUIAS as $slug => $g) {
            $r = $this->get(route('guias.pdf', $slug))->assertOk();
            $pdf = $r->getContent();

            $this->assertStringStartsWith('%PDF', $pdf, $slug);
            $this->assertStringContainsString('application/pdf', (string) $r->headers->get('Content-Type'));
            $this->assertSame(1, (int) preg_match_all('/\/Type\s*\/Page[^s]/', $pdf), "la guía «{$slug}» debe caber en una página");
            $this->assertStringContainsString("guia-rapida-{$slug}-zuraedu.pdf", (string) $r->headers->get('Content-Disposition'));
        }
    }

    public function test_todas_las_guias_tienen_el_contenido_completo(): void
    {
        foreach (GuiasRol::GUIAS as $slug => $g) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $g['color'], $slug);
            $this->assertCount(6, $g['hacer'], "$slug: 6 cosas que puedes hacer");
            $this->assertCount(3, $g['pasos'], "$slug: 3 pasos");
            $this->assertCount(3, $g['consejos'], "$slug: 3 consejos");
            foreach ($g['pasos'] as $paso) {
                $this->assertCount(2, $paso, "$slug: cada paso lleva título y texto");
            }
            foreach (array_merge($g['hacer'], $g['consejos']) as $texto) {
                $this->assertNotSame('', trim($texto));
            }
        }
    }

    public function test_cada_rol_del_sistema_tiene_una_guia(): void
    {
        foreach (GuiasRol::rolesCubiertos() as $rolInterno) {
            $mapa = (new \ReflectionClassConstant(GuiasRol::class, 'POR_ROL'))->getValue();
            $this->assertTrue(GuiasRol::existe($mapa[$rolInterno]), "$rolInterno apunta a una guía que no existe");
        }

        foreach (['Administrador', 'Director', 'Coordinador Académico', 'Registrador Académico', 'Secretaría', 'Personal Administrativo', 'Caja / Finanzas', 'Biblioteca', 'Recepción', 'Docente', 'Estudiante', 'Representante', 'super_admin'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
            $u = User::factory()->create()->assignRole($rol);
            $slug = GuiasRol::slugPara($u);

            $this->assertNotNull($slug, "$rol no tiene guía");
            $this->assertTrue(GuiasRol::existe($slug));
        }
    }

    public function test_mi_guia_lleva_a_la_guia_del_rol_y_exige_sesion(): void
    {
        $this->get(route('guias.mia'))->assertRedirect(route('login'));

        $d = Docente::factory()->create();
        $d->user->assignRole('Docente');
        $this->actingAs($d->user)->get(route('guias.mia'))->assertRedirect(route('guias.show', 'docente'));

        $sinRol = User::factory()->create();
        $this->actingAs($sinRol)->get(route('guias.mia'))->assertRedirect(route('guias.index'));
    }

    public function test_los_accesos_rapidos_enlazan_a_la_guia_del_rol(): void
    {
        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin = User::factory()->create(['activo' => true])->assignRole('Administrador');

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(route('guias.mia'), $html);
    }

    // ── Landing ─────────────────────────────────────────────────────────────

    public function test_la_landing_explica_el_sistema_por_rol_y_enlaza_cada_guia(): void
    {
        $r = $this->get('/')->assertOk();

        $r->assertSee('id="roles"', false)->assertSee('id="como-empezar"', false)->assertSee('id="faq"', false);
        $r->assertSee('Una plataforma, cada quien con lo suyo');
        foreach (GuiasRol::GUIAS as $slug => $g) {
            $r->assertSee(route('guias.show', $slug), false);
            $r->assertSee(route('guias.pdf', $slug), false);
        }
        $r->assertSee('Preguntas frecuentes');
    }

    public function test_la_landing_ya_no_muestra_cifras_que_el_sistema_no_puede_demostrar(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['500+', '25k+', '99%', 'Únete a 500'] as $inventada) {
            $this->assertStringNotContainsString($inventada, $html, "cifra sin respaldo: $inventada");
        }
        $this->assertStringContainsString('Perfiles de usuario', $html);
    }

    public function test_los_perfiles_que_anuncia_la_landing_son_los_reales(): void
    {
        $reales = Role::where('name', '!=', 'super_admin')->count();
        $this->assertGreaterThan(0, $reales);

        $html = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression('/>\s*' . $reales . '\s*<\/div>\s*<div[^>]*>\s*Perfiles de usuario/', $html);
    }

    public function test_el_menu_de_la_landing_enlaza_a_secciones_que_existen(): void
    {
        $html = $this->get('/')->getContent();

        foreach (['beneficios', 'modulos', 'roles', 'demo', 'planes', 'faq'] as $ancla) {
            $this->assertStringContainsString('href="#' . $ancla . '"', $html, "falta el enlace #$ancla");
            $this->assertStringContainsString('id="' . $ancla . '"', $html, "el enlace #$ancla apunta a una sección que no existe");
        }
    }

    public function test_el_acceso_para_iniciar_sesion_se_ve_en_el_celular_desde_el_encabezado(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // El encabezado no tiene menú móvil: el botón de login debe verse en pantallas pequeñas (antes «hidden sm:inline-flex»)
        $cabecera = substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));
        $this->assertMatchesRegularExpression('#<a href="[^"]*/login" class="inline-flex[^"]*">\s*<span class="sm:hidden">Entrar</span>#', $cabecera);
        $this->assertStringNotContainsString('hidden sm:inline-flex', $cabecera);
    }
}
