<?php

namespace Tests\Unit;

use App\Services\PromedioEstudianteService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Regla única de negocio para el promedio general de un estudiante:
 * académica tiene prioridad absoluta sobre técnica, nunca se mezclan.
 * Ver [[project_auditoria_2026_09_01_system_settings]] — hallazgo de
 * "el promedio se calcula de 4 formas distintas", consolidado aquí.
 */
class PromedioEstudianteServiceTest extends TestCase
{
    private PromedioEstudianteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromedioEstudianteService();
    }

    private function notas(array $valores): Collection
    {
        return collect($valores)->map(fn ($v) => (object) ['nota_final' => $v]);
    }

    public function test_usa_solo_academica_cuando_hay_ambas(): void
    {
        $promedio = $this->service->calcular(
            $this->notas([90, 90]),
            $this->notas([40]),
        );

        $this->assertSame(90.0, $promedio, 'Debe usar solo la nota académica (90), no mezclarla con la técnica (40).');
    }

    public function test_cae_a_tecnica_si_no_hay_academica(): void
    {
        $promedio = $this->service->calcular(
            collect(),
            $this->notas([70, 80]),
        );

        $this->assertSame(75.0, $promedio);
    }

    public function test_cae_a_tecnica_si_academica_solo_tiene_nulls(): void
    {
        // Regresión del bug sutil de BoletinController: una fila académica
        // con nota_final NULL no debe bloquear el fallback a técnica.
        $promedio = $this->service->calcular(
            $this->notas([null, null]),
            $this->notas([80]),
        );

        $this->assertSame(80.0, $promedio);
    }

    public function test_null_si_no_hay_ninguna_nota(): void
    {
        $this->assertNull($this->service->calcular(collect(), collect()));
    }

    public function test_ignora_notas_null_dentro_de_la_coleccion_academica(): void
    {
        $promedio = $this->service->calcular(
            $this->notas([90, null, 80]),
            collect(),
        );

        $this->assertSame(85.0, $promedio);
    }

    public function test_redondea_a_2_decimales(): void
    {
        $promedio = $this->service->calcular(
            $this->notas([80, 81, 82]),
            collect(),
        );

        $this->assertSame(81.0, $promedio);
    }

    public function test_calcular_desde_bulk_usa_el_matricula_id_correcto(): void
    {
        $bulkAc = collect([
            10 => $this->notas([90]),
            20 => $this->notas([50]),
        ]);
        $bulkTec = collect();

        $this->assertSame(90.0, $this->service->calcularDesdeBulk(10, $bulkAc, $bulkTec));
        $this->assertSame(50.0, $this->service->calcularDesdeBulk(20, $bulkAc, $bulkTec));
        $this->assertNull($this->service->calcularDesdeBulk(30, $bulkAc, $bulkTec));
    }
}
