<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Instalar la app. En Safari/iPhone no existe un «botón instalar» que una web pueda pulsar (solo Compartir → Añadir a pantalla de
 * inicio), así que el aviso era un cuadro de texto sin acción y parecía que «no hacía nada». Ahora es una guía paso a paso con
 * botón «Entendido», sirve también para iPad y Chrome en iPhone, y hay una entrada permanente «Instalar la app» en los accesos.
 */
class PwaInstalacionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);

        return User::factory()->create(['activo' => true])->assignRole('Administrador');
    }

    public function test_el_panel_incluye_la_guia_de_instalacion_con_su_boton_y_la_funcion_global(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="pwa-ios-ok"', $html, 'botón Entendido');
        $this->assertStringContainsString('id="pwa-guia-pasos"', $html);
        $this->assertStringContainsString('window.zuraInstalarApp', $html);
        $this->assertStringContainsString('iPadOS se presenta como Mac', $html, 'detecta el iPad');
        $this->assertStringContainsString('CriOS', $html, 'Chrome en iPhone');
    }

    public function test_los_accesos_rapidos_tienen_la_entrada_permanente_instalar_la_app(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="ar-instalar"', $html);
        $this->assertStringContainsString('Instalar la app en este dispositivo', $html);
    }

    public function test_el_manifiesto_tiene_lo_necesario_para_instalar(): void
    {
        $m = $this->actingAs($this->admin())->getJson('/pwa/manifest.json')->assertOk()->json();

        $this->assertSame('standalone', $m['display']);
        $this->assertNotEmpty($m['name']);
        $this->assertNotEmpty($m['start_url']);
        $sizes = array_column($m['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
    }
}
