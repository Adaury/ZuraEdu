<?php

namespace Tests\Feature;

use App\Models\NominaEmpleado;
use App\Models\PagoNomina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NominaController::procesarMes — hallazgo "cero tests en flujos de dinero"
 * de la auditoría del proyecto (ver
 * [[project_auditoria_2026_09_01_system_settings]]). Cubre el cálculo de
 * TSS/ISR/neto (NominaEmpleado::calcularTSS/calcularISR, escala ISR RD
 * simplificada) y que reprocesar el mismo mes no duplique PagoNomina
 * (firstOrCreate por nomina_empleado_id + mes).
 */
class NominaProcesarMesTest extends TestCase
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

    private function crearEmpleado(array $overrides = []): NominaEmpleado
    {
        $user = User::factory()->create(['activo' => true]);

        return NominaEmpleado::create(array_merge([
            'user_id'        => $user->id,
            'cargo'          => 'Docente',
            'salario_base'   => 30000,
            'tss_porcentaje' => 5.00,
            'exento_isr'     => false,
            'tipo_contrato'  => 'fijo',
            'fecha_ingreso'  => now()->subYear()->toDateString(),
            'activo'         => true,
        ], $overrides));
    }

    public function test_calcula_tss_isr_y_neto_correctamente_bajo_el_umbral_exento(): void
    {
        // Anual 30000*12=360000, <= 416220 => ISR exento por escala.
        $emp = $this->crearEmpleado(['salario_base' => 30000, 'tss_porcentaje' => 5.00]);

        $this->actingAs($this->admin())
            ->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01'])
            ->assertRedirect();

        $pago = PagoNomina::where('nomina_empleado_id', $emp->id)->where('mes', '2026-01')->first();
        $this->assertNotNull($pago);
        $this->assertEquals(1500.00, $pago->desc_tss, '30000 * 5% = 1500');
        $this->assertEquals(0.00, $pago->desc_isr, 'Anual 360000 está bajo el primer umbral exento');
        $this->assertEquals(1500.00, $pago->deducciones);
        $this->assertEquals(28500.00, $pago->salario_neto);
        $this->assertFalse((bool) $pago->pagado);
    }

    public function test_calcula_isr_en_el_segundo_tramo_de_la_escala(): void
    {
        // Anual 60000*12=720000, dentro de (624329, 867123] => tramo 20%.
        $emp = $this->crearEmpleado(['salario_base' => 60000, 'tss_porcentaje' => 5.00]);

        $this->actingAs($this->admin())
            ->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01']);

        $pago = PagoNomina::where('nomina_empleado_id', $emp->id)->first();
        // round((31216 + (720000-624329)*0.20)/12, 2) = 4195.85
        $this->assertEquals(4195.85, $pago->desc_isr);
        $this->assertEquals(3000.00, $pago->desc_tss);
        $this->assertEquals(7195.85, $pago->deducciones);
        $this->assertEquals(52804.15, $pago->salario_neto);
    }

    public function test_empleado_exento_de_isr_no_paga_isr_aunque_supere_el_umbral(): void
    {
        $emp = $this->crearEmpleado(['salario_base' => 60000, 'tss_porcentaje' => 5.00, 'exento_isr' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01']);

        $pago = PagoNomina::where('nomina_empleado_id', $emp->id)->first();
        $this->assertEquals(0.00, $pago->desc_isr);
        $this->assertEquals(57000.00, $pago->salario_neto, '60000 - 3000 tss - 0 isr');
    }

    public function test_reprocesar_el_mismo_mes_no_duplica_pagonomina(): void
    {
        $emp = $this->crearEmpleado();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01']);
        $this->actingAs($admin)->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01']);

        $this->assertEquals(
            1,
            PagoNomina::where('nomina_empleado_id', $emp->id)->where('mes', '2026-01')->count(),
            'Reprocesar el mismo mes no debe crear un segundo PagoNomina.'
        );
    }

    public function test_meses_distintos_generan_pagonomina_distintos(): void
    {
        $emp = $this->crearEmpleado();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01']);
        $this->actingAs($admin)->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-02']);

        $this->assertEquals(2, PagoNomina::where('nomina_empleado_id', $emp->id)->count());
    }

    public function test_empleado_inactivo_no_se_procesa(): void
    {
        $activo   = $this->crearEmpleado(['activo' => true]);
        $inactivo = $this->crearEmpleado(['activo' => false]);

        $this->actingAs($this->admin())
            ->post(route('admin.nomina.procesar-mes'), ['mes' => '2026-01']);

        $this->assertNotNull(PagoNomina::where('nomina_empleado_id', $activo->id)->first());
        $this->assertNull(PagoNomina::where('nomina_empleado_id', $inactivo->id)->first());
    }

    public function test_mes_invalido_cae_al_mes_actual(): void
    {
        $emp = $this->crearEmpleado();

        $this->actingAs($this->admin())
            ->post(route('admin.nomina.procesar-mes'), ['mes' => 'no-es-una-fecha']);

        $this->assertNotNull(
            PagoNomina::where('nomina_empleado_id', $emp->id)->where('mes', now()->format('Y-m'))->first()
        );
    }
}
