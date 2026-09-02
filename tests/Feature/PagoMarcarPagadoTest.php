<?php

namespace Tests\Feature;

use App\Events\PagoConfirmado;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * PagoController::marcarPagado — hallazgo de la auditoría de WhatsApp/pagos
 * (ver [[project_auditoria_2026_09_01_system_settings]]): a diferencia de
 * WebhookStripeController y CardNetController (que sí verifican
 * `$pago->estado === 'pagado'` antes de disparar PagoConfirmado), esta ruta
 * lo hacía sin guardia — un doble clic o un reintento del request duplicaba
 * el evento, y con él, el WhatsApp + notificación al representante.
 */
class PagoMarcarPagadoTest extends TestCase
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

    private function crearPago(): Pago
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $grado   = Grado::create(['nombre' => 'Grado M' . random_int(1, 9999), 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return Pago::create([
            'matricula_id' => $matricula->id, 'concepto' => 'Mensualidad', 'monto' => 5000,
            'fecha_vencimiento' => '2026-01-31', 'estado' => 'pendiente',
        ]);
    }

    public function test_marca_el_pago_y_dispara_pagoconfirmado_una_vez(): void
    {
        Event::fake([PagoConfirmado::class]);
        $pago = $this->crearPago();

        $this->actingAs($this->admin())
            ->patch(route('admin.pagos.pagar', $pago), ['metodo_pago' => 'efectivo'])
            ->assertOk();

        $this->assertEquals('pagado', $pago->fresh()->estado);
        Event::assertDispatchedTimes(PagoConfirmado::class, 1);
    }

    public function test_reintentar_marcar_pagado_no_duplica_pagoconfirmado(): void
    {
        Event::fake([PagoConfirmado::class]);
        $pago = $this->crearPago();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.pagos.pagar', $pago), ['metodo_pago' => 'efectivo']);
        $this->actingAs($admin)->patch(route('admin.pagos.pagar', $pago), ['metodo_pago' => 'efectivo'])
            ->assertOk();

        Event::assertDispatchedTimes(
            PagoConfirmado::class,
            1,
            'Un pago ya marcado como pagado no debe volver a disparar PagoConfirmado (evita WhatsApp/notificación duplicados).'
        );
    }
}
