<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Jobs\EnviarWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Verificación de la mensajería:
 *  - la cola: el worker programado procesa TODAS las colas (antes solo «default» y los WhatsApp nunca salían);
 *  - WhatsApp: números locales normalizados (809-555-1234 → 18095551234), Twilio/Meta con los datos correctos y el motivo del fallo;
 *  - pruebas de envío (correo y WhatsApp) desde la pantalla del administrador.
 */
class MensajeriaCorreoWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->tenant = Tenant::create(['nombre_institucion' => 'Colegio Msj', 'dominio' => 'msj' . random_int(100000, 999999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        $this->admin = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id, 'email' => 'admin.msj@example.test'])->assignRole('Administrador');
        app()->instance('tenant', $this->tenant);
    }

    /** Un Account SID con el formato real de Twilio: AC + 32 caracteres. */
    private function sidValido(): string
    {
        return 'AC' . str_repeat('0', 32);
    }

    private function url(string $nombre): string
    {
        return 'http://' . $this->tenant->dominio . '.zuraedu.test' . route($nombre, [], false);
    }

    // ── Cola ────────────────────────────────────────────────────────────────

    public function test_el_worker_programado_procesa_todas_las_colas_que_usa_el_sistema(): void
    {
        $evento = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'queue:work'));

        $this->assertNotNull($evento, 'el worker de cola está programado');
        foreach (['default', 'notifications', 'whatsapp', 'emails'] as $cola) {
            $this->assertStringContainsString($cola, $evento->command, "la cola «{$cola}» debe procesarse");
        }
    }

    // ── Normalización de teléfonos ───────────────────────────────────────────

    public function test_normaliza_telefonos_dominicanos_y_extranjeros(): void
    {
        $this->assertSame('18095551234', WhatsAppService::normalizarTelefono('809-555-1234'));
        $this->assertSame('18295551234', WhatsAppService::normalizarTelefono('(829) 555 1234'));
        $this->assertSame('18495551234', WhatsAppService::normalizarTelefono('+1 849 555-1234'));
        $this->assertSame('18095551234', WhatsAppService::normalizarTelefono('1-809-555-1234'));
        $this->assertSame('18095551234', WhatsAppService::normalizarTelefono('0018095551234'));
        $this->assertSame('34600123456', WhatsAppService::normalizarTelefono('+34 600 123 456'));
        $this->assertSame('18095551234', WhatsAppService::normalizarTelefono('809-555-1234', '1'));
        $this->assertSame('525512345678', WhatsAppService::normalizarTelefono('5512345678', '52'));
    }

    public function test_un_telefono_con_pocos_digitos_es_invalido(): void
    {
        $this->assertNull(WhatsAppService::normalizarTelefono('555-1234'));
        $this->assertNull(WhatsAppService::normalizarTelefono(''));
        $this->assertNull(WhatsAppService::normalizarTelefono('abc'));
    }

    // ── Envío por proveedor ──────────────────────────────────────────────────

    public function test_twilio_recibe_los_numeros_en_formato_internacional(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);
        Setting::set('whatsapp_provider', 'twilio');
        Setting::set('whatsapp_account_sid', $this->sidValido());
        Setting::set('whatsapp_auth_token', 'tok');
        Setting::set('whatsapp_from_number', '1+829-477-8613');   // tal como lo escribió el cliente: mal formado

        $r = (new EnviarWhatsApp('809-555-1234', 'Hola'))->enviar();

        $this->assertTrue($r['ok']);
        Http::assertSent(fn ($req) => $req['To'] === 'whatsapp:+18095551234' && $req['From'] === 'whatsapp:+18294778613');
    }

    public function test_meta_exige_el_phone_number_id_y_explica_el_error(): void
    {
        Http::fake();
        Setting::set('whatsapp_provider', 'meta');
        Setting::set('whatsapp_auth_token', 'tok');
        Setting::set('whatsapp_from_number', '1+829-477-8613');   // un teléfono, no un Phone Number ID

        $r = (new EnviarWhatsApp('809-555-1234', 'Hola'))->enviar();

        $this->assertFalse($r['ok']);
        $this->assertTrue($r['definitivo']);
        $this->assertStringContainsString('Phone Number ID', $r['error']);
        Http::assertNothingSent();
    }

    public function test_meta_envia_a_los_digitos_con_codigo_de_pais_y_muestra_el_error_del_proveedor(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401)]);
        Setting::set('whatsapp_provider', 'meta');
        Setting::set('whatsapp_auth_token', 'tok');
        Setting::set('whatsapp_from_number', '109876543210');

        $r = (new EnviarWhatsApp('829-555-0000', 'Hola'))->enviar();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Invalid OAuth access token', $r['error']);
        Http::assertSent(fn ($req) => $req['to'] === '18295550000' && str_contains($req->url(), '/109876543210/messages'));
    }

    public function test_un_numero_invalido_no_se_reintenta(): void
    {
        Http::fake();
        $r = (new EnviarWhatsApp('12345', 'Hola'))->enviar();

        $this->assertFalse($r['ok']);
        $this->assertTrue($r['definitivo']);
        Http::assertNothingSent();
    }

    // ── Pruebas de envío desde la pantalla ───────────────────────────────────

    public function test_el_correo_de_prueba_se_envia_al_correo_del_propio_usuario(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)->post($this->url('admin.sistema.email-notif.probar'), ['to' => 'otro@example.test'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertStringContainsString('admin.msj@example.test', session('success'), 'va a SU correo, no a una dirección del formulario');
    }

    public function test_si_el_servidor_de_correo_falla_se_muestra_el_motivo_sin_romper_la_pantalla(): void
    {
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('Expected response code "250" but got code "530", with message "530-5.7.0 Authentication Required"'));

        $r = $this->actingAs($this->admin)->post($this->url('admin.sistema.email-notif.probar'))->assertRedirect();

        $r->assertSessionHas('error');
        $this->assertStringContainsString('rechazó el usuario/contraseña', session('error'));
    }

    public function test_el_whatsapp_de_prueba_muestra_el_resultado(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'Authenticate'], 401)]);
        Setting::set('whatsapp_provider', 'twilio');
        Setting::set('whatsapp_account_sid', $this->sidValido());
        Setting::set('whatsapp_auth_token', 'malo');
        Setting::set('whatsapp_from_number', '+18095550100');

        $this->actingAs($this->admin)->post($this->url('admin.sistema.whatsapp.probar'), ['telefono_prueba' => '809-555-1234'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertStringContainsString('Twilio respondió 401', session('error'));
    }

    public function test_las_pantallas_de_configuracion_muestran_el_bloque_de_prueba(): void
    {
        $this->actingAs($this->admin)->get($this->url('admin.sistema.email-notif'))->assertOk()->assertSee('Enviar correo de prueba');
        $this->actingAs($this->admin)->get($this->url('admin.sistema.whatsapp'))->assertOk()->assertSee('Enviar WhatsApp de prueba');
    }

    public function test_quien_no_es_administrador_no_puede_usar_las_pruebas(): void
    {
        $docente = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id])->assignRole('Docente');

        $this->assertNotSame(200, $this->actingAs($docente)->post($this->url('admin.sistema.whatsapp.probar'), ['telefono_prueba' => '809-555-1234'])->getStatusCode());
    }

    // ── Guardado de la configuración de WhatsApp ─────────────────────────────

    private function guardarWa(array $datos)
    {
        return $this->actingAs($this->admin)->post($this->url('admin.sistema.whatsapp.update'), array_merge(['whatsapp_provider' => 'meta'], $datos));
    }

    public function test_con_meta_no_se_guarda_un_telefono_como_phone_number_id(): void
    {
        $this->guardarWa(['whatsapp_provider' => 'meta', 'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '1+829-477-8613'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertStringContainsString('Phone Number ID', session('error'));
        $this->assertNotSame('1+829-477-8613', Setting::get('whatsapp_from_number'), 'no queda guardado un valor que luego fallaría');
    }

    public function test_con_meta_se_guarda_el_phone_number_id_y_se_conserva_el_sid_de_twilio(): void
    {
        Setting::set('whatsapp_account_sid', $this->sidValido());

        $this->guardarWa(['whatsapp_provider' => 'meta', 'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '109876543210'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('109876543210', Setting::get('whatsapp_from_number'));
        $this->assertSame($this->sidValido(), Setting::get('whatsapp_account_sid'), 'el SID de Twilio no se borra al guardar con Meta');
    }

    public function test_con_twilio_se_valida_el_numero_de_origen(): void
    {
        $this->guardarWa(['whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => $this->sidValido(), 'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '123'])
            ->assertSessionHas('error');

        $this->guardarWa(['whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => $this->sidValido(), 'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '+18095550100'])
            ->assertSessionHas('success');
        $this->assertSame('+18095550100', Setting::get('whatsapp_from_number'));
    }

    public function test_un_account_sid_de_twilio_con_formato_incorrecto_se_rechaza_al_guardar(): void
    {
        $this->guardarWa(['whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => 'abc123', 'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '+18095550100'])
            ->assertSessionHas('error');

        $this->assertStringContainsString('Account SID', session('error'));
        $this->assertNotSame('abc123', Setting::get('whatsapp_account_sid'));
    }

    public function test_el_account_sid_correcto_se_guarda_sin_los_espacios_del_copiar_y_pegar(): void
    {
        $sid = 'AC' . str_repeat('a1', 16);

        $this->guardarWa(['whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => "  {$sid} 
", 'whatsapp_auth_token' => '  tok ', 'whatsapp_from_number' => '+18095550100'])
            ->assertSessionHas('success');

        $this->assertSame($sid, Setting::get('whatsapp_account_sid'));
        $this->assertSame('tok', Setting::get('whatsapp_auth_token'));
    }

    public function test_la_prueba_explica_un_sid_guardado_con_formato_incorrecto_sin_llamar_a_twilio(): void
    {
        Http::fake();
        Setting::set('whatsapp_provider', 'twilio');
        Setting::set('whatsapp_account_sid', 'abc123');
        Setting::set('whatsapp_auth_token', 'tok');
        Setting::set('whatsapp_from_number', '+18095550100');

        $r = (new EnviarWhatsApp('809-555-1234', 'Hola'))->enviar();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('el actual mide 6', $r['error']);
        Http::assertNothingSent();
    }

    // ── Plantillas de Twilio (Content SID) ───────────────────────────────────

    private function configurarTwilio(array $extra = []): void
    {
        foreach (array_merge([
            'whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => $this->sidValido(),
            'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '+14155238886',
        ], $extra) as $k => $v) {
            Setting::set($k, $v);
        }
    }

    public function test_con_plantilla_se_envia_el_content_sid_y_el_mensaje_como_variable(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);
        $this->configurarTwilio(['whatsapp_twilio_content_sid' => 'HX' . str_repeat('b', 32)]);

        $r = (new EnviarWhatsApp('809-555-1234', 'Aviso de prueba'))->enviar();

        $this->assertTrue($r['ok']);
        Http::assertSent(fn ($req) => $req['ContentSid'] === 'HX' . str_repeat('b', 32)
            && json_decode($req['ContentVariables'], true) === ['1' => 'Aviso de prueba']
            && ! isset($req['Body']));
    }

    public function test_sin_plantilla_se_envia_texto_libre(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);
        $this->configurarTwilio();

        (new EnviarWhatsApp('809-555-1234', 'Hola'))->enviar();

        Http::assertSent(fn ($req) => $req['Body'] === 'Hola' && ! isset($req['ContentSid']));
    }

    public function test_el_error_contentsid_required_se_explica_con_la_solucion(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['code' => 21656, 'message' => 'ContentSid Required'], 400)]);
        $this->configurarTwilio();

        $r = (new EnviarWhatsApp('809-555-1234', 'Hola'))->enviar();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Content Template Builder', $r['error']);
        $this->assertStringContainsString('Content SID', $r['error']);
    }

    public function test_el_content_sid_se_valida_y_no_se_borra_al_guardar_con_meta(): void
    {
        $this->guardarWa(['whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => $this->sidValido(), 'whatsapp_auth_token' => 'tok',
            'whatsapp_from_number' => '+14155238886', 'whatsapp_twilio_content_sid' => 'malo'])->assertSessionHasErrors('whatsapp_twilio_content_sid');

        $hx = 'HX' . str_repeat('c', 32);
        $this->guardarWa(['whatsapp_provider' => 'twilio', 'whatsapp_account_sid' => $this->sidValido(), 'whatsapp_auth_token' => 'tok',
            'whatsapp_from_number' => '+14155238886', 'whatsapp_twilio_content_sid' => $hx])->assertSessionHas('success');
        $this->assertSame($hx, Setting::get('whatsapp_twilio_content_sid'));

        $this->guardarWa(['whatsapp_provider' => 'meta', 'whatsapp_auth_token' => 'tok', 'whatsapp_from_number' => '109876543210'])->assertSessionHas('success');
        $this->assertSame($hx, Setting::get('whatsapp_twilio_content_sid'), 'con Meta el campo no se envía y no se borra');
    }

    public function test_la_pantalla_muestra_el_campo_de_plantilla_sin_interpretar_las_llaves(): void
    {
        $this->actingAs($this->admin)->get($this->url('admin.sistema.whatsapp'))->assertOk()
            ->assertSee('Content SID', false)->assertSee('{{1}}', false);
    }
}
