<?php

namespace Tests\Feature;

use App\Models\AcademicRiskScore;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** riesgo:calcular recorre cada tenant y guarda cada score en su propio centro. */
class CalcularRiesgoAcademicoCommandTest extends TestCase
{
    use RefreshDatabase;

    private function centroConEstudiante(string $nombre): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => 'rc' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-C', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado RC' . random_int(1, 99999), 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $m = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return [$tenant, $m];
    }

    public function test_calcula_cada_tenant_con_su_propio_tenant_id(): void
    {
        [$tA, $mA] = $this->centroConEstudiante('Centro Riesgo Cmd A');
        [$tB, $mB] = $this->centroConEstudiante('Centro Riesgo Cmd B');
        app()->forgetInstance('tenant');

        $this->artisan('riesgo:calcular')->assertSuccessful();

        $filas = AcademicRiskScore::withoutGlobalScopes()->get();
        $this->assertCount(2, $filas);
        $this->assertSame($tA->id, (int) $filas->firstWhere('estudiante_id', $mA->estudiante_id)->tenant_id);
        $this->assertSame($tB->id, (int) $filas->firstWhere('estudiante_id', $mB->estudiante_id)->tenant_id);
    }
}
