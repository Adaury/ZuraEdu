<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\ClassroomMessage;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use App\Models\ZcPregunta;
use App\Models\ZcQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Defectos que solo apareció al abrir las pantallas de Classroom en un navegador con datos reales (varios materiales en el aula):
 * - la carga perezosa (prohibida en local/pruebas) solo se dispara cuando hay MÁS de un elemento en la lista, por eso las pruebas de un solo
 *   material no la veían y el aula del docente y la del estudiante daban 500;
 * - el historial del chat serializaba el usuario completo (email, cédula, teléfono) y no traía el nombre del autor ni la hora legible;
 * - el editor de quiz generaba campos «preguntas[0]_enunciado» en vez de «preguntas[0][enunciado]».
 */
class ClassroomPantallasTest extends TestCase
{
    use RefreshDatabase;

    private ClaseVirtual $clase;
    private Docente $docente;
    private Estudiante $estudiante;
    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $sy = SchoolYear::create(['nombre' => '2026-Pant', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Período 1', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false]);
        $grado = Grado::create(['nombre' => 'Grado Pant', 'nivel' => 150 + (++self::$nivel), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $grupo = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true]);

        $this->docente = Docente::factory()->create();
        $this->docente->user->assignRole('Docente');
        $this->docente->user->update(['activo' => true]);
        $this->estudiante = Estudiante::factory()->create();
        $this->estudiante->user->assignRole('Estudiante');
        $this->estudiante->user->update(['activo' => true]);
        Matricula::create(['school_year_id' => $sy->id, 'estudiante_id' => $this->estudiante->id, 'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa']);

        $asig = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => Asignatura::create(['codigo' => 'PA1', 'nombre' => 'Lengua', 'area' => 'academica', 'activo' => true])->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $this->clase = ClaseVirtual::create(['asignacion_id' => $asig->id, 'nombre' => 'Aula Pantallas', 'activo' => true]);

        // VARIOS materiales, con período y con quiz: lo que dispara la carga perezosa
        foreach (['Anuncio uno' => 'anuncio', 'Guía dos' => 'material', 'Tarea tres' => 'tarea', 'Evaluación cuatro' => 'evaluacion'] as $titulo => $tipo) {
            $m = MaterialClase::create(['clase_virtual_id' => $this->clase->id, 'titulo' => $titulo, 'tipo' => $tipo, 'publicado' => true, 'puntos' => in_array($tipo, ['tarea', 'evaluacion']) ? 10 : null, 'periodo_id' => in_array($tipo, ['tarea', 'evaluacion']) ? $periodo->id : null]);
            if ($tipo === 'evaluacion') {
                $quiz = ZcQuiz::create(['material_id' => $m->id, 'intentos_max' => 1, 'autocorreccion' => true]);
                ZcPregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'P', 'tipo' => 'abierta', 'puntos' => 10, 'orden' => 0]);
            }
        }
    }

    public function test_las_pantallas_del_aula_abren_con_varios_materiales_sin_carga_perezosa(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Administrador');

        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.show', $this->clase))->assertOk()->assertSee('Tarea tres')->assertSee('Evaluación cuatro');
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.personas', $this->clase))->assertOk();
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.show', $this->clase))->assertOk()->assertSee('Tarea tres')->assertSee('Quiz online');
        $this->actingAs($admin)->get(route('admin.classroom.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.classroom.show', $this->clase))->assertOk();
        $this->actingAs($admin)->get(route('admin.classroom.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.classroom.edit', $this->clase))->assertOk();
    }

    public function test_el_grupo_aparece_con_su_nombre_y_no_en_blanco(): void
    {
        $nombre = $this->clase->asignacion->grupo->nombre_completo;
        $this->assertNotSame('', trim($nombre));

        $html = $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.show', $this->clase))->assertOk()->getContent();
        $this->assertStringContainsString('Grupo:</strong> ' . e($nombre), $html, '«Grupo:» ya no sale vacío (Grupo no tiene atributo «nombre»)');
    }

    public function test_el_historial_del_chat_trae_el_autor_y_la_hora_pero_ningun_dato_personal(): void
    {
        $this->docente->user->update(['cedula' => '001-9999999-9', 'telefono' => '809-555-1234']);
        ClassroomMessage::create(['tenant_id' => $this->docente->user->tenant_id, 'clase_virtual_id' => $this->clase->id, 'user_id' => $this->docente->user_id, 'mensaje' => 'Hola clase', 'tipo' => 'general']);

        $r = $this->actingAs($this->estudiante->user)->getJson(route('portal.estudiante.classroom.chat.index', $this->clase))->assertOk();
        $m = $r->json('mensajes.data.0');

        $this->assertSame($this->docente->user->name, $m['user_name']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $m['created_at']);
        $this->assertFalse($m['es_propio']);
        $this->assertSame('Hola clase', $m['mensaje']);

        $json = $r->getContent();
        foreach (['001-9999999-9', '809-555-1234', $this->docente->user->email, 'cedula', 'telefono', 'email'] as $secreto) {
            $this->assertStringNotContainsString($secreto, $json, "el chat no debe exponer: $secreto");
        }
    }

    public function test_el_editor_de_quiz_arma_los_campos_como_preguntas_n_campo(): void
    {
        foreach (['quiz_crear', 'quiz_editar'] as $v) {
            $t = file_get_contents(resource_path("views/portal/classroom/docente/{$v}.blade.php"));
            $this->assertStringNotContainsString(".replace('REPLACE', `preguntas[\${idx}]`)", $t, "$v: generaba «preguntas[0]_enunciado»");
            $this->assertStringContainsString('REPLACE_(\\w+)', $t, $v);
        }
    }
}
