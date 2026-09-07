<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Chat con IA del sitio público (consultas sobre la institución), sin login.
 * A propósito solo usa datos públicos del Homepage (ConfigInstitucional) —
 * nunca información interna del sistema (estudiantes, pagos, notas).
 */
class PublicSiteChatTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre, bool $modoPublico = true): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => $modoPublico]);

        return $tenant;
    }

    private function url(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio/chat';
    }

    public function test_responde_usando_la_informacion_publica_del_homepage(): void
    {
        config(['services.gemini.key' => 'clave-de-prueba']);
        $tenant = $this->crearTenant('Colegio Chat IA');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_about_texto', 'Somos un centro bilingüe fundado en 1990.');
        app()->forgetInstance('tenant');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Somos un centro bilingüe fundado en 1990.']]]],
                ],
            ], 200),
        ]);

        $response = $this->postJson($this->url($tenant), ['message' => '¿Cuándo fue fundado el colegio?']);

        $response->assertOk();
        $response->assertJson(['reply' => 'Somos un centro bilingüe fundado en 1990.']);

        Http::assertSent(function ($request) use ($tenant) {
            $prompt = $request['systemInstruction']['parts'][0]['text'] ?? '';
            return str_contains($prompt, 'Colegio Chat IA')
                && str_contains($prompt, 'Somos un centro bilingüe fundado en 1990.')
                && str_contains($prompt, 'NUNCA reveles');
        });
    }

    public function test_sin_clave_de_gemini_responde_sin_llamar_a_la_api(): void
    {
        config(['services.gemini.key' => null]);
        $tenant = $this->crearTenant('Colegio Chat Sin Clave');

        Http::fake();

        $response = $this->postJson($this->url($tenant), ['message' => 'Hola']);

        $response->assertOk();
        Http::assertNothingSent();
    }

    public function test_sin_modo_publico_el_chat_no_responde(): void
    {
        config(['services.gemini.key' => 'clave-de-prueba']);
        $tenant = $this->crearTenant('Colegio Chat Apagado', modoPublico: false);

        Http::fake();

        $response = $this->postJson($this->url($tenant), ['message' => 'Hola']);

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_el_mensaje_es_obligatorio(): void
    {
        $tenant = $this->crearTenant('Colegio Chat Sin Mensaje');

        $response = $this->postJson($this->url($tenant), []);

        $response->assertStatus(422);
    }
}
