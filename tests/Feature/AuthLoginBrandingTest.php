<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug real encontrado con la institución San Pablo: /login mostraba
 * "ZuraEdu" genérico en vez del nombre real del centro, porque leía
 * Setting::system_name (un campo aparte del Homepage que casi nadie llena)
 * sin ningún fallback al nombre real del tenant. Ahora cae a
 * ConfigInstitucional::nombre_institucion y luego a Tenant::nombre_institucion.
 * También afectaba a /register, /forgot-password y /reset-password.
 */
class AuthLoginBrandingTest extends TestCase
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

    private function url(Tenant $tenant, string $path): string
    {
        return 'http://' . $tenant->dominio . '.zuraedu.test' . $path;
    }

    public function test_login_muestra_el_nombre_real_del_tenant_sin_configuracion_extra(): void
    {
        $tenant = $this->crearTenant('Centro Educativo San Pablo');

        $response = $this->get($this->url($tenant, '/login'));

        $response->assertOk();
        $response->assertSee('Centro Educativo San Pablo');
        $response->assertDontSee('<title>Iniciar Sesión — ZuraEdu</title>', false);
    }

    public function test_login_usa_el_nombre_de_configinstitucional_si_esta_configurado(): void
    {
        $tenant = $this->crearTenant('Centro Con Nombre Legal Distinto');
        app()->instance('tenant', $tenant);
        ConfigInstitucional::set('nombre_institucion', 'Nombre Público del Homepage');
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/login'));

        $response->assertOk();
        $response->assertSee('Nombre Público del Homepage');
    }

    public function test_login_respeta_system_name_si_fue_configurado_explicitamente(): void
    {
        $tenant = $this->crearTenant('Centro Con System Name');
        app()->instance('tenant', $tenant);
        Setting::set('system_name', 'Marca Personalizada Explícita');
        app()->forgetInstance('tenant');

        $response = $this->get($this->url($tenant, '/login'));

        $response->assertOk();
        $response->assertSee('Marca Personalizada Explícita');
    }

    public function test_register_forgot_password_tambien_muestran_el_nombre_real(): void
    {
        $tenant = $this->crearTenant('Centro Educativo Otras Vistas');

        foreach (['/register', '/forgot-password'] as $path) {
            $response = $this->get($this->url($tenant, $path));
            $response->assertOk();
            $response->assertSee('Centro Educativo Otras Vistas');
        }
    }

    public function test_el_nombre_de_un_tenant_no_aparece_en_el_login_de_otro(): void
    {
        $tenantA = $this->crearTenant('Colegio Login A');
        $tenantB = $this->crearTenant('Colegio Login B');

        $response = $this->get($this->url($tenantB, '/login'));

        $response->assertOk();
        $response->assertDontSee('Colegio Login A');
        $response->assertSee('Colegio Login B');
    }
}
