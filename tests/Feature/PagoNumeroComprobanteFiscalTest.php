<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mejora de producto (reporte de evaluación 2026-09-05): ZuraEdu no emite ni
 * valida NCF/e-CF (eso requiere software homologado por la DGII, Ley 32-23)
 * — solo registra y muestra el número que el centro educativo ya obtuvo por
 * su cuenta, para que aparezca en el recibo de pago.
 */
class PagoNumeroComprobanteFiscalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Administrador');
        return $user;
    }

    private function crearPago(array $overrides = []): Pago
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $grado   = Grado::create(['nombre' => 'Grado N' . random_int(1, 9999), 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return Pago::create(array_merge([
            'matricula_id' => $matricula->id, 'concepto' => 'Mensualidad', 'monto' => 5000,
            'fecha_vencimiento' => '2026-01-31', 'estado' => 'pendiente',
        ], $overrides));
    }

    public function test_administrador_puede_registrar_el_numero_de_comprobante_fiscal(): void
    {
        $pago = $this->crearPago([
            'estado' => 'pagado', 'fecha_pago' => '2026-01-15', 'metodo_pago' => 'efectivo',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.pagos.update', $pago), [
                'concepto'          => $pago->concepto,
                'monto'             => $pago->monto,
                'fecha_vencimiento' => $pago->fecha_vencimiento->format('Y-m-d'),
                'fecha_pago'        => '2026-01-15',
                'estado'            => 'pagado',
                'metodo_pago'       => 'efectivo',
                'numero_comprobante_fiscal' => 'B0200001234',
            ])
            ->assertRedirect();

        $this->assertSame('B0200001234', $pago->fresh()->numero_comprobante_fiscal);
    }

    public function test_el_recibo_pdf_muestra_el_numero_de_comprobante_fiscal_cuando_existe(): void
    {
        $pago = $this->crearPago([
            'estado' => 'pagado', 'fecha_pago' => '2026-01-15', 'metodo_pago' => 'efectivo',
            'numero_comprobante_fiscal' => 'E310000045678',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.pagos.recibo', $pago));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_el_recibo_pdf_no_falla_cuando_no_hay_comprobante_fiscal(): void
    {
        $pago = $this->crearPago([
            'estado' => 'pagado', 'fecha_pago' => '2026-01-15', 'metodo_pago' => 'efectivo',
        ]);

        $this->assertNull($pago->numero_comprobante_fiscal);

        $this->actingAs($this->admin())
            ->get(route('admin.pagos.recibo', $pago))
            ->assertOk();
    }
}
