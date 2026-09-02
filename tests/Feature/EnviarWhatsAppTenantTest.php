<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Jobs\EnviarWhatsApp;
use App\Jobs\Middleware\ResolveTenantForJob;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * EnviarWhatsApp — hallazgo de la auditoría de WhatsApp (ver
 * [[project_auditoria_2026_09_01_system_settings]]): el job no extendía
 * TenantJob, así que en el worker de cola (sin ningún tenant vinculado)
 * Setting::get('whatsapp_...') caía al tenant por defecto — un mensaje de
 * un tenant podía salir con las credenciales de otro. Este test reproduce
 * el escenario exacto con 2 tenants con credenciales distintas.
 */
class EnviarWhatsAppTenantTest extends TestCase
{
    use RefreshDatabase;

    private function tenantConWhatsapp(string $sufijo): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => "Colegio {$sufijo}",
            'dominio'            => 'colegio' . strtolower($sufijo) . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);

        app()->instance('tenant', $tenant);
        Setting::setMany([
            'module_whatsapp'       => '1',
            'whatsapp_provider'     => 'twilio',
            'whatsapp_account_sid'  => "SID_{$sufijo}",
            'whatsapp_auth_token'   => "TOKEN_{$sufijo}",
            'whatsapp_from_number'  => "+1809000{$sufijo}",
        ]);
        app()->forgetInstance('tenant');

        return $tenant;
    }

    public function test_el_job_captura_el_tenant_vigente_al_momento_de_despachar(): void
    {
        $tenantA = $this->tenantConWhatsapp('A');
        $tenantB = $this->tenantConWhatsapp('B');

        app()->instance('tenant', $tenantA);
        $jobA = new EnviarWhatsApp('+18095551111', 'Hola A');
        app()->forgetInstance('tenant');

        app()->instance('tenant', $tenantB);
        $jobB = new EnviarWhatsApp('+18095552222', 'Hola B');
        app()->forgetInstance('tenant');

        $this->assertEquals($tenantA->id, $jobA->tenantId);
        $this->assertEquals($tenantB->id, $jobB->tenantId);
    }

    public function test_el_worker_usa_las_credenciales_del_tenant_correcto_no_las_del_tenant_por_defecto(): void
    {
        $tenantA = $this->tenantConWhatsapp('A');
        $this->tenantConWhatsapp('B'); // segundo tenant, no debe filtrarse

        // Se despacha desde dentro del contexto de tenant A (como ocurre real:
        // dentro de un request HTTP o de LoopsPerTenant::forEachTenant()).
        app()->instance('tenant', $tenantA);
        $job = new EnviarWhatsApp('+18095551111', 'Hola A');
        app()->forgetInstance('tenant');

        // Simula el worker: SIN ningún tenant vinculado (el bug original),
        // solo el middleware del job restaurándolo — igual que hace el queue
        // worker real vía ResolveTenantForJob.
        $this->assertFalse(app()->bound('tenant'), 'Precondición: worker sin tenant vinculado.');

        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        (new ResolveTenantForJob())->handle($job, fn ($j) => $j->handle());

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'SID_A/Messages.json')
                && $request['From'] === 'whatsapp:+1809000A';
        });
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'SID_B'));
    }
}
