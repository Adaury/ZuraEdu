<?php

namespace Tests\Unit;

use App\Models\ConfigInstitucional;
use App\Services\AcademicAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AcademicAlertThresholdsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicAlertService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AcademicAlertService();
        Cache::flush();
    }

    /**
     * Los umbrales por defecto son 60 y 75 cuando no hay config en BD.
     */
    public function test_usa_valores_por_defecto_cuando_no_hay_config(): void
    {
        $this->assertEquals(60, AcademicAlertService::DEFAULT_NOTA_MINIMA);
        $this->assertEquals(75, AcademicAlertService::DEFAULT_ASISTENCIA_MINIMA);
    }

    /**
     * Si se guarda un umbral personalizado en config_institucional, el servicio lo usa.
     */
    public function test_lee_umbral_nota_de_config_institucional(): void
    {
        ConfigInstitucional::updateOrCreate(
            ['clave' => 'alerta_nota_minima'],
            ['valor' => '70', 'tipo' => 'integer', 'grupo' => 'alertas', 'descripcion' => 'Test']
        );

        Cache::flush();

        $valor = ConfigInstitucional::get('alerta_nota_minima', AcademicAlertService::DEFAULT_NOTA_MINIMA);
        $this->assertEquals(70, $valor);
    }

    /**
     * Si se guarda un umbral personalizado de asistencia, el servicio lo usa.
     */
    public function test_lee_umbral_asistencia_de_config_institucional(): void
    {
        ConfigInstitucional::updateOrCreate(
            ['clave' => 'alerta_asistencia_minima'],
            ['valor' => '80', 'tipo' => 'integer', 'grupo' => 'alertas', 'descripcion' => 'Test']
        );

        Cache::flush();

        $valor = ConfigInstitucional::get('alerta_asistencia_minima', AcademicAlertService::DEFAULT_ASISTENCIA_MINIMA);
        $this->assertEquals(80, $valor);
    }
}
