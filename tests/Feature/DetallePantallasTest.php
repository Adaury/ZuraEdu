<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\Calificacion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pantallas de detalle que daban error 500 al abrirlas con datos reales (RouteSmokeTest solo recorre rutas SIN parámetros, así que no
 * las veía):
 *  - Ficha del estudiante: con CUALQUIER calificación del año actual fallaba («Undefined array key»): se asignaba anidado sobre una Collection.
 *  - Registro de calificaciones sin materia/período elegidos (la entrada a la pantalla): la vista usaba $periodo sin comprobar que existiera.
 *  - Reporte de asistencia de un estudiante: cargaba el docente de forma diferida (error con lazy loading prohibido).
 */
class DetallePantallasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Estudiante $estudiante;
    private Matricula $matricula;
    private Grupo $grupo;
    private Asignacion $asignacion;
    private Periodo $periodo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->admin = User::factory()->create(['activo' => true])->assignRole('Administrador');

        $sy = SchoolYear::create(['nombre' => '2097-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Det', 'nivel' => (Grado::withoutGlobalScopes()->max('nivel') ?? 0) + 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $this->grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);
        $this->estudiante = Estudiante::factory()->create();
        $this->matricula = Matricula::create(['school_year_id' => $sy->id, 'estudiante_id' => $this->estudiante->id, 'grupo_id' => $this->grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa']);
        $this->periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Período 1', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true]);

        $asignatura = Asignatura::create(['codigo' => 'MAT-DET', 'nombre' => 'Matemática Det', 'area' => 'academica', 'horas_semanales' => 4, 'activo' => true]);
        $docente = Docente::factory()->create();
        $this->asignacion = Asignacion::create(['school_year_id' => $sy->id, 'grupo_id' => $this->grupo->id, 'asignatura_id' => $asignatura->id, 'docente_id' => $docente->id, 'activo' => true]);
    }

    public function test_la_ficha_del_estudiante_abre_aunque_tenga_calificaciones(): void
    {
        Calificacion::create(['matricula_id' => $this->matricula->id, 'asignacion_id' => $this->asignacion->id, 'periodo_id' => $this->periodo->id, 'nota_final' => 85]);

        $this->actingAs($this->admin)->get(route('admin.estudiantes.show', $this->estudiante))->assertOk();
    }

    public function test_la_ficha_del_estudiante_abre_sin_calificaciones(): void
    {
        $this->actingAs($this->admin)->get(route('admin.estudiantes.show', $this->estudiante))->assertOk();
    }

    public function test_el_registro_de_calificaciones_abre_sin_materia_ni_periodo_elegidos(): void
    {
        $this->actingAs($this->admin)->get(route('admin.registro.calificaciones', $this->grupo))
            ->assertOk()->assertSee('Seleccionar materia', false);
    }

    public function test_el_registro_de_calificaciones_abre_con_materia_y_periodo(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.registro.calificaciones', [$this->grupo, 'asignacion_id' => $this->asignacion->id, 'periodo_id' => $this->periodo->id]))
            ->assertOk()->assertSee('Matemática Det', false);
    }

    public function test_el_reporte_de_asistencia_del_estudiante_muestra_al_docente(): void
    {
        Asistencia::create(['matricula_id' => $this->matricula->id, 'asignacion_id' => $this->asignacion->id, 'fecha' => '2025-09-01', 'estado' => 'presente', 'registrado_por' => $this->admin->id]);

        $this->actingAs($this->admin)->get(route('admin.asistencia.reporteEstudiante', $this->matricula))
            ->assertOk()->assertSee('Matemática Det', false);
    }

    public function test_las_estadisticas_de_asistencia_del_docente_abren_con_y_sin_ausencias(): void
    {
        $usuario = User::factory()->create(['activo' => true])->assignRole('Docente');
        $docente = Docente::factory()->create(['user_id' => $usuario->id]);
        $this->asignacion->update(['docente_id' => $docente->id]);

        // Sin registros: antes daba error 500 por llamar a array_max(), una función que no existe en PHP
        $this->actingAs($usuario)->get(route('portal.docente.asistencia.estadisticas', $this->asignacion))->assertOk();

        Asistencia::create(['matricula_id' => $this->matricula->id, 'asignacion_id' => $this->asignacion->id, 'fecha' => '2025-09-01', 'estado' => 'ausente', 'registrado_por' => $usuario->id]);
        $this->actingAs($usuario)->get(route('portal.docente.asistencia.estadisticas', $this->asignacion))->assertOk();
    }
}
