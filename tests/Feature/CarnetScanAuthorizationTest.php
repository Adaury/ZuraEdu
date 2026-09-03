<?php

namespace Tests\Feature;

use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * POST /api/v1/carnet/scan — hallazgo Alto de la auditoría del módulo
 * Carnet+ (ver [[project_auditoria_sigerd_whatsapp_2026_09_02.md]] y la
 * ronda de módulos grandes del 2026-09-03): la ruta no tenía ningún
 * middleware can:, así que cualquier usuario autenticado de la app móvil
 * (estudiante, padre, docente) podía llamarla con cualquier QR válido —
 * incluido el propio, expuesto por miCarnet() — y auto-registrarse
 * "presente" sin estar físicamente en el centro. Se agregó
 * can:ver-servicios, el mismo permiso que ya protege el kiosco
 * equivalente del panel admin (routes/admin/carnet.php).
 */
class CarnetScanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => 'Colegio Test',
            'dominio'            => 'colegiotest' . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);
    }

    private function usuario(Tenant $tenant, string $rol): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);
        return $user;
    }

    private function carnetActivo(Tenant $tenant, User $dueno): CarnetIdentidad
    {
        app()->instance('tenant', $tenant);
        $carnet = CarnetIdentidad::create([
            'tipo' => 'estudiante', 'user_id' => $dueno->id, 'matricula_id' => null,
            'numero_carnet' => 'C-TEST-' . random_int(1000, 9999),
            'qr_token' => 'qr_test_' . random_int(100000, 999999),
            'estado' => 'activo',
        ]);
        app()->forgetInstance('tenant');
        return $carnet;
    }

    public function test_un_estudiante_no_puede_escanear_carnets(): void
    {
        $tenant = $this->tenant();
        $estudiante = $this->usuario($tenant, 'Estudiante');
        $carnet = $this->carnetActivo($tenant, $estudiante);

        Sanctum::actingAs($estudiante);

        $this->postJson('/api/v1/carnet/scan', ['qr_token' => $carnet->qr_token])
            ->assertForbidden();

        $this->assertEquals(0, CarnetAcceso::count(), 'No debe crearse ningún registro de acceso — el request nunca debió llegar al controlador.');
    }

    public function test_un_docente_no_puede_escanear_carnets(): void
    {
        $tenant = $this->tenant();
        $docente = $this->usuario($tenant, 'Docente');
        $estudiante = $this->usuario($tenant, 'Estudiante');
        $carnet = $this->carnetActivo($tenant, $estudiante);

        Sanctum::actingAs($docente);

        $this->postJson('/api/v1/carnet/scan', ['qr_token' => $carnet->qr_token])
            ->assertForbidden();

        $this->assertEquals(0, CarnetAcceso::count());
    }

    public function test_recepcion_si_puede_escanear_carnets(): void
    {
        $tenant = $this->tenant();
        $recepcion = $this->usuario($tenant, 'Recepción');
        $estudiante = $this->usuario($tenant, 'Estudiante');
        $carnet = $this->carnetActivo($tenant, $estudiante);

        Sanctum::actingAs($recepcion);

        $this->postJson('/api/v1/carnet/scan', ['qr_token' => $carnet->qr_token])
            ->assertOk();

        $this->assertEquals(1, CarnetAcceso::where('carnet_identidad_id', $carnet->id)->count());
    }
}
