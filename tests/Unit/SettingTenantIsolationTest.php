<?php

namespace Tests\Unit;

use App\Helpers\Setting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * App\Helpers\Setting::$loaded es un caché en memoria de vida-de-proceso.
 * En un request HTTP normal (proceso de corta duración) esto es inofensivo,
 * pero en un proceso de larga duración que atiende varios tenants — un
 * queue worker, o un comando de consola que itera tenants vía
 * App\Console\Commands\Concerns\LoopsPerTenant (aplicado hoy a 8 comandos) —
 * un caché sin la clave de tenant arrastraba los settings del primer tenant
 * leído a todos los siguientes, hasta el próximo set()/setMany(). Hallazgo
 * descubierto durante la auditoría del módulo WhatsApp — ver
 * [[project_auditoria_2026_09_01_system_settings]].
 */
class SettingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $sufijo): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => "Colegio {$sufijo}",
            'dominio'            => 'colegio' . strtolower($sufijo) . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);
    }

    public function test_leer_settings_de_un_tenant_no_contamina_al_siguiente_en_el_mismo_proceso(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        app()->instance('tenant', $a);
        Setting::set('nombre_prueba', 'VALOR_A');
        app()->forgetInstance('tenant');

        app()->instance('tenant', $b);
        Setting::set('nombre_prueba', 'VALOR_B');
        app()->forgetInstance('tenant');

        // Simula un worker/comando que procesa tenant A primero — esto puebla
        // el caché en memoria — y luego tenant B, SIN ningún set() de por medio.
        app()->instance('tenant', $a);
        $this->assertEquals('VALOR_A', Setting::get('nombre_prueba'));
        app()->forgetInstance('tenant');

        app()->instance('tenant', $b);
        $this->assertEquals(
            'VALOR_B',
            Setting::get('nombre_prueba'),
            'Tenant B no debe heredar el valor cacheado de tenant A.'
        );
        app()->forgetInstance('tenant');
    }

    public function test_volver_al_primer_tenant_conserva_su_propio_valor(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');

        app()->instance('tenant', $a);
        Setting::set('nombre_prueba', 'VALOR_A');
        app()->forgetInstance('tenant');

        app()->instance('tenant', $b);
        Setting::set('nombre_prueba', 'VALOR_B');
        Setting::get('nombre_prueba'); // puebla el caché en memoria de B
        app()->forgetInstance('tenant');

        app()->instance('tenant', $a);
        $this->assertEquals('VALOR_A', Setting::get('nombre_prueba'));
        app()->forgetInstance('tenant');
    }
}
