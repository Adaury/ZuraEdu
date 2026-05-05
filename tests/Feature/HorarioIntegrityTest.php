<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Aula;
use App\Models\FranjaHoraria;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\SchoolYear;
use App\Services\HorarioIntegrityChecker;
use App\Services\HorarioValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de integridad del sistema de horarios.
 *
 * Cubren:
 *   - I1: Sin docente duplicado en mismo slot
 *   - I2: Sin aula duplicada en mismo slot
 *   - I3: Sin grupo duplicado en mismo slot
 *   - I4: Cada asignación cubre sus horas_semana
 *   - V*: Validador previo detecta datos faltantes
 */
class HorarioIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    //  HELPERS
    // =========================================================================

    /**
     * Crea un HorarioDetalle mínimo con los IDs dados (sin FK real para test unitario).
     * Usamos un array en lugar de BD para probar el checker directamente con mocks
     * cuando no hay factories disponibles.
     */
    private function makeDetalleCollection(array $rows): \Illuminate\Support\Collection
    {
        return collect($rows)->map(function (array $r) {
            $detalle                  = new HorarioDetalle();
            $detalle->id              = $r['id']        ?? 1;
            $detalle->horario_id      = $r['horario_id']?? 1;
            $detalle->asignacion_id   = $r['asig_id']   ?? 1;
            $detalle->aula_id         = $r['aula_id']   ?? null;
            $detalle->franja_id       = $r['franja_id'] ?? 1;
            $detalle->dia             = $r['dia']        ?? 'lunes';

            // Relaciones simuladas
            $asig              = new Asignacion();
            $asig->id          = $r['asig_id'] ?? 1;
            $asig->docente_id  = $r['docente_id'] ?? 1;
            $asig->grupo_id    = $r['grupo_id']   ?? 1;
            $asig->asignatura_id = $r['asignatura_id'] ?? 1;
            $asig->horas_semana  = $r['horas_semana']  ?? 2;
            $detalle->setRelation('asignacion', $asig);

            $franja       = new FranjaHoraria();
            $franja->id   = $r['franja_id'] ?? 1;
            $franja->nombre = $r['franja_nombre'] ?? 'Franja 1';
            $detalle->setRelation('franja', $franja);

            if ($r['aula_id'] ?? null) {
                $aula       = new Aula();
                $aula->id   = $r['aula_id'];
                $aula->nombre = $r['aula_nombre'] ?? 'Aula A';
                $detalle->setRelation('aula', $aula);
            }

            return $detalle;
        });
    }

    // =========================================================================
    //  I1 — DOCENTE DUPLICADO
    // =========================================================================

    /** Un horario limpio no debe tener docentes duplicados. */
    public function test_horario_sin_docente_duplicado_pasa_check(): void
    {
        $checker   = new HorarioIntegrityChecker();
        $detalles  = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'lunes',   'franja_id' => 1],
            ['id' => 2, 'asig_id' => 2, 'docente_id' => 10, 'grupo_id' => 2, 'dia' => 'lunes',   'franja_id' => 2], // misma docente, otra franja
            ['id' => 3, 'asig_id' => 3, 'docente_id' => 10, 'grupo_id' => 3, 'dia' => 'martes',  'franja_id' => 1], // misma franja, otro día
        ]);

        // Llamamos al método privado directamente via Reflection
        $ref    = new \ReflectionMethod($checker, 'checkDocenteDuplicado');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertEmpty($result, 'No debe haber violaciones de docente duplicado.');
    }

    /** Detecta cuando el mismo docente está en dos clases simultáneas. */
    public function test_docente_duplicado_es_detectado(): void
    {
        $checker  = new HorarioIntegrityChecker();
        $detalles = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'lunes', 'franja_id' => 1],
            ['id' => 2, 'asig_id' => 2, 'docente_id' => 10, 'grupo_id' => 2, 'dia' => 'lunes', 'franja_id' => 1], // mismo slot!
        ]);

        $ref    = new \ReflectionMethod($checker, 'checkDocenteDuplicado');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertCount(1, $result);
        $this->assertEquals('docente_duplicado', $result[0]['tipo']);
        $this->assertEquals('critico', $result[0]['severidad']);
    }

    // =========================================================================
    //  I2 — AULA DUPLICADA
    // =========================================================================

    public function test_aula_duplicada_es_detectada(): void
    {
        $checker  = new HorarioIntegrityChecker();
        $detalles = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'aula_id' => 5, 'dia' => 'martes', 'franja_id' => 2],
            ['id' => 2, 'asig_id' => 2, 'docente_id' => 20, 'grupo_id' => 2, 'aula_id' => 5, 'dia' => 'martes', 'franja_id' => 2], // misma aula y slot!
        ]);

        $ref    = new \ReflectionMethod($checker, 'checkAulaDuplicada');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertCount(1, $result);
        $this->assertEquals('aula_duplicada', $result[0]['tipo']);
    }

    public function test_aula_libre_no_genera_violacion(): void
    {
        $checker  = new HorarioIntegrityChecker();
        $detalles = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'aula_id' => 5, 'dia' => 'lunes', 'franja_id' => 1],
            ['id' => 2, 'asig_id' => 2, 'docente_id' => 20, 'grupo_id' => 2, 'aula_id' => 6, 'dia' => 'lunes', 'franja_id' => 1], // otra aula
        ]);

        $ref    = new \ReflectionMethod($checker, 'checkAulaDuplicada');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertEmpty($result);
    }

    // =========================================================================
    //  I3 — GRUPO DUPLICADO
    // =========================================================================

    public function test_grupo_duplicado_es_detectado(): void
    {
        $checker  = new HorarioIntegrityChecker();
        $detalles = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'jueves', 'franja_id' => 3],
            ['id' => 2, 'asig_id' => 2, 'docente_id' => 20, 'grupo_id' => 1, 'dia' => 'jueves', 'franja_id' => 3], // mismo grupo y slot!
        ]);

        $ref    = new \ReflectionMethod($checker, 'checkGrupoDuplicado');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertCount(1, $result);
        $this->assertEquals('grupo_duplicado', $result[0]['tipo']);
    }

    // =========================================================================
    //  I4 — HORAS SEMANALES
    // =========================================================================

    public function test_horas_insuficientes_son_detectadas(): void
    {
        $checker  = new HorarioIntegrityChecker();

        // Asignación que requiere 3 horas pero solo tiene 2 detalles
        $detalles = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'lunes',  'franja_id' => 1, 'horas_semana' => 3],
            ['id' => 2, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'martes', 'franja_id' => 1, 'horas_semana' => 3],
            // falta una hora
        ]);

        $ref    = new \ReflectionMethod($checker, 'checkHorasSemanales');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertNotEmpty($result);
        $this->assertEquals('horas_insuficientes', $result[0]['tipo']);
        $this->assertEquals('advertencia', $result[0]['severidad']);
        $this->assertStringContainsString('Faltan 1 slot(s)', $result[0]['mensaje']);
    }

    public function test_horas_completas_no_generan_violacion(): void
    {
        $checker  = new HorarioIntegrityChecker();

        // Asignación con 2 horas requeridas y 2 detalles
        $detalles = $this->makeDetalleCollection([
            ['id' => 1, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'lunes',  'franja_id' => 1, 'horas_semana' => 2],
            ['id' => 2, 'asig_id' => 1, 'docente_id' => 10, 'grupo_id' => 1, 'dia' => 'martes', 'franja_id' => 1, 'horas_semana' => 2],
        ]);

        $ref    = new \ReflectionMethod($checker, 'checkHorasSemanales');
        $ref->setAccessible(true);
        $result = $ref->invoke($checker, $detalles);

        $this->assertEmpty($result);
    }

    // =========================================================================
    //  VALIDADOR PREVIO — casos sin BD (unit)
    // =========================================================================

    /** Sin año escolar activo retorna error. */
    public function test_validador_falla_sin_school_year(): void
    {
        // No hay ningún SchoolYear en BD (RefreshDatabase lo limpió)
        $validacion = (new HorarioValidatorService)->validar(null, []);

        $this->assertFalse($validacion['valido']);
        $this->assertNotEmpty($validacion['errores']);
        $this->assertStringContainsString('año escolar', strtolower($validacion['errores'][0]));
    }
}
