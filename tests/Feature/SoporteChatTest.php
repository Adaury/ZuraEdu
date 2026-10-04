<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\SupportMessage;
use App\Models\SupportSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chat de soporte público: las respuestas del admin deben llegar al cliente con la sesión abierta, y hay respuestas
 * automáticas (acuse y número de soporte) para que nunca se quede sin contestación.
 *
 * Fallos reales que cubre: la consulta de mensajes daba 500 (relaciones cargadas de forma diferida), el widget no
 * consultaba tras iniciar la conversación, y el límite de 10 peticiones/min del grupo bloqueaba la consulta periódica.
 */
class SoporteChatTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->tenant = Tenant::create(['nombre_institucion' => 'Colegio Chat', 'dominio' => 'chat' . random_int(100000, 999999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        $this->admin = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id])->assignRole('Administrador');

    }

    /** URL con el dominio del colegio, para que ResolveTenant identifique el colegio (como en PublicSitioAdsTest). */
    private function ruta(string $nombre, mixed $parametros = []): string
    {
        return 'http://' . $this->tenant->dominio . '.zuraedu.test' . route($nombre, $parametros, false);
    }

    private function iniciar(string $mensaje = 'Hola, necesito ayuda'): string
    {
        app()->instance('tenant', $this->tenant);

        return $this->postJson($this->ruta('support.chat.start'), ['nombre' => 'María Pérez', 'mensaje' => $mensaje])
            ->assertCreated()->json('token');
    }

    private function mensajes(string $token)
    {
        return $this->getJson($this->ruta('support.chat.messages', $token))->assertOk()->json();
    }

    public function test_al_iniciar_la_conversacion_llega_un_acuse_automatico(): void
    {
        $token = $this->iniciar();

        $m = $this->mensajes($token);
        $this->assertCount(2, $m);
        $this->assertSame('visitor', $m[0]['origen']);
        $this->assertSame('admin', $m[1]['origen']);
        $this->assertStringContainsString('Hola María', $m[1]['mensaje']);
        $this->assertStringContainsString('agente te responderá', $m[1]['mensaje']);
    }

    public function test_la_respuesta_del_admin_llega_al_cliente_por_la_consulta_de_mensajes(): void
    {
        $token = $this->iniciar();
        $sesion = SupportSession::where('token', $token)->firstOrFail();

        $this->actingAs($this->admin)->postJson($this->ruta('admin.soporte.chat.reply', $sesion), ['mensaje' => 'Soy Ana, de soporte'])->assertCreated();

        $textos = array_column($this->mensajes($token), 'mensaje');
        $this->assertContains('Soy Ana, de soporte', $textos);
    }

    public function test_si_sigue_escribiendo_sin_que_lo_atiendan_recibe_el_numero_de_soporte_una_sola_vez(): void
    {
        app()->instance('tenant', $this->tenant);
        Setting::set('soporte_telefono', '809-555-0100');
        $token = $this->iniciar();

        $this->postJson($this->ruta('support.chat.send', $token), ['mensaje' => 'Sigo aquí'])->assertCreated();
        $this->postJson($this->ruta('support.chat.send', $token), ['mensaje' => '¿Alguien?'])->assertCreated();

        $conNumero = array_filter(array_column($this->mensajes($token), 'mensaje'), fn ($t) => str_contains($t, '809-555-0100'));
        $this->assertCount(1, $conNumero, 'el número se ofrece una sola vez');
    }

    public function test_sin_numero_configurado_se_usa_el_telefono_institucional(): void
    {
        app()->instance('tenant', $this->tenant);
        \App\Models\ConfigInstitucional::set('telefono', '829-555-0199');
        $token = $this->iniciar();

        $this->postJson($this->ruta('support.chat.send', $token), ['mensaje' => 'Otra duda'])->assertCreated();

        $this->assertTrue(collect($this->mensajes($token))->contains(fn ($m) => str_contains($m['mensaje'], '829-555-0199')));
    }

    public function test_si_una_persona_ya_atiende_no_se_envian_mas_respuestas_automaticas(): void
    {
        $token = $this->iniciar();
        $sesion = SupportSession::where('token', $token)->firstOrFail();
        $this->actingAs($this->admin)->postJson($this->ruta('admin.soporte.chat.reply', $sesion), ['mensaje' => 'Te atiendo yo'])->assertCreated();

        $this->postJson($this->ruta('support.chat.send', $token), ['mensaje' => 'Gracias, otra pregunta'])->assertCreated();

        $automaticos = SupportMessage::where('session_id', $sesion->id)->where('origen', 'admin')->whereNull('user_id')->count();
        $this->assertSame(1, $automaticos, 'solo el acuse inicial');
    }

    public function test_el_panel_del_admin_puede_abrir_la_conversacion(): void
    {
        $token = $this->iniciar();
        $sesion = SupportSession::where('token', $token)->firstOrFail();

        $r = $this->actingAs($this->admin)->getJson($this->ruta('admin.soporte.chat.messages', $sesion))->assertOk()->json();
        $this->assertCount(2, $r);
    }

    public function test_la_consulta_periodica_no_se_bloquea_con_el_limite_del_grupo(): void
    {
        $token = $this->iniciar();

        // 12 consultas seguidas (una cada 5 s durante un minuto) deben pasar; antes se bloqueaban tras 10
        for ($i = 0; $i < 12; $i++) {
            $this->getJson($this->ruta('support.chat.messages', $token))->assertOk();
        }
    }
}
