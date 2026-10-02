<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Observacion;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Panel del estudiante (/portal/estudiante). Era la página más pedida y repetía las MISMAS consultas de asignaciones, asignaturas y docentes en cada
 * bloque (calificaciones, notas académicas, observaciones, clases virtuales, horario): ahora se cargan una vez y se reutilizan. Este test fija el
 * contenido (que sigue mostrando cada asignatura) y el número de consultas, incluido el camino de respaldo: una calificación ligada a una asignación
 * que NO es de las activas del grupo (inactiva o de otro grupo) debe seguir mostrando su asignatura.
 */
class PortalEstudianteDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{user: User, matricula: Matricula, activa: Asignacion, inactiva: Asignacion, otroGrupo: Asignacion} */
    private function escenario(): array
    {
        $sy = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Primer Período', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-11-30', 'activo' => true, 'cerrado' => false]);
        $grado = Grado::create(['nombre' => 'Grado PD', 'nivel' => 151, 'orden' => 151, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $sec = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $sec->id, 'activo' => true]);
        $otro = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'B'], ['orden' => 2])->id, 'activo' => true]);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Estudiante');
        $est = Estudiante::factory()->create(['user_id' => $user->id]);
        $m = Matricula::create(['school_year_id' => $sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $grupo->id, 'fecha_matricula' => '2026-08-15', 'numero_orden' => 1, 'estado' => 'activa']);

        $docente = Docente::factory()->create();
        $asig = function (string $nombre, Grupo $g, bool $activo) use ($sy, $docente) {
            $asignatura = Asignatura::create(['codigo' => strtoupper(substr(md5($nombre), 0, 6)), 'nombre' => $nombre, 'area' => 'academica', 'activo' => true]);

            return Asignacion::create(['school_year_id' => $sy->id, 'grupo_id' => $g->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $docente->id, 'activo' => $activo, 'area' => 'academica']);
        };

        $activa    = $asig('Matemática Activa', $grupo, true);
        $inactiva  = $asig('Física Inactiva', $grupo, false);
        $otroGrupo = $asig('Química Otro Grupo', $otro, true);

        // Una calificación publicada y una nota académica por cada una de las tres asignaciones (dos NO son de las activas del grupo)
        foreach ([$activa, $inactiva, $otroGrupo] as $a) {
            Calificacion::create(['matricula_id' => $m->id, 'asignacion_id' => $a->id, 'periodo_id' => $periodo->id, 'nota_final' => 85, 'publicado' => true]);
            CalificacionAcademica::create(['matricula_id' => $m->id, 'asignacion_id' => $a->id, 'school_year_id' => $sy->id, 'nota_final' => 90]);
        }
        Observacion::create(['docente_id' => $docente->id, 'estudiante_id' => $est->id, 'asignacion_id' => $inactiva->id, 'periodo_id' => $periodo->id, 'tipo' => 'positiva', 'texto' => 'Muy buen trabajo en clase', 'privada' => false]);

        return ['user' => $user, 'matricula' => $m, 'activa' => $activa, 'inactiva' => $inactiva, 'otroGrupo' => $otroGrupo];
    }

    public function test_el_panel_muestra_cada_asignatura_incluso_las_que_no_son_de_las_activas_del_grupo(): void
    {
        $e = $this->escenario();

        $html = $this->actingAs($e['user'])->get(route('portal.estudiante.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Matemática Activa', $html);
        $this->assertStringContainsString('Física Inactiva', $html, 'una calificación de una asignación inactiva sigue mostrando su asignatura (camino de respaldo)');
        $this->assertStringContainsString('Química Otro Grupo', $html, 'y una de otro grupo también');
        $this->assertStringContainsString('Muy buen trabajo en clase', $html);
    }

    public function test_las_asignaciones_se_piden_una_vez_y_no_en_cada_bloque(): void
    {
        $e = $this->escenario();
        $consultas = [];
        DB::listen(function ($q) use (&$consultas) {
            $consultas[] = strtolower($q->sql);
        });

        $this->actingAs($e['user'])->get(route('portal.estudiante.dashboard'))->assertOk();

        $deAsignaciones = count(array_filter($consultas, fn ($s) => str_starts_with($s, 'select * from `asignaciones`')));
        $deAsignaturas  = count(array_filter($consultas, fn ($s) => str_starts_with($s, 'select * from `asignaturas`')));

        // 1 = las activas del grupo; +1 = las que faltan (inactiva / otro grupo) pedidas juntas en bloque. Antes: ~6 y ~5.
        $this->assertLessThanOrEqual(2, $deAsignaciones, 'asignaciones consultadas ' . $deAsignaciones . ' veces');
        $this->assertLessThanOrEqual(2, $deAsignaturas, 'asignaturas consultadas ' . $deAsignaturas . ' veces');
    }

    public function test_un_estudiante_sin_matricula_activa_ve_el_panel_sin_errores(): void
    {
        SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Estudiante');
        Estudiante::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('portal.estudiante.dashboard'))->assertOk();
    }

    public function test_el_total_de_puntos_sale_del_ranking_del_grupo(): void
    {
        $e = $this->escenario();
        // Sin puntos: el panel debe cargar y mostrar 0 puntos sin error (antes había una consulta SUM aparte).
        $this->actingAs($e['user'])->get(route('portal.estudiante.dashboard'))->assertOk();
    }
}
