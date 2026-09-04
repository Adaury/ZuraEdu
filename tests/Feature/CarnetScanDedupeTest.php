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
 * Hallazgo Medio de la auditoría completa 2026-09-04: ni
 * CarnetCheckinController::scan() (kiosco/admin) ni CarnetApiController::scan()
 * (app móvil) verificaban si ya existía un CarnetAcceso reciente del mismo
 * carnet+tipo_evento antes de crear uno nuevo — dos escaneos seguidos del
 * mismo QR (doble-tap, glitch del kiosco) generaban dos registros de acceso
 * y despachaban dos notificaciones WhatsApp duplicadas al padre.
 */
class CarnetScanDedupeTest extends TestCase
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

    public function test_escanear_dos_veces_seguidas_en_el_kiosco_no_duplica_el_registro(): void
    {
        $tenant = $this->tenant();
        $recepcion = $this->usuario($tenant, 'Recepción');
        $estudiante = $this->usuario($tenant, 'Estudiante');
        $carnet = $this->carnetActivo($tenant, $estudiante);

        $this->actingAs($recepcion)
            ->postJson(route('admin.carnet.scan'), ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk()
            ->assertJsonMissing(['duplicado' => true]);

        $this->actingAs($recepcion)
            ->postJson(route('admin.carnet.scan'), ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk()
            ->assertJson(['duplicado' => true]);

        $this->assertEquals(1, CarnetAcceso::where('carnet_identidad_id', $carnet->id)->count());
    }

    public function test_escanear_dos_veces_seguidas_por_la_api_movil_no_duplica_el_registro(): void
    {
        $tenant = $this->tenant();
        $recepcion = $this->usuario($tenant, 'Recepción');
        $estudiante = $this->usuario($tenant, 'Estudiante');
        $carnet = $this->carnetActivo($tenant, $estudiante);

        Sanctum::actingAs($recepcion);

        $this->postJson('/api/v1/carnet/scan', ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk()
            ->assertJsonMissing(['duplicado' => true]);

        $this->postJson('/api/v1/carnet/scan', ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk()
            ->assertJson(['duplicado' => true]);

        $this->assertEquals(1, CarnetAcceso::where('carnet_identidad_id', $carnet->id)->count());
    }

    public function test_entrada_y_salida_seguidas_si_generan_dos_registros(): void
    {
        // Tipos de evento distintos NO deben ser deduplicados entre sí.
        $tenant = $this->tenant();
        $recepcion = $this->usuario($tenant, 'Recepción');
        $estudiante = $this->usuario($tenant, 'Estudiante');
        $carnet = $this->carnetActivo($tenant, $estudiante);

        $this->actingAs($recepcion)
            ->postJson(route('admin.carnet.scan'), ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk();

        $this->actingAs($recepcion)
            ->postJson(route('admin.carnet.scan'), ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'salida'])
            ->assertOk()
            ->assertJsonMissing(['duplicado' => true]);

        $this->assertEquals(2, CarnetAcceso::where('carnet_identidad_id', $carnet->id)->count());
    }
}
