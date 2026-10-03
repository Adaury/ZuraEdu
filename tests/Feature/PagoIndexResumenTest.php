<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El resumen de admin/pagos hacía 4 consultas con el mismo exists() sobre matrículas
 * (~400 ms c/u con 49.500 pagos); ahora es una sola agrupada por estado. Fija que
 * los totales no cambiaron, que se acota al año escolar actual y que se aísla por tenant.
 */
class PagoIndexResumenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function tenantConAdmin(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Pagos Resumen',
            'dominio'            => 'colegiopagosres' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);
        // Las rutas de pagos exigen tenant.feature:pagos (plan + módulo encendido).
        $tenant->enableFeature('pagos');
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        return [$tenant, $admin];
    }

    private function matricula(SchoolYear $sy): Matricula
    {
        $grado   = Grado::create(['nombre' => 'Grado PR' . random_int(1, 99999), 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        return Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
    }

    private function pago(Matricula $m, string $estado, float $monto): void
    {
        Pago::create([
            'matricula_id' => $m->id, 'concepto' => 'Mensualidad', 'monto' => $monto,
            'fecha_vencimiento' => now()->addYear()->toDateString(),   // lejos: sincronizarVencidos() no lo toca
            'estado' => $estado,
            'fecha_pago' => $estado === 'pagado' ? now()->toDateString() : null,
        ]);
    }

    public function test_resumen_suma_por_estado_solo_del_anio_actual(): void
    {
        [$tenant, $admin] = $this->tenantConAdmin();
        $syActual = SchoolYear::create(['nombre' => '2090-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $syViejo  = SchoolYear::create(['nombre' => '2089-A', 'fecha_inicio' => '2024-08-01', 'fecha_fin' => '2025-06-30', 'activo' => false]);

        $mAct = $this->matricula($syActual);
        $this->pago($mAct, 'pendiente', 100);
        $this->pago($mAct, 'pendiente', 50);
        $this->pago($mAct, 'pagado', 300);
        $this->pago($mAct, 'cancelado', 999);   // no cuenta en el resumen

        $this->pago($this->matricula($syViejo), 'pagado', 7777);   // otro año: no cuenta

        // Otro tenant: nunca debe sumarse.
        [$t2] = $this->tenantConAdmin();
        $sy2 = SchoolYear::create(['nombre' => '2091-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $this->pago($this->matricula($sy2), 'pendiente', 5555);
        app()->forgetInstance('tenant');

        $resumen = $this->actingAs($admin)->get(route('admin.pagos.index'))->assertOk()->viewData('resumen');

        $this->assertEquals(150.0, $resumen['pendiente']);
        $this->assertEquals(300.0, $resumen['pagado']);
        $this->assertEquals(0.0, $resumen['vencido']);
        $this->assertSame(3, $resumen['total']);
    }

    public function test_resumen_sin_pagos_devuelve_ceros(): void
    {
        [$tenant, $admin] = $this->tenantConAdmin();
        SchoolYear::create(['nombre' => '2092-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        app()->forgetInstance('tenant');

        $resumen = $this->actingAs($admin)->get(route('admin.pagos.index'))->assertOk()->viewData('resumen');

        $this->assertSame(['pendiente' => 0.0, 'pagado' => 0.0, 'vencido' => 0.0, 'total' => 0], $resumen);
    }
}
