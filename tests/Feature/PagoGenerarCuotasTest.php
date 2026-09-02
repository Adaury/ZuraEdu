<?php

namespace Tests\Feature;

use App\Models\Beca;
use App\Models\BecaEstudiante;
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
 * PagoController::generarCuotas — hallazgo "cero tests en flujos de dinero"
 * de la auditoría del proyecto (ver
 * [[project_auditoria_2026_09_01_system_settings]]). Cubre el cálculo de
 * descuento por beca (porcentaje y monto fijo) y que reintentar la
 * generación no duplique cuotas ya creadas.
 */
class PagoGenerarCuotasTest extends TestCase
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

    private function crearMatricula(?SchoolYear $schoolYear = null): array
    {
        $schoolYear ??= SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);

        $nivel   = Grado::max('nivel') + 1;
        $grado   = Grado::create(['nombre' => 'Grado P' . random_int(1, 9999), 'nivel' => $nivel, 'orden' => $nivel, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return compact('schoolYear', 'grupo', 'estudiante', 'matricula');
    }

    public function test_genera_cuota_con_el_monto_pleno_sin_beca(): void
    {
        $e = $this->crearMatricula();

        $this->actingAs($this->admin())->post(route('admin.pagos.generar-cuotas'), [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
        ])->assertRedirect();

        $pago = Pago::where('matricula_id', $e['matricula']->id)->first();
        $this->assertNotNull($pago);
        $this->assertEquals(5000, $pago->monto);
        $this->assertNull($pago->notas);
    }

    public function test_aplica_descuento_de_beca_por_porcentaje(): void
    {
        $e = $this->crearMatricula();

        $beca = Beca::create([
            'nombre' => 'Beca 20%', 'tipo' => 'porcentaje', 'valor' => 20, 'activo' => true,
        ]);
        BecaEstudiante::create([
            'beca_id' => $beca->id, 'matricula_id' => $e['matricula']->id, 'fecha_inicio' => now()->toDateString(), 'activo' => true,
        ]);

        $this->actingAs($this->admin())->post(route('admin.pagos.generar-cuotas'), [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
        ])->assertRedirect();

        $pago = Pago::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(4000, $pago->monto, '5000 - 20% = 4000');
        $this->assertStringContainsString('Beca 20%', $pago->notas);
    }

    public function test_aplica_descuento_de_beca_por_monto_fijo(): void
    {
        $e = $this->crearMatricula();

        $beca = Beca::create([
            'nombre' => 'Beca RD$1500', 'tipo' => 'monto_fijo', 'valor' => 1500, 'activo' => true,
        ]);
        BecaEstudiante::create([
            'beca_id' => $beca->id, 'matricula_id' => $e['matricula']->id, 'fecha_inicio' => now()->toDateString(), 'activo' => true,
        ]);

        $this->actingAs($this->admin())->post(route('admin.pagos.generar-cuotas'), [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
        ])->assertRedirect();

        $pago = Pago::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(3500, $pago->monto, '5000 - 1500 = 3500');
    }

    public function test_descuento_de_monto_fijo_no_deja_el_pago_en_negativo(): void
    {
        $e = $this->crearMatricula();

        $beca = Beca::create([
            'nombre' => 'Beca total', 'tipo' => 'monto_fijo', 'valor' => 9999, 'activo' => true,
        ]);
        BecaEstudiante::create([
            'beca_id' => $beca->id, 'matricula_id' => $e['matricula']->id, 'fecha_inicio' => now()->toDateString(), 'activo' => true,
        ]);

        $this->actingAs($this->admin())->post(route('admin.pagos.generar-cuotas'), [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
        ])->assertRedirect();

        $pago = Pago::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(0, $pago->monto);
    }

    public function test_beca_inactiva_no_se_aplica(): void
    {
        $e = $this->crearMatricula();

        $beca = Beca::create([
            'nombre' => 'Beca vencida', 'tipo' => 'porcentaje', 'valor' => 50, 'activo' => true,
        ]);
        BecaEstudiante::create([
            'beca_id' => $beca->id, 'matricula_id' => $e['matricula']->id, 'fecha_inicio' => now()->toDateString(), 'activo' => false,
        ]);

        $this->actingAs($this->admin())->post(route('admin.pagos.generar-cuotas'), [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
        ])->assertRedirect();

        $pago = Pago::where('matricula_id', $e['matricula']->id)->first();
        $this->assertEquals(5000, $pago->monto);
    }

    public function test_reintentar_no_duplica_la_misma_cuota(): void
    {
        $e = $this->crearMatricula();
        $admin = $this->admin();

        $payload = [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
        ];

        $this->actingAs($admin)->post(route('admin.pagos.generar-cuotas'), $payload);
        $this->actingAs($admin)->post(route('admin.pagos.generar-cuotas'), $payload);

        $this->assertEquals(
            1,
            Pago::where('matricula_id', $e['matricula']->id)
                ->where('concepto', 'Mensualidad Enero')
                ->where('fecha_vencimiento', '2026-01-31')
                ->count(),
            'Reintentar con el mismo concepto+vencimiento no debe crear una segunda cuota.'
        );
    }

    public function test_concepto_distinto_si_genera_una_cuota_nueva(): void
    {
        $e = $this->crearMatricula();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.pagos.generar-cuotas'), [
            'concepto' => 'Mensualidad Enero', 'monto' => 5000, 'fecha_vencimiento' => '2026-01-31',
        ]);
        $this->actingAs($admin)->post(route('admin.pagos.generar-cuotas'), [
            'concepto' => 'Mensualidad Febrero', 'monto' => 5000, 'fecha_vencimiento' => '2026-02-28',
        ]);

        $this->assertEquals(2, Pago::where('matricula_id', $e['matricula']->id)->count());
    }

    public function test_filtra_por_grupo_cuando_se_especifica(): void
    {
        $e1 = $this->crearMatricula();
        // Mismo año escolar (SchoolYear::actual() debe resolver sin ambigüedad),
        // grupo distinto — así el filtro grupo_id es lo único que decide.
        $e2 = $this->crearMatricula($e1['schoolYear']);

        $this->actingAs($this->admin())->post(route('admin.pagos.generar-cuotas'), [
            'concepto'          => 'Mensualidad Enero',
            'monto'             => 5000,
            'fecha_vencimiento' => '2026-01-31',
            'grupo_id'          => $e1['grupo']->id,
        ]);

        $this->assertEquals(1, Pago::where('matricula_id', $e1['matricula']->id)->count());
        $this->assertEquals(0, Pago::where('matricula_id', $e2['matricula']->id)->count());
    }
}
