<?php

namespace Tests\Feature;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Promocion;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Estudiante;
use App\Services\RegistroAcademicoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GAP 02 del roadmap de producto (docs/ROADMAP_PRODUCTO_ZURAEDU_2026_2027.md):
 * RegistroAcademicoService::calcularPromocion() decide si un estudiante
 * repite el año y no tenía NINGÚN test — es la única lógica de negocio de
 * alto riesgo de todo el sistema sin red de seguridad.
 *
 * Estos tests NO modifican el algoritmo — solo fijan su comportamiento
 * real, incluidos los casos límite ya documentados en la auditoría:
 * - Primer ciclo usa la escala cualitativa 1-4 (umbral 2.5), NO la de 100.
 * - pct_asistencia === null se trata como si SÍ cumpliera el mínimo de
 *   asistencia (decisión de diseño existente, no un bug).
 * - "condicionado" en segundo ciclo depende solo de materias_reprobadas,
 *   sin importar si el motivo del no-aprobado fue el promedio o la
 *   asistencia (caso límite #8 — se fija explícitamente el comportamiento
 *   real observado en el código, no lo que "debería" ser).
 *
 * Se usa el parámetro $registroPrecargado del propio método (ya existe en
 * la firma, pensado para evitar recalcular buildRegistro() en bucle) para
 * probar la lógica de decisión de forma aislada, sin tener que reconstruir
 * todo el pipeline de evaluación (CompetenciaEspecifica/IndicadorLogro/
 * EvaluacionRegistro) — ese pipeline es un concern distinto de la decisión
 * de promoción en sí, que es lo que este archivo cubre.
 */
class RegistroAcademicoServicePromocionTest extends TestCase
{
    use RefreshDatabase;

    private function crearMatricula(string $ciclo): array
    {
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $grado   = Grado::create([
            'nombre' => 'Grado P' . random_int(1, 9999), 'nivel' => 1, 'orden' => 1,
            'ciclo' => $ciclo, 'activo' => true,
        ]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create([
            'school_year_id' => $schoolYear->id, 'grado_id' => $grado->id,
            'seccion_id' => $seccion->id, 'activo' => true,
        ]);
        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15',
            'numero_orden' => 1, 'estado' => 'activa',
        ]);

        return [$matricula, $schoolYear];
    }

    /** Construye un $registroPrecargado mínimo para una sola matrícula. */
    private function registro(Matricula $matricula, ?float $promedio, ?float $asistencia, array $materias = []): array
    {
        return ['registro' => [[
            'matricula'        => $matricula,
            'promedio_general' => $promedio,
            'pct_asistencia'   => $asistencia,
            'materias'         => $materias,
        ]]];
    }

    private function materiaReprobada(string $nombre): array
    {
        return ['aprobada' => false, 'asignacion' => (object) ['asignatura' => (object) ['nombre' => $nombre]]];
    }

    private function materiaAprobada(): array
    {
        return ['aprobada' => true];
    }

    // ── Primer ciclo (escala cualitativa 1-4, umbral 2.5) ──────────────────

    public function test_primer_ciclo_promedio_exactamente_2_5_promueve(): void
    {
        [$matricula, $sy] = $this->crearMatricula('primer_ciclo');
        $registro = $this->registro($matricula, 2.5, 90.0);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('promovido', $promo->estado);
    }

    public function test_primer_ciclo_promedio_2_49_no_promueve(): void
    {
        [$matricula, $sy] = $this->crearMatricula('primer_ciclo');
        $registro = $this->registro($matricula, 2.49, 90.0);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('no_promovido', $promo->estado);
    }

    public function test_primer_ciclo_asistencia_null_no_bloquea_la_promocion(): void
    {
        [$matricula, $sy] = $this->crearMatricula('primer_ciclo');
        $registro = $this->registro($matricula, 3.0, null);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('promovido', $promo->estado, 'pct_asistencia null se trata como que sí cumple el mínimo (comportamiento real del código).');
    }

    public function test_primer_ciclo_asistencia_por_debajo_del_minimo_no_promueve(): void
    {
        [$matricula, $sy] = $this->crearMatricula('primer_ciclo');
        $registro = $this->registro($matricula, 3.5, 74.0);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('no_promovido', $promo->estado, 'Promedio suficiente pero asistencia bajo 75% debe bloquear la promoción.');
    }

    // ── Segundo ciclo (escala 0-100, umbral 65) ─────────────────────────────

    public function test_segundo_ciclo_promedio_65_y_asistencia_75_promueve(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, 65.0, 75.0);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('promovido', $promo->estado, 'Ambos límites (65 y 75%) son inclusive.');
    }

    public function test_segundo_ciclo_no_aprueba_con_una_materia_reprobada_queda_condicionado(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, 64.0, 90.0, [
            $this->materiaReprobada('Matemática'),
            $this->materiaAprobada(),
        ]);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('condicionado', $promo->estado);
        $this->assertSame(1, $promo->materias_reprobadas);
    }

    public function test_segundo_ciclo_no_aprueba_con_tres_materias_reprobadas_no_promueve(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, 64.0, 90.0, [
            $this->materiaReprobada('Matemática'),
            $this->materiaReprobada('Español'),
            $this->materiaReprobada('Ciencias'),
        ]);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('no_promovido', $promo->estado, 'Más de 2 reprobadas debe ser no_promovido, no condicionado.');
    }

    public function test_segundo_ciclo_falla_solo_por_asistencia_con_pocas_reprobadas_tambien_queda_condicionado(): void
    {
        // Caso límite documentado en la auditoría: el código decide
        // "condicionado" mirando únicamente materias_reprobadas, sin
        // distinguir si el motivo del no-aprobado fue el promedio o la
        // asistencia. Este test fija ese comportamiento real (no lo que
        // "debería" ser) para que un cambio futuro sea deliberado, no
        // accidental.
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, 90.0, 70.0, [
            $this->materiaReprobada('Matemática'),
        ]);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('condicionado', $promo->estado);
    }

    // ── Casos límite generales ───────────────────────────────────────────

    public function test_promedio_final_null_queda_pendiente(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, null, null);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame('pendiente', $promo->estado);
    }

    public function test_calcular_dos_veces_no_duplica_la_fila_de_promocion(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $service = new RegistroAcademicoService();

        $service->calcularPromocion($matricula, $sy, $this->registro($matricula, 60.0, 90.0));
        $service->calcularPromocion($matricula, $sy, $this->registro($matricula, 70.0, 90.0));

        $this->assertSame(1, Promocion::where('matricula_id', $matricula->id)->where('school_year_id', $sy->id)->count());
        $this->assertSame('promovido', Promocion::where('matricula_id', $matricula->id)->first()->estado, 'El segundo cálculo debe sobreescribir el resultado, no acumular.');
    }

    public function test_materias_reprobadas_detalle_contiene_los_nombres_correctos(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, 40.0, 90.0, [
            $this->materiaReprobada('Matemática'),
            $this->materiaReprobada('Español'),
            $this->materiaReprobada('Ciencias Sociales'),
            $this->materiaAprobada(),
        ]);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame(3, $promo->materias_reprobadas);
        $this->assertEqualsCanonicalizing(
            ['Matemática', 'Español', 'Ciencias Sociales'],
            $promo->materias_reprobadas_detalle
        );
    }

    public function test_sin_materias_reprobadas_el_detalle_queda_vacio(): void
    {
        [$matricula, $sy] = $this->crearMatricula('segundo_ciclo');
        $registro = $this->registro($matricula, 90.0, 90.0, [
            $this->materiaAprobada(),
            $this->materiaAprobada(),
        ]);

        $promo = (new RegistroAcademicoService())->calcularPromocion($matricula, $sy, $registro);

        $this->assertSame(0, $promo->materias_reprobadas);
        $this->assertSame([], $promo->materias_reprobadas_detalle);
    }
}
