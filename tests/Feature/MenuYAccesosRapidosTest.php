<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\User;
use App\Support\AccesosRapidos;
use App\Support\MenuFiltro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Menú por rol y accesos rápidos.
 *
 * El recorrido real de menús (navegador, 21 roles) encontró enlaces visibles que daban 403 (Director: 4, Personal Administrativo: 6 de 15…)
 * y atajos de la app instalada (PWA) iguales para todos, aunque apuntaran a pantallas de administración. Aquí queda cubierto:
 * el filtro oculta lo que el rol no puede abrir (y los grupos vacíos), y los accesos rápidos solo ofrecen pantallas permitidas.
 */
class MenuYAccesosRapidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        MenuFiltro::olvidar();
    }

    private function usuario(string $rol): User
    {
        Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);

        return User::factory()->create(['activo' => true])->assignRole($rol);
    }

    private function href(string $ruta): string
    {
        return route($ruta);
    }

    // ── MenuFiltro ──────────────────────────────────────────────────────────

    public function test_el_filtro_oculta_lo_que_el_rol_no_puede_abrir_y_deja_lo_demas(): void
    {
        $u = $this->usuario('Personal Administrativo');
        $html = '<ul><li class="nav-item"><a href="' . $this->href('admin.estudiantes.index') . '">Estudiantes</a></li>'
              . '<li class="nav-item"><a href="' . $this->href('admin.reportes.index') . '">Reportes</a></li></ul>';

        $this->assertTrue(MenuFiltro::permite($this->href('admin.reportes.index'), $u), 'precondición: Reportes sí es de este rol');
        $this->assertFalse(MenuFiltro::permite($this->href('admin.estudiantes.index'), $u), 'precondición: Estudiantes da 403 a este rol');

        $r = MenuFiltro::filtrar($html, $u);

        $this->assertStringNotContainsString('Estudiantes', $r);
        $this->assertStringContainsString('Reportes', $r);
    }

    public function test_un_grupo_que_se_queda_sin_enlaces_desaparece_con_su_titulo(): void
    {
        $u = $this->usuario('Personal Administrativo');
        $html = '<div class="nav-section-title">Gestión</div><ul class="list-unstyled"><li class="nav-item"><a href="' . $this->href('admin.estudiantes.index') . '">Estudiantes</a></li></ul>'
              . '<div class="nav-section-title">Supervisión</div><ul class="list-unstyled"><li class="nav-item"><a href="' . $this->href('admin.reportes.index') . '">Reportes</a></li></ul>';

        $r = MenuFiltro::filtrar($html, $u);

        $this->assertStringNotContainsString('Gestión', $r, 'el título del grupo vacío también se quita');
        $this->assertStringContainsString('Supervisión', $r);
        $this->assertStringContainsString('Reportes', $r);
    }

    public function test_un_submenu_vacio_quita_tambien_su_cabecera(): void
    {
        $u = $this->usuario('Personal Administrativo');
        $html = '<ul><li class="nav-item"><a href="#" class="toggle">Académico</a><ul><li class="nav-item"><a href="' . $this->href('admin.estudiantes.index') . '">Estudiantes</a></li></ul></li>'
              . '<li class="nav-item"><a href="' . $this->href('admin.reportes.index') . '">Reportes</a></li></ul>';

        $r = MenuFiltro::filtrar($html, $u);

        $this->assertStringNotContainsString('Académico', $r);
        $this->assertStringContainsString('Reportes', $r);
    }

    public function test_enlaces_externos_anclas_y_rutas_desconocidas_no_se_ocultan(): void
    {
        $u = $this->usuario('Personal Administrativo');
        $html = '<ul><li class="nav-item"><a href="https://ejemplo.com/ayuda">Ayuda externa</a></li>'
              . '<li class="nav-item"><a href="#">Ancla</a></li>'
              . '<li class="nav-item"><a href="/ruta/que/no/existe">Desconocida</a></li></ul>';

        $r = MenuFiltro::filtrar($html, $u);

        foreach (['Ayuda externa', 'Ancla', 'Desconocida'] as $texto) {
            $this->assertStringContainsString($texto, $r, "$texto no debe ocultarse por duda");
        }
    }

    public function test_el_administrador_y_el_superadministrador_conservan_todos_los_enlaces(): void
    {
        $html = '<ul><li class="nav-item"><a href="' . $this->href('admin.estudiantes.index') . '">Estudiantes</a></li>'
              . '<li class="nav-item"><a href="' . $this->href('admin.reportes.index') . '">Reportes</a></li></ul>';

        foreach (['Administrador', 'super_admin'] as $rol) {
            MenuFiltro::olvidar();
            $r = MenuFiltro::filtrar($html, $this->usuario($rol));
            $this->assertStringContainsString('Estudiantes', $r, $rol);
            $this->assertStringContainsString('Reportes', $r, $rol);
        }
    }

    public function test_conserva_tildes_y_enie_al_filtrar(): void
    {
        $u = $this->usuario('Administrador');
        $r = MenuFiltro::filtrar('<ul><li class="nav-item"><a href="' . $this->href('admin.reportes.index') . '">Planificación · Año Escolar ñandú</a></li></ul>', $u);

        $this->assertStringContainsString('Planificación · Año Escolar ñandú', $r);
    }

    public function test_el_menu_real_del_personal_administrativo_ya_no_ofrece_pantallas_que_le_dan_403(): void
    {
        $u = $this->usuario('Personal Administrativo');

        $html = $this->actingAs($u)->get(route('admin.reportes.index'))->assertOk()->getContent();
        $menu = substr($html, strpos($html, '<nav class="sidebar-nav">'), strpos($html, '</nav>') - strpos($html, '<nav class="sidebar-nav">'));

        $this->assertStringNotContainsString('href="' . $this->href('admin.estudiantes.index') . '"', $menu);
        $this->assertStringNotContainsString('href="' . $this->href('admin.matriculas.index') . '"', $menu);
        $this->assertStringContainsString('href="' . $this->href('admin.reportes.index') . '"', $menu);
    }

    // ── Accesos rápidos ─────────────────────────────────────────────────────

    public function test_todas_las_rutas_de_los_accesos_rapidos_existen(): void
    {
        $tabla = (new \ReflectionClassConstant(AccesosRapidos::class, 'POR_ROL'))->getValue();
        $inicio = (new \ReflectionClassConstant(AccesosRapidos::class, 'INICIO'))->getValue();

        $faltan = [];
        foreach ($tabla as $rol => $items) {
            foreach ($items as [$etiqueta, $ruta]) {
                if (! Route::has($ruta)) {
                    $faltan[] = "$rol → $ruta";
                }
            }
        }
        foreach ($inicio as $rol => $ruta) {
            if (! Route::has($ruta)) {
                $faltan[] = "inicio $rol → $ruta";
            }
        }

        $this->assertSame([], $faltan, "Rutas de AccesosRapidos que no existen:\n  " . implode("\n  ", $faltan));
    }

    public function test_cada_rol_tiene_accesos_y_todos_son_abribles_por_ese_rol(): void
    {
        $roles = ['Administrador', 'Director', 'Coordinador Académico', 'Registrador Académico', 'Secretaría', 'Personal Administrativo', 'Caja / Finanzas', 'Biblioteca', 'Recepción', 'Docente', 'Estudiante', 'Representante', 'super_admin'];

        foreach ($roles as $rol) {
            MenuFiltro::olvidar();
            $u = $this->usuario($rol);
            $items = AccesosRapidos::para($u);

            $this->assertNotEmpty($items, "$rol no tiene accesos rápidos");
            foreach ($items as $a) {
                $this->assertTrue(MenuFiltro::permite($a['url'], $u), "$rol: «{$a['etiqueta']}» no es abrible por ese rol");
                $this->assertStringStartsWith('/', $a['path']);
            }
        }
    }

    public function test_el_personal_administrativo_no_ve_accesos_a_estudiantes(): void
    {
        $etiquetas = array_column(AccesosRapidos::para($this->usuario('Personal Administrativo')), 'etiqueta');

        $this->assertNotContains('Estudiantes', $etiquetas);
        $this->assertContains('Reportes', $etiquetas);
    }

    public function test_el_rol_con_mas_prioridad_define_los_accesos(): void
    {
        $u = $this->usuario('Docente');
        $u->assignRole('Administrador');

        $this->assertSame('administrador', AccesosRapidos::rol($u));
    }

    // ── Icono en pantalla y manifiesto de la app instalada ──────────────────

    public function test_el_icono_de_accesos_rapidos_aparece_en_el_panel_de_administracion(): void
    {
        $html = $this->actingAs($this->usuario('Administrador'))->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="arBoton"', $html);
        $this->assertStringContainsString('Accesos rápidos', $html);
        $this->assertStringContainsString('arg-grid', $html, 'cuadrícula de accesos en la portada');
    }

    public function test_el_icono_aparece_en_el_portal_del_docente(): void
    {
        $d = Docente::factory()->create();
        $d->user->assignRole('Docente');
        $d->user->update(['activo' => true]);

        $html = $this->actingAs($d->user)->get(route('portal.docente.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="arBoton"', $html);
        $this->assertStringContainsString('Tomar asistencia', $html);
    }

    public function test_el_manifiesto_de_un_docente_abre_en_su_portal_y_sus_atajos_son_suyos(): void
    {
        $d = Docente::factory()->create();
        $d->user->assignRole('Docente');
        $d->user->update(['activo' => true]);

        $r = $this->actingAs($d->user)->get('/pwa/manifest.json')->assertOk();
        $m = $r->json();

        $this->assertSame('/portal/docente', $m['start_url']);
        $this->assertNotEmpty($m['shortcuts']);
        $this->assertLessThanOrEqual(4, count($m['shortcuts']));
        foreach ($m['shortcuts'] as $s) {
            $this->assertStringStartsWith('/portal/docente', $s['url'], 'un docente no debe recibir atajos de administración');
        }
        $this->assertStringContainsString('private', (string) $r->headers->get('Cache-Control'), 'depende del usuario: no se cachea en común');
    }

    public function test_el_manifiesto_de_un_estudiante_no_tiene_atajos_de_administracion(): void
    {
        $e = Estudiante::factory()->create();
        $e->user->assignRole('Estudiante');
        $e->user->update(['activo' => true]);

        $m = $this->actingAs($e->user)->get('/pwa/manifest.json')->assertOk()->json();

        $this->assertSame('/portal/estudiante', $m['start_url']);
        foreach ($m['shortcuts'] as $s) {
            $this->assertStringNotContainsString('/admin/', $s['url']);
        }
    }

    public function test_sin_sesion_el_manifiesto_sigue_funcionando(): void
    {
        $r = $this->get('/pwa/manifest.json')->assertOk();

        $this->assertArrayHasKey('start_url', $r->json());
        $this->assertStringContainsString('public', (string) $r->headers->get('Cache-Control'));
    }
}
