<?php

namespace Tests\Feature;

use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto — Fase 2 del portal público: el feature flag
 * "modo_publico" ya existía en SuperAdmin\TenantController::ALL_FEATURES
 * sin usarse en ningún lado. Ahora PublicSiteController::show() lo exige
 * para mostrar el contenido; si está apagado o no tiene fila todavía,
 * muestra "portal no disponible" en vez de un 404 o un error.
 */
class PortalPublicoModoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombre)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    private function urlSitio(Tenant $tenant): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test/sitio';
    }

    public function test_sin_fila_de_modo_publico_muestra_portal_no_disponible(): void
    {
        $tenant = $this->crearTenant('Colegio Sin Flag');
        // A propósito: no se crea ninguna fila de TenantFeature.

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('no tiene su portal público activado');
    }

    public function test_modo_publico_desactivado_muestra_portal_no_disponible(): void
    {
        $tenant = $this->crearTenant('Colegio Apagado');
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => false]);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_titulo', 'No Debe Verse');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('no tiene su portal público activado');
        $response->assertDontSee('No Debe Verse');
    }

    public function test_modo_publico_activado_muestra_el_contenido_normal(): void
    {
        $tenant = $this->crearTenant('Colegio Encendido');
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => true]);

        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('hp_hero_titulo', 'Sí Debe Verse');
        app()->forgetInstance('tenant');

        $response = $this->get($this->urlSitio($tenant));

        $response->assertOk();
        $response->assertSee('Sí Debe Verse');
        $response->assertDontSee('no tiene su portal público activado');
    }

    public function test_backfill_activa_modo_publico_solo_para_tenants_sin_fila(): void
    {
        $sinFila       = $this->crearTenant('Colegio Backfill Sin Fila');
        $conFilaActiva = $this->crearTenant('Colegio Backfill Con Fila Activa');
        $conFilaOff    = $this->crearTenant('Colegio Backfill Con Fila Off');

        TenantFeature::create(['tenant_id' => $conFilaActiva->id, 'feature' => 'modo_publico', 'activo' => true]);
        TenantFeature::create(['tenant_id' => $conFilaOff->id, 'feature' => 'modo_publico', 'activo' => false]);

        $this->artisan('tenant:activar-modo-publico')->assertSuccessful();

        $this->assertTrue(
            TenantFeature::where('tenant_id', $sinFila->id)->where('feature', 'modo_publico')->value('activo') == 1,
            'El tenant sin fila debía quedar activo tras el backfill.'
        );
        $this->assertTrue(
            TenantFeature::where('tenant_id', $conFilaActiva->id)->where('feature', 'modo_publico')->value('activo') == 1,
            'El tenant que ya estaba activo no debía cambiar.'
        );
        $this->assertFalse(
            (bool) TenantFeature::where('tenant_id', $conFilaOff->id)->where('feature', 'modo_publico')->value('activo'),
            'El tenant que un SuperAdmin desactivó a propósito NO debe reactivarse por el backfill.'
        );
    }

    public function test_backfill_con_dry_run_no_guarda_nada(): void
    {
        $tenant = $this->crearTenant('Colegio Dry Run');

        $this->artisan('tenant:activar-modo-publico', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull(
            TenantFeature::where('tenant_id', $tenant->id)->where('feature', 'modo_publico')->value('activo'),
            'El dry-run no debe crear ninguna fila.'
        );
    }
}
