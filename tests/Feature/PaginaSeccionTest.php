<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\PaginaSeccion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Portal Público Fase 2 — constructor visual de bloques (pagina_secciones).
 * Reemplaza tests\Feature\HomepageOrdenTest.php, que cubría la iteración
 * anterior de "orden de secciones" (hp_orden CSV + botones subir/bajar, sin
 * tabla nueva) -- superada por este sistema de bloques N-instancia con
 * drag&drop real.
 */
class PaginaSeccionTest extends TestCase
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

        app()->instance('tenant', $tenant);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');
        app()->forgetInstance('tenant');

        return compact('tenant', 'user');
    }

    private function urlSitio(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio';
    }

    private function crearSeccion(Tenant $tenant, string $tipo, array $contenido = [], int $orden = 1, bool $activo = true): PaginaSeccion
    {
        app()->instance('tenant', $tenant);
        $seccion = PaginaSeccion::create([
            'tenant_id' => $tenant->id, 'tipo' => $tipo, 'orden' => $orden,
            'activo' => $activo, 'contenido' => $contenido,
        ]);
        app()->forgetInstance('tenant');

        return $seccion;
    }

    // ============================================================
    //  Backfill (comando sitio:migrar-secciones)
    // ============================================================

    public function test_backfill_convierte_la_config_hp_existente_en_bloques(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Backfill');

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_titulo', 'Bienvenidos');
        ConfigInstitucional::set('hp_orden', 'contacto,hero,about,stats,features,noticias,carrusel');
        ConfigInstitucional::set('hp_contacto_visible', '0');
        app()->forgetInstance('tenant');

        $this->artisan('sitio:migrar-secciones')->run();

        $secciones = PaginaSeccion::forTenant($tenant->id)->orderBy('orden')->get();
        $this->assertSame(['contacto', 'hero', 'about', 'stats', 'features', 'noticias', 'carrusel'], $secciones->pluck('tipo')->all());

        $hero = $secciones->firstWhere('tipo', 'hero');
        $this->assertSame('Bienvenidos', $hero->contenido['titulo']);

        $contacto = $secciones->firstWhere('tipo', 'contacto');
        $this->assertFalse($contacto->activo, 'hp_contacto_visible=0 debe traducirse a activo=false.');
    }

    public function test_backfill_es_idempotente(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Idempotente');

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_titulo', 'x');
        app()->forgetInstance('tenant');

        $this->artisan('sitio:migrar-secciones')->run();
        $primerConteo = PaginaSeccion::forTenant($tenant->id)->count();

        $this->artisan('sitio:migrar-secciones')->run();
        $segundoConteo = PaginaSeccion::forTenant($tenant->id)->count();

        $this->assertSame($primerConteo, $segundoConteo);
    }

    // ============================================================
    //  CRUD del constructor (Admin\PaginaSeccionController)
    // ============================================================

    public function test_agregar_un_bloque_lo_crea_al_final_del_orden(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Agregar');
        $this->crearSeccion($tenant, 'hero', [], 1);
        $this->crearSeccion($tenant, 'about', [], 2);

        $response = $this->actingAs($user)->post(route('admin.secciones.store'), ['tipo' => 'stats']);

        $nueva = PaginaSeccion::forTenant($tenant->id)->where('tipo', 'stats')->first();
        $this->assertNotNull($nueva);
        $this->assertSame(3, $nueva->orden);
        $response->assertRedirect(route('admin.secciones.edit', $nueva));
    }

    public function test_agregar_un_segundo_bloque_del_mismo_tipo_es_valido(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Multi Hero');
        $this->crearSeccion($tenant, 'hero', ['titulo' => 'Primero'], 1);

        $this->actingAs($user)->post(route('admin.secciones.store'), ['tipo' => 'hero']);

        $this->assertSame(2, PaginaSeccion::forTenant($tenant->id)->where('tipo', 'hero')->count());
    }

    public function test_tipo_invalido_es_rechazado(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Tipo Invalido');

        $response = $this->actingAs($user)->post(route('admin.secciones.store'), ['tipo' => 'no-existe']);

        $response->assertSessionHasErrors('tipo');
    }

    public function test_actualizar_un_bloque_guarda_su_contenido_y_respeta_el_checkbox_activo_desmarcado(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Actualizar');
        $seccion = $this->crearSeccion($tenant, 'about', ['titulo' => 'viejo', 'texto' => ''], 1, true);

        // Sin 'activo' en el payload == checkbox desmarcado en un formulario real.
        $this->actingAs($user)->put(route('admin.secciones.update', $seccion), [
            'contenido' => ['titulo' => 'Sobre Nosotros', 'texto' => '<p>Hola</p>'],
        ]);

        $seccion->refresh();
        $this->assertSame('Sobre Nosotros', $seccion->contenido['titulo']);
        $this->assertFalse($seccion->activo, 'activo debe quedar en false cuando el checkbox no viene en el payload.');
    }

    public function test_toggle_invierte_el_estado_activo(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Toggle');
        $seccion = $this->crearSeccion($tenant, 'stats', [], 1, true);

        $response = $this->actingAs($user)->patch(route('admin.secciones.toggle', $seccion));

        $response->assertJson(['ok' => true, 'activo' => false]);
        $this->assertFalse($seccion->fresh()->activo);
    }

    public function test_eliminar_un_bloque_lo_borra(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Eliminar');
        $seccion = $this->crearSeccion($tenant, 'features', [], 1);

        $this->actingAs($user)->delete(route('admin.secciones.destroy', $seccion));

        $this->assertNull(PaginaSeccion::forTenant($tenant->id)->find($seccion->id));
    }

    public function test_un_usuario_sin_permiso_no_puede_administrar_secciones(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Sin Permiso',
            'dominio' => 'colegiosinpermiso' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Coordinador Académico'); // entra al panel admin pero sin gestionar-configuracion
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.secciones.index'));

        $response->assertForbidden();
    }

    // ============================================================
    //  Reordenar (drag&drop -- POST admin.secciones.reordenar)
    // ============================================================

    public function test_reordenar_actualiza_el_orden_segun_el_array_recibido(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Reordenar');
        $a = $this->crearSeccion($tenant, 'hero', [], 1);
        $b = $this->crearSeccion($tenant, 'about', [], 2);
        $c = $this->crearSeccion($tenant, 'contacto', [], 3);

        $response = $this->actingAs($user)->post(route('admin.secciones.reordenar'), [
            'orden' => [$c->id, $a->id, $b->id],
        ]);

        $response->assertJson(['ok' => true]);
        $this->assertSame(1, $c->fresh()->orden);
        $this->assertSame(2, $a->fresh()->orden);
        $this->assertSame(3, $b->fresh()->orden);
    }

    public function test_reordenar_ignora_ids_de_otro_tenant(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Reordenar A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Reordenar B');

        $propia = $this->crearSeccion($tenantA, 'hero', [], 1);
        $ajena  = $this->crearSeccion($tenantB, 'hero', [], 1);

        $this->actingAs($userA)->post(route('admin.secciones.reordenar'), [
            'orden' => [$ajena->id, $propia->id],
        ]);

        $this->assertSame(1, $ajena->fresh()->orden, 'La sección de otro tenant no debe modificarse.');
        $this->assertSame(2, $propia->fresh()->orden);
    }

    // ============================================================
    //  Aislamiento cross-tenant en edit/update/destroy/toggle
    //  (SubstituteBindings corre antes que ResolveTenant -- autorizar()
    //  es la segunda capa de defensa, documentada en
    //  PublicSiteController::noticiaShow()).
    // ============================================================

    public function test_no_puede_editar_un_bloque_de_otro_tenant(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Cross A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Cross B');
        $ajena = $this->crearSeccion($tenantB, 'hero', [], 1);

        $this->actingAs($userA)->get(route('admin.secciones.edit', $ajena))->assertNotFound();
        $this->actingAs($userA)->put(route('admin.secciones.update', $ajena), ['contenido' => []])->assertNotFound();
        $this->actingAs($userA)->patch(route('admin.secciones.toggle', $ajena))->assertNotFound();
        $this->actingAs($userA)->delete(route('admin.secciones.destroy', $ajena))->assertNotFound();
    }

    // ============================================================
    //  Sitio público: orden, multi-instancia, gating por contenido
    // ============================================================

    public function test_el_orden_guardado_se_refleja_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Orden Sitio');
        $this->crearSeccion($tenant, 'about', ['titulo' => 'Segunda', 'texto' => ''], 1);
        $this->crearSeccion($tenant, 'contacto', ['direccion' => 'Calle Primero 1'], 2);

        // Reordenar: contacto primero.
        app()->instance('tenant', $tenant);
        PaginaSeccion::forTenant($tenant->id)->where('tipo', 'contacto')->update(['orden' => 1]);
        PaginaSeccion::forTenant($tenant->id)->where('tipo', 'about')->update(['orden' => 2]);
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));
        $response->assertOk();

        $html = $response->getContent();
        $posContacto = strpos($html, 'Calle Primero 1');
        $posAbout    = strpos($html, 'Segunda');

        $this->assertNotFalse($posContacto);
        $this->assertNotFalse($posAbout);
        $this->assertLessThan($posAbout, $posContacto, 'Contacto debía aparecer antes que About tras reordenar.');
    }

    public function test_reordenar_un_tenant_no_afecta_el_orden_de_otro(): void
    {
        ['tenant' => $tenantA] = $this->crearAdmin('Colegio Orden A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Orden B');

        $this->crearSeccion($tenantA, 'hero', ['titulo' => 'A'], 1);
        $this->crearSeccion($tenantA, 'about', ['titulo' => 'About A', 'texto' => 'x'], 2);
        $this->crearSeccion($tenantB, 'hero', ['titulo' => 'B'], 1);

        $ordenA = PaginaSeccion::forTenant($tenantA->id)->ordenadas()->pluck('tipo')->all();
        $ordenB = PaginaSeccion::forTenant($tenantB->id)->ordenadas()->pluck('tipo')->all();

        $this->assertSame(['hero', 'about'], $ordenA);
        $this->assertSame(['hero'], $ordenB);
    }

    /** El objetivo central de esta fase: dos bloques del mismo tipo deben renderizar ambos, de forma independiente. */
    public function test_dos_bloques_hero_renderizan_ambos_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Multi Hero Sitio');
        $this->crearSeccion($tenant, 'hero', ['titulo' => 'Hero Uno'], 1);
        $this->crearSeccion($tenant, 'hero', ['titulo' => 'Hero Dos'], 2);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('Hero Uno');
        $response->assertSee('Hero Dos');
        $this->assertSame(2, substr_count($response->getContent(), 'class="hero"'));
    }

    public function test_un_bloque_inactivo_no_aparece_en_el_sitio_publico(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Bloque Inactivo');
        $this->crearSeccion($tenant, 'about', ['titulo' => 'No debe verse', 'texto' => 'x'], 1, activo: false);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertDontSee('No debe verse');
    }

    public function test_un_bloque_about_activo_pero_sin_contenido_real_no_aparece(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Bloque Vacio');
        $this->crearSeccion($tenant, 'about', ['titulo' => '', 'texto' => ''], 1, activo: true);

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        // Sin ningún bloque con contenido real, debe caer al empty-state.
        $response->assertSee('aún no ha publicado contenido');
    }

    public function test_sin_ningun_bloque_se_muestra_el_empty_state(): void
    {
        ['tenant' => $tenant] = $this->crearAdmin('Colegio Sin Bloques');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('aún no ha publicado contenido');
    }
}
