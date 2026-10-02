<?php

namespace Tests\Feature;

use App\Jobs\SincronizarOdooJob;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\OdooConexion;
use App\Models\OdooVinculo;
use App\Models\Pago;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Odoo\OdooException;
use App\Services\Odoo\OdooSincronizador;
use App\Services\Odoo\OdooUrlSegura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integración con Odoo por centro educativo. No hay un Odoo real en los tests: `OdooFalso` responde como el JSON-RPC de Odoo
 * (autenticación, search_read, create, write, action_post, errores) y registra cada llamada para poder afirmar QUÉ se envió.
 */
class OdooIntegracionTest extends TestCase
{
    use RefreshDatabase;

    private OdooFalso $odoo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
        config(['odoo.verificar_dns' => false]);   // los tests no hacen consultas DNS
        $this->odoo = new OdooFalso();
        Http::fake(fn (Request $r) => $this->odoo->respuestaCruda ?? Http::response($this->odoo->manejar($r->data())));
    }

    // ── ayudas ──────────────────────────────────────────────────────────────

    private function colegio(string $e = 'A'): Tenant
    {
        $t = Tenant::create(['nombre_institucion' => "Colegio {$e}", 'dominio' => strtolower($e) . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $t);

        return $t;
    }

    private function admin(): User
    {
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole('Administrador');

        return $u;
    }

    private function conexion(array $extra = []): OdooConexion
    {
        return OdooConexion::create(array_merge([
            'url' => 'https://colegio.odoo.com', 'base_datos' => 'colegio', 'usuario' => 'admin@colegio.test', 'api_key' => 'CLAVE-SECRETA-123',
            'activo' => true, 'sync_contactos' => true, 'sync_facturas' => false,
        ], $extra));
    }

    private function representante(array $extra = []): Representante
    {
        return Representante::factory()->create(array_merge(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@ejemplo.test', 'telefono' => '809-555-0101', 'cedula' => '001-1234567-8'], $extra));
    }

    /** Una cuota de un estudiante cuyo representante principal es $rep. */
    private function cuota(Representante $rep, float $monto = 2500, string $estado = 'pendiente'): Pago
    {
        $sy = SchoolYear::firstOrCreate(['nombre' => '2026-2027'], ['fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $grado = Grado::firstOrCreate(['nivel' => 141], ['nombre' => 'Grado OD', 'orden' => 141, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $grupo = Grupo::firstOrCreate(['school_year_id' => $sy->id, 'grado_id' => $grado->id], ['seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);
        $est = Estudiante::factory()->create();
        $est->representantes()->attach($rep->id, ['es_principal' => 1]);
        $m = Matricula::create(['school_year_id' => $sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $grupo->id, 'fecha_matricula' => '2026-08-15', 'numero_orden' => 1, 'estado' => 'activa']);

        return Pago::create(['matricula_id' => $m->id, 'concepto' => 'Mensualidad Octubre', 'monto' => $monto, 'fecha_vencimiento' => '2026-10-31', 'estado' => $estado]);
    }

    private function sincronizar(OdooConexion $c): array
    {
        return (new OdooSincronizador($c->fresh()))->ejecutar();
    }

    // ── seguridad de la URL (SSRF) ──────────────────────────────────────────

    public function test_la_url_rechaza_direcciones_internas_y_esquemas_inseguros(): void
    {
        foreach ([
            'http://mi.odoo.com', 'ftp://mi.odoo.com', 'https://localhost', 'https://127.0.0.1', 'https://10.0.0.5', 'https://192.168.1.10',
            'https://172.16.0.1', 'https://169.254.169.254/latest/meta-data', 'https://100.64.0.1', 'https://[::1]', 'https://0.0.0.0',
            'https://usuario:clave@mi.odoo.com', 'https://servidor.local', 'no-es-una-url', '',
        ] as $mala) {
            try {
                OdooUrlSegura::validar($mala);
                $this->fail("Debió rechazar: {$mala}");
            } catch (OdooException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_la_url_acepta_https_publico_y_normaliza(): void
    {
        $r = OdooUrlSegura::validar('https://Mi-Colegio.odoo.com/');
        $this->assertSame('https://Mi-Colegio.odoo.com', $r['url']);
        $this->assertSame(443, $r['port']);

        $this->assertSame(['8.8.8.8'], OdooUrlSegura::validar('https://8.8.8.8:8443')['ips']);
    }

    public function test_en_desarrollo_se_puede_permitir_la_red_local_de_forma_explicita(): void
    {
        config(['odoo.permitir_red_local' => true]);

        $this->assertSame('http://127.0.0.1:8069', OdooUrlSegura::validar('http://127.0.0.1:8069')['url']);
    }

    // ── guardar credenciales ────────────────────────────────────────────────

    public function test_guardar_cifra_la_clave_y_nunca_la_devuelve(): void
    {
        $this->colegio();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.odoo.guardar'), [
            'url' => 'https://colegio.odoo.com', 'base_datos' => 'colegio', 'usuario' => 'admin@colegio.test', 'api_key' => 'CLAVE-SECRETA-123', 'activo' => 1, 'sync_contactos' => 1,
        ])->assertRedirect(route('admin.odoo.index'))->assertSessionHas('success');

        $c = OdooConexion::first();
        $this->assertSame('CLAVE-SECRETA-123', $c->api_key, 'se descifra al leerla');
        $this->assertStringNotContainsString('CLAVE-SECRETA-123', (string) \DB::table('odoo_conexiones')->value('api_key'), 'en la base está cifrada');
        $this->assertArrayNotHasKey('api_key', $c->toArray());
        $this->assertStringNotContainsString('CLAVE-SECRETA-123', $this->actingAs($admin)->get(route('admin.odoo.index'))->getContent(), 'la pantalla no la muestra');
        $this->assertDatabaseMissing('activity_logs', ['descripcion' => 'CLAVE-SECRETA-123']);
        $this->assertStringNotContainsString('CLAVE-SECRETA', (string) \DB::table('activity_logs')->where('accion', 'odoo_configuracion')->value('descripcion'));
    }

    public function test_guardar_con_la_clave_vacia_conserva_la_anterior(): void
    {
        $this->colegio();
        $this->conexion();

        $this->actingAs($this->admin())->put(route('admin.odoo.guardar'), [
            'url' => 'https://colegio.odoo.com', 'base_datos' => 'colegio', 'usuario' => 'admin@colegio.test', 'api_key' => '', 'activo' => 1, 'sync_facturas' => 1,
        ])->assertSessionHas('success');

        $c = OdooConexion::first();
        $this->assertSame('CLAVE-SECRETA-123', $c->api_key);
        $this->assertTrue($c->sync_facturas);
    }

    public function test_guardar_rechaza_una_url_que_apunta_a_la_red_interna_y_no_guarda_nada(): void
    {
        $this->colegio();

        $this->actingAs($this->admin())->put(route('admin.odoo.guardar'), [
            'url' => 'https://169.254.169.254', 'base_datos' => 'x', 'usuario' => 'a@b.c', 'api_key' => 'k',
        ])->assertSessionHasErrors('url');

        $this->assertSame(0, OdooConexion::count());
    }

    public function test_cambiar_de_odoo_reinicia_los_vinculos(): void
    {
        $this->colegio();
        $c = $this->conexion();
        OdooVinculo::create(['entidad_tipo' => 'representante', 'entidad_id' => 1, 'odoo_modelo' => 'res.partner', 'odoo_id' => 55, 'huella' => 'x']);

        $this->actingAs($this->admin())->put(route('admin.odoo.guardar'), [
            'url' => 'https://otro.odoo.com', 'base_datos' => 'colegio', 'usuario' => 'admin@colegio.test', 'api_key' => '', 'activo' => 1,
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'vínculos anteriores se reiniciaron'));

        $this->assertSame(0, OdooVinculo::count(), 'los ids del Odoo anterior no valen en el nuevo');
    }

    public function test_solo_el_administrador_puede_ver_y_guardar(): void
    {
        $this->colegio();
        $secretaria = User::factory()->create(['activo' => true]);
        $secretaria->assignRole('Secretaría');
        $docente = User::factory()->create(['activo' => true]);
        $docente->assignRole('Docente');

        $this->actingAs($secretaria)->get(route('admin.odoo.index'))->assertForbidden();
        $this->actingAs($secretaria)->put(route('admin.odoo.guardar'), ['url' => 'https://x.odoo.com'])->assertForbidden();
        $this->actingAs($docente)->get(route('admin.odoo.index'))->assertRedirect();   // EnsureAdminAccess lo manda a su portal
        $this->actingAs($this->admin())->get(route('admin.odoo.index'))->assertOk();
    }

    public function test_cada_centro_ve_solo_su_conexion(): void
    {
        $this->colegio('A');
        $this->conexion(['url' => 'https://colegio-a.odoo.com']);

        $this->colegio('B');
        $adminB = $this->admin();

        $html = $this->actingAs($adminB)->get(route('admin.odoo.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('colegio-a.odoo.com', $html);
        $this->assertSame(0, OdooConexion::count(), 'el colegio B no ve la conexión del A');
    }

    // ── probar conexión ─────────────────────────────────────────────────────

    public function test_probar_con_credenciales_correctas_guarda_uid_y_version(): void
    {
        $this->colegio();
        $this->conexion();

        $this->actingAs($this->admin())->post(route('admin.odoo.probar'))->assertSessionHas('success');

        $c = OdooConexion::first();
        $this->assertTrue($c->ultimo_test_ok);
        $this->assertSame(7, (int) $c->uid_odoo);
        $this->assertSame('17.0', $c->version_odoo);
    }

    public function test_probar_con_la_clave_equivocada_explica_el_problema(): void
    {
        $this->colegio();
        $this->conexion(['api_key' => 'CLAVE-MALA']);

        $this->actingAs($this->admin())->post(route('admin.odoo.probar'))->assertSessionHas('error', fn ($m) => str_contains($m, 'rechazó las credenciales'));

        $c = OdooConexion::first();
        $this->assertFalse($c->ultimo_test_ok);
        $this->assertStringNotContainsString('CLAVE-MALA', (string) $c->ultimo_error, 'el mensaje de error nunca lleva la clave');
    }

    public function test_probar_avisa_si_al_usuario_le_faltan_permisos(): void
    {
        $this->colegio();
        $this->conexion(['sync_facturas' => true]);
        $this->odoo->sinPermisoEn = ['account.move'];

        $this->actingAs($this->admin())->post(route('admin.odoo.probar'))->assertSessionHas('warning', fn ($m) => str_contains($m, 'account.move'));

        $this->assertFalse(OdooConexion::first()->ultimo_test_ok);
    }

    public function test_probar_hacia_un_servidor_que_no_es_odoo_da_un_error_claro(): void
    {
        $this->colegio();
        $this->conexion();
        $this->odoo->respuestaCruda = Http::response('<html>hola</html>', 200);   // un servidor que NO es Odoo

        $this->actingAs($this->admin())->post(route('admin.odoo.probar'))->assertSessionHas('error', fn ($m) => str_contains($m, 'no parece venir de un Odoo'));
    }

    public function test_un_redireccionamiento_no_se_sigue(): void
    {
        $this->colegio();
        $this->conexion();
        $this->odoo->respuestaCruda = Http::response('', 302, ['Location' => 'http://169.254.169.254/']);

        $this->actingAs($this->admin())->post(route('admin.odoo.probar'))->assertSessionHas('error', fn ($m) => str_contains($m, 'redirección'));
    }

    // ── contactos ───────────────────────────────────────────────────────────

    public function test_crea_los_contactos_y_no_vuelve_a_enviar_lo_que_no_cambio(): void
    {
        $t = $this->colegio();
        $c = $this->conexion();
        $rep = $this->representante();

        $r = $this->sincronizar($c);
        $this->assertSame(1, $r['contactos']['creados']);

        $partner = $this->odoo->partners[array_key_first($this->odoo->partners)];
        $this->assertSame("SGE{$t->id}-R-{$rep->id}", $partner['ref']);
        $this->assertSame('Ana Pérez', $partner['name']);
        $this->assertSame('ana@ejemplo.test', $partner['email']);
        $this->assertArrayNotHasKey('vat', $partner, 'la cédula no va en vat (Odoo valida su formato y rechazaría el contacto)');
        $this->assertStringContainsString('001-1234567-8', $partner['comment']);

        $llamadas = $this->odoo->totalEscrituras();
        $r2 = $this->sincronizar($c);
        $this->assertSame(1, $r2['contactos']['sin_cambios']);
        $this->assertSame($llamadas, $this->odoo->totalEscrituras(), 'la segunda vez no crea ni escribe nada en Odoo');
    }

    public function test_si_el_contacto_cambia_se_actualiza_y_no_se_duplica(): void
    {
        $this->colegio();
        $c = $this->conexion();
        $rep = $this->representante();
        $this->sincronizar($c);

        $rep->update(['telefono' => '829-555-9999']);
        $r = $this->sincronizar($c);

        $this->assertSame(1, $r['contactos']['actualizados']);
        $this->assertCount(1, $this->odoo->partners);
        $this->assertSame('829-555-9999', $this->odoo->partners[array_key_first($this->odoo->partners)]['phone']);
    }

    public function test_si_se_pierde_el_vinculo_se_readopta_por_la_ref_y_no_duplica(): void
    {
        $this->colegio();
        $c = $this->conexion();
        $this->representante();
        $this->sincronizar($c);

        OdooVinculo::query()->delete();   // p. ej. alguien reinició los vínculos
        $this->sincronizar($c);

        $this->assertCount(1, $this->odoo->partners, 'encontró el contacto por su ref en vez de crear otro');
        $this->assertSame(1, OdooVinculo::count());
    }

    public function test_si_el_contacto_se_borro_en_odoo_se_vuelve_a_crear(): void
    {
        $this->colegio();
        $c = $this->conexion();
        $rep = $this->representante();
        $this->sincronizar($c);
        $this->odoo->partners = [];   // lo borraron en Odoo

        $rep->update(['telefono' => '809-000-0000']);
        $r = $this->sincronizar($c);

        $this->assertSame(0, $r['contactos']['errores']);
        $this->assertCount(1, $this->odoo->partners);
    }

    public function test_solo_envia_los_representantes_del_centro_actual(): void
    {
        $a = $this->colegio('A');
        $cA = $this->conexion();
        $this->representante(['nombres' => 'DeA']);

        $this->colegio('B');
        $this->representante(['nombres' => 'DeB']);

        app()->instance('tenant', $a);
        $this->sincronizar($cA);

        $nombres = array_column($this->odoo->partners, 'name');
        $this->assertCount(1, $nombres);
        $this->assertStringContainsString('DeA', $nombres[0]);
    }

    public function test_un_error_en_un_contacto_no_detiene_a_los_demas(): void
    {
        $this->colegio();
        $c = $this->conexion();
        $this->representante(['nombres' => 'Falla']);
        $this->representante(['nombres' => 'Bien', 'email' => 'bien@ejemplo.test']);
        $this->odoo->falloAlCrearPartnerConNombre = 'Falla';

        $r = $this->sincronizar($c);

        $this->assertSame(1, $r['contactos']['creados']);
        $this->assertSame(1, $r['contactos']['errores']);
        $this->assertNotEmpty($r['errores']);
        $this->assertNotNull($c->fresh()->ultimo_error);
        $this->assertNotNull($c->fresh()->ultima_sync_at);
    }

    public function test_si_las_credenciales_fallan_se_detiene_todo(): void
    {
        $this->colegio();
        $c = $this->conexion(['api_key' => 'CLAVE-MALA']);
        $this->representante();
        $this->representante(['nombres' => 'Otra']);

        $r = $this->sincronizar($c);

        $this->assertSame([], $this->odoo->partners);
        $this->assertCount(1, $r['errores'], 'se corta al primer error de credenciales, sin insistir con cada registro');
        $this->assertStringContainsString('rechazó las credenciales', $c->fresh()->ultimo_error);
    }

    public function test_respeta_el_lote_maximo_por_ejecucion(): void
    {
        $this->colegio();
        $c = $this->conexion();
        foreach (range(1, 5) as $i) {
            $this->representante(['nombres' => "R{$i}", 'email' => "r{$i}@ejemplo.test"]);
        }
        config(['odoo.lote_maximo' => 2]);

        $this->assertSame(2, $this->sincronizar($c)['contactos']['creados']);
        $this->assertSame(2, $this->sincronizar($c)['contactos']['creados']);
        $this->assertSame(1, $this->sincronizar($c)['contactos']['creados']);
        $this->assertCount(5, $this->odoo->partners);
    }

    public function test_con_contactos_apagados_no_envia_contactos(): void
    {
        $this->colegio();
        $c = $this->conexion(['sync_contactos' => false]);
        $this->representante();

        $r = $this->sincronizar($c);

        $this->assertNull($r['contactos']);
        $this->assertSame([], $this->odoo->partners);
    }

    // ── facturas ────────────────────────────────────────────────────────────

    public function test_crea_la_factura_de_la_cuota_a_nombre_del_representante_sin_impuestos(): void
    {
        $t = $this->colegio();
        $c = $this->conexion(['sync_contactos' => false, 'sync_facturas' => true]);
        $rep = $this->representante();
        $pago = $this->cuota($rep, 2500);

        $r = $this->sincronizar($c);

        $this->assertSame(1, $r['facturas']['creados']);
        $factura = $this->odoo->moves[array_key_first($this->odoo->moves)];
        $this->assertSame('out_invoice', $factura['move_type']);
        $this->assertSame("SGE{$t->id}-P-{$pago->id}", $factura['ref']);
        $this->assertSame('2026-10-31', $factura['invoice_date_due']);
        $linea = $factura['invoice_line_ids'][0][2];
        $this->assertSame(2500.0, $linea['price_unit']);
        $this->assertSame([[6, 0, []]], $linea['tax_ids'], 'sin impuestos: el total en Odoo es el monto de la cuota');
        $this->assertArrayHasKey($factura['partner_id'], $this->odoo->partners, 'el contacto se crea aunque "contactos" esté apagado: la factura lo necesita');
        $this->assertArrayNotHasKey('journal_id', $factura);
    }

    public function test_la_factura_no_se_duplica_al_repetir(): void
    {
        $this->colegio();
        $c = $this->conexion(['sync_facturas' => true]);
        $this->cuota($this->representante());

        $this->sincronizar($c);
        $r = $this->sincronizar($c);

        $this->assertCount(1, $this->odoo->moves);
        $this->assertSame(1, $r['facturas']['sin_cambios']);
    }

    public function test_si_la_cuota_cambia_despues_no_se_reescribe_la_factura_y_se_avisa(): void
    {
        $this->colegio();
        $c = $this->conexion(['sync_facturas' => true]);
        $pago = $this->cuota($this->representante(), 2500);
        $this->sincronizar($c);

        $pago->update(['monto' => 3000]);
        $r = $this->sincronizar($c);

        $this->assertSame(1, $r['facturas']['cambiados']);
        $this->assertCount(1, $this->odoo->moves);
        $this->assertSame(2500.0, $this->odoo->moves[array_key_first($this->odoo->moves)]['invoice_line_ids'][0][2]['price_unit'], 'la factura en Odoo queda como estaba');
        $this->assertStringContainsString('cambió después', (string) OdooVinculo::where('entidad_tipo', 'pago')->value('ultimo_error'));
    }

    public function test_una_cuota_sin_representante_se_omite_y_se_cuenta(): void
    {
        $this->colegio();
        $c = $this->conexion(['sync_facturas' => true]);
        $pago = $this->cuota($this->representante());
        \DB::table('estudiante_representante')->delete();   // el estudiante se quedó sin representante

        $r = $this->sincronizar($c);

        $this->assertSame(1, $r['facturas']['sin_representante']);
        $this->assertSame([], $this->odoo->moves);
        $this->assertSame(0, OdooVinculo::where('entidad_tipo', 'pago')->count());
    }

    public function test_publicar_las_facturas_llama_a_action_post_y_si_falla_queda_el_aviso(): void
    {
        $this->colegio();
        $c = $this->conexion(['sync_facturas' => true, 'publicar_facturas' => true]);
        $this->cuota($this->representante());

        $this->sincronizar($c);
        $this->assertTrue($this->odoo->moves[array_key_first($this->odoo->moves)]['_publicada'] ?? false);

        // Otra cuota cuya publicación falla en Odoo: la factura queda creada en borrador con el motivo anotado.
        $this->odoo->falloAlPublicar = true;
        $pago2 = $this->cuota($this->representante(['nombres' => 'Otro', 'email' => 'o@ejemplo.test']));
        $this->sincronizar($c);
        $this->assertStringContainsString('no se pudo publicar', (string) OdooVinculo::where('entidad_id', $pago2->id)->where('entidad_tipo', 'pago')->value('ultimo_error'));
    }

    public function test_usa_el_diario_configurado(): void
    {
        $this->colegio();
        $c = $this->conexion(['sync_facturas' => true, 'diario_id' => 12]);
        $this->cuota($this->representante());

        $this->sincronizar($c);

        $this->assertSame(12, $this->odoo->moves[array_key_first($this->odoo->moves)]['journal_id']);
    }

    // ── desconectar, job y comando ──────────────────────────────────────────

    public function test_desconectar_borra_credenciales_y_vinculos(): void
    {
        $this->colegio();
        $this->conexion();
        OdooVinculo::create(['entidad_tipo' => 'representante', 'entidad_id' => 1, 'odoo_modelo' => 'res.partner', 'odoo_id' => 5, 'huella' => 'x']);

        $this->actingAs($this->admin())->delete(route('admin.odoo.desconectar'))->assertSessionHas('success');

        $this->assertSame(0, OdooConexion::count());
        $this->assertSame(0, OdooVinculo::count());
    }

    public function test_el_job_solo_envia_si_la_integracion_esta_activa(): void
    {
        $this->colegio();
        $c = $this->conexion(['activo' => false]);
        $this->representante();

        SincronizarOdooJob::dispatchSync();
        $this->assertSame([], $this->odoo->partners);

        $c->update(['activo' => true]);
        SincronizarOdooJob::dispatchSync();
        $this->assertCount(1, $this->odoo->partners);
    }

    public function test_el_comando_recorre_los_centros_con_odoo_activo_y_cada_uno_envia_lo_suyo(): void
    {
        $a = $this->colegio('A');
        $this->conexion(['url' => 'https://a.odoo.com']);
        $this->representante(['nombres' => 'DeA']);

        $b = $this->colegio('B');
        $this->conexion(['url' => 'https://b.odoo.com', 'activo' => false]);   // pausado
        $this->representante(['nombres' => 'DeB']);

        $this->colegio('C');   // sin conexión
        $this->representante(['nombres' => 'DeC']);

        $this->artisan('odoo:sincronizar')->assertSuccessful();

        $nombres = array_column($this->odoo->partners, 'name');
        $this->assertCount(1, $nombres, 'solo el centro con la integración activa');
        $this->assertStringContainsString('DeA', $nombres[0]);
    }
}

/** Odoo simulado: contesta como el JSON-RPC real y guarda lo que recibe. */
class OdooFalso
{
    public array $partners = [];
    public array $moves = [];
    public array $sinPermisoEn = [];
    public ?string $falloAlCrearPartnerConNombre = null;
    public bool $falloAlPublicar = false;
    /** Si se define, se devuelve tal cual en lugar de simular un Odoo (servidor que no es Odoo, redirecciones...). */
    public mixed $respuestaCruda = null;
    private int $sig = 100;
    public array $registro = [];

    public function totalEscrituras(): int
    {
        return count(array_filter($this->registro, fn ($l) => in_array($l, ['create', 'write', 'action_post'], true)));
    }

    public function manejar(array $cuerpo): array
    {
        $p = $cuerpo['params'];
        $a = $p['args'];

        if ($p['service'] === 'common') {
            return match ($p['method']) {
                'version'      => $this->ok(['server_version' => '17.0']),
                'authenticate' => $this->ok($a[2] === 'CLAVE-SECRETA-123' ? 7 : false),
            };
        }

        [, , $clave, $modelo, $metodo, $argsM, $kw] = $a;
        if ($clave !== 'CLAVE-SECRETA-123') {
            return $this->error('Access Denied');
        }
        $this->registro[] = $metodo;
        $coleccion = $modelo === 'res.partner' ? 'partners' : 'moves';

        switch ($metodo) {
            case 'check_access_rights':
                return $this->ok(! in_array($modelo, $this->sinPermisoEn, true));
            case 'search_read':
                $ref = $argsM[0][0][2];
                foreach ($this->$coleccion as $id => $r) {
                    if (($r['ref'] ?? null) === $ref) {
                        return $this->ok([['id' => $id]]);
                    }
                }

                return $this->ok([]);
            case 'create':
                $vals = $argsM[0];
                if ($modelo === 'res.partner' && $this->falloAlCrearPartnerConNombre && str_contains($vals['name'], $this->falloAlCrearPartnerConNombre)) {
                    return $this->error('ValidationError: contacto inválido');
                }
                $this->$coleccion[$this->sig] = $vals;

                return $this->ok($this->sig++);
            case 'write':
                $id = $argsM[0][0];
                if (! isset($this->$coleccion[$id])) {
                    return $this->error('Record does not exist or has been deleted.');
                }
                $this->$coleccion[$id] = array_merge($this->$coleccion[$id], $argsM[1]);

                return $this->ok(true);
            case 'action_post':
                if ($this->falloAlPublicar) {
                    return $this->error('UserError: el diario no permite publicar');
                }
                $this->moves[$argsM[0][0]]['_publicada'] = true;

                return $this->ok(true);
        }

        return $this->error("método no soportado por el Odoo falso: {$metodo}");
    }

    private function ok(mixed $r): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'result' => $r];
    }

    private function error(string $m): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => 200, 'message' => 'Odoo Server Error', 'data' => ['message' => $m]]];
    }
}
