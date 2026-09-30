<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Services\SigerdExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * validarNomina() detectaba cédulas duplicadas con in_array() dentro del bucle: O(n²)
 * (4.950 matrículas ≈ 4,7 s) y, al ser comparación laxa, "0123" == "123" marcaba
 * duplicados falsos. Ahora usa un conjunto hash con claves string.
 */
class SigerdValidarNominaTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{sy: SchoolYear, grupo: Grupo} */
    private function contexto(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Sigerd Validar',
            'dominio'            => 'colegiosigerd' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado SV' . random_int(1, 99999), 'nivel' => random_int(1, 200), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        return compact('sy', 'grupo');
    }

    private function matricular(array $c, ?string $cedula, ?string $nacimiento = '2012-05-01'): void
    {
        $est = Estudiante::factory()->create(['cedula' => $cedula, 'fecha_nacimiento' => $nacimiento]);
        Matricula::create([
            'school_year_id' => $c['sy']->id, 'estudiante_id' => $est->id, 'grupo_id' => $c['grupo']->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => Matricula::count() + 1, 'estado' => 'activa',
        ]);
    }

    private function descripciones(array $resultado): array
    {
        return array_column($resultado['errores'], 'descripcion');
    }

    // Nota: estudiantes tiene UNIQUE (tenant_id, cedula), así que una cédula duplicada dentro
    // del mismo tenant no puede existir en la BD; la rama "Cedula duplicada" es defensiva.
    public function test_detecta_sin_cedula_y_sin_fecha_de_nacimiento(): void
    {
        $c = $this->contexto();
        $this->matricular($c, '00100000001');             // correcta
        $this->matricular($c, null);                      // sin cédula
        $this->matricular($c, '00100000002', null);       // sin fecha de nacimiento

        $res = (new SigerdExportService())->validarNomina($c['sy'], $c['grupo']->id);
        $desc = $this->descripciones($res);

        $this->assertFalse($res['ok']);
        $this->assertSame(3, $res['total']);
        $this->assertContains('Sin cedula/RNE', $desc);
        $this->assertContains('Sin fecha de nacimiento', $desc);
        $this->assertCount(2, $desc);
    }

    public function test_cedulas_numericamente_iguales_pero_distintas_no_son_duplicadas(): void
    {
        $c = $this->contexto();
        $this->matricular($c, '0123');
        $this->matricular($c, '123');     // in_array laxo: "0123" == "123" -> falso duplicado

        $res = (new SigerdExportService())->validarNomina($c['sy'], $c['grupo']->id);

        $this->assertTrue($res['ok'], 'Cédulas distintas no deben marcarse como duplicadas: ' . json_encode($res['errores']));
        $this->assertSame(2, $res['total']);
    }
}
