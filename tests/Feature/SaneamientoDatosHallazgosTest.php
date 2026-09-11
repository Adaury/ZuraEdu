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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Auditoría Don Bosco (Sección 6): las 3 nuevas verificaciones agregadas a
 * `sge:saneamiento` para detectar arrastre histórico de los 2 hallazgos
 * reales encontrados (estudiantes retirados que continúan activos, y años
 * escolares vencidos que siguen activos generando pagos fuera de rango).
 * Estas verificaciones son solo de reporte -- no corrigen nada, solo
 * detectan (mismo patrón que el resto del comando, que solo --fix elimina
 * pagos huérfanos).
 */
class SaneamientoDatosHallazgosTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => 'Colegio Saneamiento',
            'dominio'            => 'colegiosaneamiento' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    private function crearMatricula(Tenant $tenant, SchoolYear $schoolYear, string $estadoMatricula, string $estadoEstudiante): Matricula
    {
        app()->instance('tenant', $tenant);

        $nivel   = (Grado::max('nivel') ?? 0) + 1;
        $grado   = Grado::create(['nombre' => 'Grado S' . random_int(1, 99999), 'nivel' => $nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create(['estado' => $estadoEstudiante]);

        $matricula = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => $schoolYear->fecha_inicio, 'numero_orden' => 1, 'estado' => $estadoMatricula,
        ]);

        app()->forgetInstance('tenant');

        return $matricula;
    }

    public function test_detecta_estudiante_activo_sin_matricula_activa(): void
    {
        $tenant = $this->crearTenant();
        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2025-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        app()->forgetInstance('tenant');

        // El bug real: matrícula retirada, pero estudiante sigue 'activo'.
        $matricula = $this->crearMatricula($tenant, $sy, 'retirada', 'activo');

        Artisan::call('sge:saneamiento', ['--tenant' => $tenant->id]);
        $salida = Artisan::output();

        $this->assertStringContainsString('sin ninguna matrícula activa', $salida);
        $this->assertStringContainsString("estudiante_id={$matricula->estudiante_id}", $salida);
    }

    public function test_no_reporta_estudiante_activo_con_matricula_activa(): void
    {
        $tenant = $this->crearTenant();
        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2025-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        app()->forgetInstance('tenant');

        $this->crearMatricula($tenant, $sy, 'activa', 'activo');

        Artisan::call('sge:saneamiento', ['--tenant' => $tenant->id]);

        $this->assertStringNotContainsString('sin ninguna matrícula activa', Artisan::output());
    }

    public function test_detecta_anio_escolar_vencido_que_sigue_activo(): void
    {
        $tenant = $this->crearTenant();
        app()->instance('tenant', $tenant);
        // Terminó hace más de un año pero sigue activo=true -- el escenario real encontrado.
        $sy = SchoolYear::create(['nombre' => '2024-2025', 'fecha_inicio' => '2024-09-01', 'fecha_fin' => '2025-06-27', 'activo' => true]);
        app()->forgetInstance('tenant');

        Artisan::call('sge:saneamiento', ['--tenant' => $tenant->id]);
        $salida = Artisan::output();

        $this->assertStringContainsString("Año escolar '2024-2025' sigue activo", $salida);
    }

    public function test_no_reporta_anio_escolar_vigente(): void
    {
        $tenant = $this->crearTenant();
        app()->instance('tenant', $tenant);
        SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => now()->subMonth(), 'fecha_fin' => now()->addMonths(9), 'activo' => true]);
        app()->forgetInstance('tenant');

        Artisan::call('sge:saneamiento', ['--tenant' => $tenant->id]);

        $this->assertStringNotContainsString('sigue activo pero terminó', Artisan::output());
    }

    public function test_detecta_pago_con_vencimiento_fuera_del_anio_escolar_de_su_matricula(): void
    {
        $tenant = $this->crearTenant();
        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2024-2025', 'fecha_inicio' => '2024-09-01', 'fecha_fin' => '2025-06-27', 'activo' => true]);
        app()->forgetInstance('tenant');

        $matricula = $this->crearMatricula($tenant, $sy, 'activa', 'activo');

        app()->instance('tenant', $tenant);
        // Vencimiento muy posterior al fin del año escolar -- el escenario real encontrado.
        $pago = Pago::create([
            'matricula_id' => $matricula->id, 'concepto' => 'Cuota fuera de rango', 'monto' => 1000,
            'fecha_vencimiento' => '2026-12-05', 'estado' => 'pendiente',
        ]);
        app()->forgetInstance('tenant');

        Artisan::call('sge:saneamiento', ['--tenant' => $tenant->id]);
        $salida = Artisan::output();

        $this->assertStringContainsString('fuera del año escolar de su matrícula', $salida);
        $this->assertStringContainsString("pago_id={$pago->id}", $salida);
    }

    public function test_no_reporta_pago_dentro_del_rango_del_anio_escolar(): void
    {
        $tenant = $this->crearTenant();
        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2025-2026', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        app()->forgetInstance('tenant');

        $matricula = $this->crearMatricula($tenant, $sy, 'activa', 'activo');

        app()->instance('tenant', $tenant);
        Pago::create([
            'matricula_id' => $matricula->id, 'concepto' => 'Cuota normal', 'monto' => 1000,
            'fecha_vencimiento' => '2026-01-31', 'estado' => 'pendiente',
        ]);
        app()->forgetInstance('tenant');

        Artisan::call('sge:saneamiento', ['--tenant' => $tenant->id]);

        $this->assertStringNotContainsString('fuera del año escolar', Artisan::output());
    }

    public function test_aislamiento_cross_tenant(): void
    {
        $tenantA = $this->crearTenant();
        $tenantB = $this->crearTenant();

        app()->instance('tenant', $tenantA);
        $syA = SchoolYear::create(['nombre' => '2024-2025', 'fecha_inicio' => '2024-09-01', 'fecha_fin' => '2025-06-27', 'activo' => true]);
        app()->forgetInstance('tenant');
        $this->crearMatricula($tenantA, $syA, 'retirada', 'activo');

        // Solo se pide el reporte del tenant B -- no debe mencionar nada del tenant A.
        Artisan::call('sge:saneamiento', ['--tenant' => $tenantB->id]);
        $salida = Artisan::output();

        $this->assertStringNotContainsString('sin ninguna matrícula activa', $salida);
        $this->assertStringNotContainsString('sigue activo pero terminó', $salida);
    }
}
