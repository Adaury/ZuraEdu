<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CentroAdministracionController;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route as RouteFacade;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Roadmap de producto: "centro de administración unificado tipo Moodle"
 * (docs/ZURAEDU_ADMIN_ARCHITECTURE.md). Página de aterrizaje que agrupa por
 * categoría enlaces a páginas de administración YA EXISTENTES — no crea
 * ninguna funcionalidad nueva, solo filtra qué mostrar según el permiso/gate
 * que ya protege cada ruta destino Y (si aplica) el módulo (TenantFeature)
 * del que depende esa ruta.
 */
class CentroAdministracionTest extends TestCase
{
    use RefreshDatabase;

    private ?Tenant $tenant = null;

    protected function setUp(): void
    {
        parent::setUp();
        // Tenant::can() cachea 300s (store 'array' persiste dentro del
        // mismo proceso de test) — sin esto, un test que apaga una feature
        // podría leer el valor 'activo' de un test anterior.
        Cache::flush();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearUsuarioConRol(string $rol): User
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Centro Admin',
            'dominio'            => 'colegiocentroadmin' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $this->tenant = $tenant;

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);

        return $user;
    }

    /** Todas las features que el catálogo usa como flag, encendidas de una vez. */
    private function habilitarTodasLasFeatures(Tenant $tenant): void
    {
        $this->habilitarFeatures($tenant, [
            'horarios', 'classroom', 'proyectos', 'evaluaciones_docentes',
            'salud', 'disciplina', 'seguimiento_social', 'tutorias',
            'gamificacion', 'reconocimientos', 'reuniones', 'pagos', 'nomina',
            'biblioteca', 'inventario', 'transporte', 'cafeteria',
            'modo_publico', 'whatsapp',
        ]);
    }

    private function habilitarFeatures(Tenant $tenant, array $features): void
    {
        foreach ($features as $f) {
            $tenant->enableFeature($f);
        }
    }

    /**
     * Etiquetas de tarjetas realmente visibles según $categorias (viewData),
     * NO el HTML completo de la página. Necesario porque el sidebar del
     * layout (resources/views/layouts/admin.blade.php:3204-3245) muestra
     * "Cafetería"/"Biblioteca"/"Transporte Escolar" sin filtrar por
     * TenantFeature (gap preexistente, fuera de este alcance) — un
     * assertDontSee() sobre el HTML completo daría falsos negativos con
     * esas 3 etiquetas específicas aunque la tarjeta del hub sí esté oculta.
     */
    private function etiquetasVisibles($response): array
    {
        $etiquetas = [];
        foreach ($response->viewData('categorias') as $items) {
            foreach ($items as $item) {
                $etiquetas[] = $item['label'];
            }
        }

        return $etiquetas;
    }

    // ============================================================
    //  Permisos (comportamiento ya existente)
    // ============================================================

    public function test_un_administrador_ve_todas_las_categorias_con_el_plan_completo(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');
        $this->habilitarTodasLasFeatures($this->tenant);

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertSee('Personas y Comunidad');
        $response->assertSee('Estructura Académica');
        $response->assertSee('Docencia y Aula');
        $response->assertSee('Evaluación y Calificaciones');
        $response->assertSee('Bienestar y Convivencia');
        $response->assertSee('Comunicación');
        $response->assertSee('Finanzas');
        $response->assertSee('Servicios y Logística');
        $response->assertSee('Reportes y Analítica');
        $response->assertSee('Solicitudes y Soporte');
        $response->assertSee('Integraciones e Importación');
        $response->assertSee('Configuración del Sistema');
        $response->assertSee('Página Web Institucional');
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
        // "Nómina" (gestionar-pagos) ni nada de Configuración del Sistema
        // (solo-administrador).
        $user = $this->crearUsuarioConRol('Biblioteca');
        $this->habilitarTodasLasFeatures($this->tenant);

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertDontSee('Facturación');
        $response->assertDontSee('Nómina');
        $response->assertDontSee('Docentes');
        $response->assertDontSee('Respaldos');
        $response->assertDontSee('Tablero Ejecutivo');
    }

    public function test_las_tarjetas_visibles_apuntan_a_rutas_reales(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');
        $this->habilitarTodasLasFeatures($this->tenant);

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

    // ============================================================
    //  Módulos activos (TenantFeature) — gap nuevo
    // ============================================================

    public function test_una_tarjeta_con_feature_apagado_no_aparece(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');
        // Sin habilitar ninguna feature.

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));
        $response->assertOk();

        $etiquetas = $this->etiquetasVisibles($response);
        $this->assertNotContains('Cafetería', $etiquetas);
        $this->assertNotContains('Biblioteca', $etiquetas);
        $this->assertNotContains('Transporte Escolar', $etiquetas);
    }

    public function test_una_tarjeta_con_feature_encendido_si_aparece(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');
        $this->habilitarFeatures($this->tenant, ['cafeteria']);

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));
        $response->assertOk();

        $etiquetas = $this->etiquetasVisibles($response);
        $this->assertContains('Cafetería', $etiquetas);
        $this->assertNotContains('Transporte Escolar', $etiquetas);
    }

    public function test_una_tarjeta_core_siempre_aparece_si_el_permiso_lo_permite(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');
        // Sin habilitar ninguna feature — las tarjetas core (sin flag) no
        // deben depender de esto.

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertSee('Docentes');
        $response->assertSee('Estudiantes');
        $response->assertSee('Calificaciones');
        $response->assertSee('Facturación y Plan');
        $response->assertSee('Año Escolar');
    }

    public function test_una_categoria_entera_desaparece_si_todas_sus_tarjetas_estan_apagadas(): void
    {
        // 'Servicios y Logística' tiene 2 tarjetas core sin feature (Carnet+,
        // Eventos), ambas detrás de 'ver-servicios' — así que ningún rol con
        // ese permiso puede probar el vaciado total por features solamente.
        // Se sobreescriben los permisos de un rol admitido por
        // EnsureAdminAccess a un set mínimo y controlado (solo ver-dashboard)
        // para aislar el escenario: sin 'ver-servicios' Y sin features, la
        // categoría entera queda sin ninguna tarjeta visible.
        \Spatie\Permission\Models\Role::findByName('Personal Administrativo')->syncPermissions(['ver-dashboard']);
        $user = $this->crearUsuarioConRol('Personal Administrativo');
        // Sin ninguna feature encendida.

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertDontSee('Servicios y Logística');
    }

    public function test_un_super_admin_ve_las_tarjetas_aunque_el_tenant_no_tenga_el_feature(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Super Admin View',
            'dominio'            => 'colegiosuperadminview' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $superAdminTenant = Tenant::create([
            'nombre_institucion' => 'ZuraEdu Plataforma',
            'dominio'            => 'plataformacentroadmin' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $superAdminTenant->id]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');
        // Sin ninguna feature encendida en el tenant impersonado.

        // Patrón real de impersonación (ver ResolveTenant.php:43-49): la
        // sesión 'sa_tenant_id' es lo que hace que ResolveTenant bindee este
        // tenant para un super_admin — bindear app('tenant') a mano no basta,
        // el middleware de la request real lo pisaría de todas formas.
        $response = $this->actingAs($user)
            ->withSession(['sa_tenant_id' => $tenant->id])
            ->get(route('admin.centro-administracion'));

        $response->assertOk();
        $response->assertSee('Cafetería');
    }

    // ============================================================
    //  Integridad del catálogo — evita nombres/rutas mal escritos
    // ============================================================

    public function test_todas_las_rutas_del_catalogo_existen(): void
    {
        foreach (CentroAdministracionController::catalogo() as $categoria => $items) {
            foreach ($items as $item) {
                $this->assertTrue(
                    RouteFacade::has($item[1]),
                    "La ruta '{$item[1]}' de la tarjeta '{$item[0]}' (categoría '{$categoria}') no existe."
                );
            }
        }
    }

    public function test_todos_los_permisos_del_catalogo_existen(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);

        foreach (CentroAdministracionController::catalogo() as $categoria => $items) {
            foreach ($items as $item) {
                $ability = $item[3];
                $esPermiso = Permission::where('name', $ability)->exists();
                $esGate    = Gate::has($ability);

                $this->assertTrue(
                    $esPermiso || $esGate,
                    "'{$ability}' (tarjeta '{$item[0]}', categoría '{$categoria}') no es ni un permiso Spatie ni un Gate registrado."
                );
            }
        }
    }

    public function test_todos_los_features_del_catalogo_son_conocidos(): void
    {
        // Unión de las dos fuentes reales de features válidos: las que
        // bloquean una ruta vía middleware (CheckTenantFeature::LABELS) y
        // las que SuperAdmin puede togglear pero se verifican con un
        // abort() explícito en el controlador en vez de middleware (ej.
        // 'modo_publico', ver PublicSiteController::show():47) —
        // TenantController::ALL_FEATURES es el catálogo canónico de esas.
        $porMiddleware = array_keys((new \ReflectionClass(\App\Http\Middleware\CheckTenantFeature::class))
            ->getConstant('LABELS'));
        $porToggleSuperAdmin = array_keys((new \ReflectionClass(\App\Http\Controllers\SuperAdmin\TenantController::class))
            ->getConstant('ALL_FEATURES'));
        $conocidas = array_unique(array_merge($porMiddleware, $porToggleSuperAdmin));

        foreach (CentroAdministracionController::catalogo() as $categoria => $items) {
            foreach ($items as $item) {
                $feature = $item[4] ?? null;
                if ($feature === null) {
                    continue;
                }

                $this->assertContains(
                    $feature,
                    $conocidas,
                    "El feature '{$feature}' (tarjeta '{$item[0]}', categoría '{$categoria}') no es un TenantFeature conocido."
                );
            }
        }
    }

    public function test_el_catalogo_no_tiene_rutas_duplicadas(): void
    {
        $rutas = [];
        foreach (CentroAdministracionController::catalogo() as $items) {
            foreach ($items as $item) {
                $rutas[] = $item[1];
            }
        }

        $this->assertCount(count($rutas), array_unique($rutas), 'Hay nombres de ruta duplicados en el catálogo.');
    }

    // ============================================================
    //  Buscador
    // ============================================================

    public function test_el_indice_de_busqueda_es_insensible_a_acentos_y_soporta_alias(): void
    {
        $user = $this->crearUsuarioConRol('Administrador');
        $this->habilitarTodasLasFeatures($this->tenant);

        $response = $this->actingAs($user)->get(route('admin.centro-administracion'));
        $categorias = $response->viewData('categorias');

        $matriculas = null;
        $pagos = null;
        foreach ($categorias as $items) {
            foreach ($items as $item) {
                if ($item['label'] === 'Matrículas') $matriculas = $item;
                if ($item['label'] === 'Pagos y Colegiaturas') $pagos = $item;
            }
        }

        $this->assertNotNull($matriculas);
        $this->assertStringContainsString('matriculas', $matriculas['busqueda']);

        $this->assertNotNull($pagos);
        $this->assertStringContainsString('cobros', $pagos['busqueda']);
    }
}
