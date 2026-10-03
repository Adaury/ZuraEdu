<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Libro de calificaciones del portal docente, revisado en un navegador real:
 * - guardeCeldaAcad y guardarCalificaciones aceptaban CUALQUIER id de matrícula que mandara el navegador (solo se comprobaba que existiera),
 *   así que un docente podía escribir notas sobre alumnos que no son de su grupo;
 * - el PDF del boletín del docente fallaba siempre («Undefined variable $periodo»): pasaba 8 de las ~20 variables que pide la plantilla;
 * - el PDF dibujaba «?» donde la plantilla usaba símbolos que la fuente del encabezado no tiene (◆ ▶ ✎ ↑ ↓ ≥).
 */
class CalificacionesDocentePantallasTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;
    private SchoolYear $sy;
    private Docente $docente;
    private Asignacion $asignacion;
    private Matricula $propia;
    private Matricula $ajena;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->sy = SchoolYear::create(['nombre' => '2026-Cal', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        foreach ([1, 2, 3, 4] as $n) {
            Periodo::create(['school_year_id' => $this->sy->id, 'numero' => $n, 'nombre' => "Período $n", 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => $n === 1, 'cerrado' => false]);
        }
        $miGrupo = $this->grupo('Grado Cal A');
        $otroGrupo = $this->grupo('Grado Cal B');

        $this->docente = Docente::factory()->create();
        $this->docente->user->assignRole('Docente');
        $this->docente->user->update(['activo' => true]);

        $this->asignacion = Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $miGrupo->id, 'asignatura_id' => Asignatura::create(['codigo' => 'CAL1', 'nombre' => 'Lengua', 'area' => 'academica', 'activo' => true])->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);

        $this->propia = $this->matricular($miGrupo);
        $this->ajena = $this->matricular($otroGrupo);
    }

    private function grupo(string $nombre): Grupo
    {
        $grado = Grado::create(['nombre' => $nombre, 'nivel' => 170 + (++self::$nivel), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);

        return Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);
    }

    private function matricular(Grupo $g): Matricula
    {
        return Matricula::create(['school_year_id' => $this->sy->id, 'estudiante_id' => Estudiante::factory()->create()->id, 'grupo_id' => $g->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa']);
    }

    public function test_la_celda_guarda_la_nota_de_un_alumno_del_grupo(): void
    {
        $this->actingAs($this->docente->user)
            ->patchJson(route('portal.docente.calificaciones.acad.celda', $this->asignacion), ['matricula_id' => $this->propia->id, 'campo' => 'comp1_p1', 'valor' => 80])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertEquals(80, CalificacionAcademica::where('matricula_id', $this->propia->id)->where('asignacion_id', $this->asignacion->id)->value('comp1_p1'));
    }

    public function test_la_celda_rechaza_una_matricula_de_otro_grupo_y_no_crea_nada(): void
    {
        $this->actingAs($this->docente->user)
            ->patchJson(route('portal.docente.calificaciones.acad.celda', $this->asignacion), ['matricula_id' => $this->ajena->id, 'campo' => 'comp1_p1', 'valor' => 99])
            ->assertNotFound();

        $this->assertSame(0, CalificacionAcademica::where('matricula_id', $this->ajena->id)->count(), 'no se escribió una nota sobre un alumno que no es del grupo');
    }

    public function test_el_guardado_masivo_ignora_las_matriculas_ajenas_al_grupo(): void
    {
        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.calificaciones.guardar', $this->asignacion), [
                'notas' => [
                    $this->propia->id => ['p1' => 70, 'p2' => 71, 'p3' => 72, 'p4' => 73],
                    $this->ajena->id => ['p1' => 99, 'p2' => 99, 'p3' => 99, 'p4' => 99],
                ],
            ]);

        $this->assertGreaterThan(0, CalificacionAcademica::where('matricula_id', $this->propia->id)->count(), 'el alumno del grupo sí se guarda');
        $this->assertSame(0, CalificacionAcademica::where('matricula_id', $this->ajena->id)->count(), 'el alumno de otro grupo se ignora');
    }

    public function test_el_pdf_del_boletin_del_docente_se_genera(): void
    {
        $r = $this->actingAs($this->docente->user)->get(route('portal.docente.boletin.pdf', [$this->asignacion, $this->propia]));

        $r->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $r->getContent());
    }

    public function test_el_pdf_del_boletin_acepta_otro_periodo_pero_no_uno_de_otro_ano(): void
    {
        $otroAnio = SchoolYear::create(['nombre' => '2030-Otro', 'fecha_inicio' => '2029-08-01', 'fecha_fin' => '2030-06-30', 'activo' => false]);
        $foraneo = Periodo::create(['school_year_id' => $otroAnio->id, 'numero' => 1, 'nombre' => 'Ajeno', 'fecha_inicio' => '2029-08-01', 'fecha_fin' => '2030-06-30', 'activo' => false, 'cerrado' => false]);
        $segundo = Periodo::where('school_year_id', $this->sy->id)->where('numero', 2)->firstOrFail();

        $this->actingAs($this->docente->user)->get(route('portal.docente.boletin.pdf', [$this->asignacion, $this->propia]) . '?periodo=' . $segundo->id)
            ->assertOk()->assertHeader('content-disposition', 'attachment; filename=boletin_' . \Illuminate\Support\Str::slug($this->propia->estudiante->apellidos) . '_2.pdf');
        // El período de otro año escolar no se usa: cae al activo (número 1)
        $this->actingAs($this->docente->user)->get(route('portal.docente.boletin.pdf', [$this->asignacion, $this->propia]) . '?periodo=' . $foraneo->id)
            ->assertOk()->assertHeader('content-disposition', 'attachment; filename=boletin_' . \Illuminate\Support\Str::slug($this->propia->estudiante->apellidos) . '_1.pdf');
    }

    public function test_un_docente_ajeno_no_descarga_el_boletin_de_otro(): void
    {
        $otro = Docente::factory()->create();
        $otro->user->assignRole('Docente');
        $otro->user->update(['activo' => true]);

        $this->actingAs($otro->user)->get(route('portal.docente.boletin.pdf', [$this->asignacion, $this->propia]))->assertForbidden();
    }

    public function test_la_plantilla_del_boletin_no_usa_simbolos_que_el_pdf_no_dibuja(): void
    {
        $t = file_get_contents(resource_path('views/admin/boletines/pdf.blade.php'));

        foreach (['&#9670;', '&#9654;', '&#9998;', "\u{2191}", "\u{2193}", "\u{2265}"] as $simbolo) {
            $this->assertStringNotContainsString($simbolo, $t, 'la fuente del encabezado no tiene este símbolo y el PDF mostraba «?»');
        }
    }

    public function test_la_pantalla_restaura_la_celda_y_explica_el_rechazo(): void
    {
        $t = file_get_contents(resource_path('views/portal/docente/calificaciones.blade.php'));

        $this->assertStringContainsString('function revertirCelda', $t);
        $this->assertStringContainsString('res.status === 403 || res.status === 422', $t, 'los rechazos del servidor se muestran con su motivo');
        $this->assertStringContainsString('clearTimeout(_statusTimer)', $t, 'un «Guardado» anterior ya no borra un mensaje de error posterior');
    }
}
