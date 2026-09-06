<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\ConfigInstitucional;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoría de consolidación del "Centro de Administración": el logo del
 * sidebar/portal (Setting.system_logo, subido desde /admin/sistema) y el
 * logo del portal público (ConfigInstitucional.hp_logo_path, subido desde
 * el editor Branding/Institución) eran dos campos independientes sin
 * ninguna relación — subirlo en un lugar no se reflejaba en el otro.
 * Ahora ambos puntos de subida/eliminación sincronizan los dos campos.
 */
class BrandingLogoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Storage::fake('public');
    }

    private function crearAdmin(string $nombreTenant): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombreTenant,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombreTenant)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    public function test_subir_logo_desde_homepage_tambien_actualiza_system_logo(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Sync Homepage');

        $this->actingAs($user)->post(route('admin.homepage.update'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        app()->instance('tenant', $tenant);
        $hpLogo     = ConfigInstitucional::get('hp_logo_path');
        $systemLogo = Setting::get('system_logo');
        app()->forgetInstance('tenant');

        $this->assertNotEmpty($hpLogo);
        $this->assertSame($hpLogo, $systemLogo, 'system_logo debe quedar igual a hp_logo_path tras subir desde Homepage.');
    }

    public function test_subir_logo_desde_sistema_tambien_actualiza_hp_logo_path(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Sync Sistema');

        $this->actingAs($user)->post(route('admin.sistema.logo'), [
            'logo' => UploadedFile::fake()->image('logo.jpg'),
        ]);

        app()->instance('tenant', $tenant);
        $hpLogo     = ConfigInstitucional::get('hp_logo_path');
        $systemLogo = Setting::get('system_logo');
        app()->forgetInstance('tenant');

        $this->assertNotEmpty($systemLogo);
        $this->assertSame($systemLogo, $hpLogo, 'hp_logo_path debe quedar igual a system_logo tras subir desde Sistema.');
    }

    public function test_eliminar_logo_desde_sistema_tambien_limpia_hp_logo_path_si_coincidian(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Sync Delete');

        $this->actingAs($user)->post(route('admin.sistema.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $this->actingAs($user)->post(route('admin.sistema.logo.delete'));

        app()->instance('tenant', $tenant);
        $hpLogo     = ConfigInstitucional::get('hp_logo_path');
        $systemLogo = Setting::get('system_logo');
        app()->forgetInstance('tenant');

        $this->assertEmpty($systemLogo);
        $this->assertEmpty($hpLogo, 'hp_logo_path no debe quedar apuntando a un archivo ya eliminado.');
    }

    public function test_sincronizar_el_logo_de_un_tenant_no_afecta_a_otro(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Sync A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Sync B');

        $this->actingAs($userA)->post(route('admin.homepage.update'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        app()->instance('tenant', $tenantB);
        $this->assertEmpty(Setting::get('system_logo'));
        $this->assertEmpty(ConfigInstitucional::get('hp_logo_path'));
        app()->forgetInstance('tenant');
    }
}
