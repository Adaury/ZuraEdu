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
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel "planilla anual del estudiante" en la planilla académica de admin:
 * al seleccionar una nota se carga (JSON) el detalle de todas las materias.
 * Solo admin/coordinación; la matrícula debe ser del grupo de la asignación
 * abierta (no basta con el ID) y del mismo tenant.
 */
class PlanillaAcademicaPanelTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 50;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{tenant: Tenant, admin: User, sy: SchoolYear, asig: Asignacion, asig2: Asignacion, mat: Matricula, otraMat: Matricula} */
    private function escenario(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Panel Anual',
            'dominio'            => 'colegiopanel' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy    = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $mkGrupo = function () use ($sy, $seccion) {
            self::$nivel++;
            $grado = Grado::create(['nombre' => 'Grado PA' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'segundo_ciclo', 'activo' => true]);
            return Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        };
        $mkAsig = function (Grupo $g, string $nombre) use ($sy) {
            $asignatura = Asignatura::create(['codigo' => 'PA' . random_int(10000, 99999), 'nombre' => $nombre, 'activo' => true]);
            return Asignacion::create([
                'school_year_id' => $sy->id, 'grupo_id' => $g->id, 'asignatura_id' => $asignatura->id,
                'docente_id' => null, 'activo' => true, 'area' => 'academica', 'tipo_evaluacion' => 'componentes',
            ]);
        };
        $mkMat = fn (Grupo $g) => Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $g->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $g1 = $mkGrupo();
        $g2 = $mkGrupo();
        $asig  = $mkAsig($g1, 'Matematica');
        $asig2 = $mkAsig($g1, 'Lengua');
        $mat     = $mkMat($g1);
        $otraMat = $mkMat($g2);   // otro grupo

        // Notas: Matematica P1 80/60 (prom 70), final 70 A; Lengua sin notas.
        CalificacionAcademica::create([
            'matricula_id' => $mat->id, 'asignacion_id' => $asig->id, 'school_year_id' => $sy->id,
            'avg_comp1_p1' => 80, 'avg_comp2_p1' => 60, 'nota_final' => 70, 'situacion' => 'A',
        ]);

        return compact('tenant', 'admin', 'sy', 'asig', 'asig2', 'mat', 'otraMat');
    }

    private function url(Matricula $m, Asignacion $a): string
    {
        return route('admin.calificaciones.planilla-academica.estudiante', $m) . '?asignacion_id=' . $a->id;
    }

    public function test_admin_recibe_detalle_anual_con_todas_las_materias(): void
    {
        $e = $this->escenario();

        $json = $this->actingAs($e['admin'])->getJson($this->url($e['mat'], $e['asig']))
            ->assertOk()
            ->assertJsonCount(2, 'materias')
            ->json();

        $mate = collect($json['materias'])->firstWhere('materia', 'Matematica');
        $this->assertEquals(70.0, $mate['periodos'][1]);   // promedio de 80 y 60
        $this->assertNull($mate['periodos'][2]);
        $this->assertEquals(70.0, $mate['nota_final']);
        $this->assertSame('A', $mate['situacion']);

        $lengua = collect($json['materias'])->firstWhere('materia', 'Lengua');
        $this->assertNull($lengua['nota_final']);          // sin registro: no inventa notas

        $this->assertEquals(70.0, $json['promedio']);
        $this->assertSame(1, $json['aprobadas']);
        $this->assertSame(0, $json['reprobadas']);
    }

    public function test_rechaza_matricula_de_otro_grupo_aunque_el_id_exista(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['admin'])->getJson($this->url($e['otraMat'], $e['asig']))->assertNotFound();
    }

    public function test_no_expone_matriculas_de_otro_tenant(): void
    {
        $e  = $this->escenario();
        $e2 = $this->escenario();   // segundo tenant con su propia matrícula
        app()->forgetInstance('tenant');

        // El admin del tenant 1 pide una matrícula del tenant 2 (mismo asignacion_id del tenant 2).
        $this->actingAs($e['admin'])->getJson($this->url($e2['mat'], $e2['asig']))->assertNotFound();
    }

    public function test_docente_no_puede_usar_el_panel(): void
    {
        $e = $this->escenario();

        app()->instance('tenant', $e['tenant']);
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $e['tenant']->id]);
        $u->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $u->id]);
        $e['asig']->update(['docente_id' => $docente->id]);
        app()->forgetInstance('tenant');

        // EnsureAdminAccess redirige (302) a los docentes fuera de /admin; si llegara
        // al controlador, detalleAnualEstudiante() responde 403. Nunca debe dar 200.
        $res = $this->actingAs($u)->getJson($this->url($e['mat'], $e['asig']));
        $this->assertContains($res->getStatusCode(), [302, 403]);
        $this->assertNull($res->headers->get('Content-Type') === 'application/json' ? $res->json('materias') : null);
    }

    public function test_la_planilla_incluye_el_panel_para_admin(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['admin'])
            ->get(route('admin.calificaciones.planilla-academica', ['asignacion_id' => $e['asig']->id]))
            ->assertOk()
            ->assertSee('id="panel-anual"', false)
            ->assertSee('planilla-academica/estudiante/__MID__', false);
    }
}
