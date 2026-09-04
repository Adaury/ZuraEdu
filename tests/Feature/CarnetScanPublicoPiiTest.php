<?php

namespace Tests\Feature;

use App\Models\CarnetIdentidad;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /checkin/scan/{qrToken} — hallazgo Alto de la auditoría completa
 * 2026-09-04 (H2): el QR impreso en el carnet físico es literalmente esta
 * URL, con un token PERMANENTE (nunca expira). Antes, escanearla (o
 * simplemente conocer el token, ej. de una foto del carnet) devolvía
 * nombre completo y grupo del dueño sin ningún login — exposición
 * indefinida de PII. La app móvil real (mobile/app/(docente)/carnet-scan.tsx)
 * nunca llamó este endpoint (usa el autenticado scan() vía carnetApi.scan()),
 * así que reducir la respuesta no rompe ningún cliente real.
 */
class CarnetScanPublicoPiiTest extends TestCase
{
    use RefreshDatabase;

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

    private function carnetActivo(Tenant $tenant): CarnetIdentidad
    {
        $dueno = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id, 'name' => 'Estudiante Secreto']);

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

    public function test_el_endpoint_publico_no_devuelve_nombre_ni_grupo(): void
    {
        $tenant = $this->tenant();
        $carnet = $this->carnetActivo($tenant);

        // Sin actingAs — este endpoint es público a propósito, cualquiera
        // con el token (impreso en el carnet) puede llegar aquí. Sin sesión,
        // ResolveTenant necesita el host para saber de qué tenant es este
        // carnet — usamos el dominio real, igual que otras pruebas de esta
        // sesión que ejercitan flujos sin autenticación.
        $response = $this->getJson("http://{$tenant->dominio}/checkin/scan/{$carnet->qr_token}");

        $response->assertOk();
        $response->assertJson(['valido' => true, 'numero_carnet' => $carnet->numero_carnet, 'tipo' => 'estudiante']);
        $response->assertJsonMissing(['nombre']);
        $response->assertJsonMissing(['grupo']);
        $this->assertStringNotContainsString('Estudiante Secreto', $response->getContent());
    }

    public function test_token_invalido_no_filtra_informacion(): void
    {
        $response = $this->getJson('/checkin/scan/token-que-no-existe');

        $response->assertNotFound();
        $response->assertJson(['valido' => false]);
    }
}
