<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Estudiante;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VentaCafeteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recarga de la cafetería (la pantalla, no el libro de saldos — ese lo cubre CafeteriaSaldoTest). Revisión:
 *  - mover dinero exigía solo «ver-servicios»: Biblioteca y Recepción podían recargar, vender y AJUSTAR saldos;
 *  - sin tope de monto (un 99999999999 daba error 500 de columna) y un ajuste podía dejar el saldo en negativo;
 *  - doble clic / recargar la página repetía la recarga; no quedaba rastro en el log de actividad;
 *  - `exists:` veía estudiantes de todos los colegios.
 */
class CafeteriaRecargaWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['activo' => true])->assignRole($rol);
    }

    private function recargar(User $u, Estudiante $e, array $extra = [])
    {
        return $this->actingAs($u)->post(route('admin.cafeteria.recargas.store'), array_merge(['estudiante_id' => $e->id, 'monto' => 100], $extra));
    }

    // ── Quién puede mover dinero ─────────────────────────────────────────────

    public function test_biblioteca_puede_ver_la_cafeteria_pero_no_mover_dinero(): void
    {
        $e = Estudiante::factory()->create();
        $biblio = $this->usuario('Biblioteca');

        $this->actingAs($biblio)->get(route('admin.cafeteria.dashboard'))->assertOk();
        $this->recargar($biblio, $e)->assertForbidden();
        $this->actingAs($biblio)->post(route('admin.cafeteria.ventas.store'), ['estudiante_id' => $e->id, 'monto' => 10])->assertForbidden();
        $this->actingAs($biblio)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => 10, 'descripcion' => 'x'])->assertForbidden();

        $this->assertSame(0, VentaCafeteria::where('estudiante_id', $e->id)->count());
    }

    public function test_recepcion_puede_recargar_y_vender_pero_no_ajustar(): void
    {
        $e = Estudiante::factory()->create();
        $recep = $this->usuario('Recepción');

        $this->recargar($recep, $e)->assertSessionHas('success');
        $this->actingAs($recep)->post(route('admin.cafeteria.ventas.store'), ['estudiante_id' => $e->id, 'monto' => 10])->assertSessionHas('success');
        $this->actingAs($recep)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => -5, 'descripcion' => 'x'])->assertForbidden();

        $this->assertEquals(90.0, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_administrador_y_director_pueden_recargar_y_ajustar(): void
    {
        $e = Estudiante::factory()->create();

        foreach (['Administrador', 'Director'] as $rol) {
            $u = $this->usuario($rol);
            $this->recargar($u, $e)->assertSessionHas('success');
            $this->actingAs($u)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => 5, 'descripcion' => 'corrección'])->assertSessionHas('success');
        }

        $this->assertEquals(210.0, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_los_botones_de_mover_dinero_no_aparecen_para_quien_no_puede(): void
    {
        $e = Estudiante::factory()->create();

        $biblio = $this->actingAs($this->usuario('Biblioteca'))->get(route('admin.cafeteria.balance', $e))->assertOk()->getContent();
        $this->assertStringNotContainsString('modalRecarga = true', $biblio, 'sin botón que abra la recarga');
        $this->assertStringNotContainsString('modalVenta = true', $biblio, 'sin botón que abra la venta');

        $admin = $this->actingAs($this->usuario('Administrador'))->get(route('admin.cafeteria.balance', $e))->assertOk()->getContent();
        $this->assertStringContainsString('modalRecarga = true', $admin);
        $this->assertStringContainsString('name="token_operacion"', $admin);
    }

    // ── Montos y datos ───────────────────────────────────────────────────────

    public function test_un_monto_absurdo_se_rechaza_en_vez_de_dar_error_500(): void
    {
        $e = Estudiante::factory()->create();
        $admin = $this->usuario('Administrador');

        $this->recargar($admin, $e, ['monto' => 99999999999])->assertSessionHasErrors('monto');
        $this->recargar($admin, $e, ['monto' => 50000.01])->assertSessionHasErrors('monto');
        $this->recargar($admin, $e, ['monto' => 0])->assertSessionHasErrors('monto');
        $this->recargar($admin, $e, ['monto' => 50000])->assertSessionHas('success');
    }

    public function test_un_ajuste_no_puede_dejar_el_saldo_en_negativo_ni_ser_cero(): void
    {
        $e = Estudiante::factory()->create();
        $admin = $this->usuario('Administrador');
        $this->recargar($admin, $e, ['monto' => 30]);

        $this->actingAs($admin)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => -30.01, 'descripcion' => 'x'])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => 0, 'descripcion' => 'x'])->assertSessionHasErrors('monto');
        $this->actingAs($admin)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => -30, 'descripcion' => 'justo'])->assertSessionHas('success');

        $this->assertEquals(0.0, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_un_estudiante_de_otro_colegio_es_rechazado_por_validacion(): void
    {
        $otro = Tenant::create(['nombre_institucion' => 'Otro', 'dominio' => 'otro' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $otro);
        $ajeno = Estudiante::factory()->create();
        app()->forgetInstance('tenant');

        $this->recargar($this->usuario('Administrador'), $ajeno)->assertSessionHasErrors('estudiante_id');

        app()->instance('tenant', $otro);
        $this->assertSame(0, VentaCafeteria::where('estudiante_id', $ajeno->id)->count());
    }

    // ── Doble envío y auditoría ──────────────────────────────────────────────

    public function test_enviar_dos_veces_el_mismo_formulario_no_repite_la_recarga(): void
    {
        $e = Estudiante::factory()->create();
        $admin = $this->usuario('Administrador');
        $token = 'tok-' . bin2hex(random_bytes(8));

        $this->recargar($admin, $e, ['token_operacion' => $token])->assertSessionHas('success');
        $segunda = $this->recargar($admin, $e, ['token_operacion' => $token]);

        $segunda->assertSessionHas('success');
        $this->assertStringContainsString('no se repitió', session('success'));
        $this->assertSame(1, VentaCafeteria::where('estudiante_id', $e->id)->where('tipo', 'recarga')->count());
        $this->assertEquals(100.0, VentaCafeteria::saldoEstudiante($e->id));

        // Otro formulario (otro token) sí es otra recarga
        $this->recargar($admin, $e, ['token_operacion' => 'otro-' . bin2hex(random_bytes(8))])->assertSessionHas('success');
        $this->assertEquals(200.0, VentaCafeteria::saldoEstudiante($e->id));
    }

    public function test_la_recarga_y_el_ajuste_quedan_en_el_log_de_actividad(): void
    {
        $e = Estudiante::factory()->create();
        $admin = $this->usuario('Administrador');

        $this->recargar($admin, $e, ['monto' => 150.5]);
        $this->actingAs($admin)->post(route('admin.cafeteria.ajustes.store'), ['estudiante_id' => $e->id, 'monto' => -10, 'descripcion' => 'Error de cobro']);

        $recarga = ActivityLog::withoutGlobalScopes()->where('accion', 'cafeteria_recarga')->latest('id')->first();
        $this->assertNotNull($recarga);
        $this->assertSame($admin->id, (int) $recarga->user_id);
        $this->assertStringContainsString('150.50', $recarga->descripcion);

        $ajuste = ActivityLog::withoutGlobalScopes()->where('accion', 'cafeteria_ajuste')->latest('id')->first();
        $this->assertNotNull($ajuste);
        $this->assertStringContainsString('Error de cobro', $ajuste->descripcion);
    }

    public function test_la_migracion_da_los_permisos_a_los_roles_que_ya_recargaban_sin_tocar_los_demas(): void
    {
        // Base anterior al cambio: los permisos nuevos no existen (en una instalación real el seeder no vuelve a correr)
        \Spatie\Permission\Models\Permission::whereIn('name', ['operar-cafeteria', 'ajustar-saldo-cafeteria'])->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $antes = \Spatie\Permission\Models\Role::where('name', 'Biblioteca')->first()->permissions()->pluck('name')->sort()->values()->all();

        (require database_path('migrations/2026_10_05_000001_permisos_cafeteria_operar_y_ajustar.php'))->up();

        $permisos = fn (string $rol) => \Spatie\Permission\Models\Role::where('name', $rol)->first()->permissions()->pluck('name')->all();
        $this->assertContains('operar-cafeteria', $permisos('Administrador'));
        $this->assertContains('ajustar-saldo-cafeteria', $permisos('Administrador'));
        $this->assertContains('operar-cafeteria', $permisos('Director'));
        $this->assertContains('operar-cafeteria', $permisos('Recepción'));
        $this->assertNotContains('ajustar-saldo-cafeteria', $permisos('Recepción'));
        $this->assertNotContains('operar-cafeteria', $permisos('Biblioteca'), 'Biblioteca ve la cafetería pero no mueve dinero');
        $this->assertSame($antes, collect($permisos('Biblioteca'))->sort()->values()->all(), 'no se toca ningún otro permiso');

        // Y se puede ejecutar de nuevo sin duplicar nada
        (require database_path('migrations/2026_10_05_000001_permisos_cafeteria_operar_y_ajustar.php'))->up();
        $this->assertSame(1, \Spatie\Permission\Models\Permission::where('name', 'operar-cafeteria')->count());
    }

    // ── Revisión completa de la pantalla (los botones no abrían el formulario) ────

    public function test_los_formularios_estan_dentro_del_contenedor_de_alpine_para_que_los_botones_los_abran(): void
    {
        $admin = $this->usuario('Administrador');
        $e = Estudiante::factory()->create();

        foreach ([route('admin.cafeteria.dashboard'), route('admin.cafeteria.ventas'), route('admin.cafeteria.balance', $e)] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            $raiz = strpos($html, 'x-data="{ modalVenta');
            $this->assertNotFalse($raiz, "$url tiene la raíz de Alpine");

            foreach (['modalVenta', 'modalRecarga'] as $modal) {
                $pos = strpos($html, 'x-show="' . $modal . '"');
                $this->assertNotFalse($pos, "$url tiene el formulario $modal");
                $entre = substr($html, $raiz, $pos - $raiz);
                // Entre la raíz y el formulario hay más <div> abiertos que cerrados: el formulario está DENTRO de la raíz
                $this->assertGreaterThan(
                    preg_match_all('#</div>#', $entre),
                    preg_match_all('#<div[ >]#', $entre),
                    "$url: el formulario $modal quedó fuera del contenedor x-data y nunca se abriría"
                );
            }
        }
    }

    public function test_la_pantalla_no_carga_a_todos_los_estudiantes_en_los_formularios(): void
    {
        Estudiante::factory()->count(25)->create(['apellidos' => 'ZzApellidoQueNoDebeSalirEnLaPagina']);

        $html = $this->actingAs($this->usuario('Administrador'))->get(route('admin.cafeteria.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('ZzApellidoQueNoDebeSalirEnLaPagina', $html, 'ya no hay un <select> con todos los estudiantes');
        $this->assertStringContainsString(route('admin.cafeteria.estudiantes.buscar'), $html, 'usa el buscador');
    }

    public function test_el_buscador_devuelve_solo_estudiantes_activos_del_colegio_y_exige_dos_letras(): void
    {
        $admin = $this->usuario('Administrador');
        Estudiante::factory()->create(['nombres' => 'Zelmira', 'apellidos' => 'Buscable']);
        Estudiante::factory()->create(['nombres' => 'Zelmira', 'apellidos' => 'Retirada', 'estado' => 'inactivo']);

        $otro = Tenant::create(['nombre_institucion' => 'Otro', 'dominio' => 'otro' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $otro);
        Estudiante::factory()->create(['nombres' => 'Zelmira', 'apellidos' => 'DeOtroColegio']);
        app()->forgetInstance('tenant');

        $r = $this->actingAs($admin)->getJson(route('admin.cafeteria.estudiantes.buscar', ['q' => 'Zelmira']))->assertOk()->json();
        $apellidos = collect($r)->pluck('nombre')->implode(' | ');

        $this->assertStringContainsString('Buscable', $apellidos);
        $this->assertStringNotContainsString('Retirada', $apellidos, 'solo activos');
        $this->assertStringNotContainsString('DeOtroColegio', $apellidos, 'jamás de otro colegio');

        $this->assertSame([], $this->actingAs($admin)->getJson(route('admin.cafeteria.estudiantes.buscar', ['q' => 'Z']))->json(), 'mínimo dos letras');
        $this->assertSame([], $this->actingAs($admin)->getJson(route('admin.cafeteria.estudiantes.buscar', ['q' => '%%']))->json(), 'los comodines del LIKE no listan a todos');
    }

    public function test_los_avisos_de_validacion_salen_en_espanol_y_se_muestran_en_la_pantalla(): void
    {
        $e = Estudiante::factory()->create();
        $admin = $this->usuario('Administrador');

        $r = $this->actingAs($admin)->from(route('admin.cafeteria.dashboard'))
            ->post(route('admin.cafeteria.recargas.store'), ['estudiante_id' => $e->id, 'monto' => 99999999]);
        $r->assertRedirect(route('admin.cafeteria.dashboard'));
        $this->assertStringContainsString('El monto debe estar entre', session('errors')->first('monto'));

        // y la pantalla los pinta (antes la página se recargaba sin decir nada)
        $this->actingAs($admin)->withSession(['errors' => session('errors')])->get(route('admin.cafeteria.dashboard'))
            ->assertSee('No se pudo registrar', false)->assertSee('El monto debe estar entre', false);
    }
}
