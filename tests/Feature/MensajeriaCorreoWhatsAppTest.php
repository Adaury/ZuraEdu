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
        Setting::set('whatsapp_account_sid', 'AC123');
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
        Setting::set('whatsapp_account_sid', 'AC123');
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
}
