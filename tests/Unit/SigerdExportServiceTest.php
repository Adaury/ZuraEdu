<?php

namespace Tests\Unit;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Services\SigerdExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SigerdExportService — 2 hallazgos de la auditoría del módulo SIGERD (ver
 * [[project_auditoria_2026_09_01_system_settings]]):
 * 1. Inyección de fórmulas: campos de texto libre se volcaban directo a
 *    celdas de Excel/CSV sin sanitizar un "=/+/-/@" inicial — el archivo
 *    se sube al portal oficial MINERD.
 * 2. Bug de datos real en el CSV de calificaciones: las columnas P1-P4
 *    mostraban la misma nota_final repetida 5 veces en vez de los
 *    promedios de componentes por período (Excel y PDF sí estaban bien).
 */
class SigerdExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function reflect(string $method): \ReflectionMethod
    {
        $ref = new \ReflectionMethod(SigerdExportService::class, $method);
        $ref->setAccessible(true);
        return $ref;
    }

    // ── Inyección de fórmulas ────────────────────────────────────────────

    public function test_detecta_valores_que_empiezan_con_caracteres_de_formula(): void
    {
        $service = new SigerdExportService();
        $esPeligrosa = $this->reflect('esFormulaPeligrosa');

        foreach (['=1+1', '+1', '-1', '@SUM(A1)', "\tmal", "\rmal"] as $valor) {
            $this->assertTrue($esPeligrosa->invoke($service, $valor), "Debía marcarse peligroso: {$valor}");
        }
    }

    public function test_no_marca_texto_ni_numeros_normales_como_peligrosos(): void
    {
        $service = new SigerdExportService();
        $esPeligrosa = $this->reflect('esFormulaPeligrosa');

        foreach (['Pérez', 'García-Nuñez', 80.5, 0, '', null, 'Santo Domingo'] as $valor) {
            $this->assertFalse($esPeligrosa->invoke($service, $valor), 'No debía marcarse peligroso: ' . var_export($valor, true));
        }
    }

    public function test_sanitizar_celda_csv_antepone_comilla_solo_a_valores_peligrosos(): void
    {
        $service = new SigerdExportService();
        $sanitizar = $this->reflect('sanitizarCeldaCsv');

        $this->assertEquals("'=1+1", $sanitizar->invoke($service, '=1+1'));
        $this->assertEquals('Pérez', $sanitizar->invoke($service, 'Pérez'));
        $this->assertEquals(80.5, $sanitizar->invoke($service, 80.5));
    }

    public function test_export_nomina_csv_neutraliza_apellido_con_formula(): void
    {
        $sy = SchoolYear::create(['nombre' => '2026-Test', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado X', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create(['apellidos' => '=HYPERLINK("http://evil.com","x")']);
        Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $response = (new SigerdExportService())->exportarNomina($sy, null, 'csv');
        $csv = $response->getContent();

        $this->assertStringNotContainsString(
            ',=HYPERLINK',
            $csv,
            'La fórmula no debe quedar como primer carácter de la celda en el CSV.'
        );
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'Debe quedar neutralizada con comilla, no eliminada.');
    }

    // ── Bug de datos: CSV de calificaciones con P1-P4 correctos ──────────

    public function test_export_calificaciones_csv_usa_promedios_por_periodo_no_nota_final_repetida(): void
    {
        $sy = SchoolYear::create(['nombre' => '2026-Test2', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Y', 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create();
        $matricula = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        $asignatura = Asignatura::create(['codigo' => 'SIG1', 'nombre' => 'Lengua Española', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'activo' => true, 'area' => 'academica',
        ]);

        CalificacionAcademica::create([
            'matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $sy->id,
            'avg_comp1_p1' => 80, 'avg_comp2_p1' => 80, 'avg_comp3_p1' => 80, 'avg_comp4_p1' => 80,
            'avg_comp1_p2' => 70, 'avg_comp2_p2' => 70, 'avg_comp3_p2' => 70, 'avg_comp4_p2' => 70,
            'avg_comp1_p3' => 60, 'avg_comp2_p3' => 60, 'avg_comp3_p3' => 60, 'avg_comp4_p3' => 60,
            'avg_comp1_p4' => 90, 'avg_comp2_p4' => 90, 'avg_comp3_p4' => 90, 'avg_comp4_p4' => 90,
            'nota_final' => 75, 'situacion' => 'A',
        ]);

        $response = (new SigerdExportService())->exportarCalificaciones($sy, $grupo->id, null, 'csv');
        $csv = $response->getContent();
        $lineas = array_values(array_filter(explode("\n", $csv)));
        $fila = str_getcsv(trim($lineas[1]));

        // Header: No., RNE, Apellidos, Nombres, Asignatura, P1, P2, P3, P4, N.F., Situacion
        $this->assertEquals('80', $fila[5], 'P1 debe ser el promedio de componentes del período 1');
        $this->assertEquals('70', $fila[6], 'P2 debe ser el promedio de componentes del período 2');
        $this->assertEquals('60', $fila[7], 'P3 debe ser el promedio de componentes del período 3');
        $this->assertEquals('90', $fila[8], 'P4 debe ser el promedio de componentes del período 4');
        $this->assertEquals('75', $fila[9], 'N.F. debe ser nota_final');
    }
}
