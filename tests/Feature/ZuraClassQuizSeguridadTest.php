<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\ZcIntento;
use App\Models\ZcOpcion;
use App\Models\ZcPregunta;
use App\Models\ZcQuiz;
use App\Models\ZcRespuesta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quiz de ZuraClass: límite de tiempo vigilado al guardar/enviar, quiz en borrador
 * inaccesible, el intento vencido SÍ llega a la entrega y al libro, la nota es el
 * mejor intento, las abiertas no se califican solas y editar no borra respuestas.
 */
class ZuraClassQuizSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $sy;
    private Periodo $periodo;
    private Docente $docente;
    private Estudiante $estudiante;
    private Matricula $matricula;
    private Asignacion $asignacion;
    private ClaseVirtual $clase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $this->sy = SchoolYear::create(['nombre' => '2026-Quiz', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $this->periodo = Periodo::create([
            'school_year_id' => $this->sy->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);

        $grado   = Grado::create(['nombre' => 'Grado QZ', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $this->docente = Docente::factory()->create();
        $this->docente->user->assignRole('Docente');
        $this->docente->user->update(['activo' => true]);

        $this->estudiante = Estudiante::factory()->create();
        $this->estudiante->user->assignRole('Estudiante');
        $this->estudiante->user->update(['activo' => true]);
        $this->matricula = Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $this->estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $asig = Asignatura::create(['codigo' => 'QZ' . random_int(1000, 9999), 'nombre' => 'Lengua', 'area' => 'academica', 'activo' => true]);
        $this->asignacion = Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asig->id,
            'docente_id' => $this->docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $this->clase = ClaseVirtual::create(['asignacion_id' => $this->asignacion->id, 'nombre' => 'Aula Quiz', 'activo' => true]);
    }

    /**
     * Material + quiz de 2 preguntas de selección (10 pts c/u) y, opcionalmente, una abierta.
     *
     * @return array{material: MaterialClase, quiz: ZcQuiz, preguntas: ZcPregunta[], correctas: array<int,int>, incorrectas: array<int,int>}
     */
    private function quiz(array $quizExtra = [], bool $publicado = true, bool $conAbierta = false): array
    {
        $material = MaterialClase::create([
            'clase_virtual_id' => $this->clase->id, 'titulo' => 'Quiz ' . random_int(1, 99999), 'tipo' => 'evaluacion',
            'puntos' => 20, 'periodo_id' => $this->periodo->id, 'publicado' => $publicado,
        ]);
        $quiz = ZcQuiz::create(array_merge([
            'material_id' => $material->id, 'duracion_minutos' => null, 'intentos_max' => 2,
            'autocorreccion' => true, 'aleatorizar_preguntas' => false, 'mostrar_respuestas' => true,
        ], $quizExtra));

        $preguntas = $correctas = $incorrectas = [];
        foreach ([1, 2] as $i) {
            $p   = ZcPregunta::create(['quiz_id' => $quiz->id, 'enunciado' => "Pregunta {$i}", 'tipo' => 'multiple', 'puntos' => 10, 'orden' => $i]);
            $ok  = ZcOpcion::create(['pregunta_id' => $p->id, 'texto' => 'Correcta', 'es_correcta' => true, 'orden' => 0]);
            $mal = ZcOpcion::create(['pregunta_id' => $p->id, 'texto' => 'Incorrecta', 'es_correcta' => false, 'orden' => 1]);
            $preguntas[]         = $p;
            $correctas[$p->id]   = $ok->id;
            $incorrectas[$p->id] = $mal->id;
        }
        if ($conAbierta) {
            $preguntas[] = ZcPregunta::create(['quiz_id' => $quiz->id, 'enunciado' => 'Explica', 'tipo' => 'abierta', 'puntos' => 10, 'orden' => 3]);
        }

        return compact('material', 'quiz', 'preguntas', 'correctas', 'incorrectas');
    }

    private function nuevoIntento(ZcQuiz $quiz, $inicio = null): ZcIntento
    {
        return ZcIntento::create([
            'quiz_id' => $quiz->id, 'matricula_id' => $this->matricula->id, 'estado' => 'en_curso',
            'puntuacion_max' => $quiz->puntaje_total, 'iniciado_en' => $inicio ?? now(),
            'numero_intento' => ZcIntento::where('quiz_id', $quiz->id)->where('matricula_id', $this->matricula->id)->count() + 1,
        ]);
    }

    private function enviar(MaterialClase $m, ZcIntento $intento, array $respuestas)
    {
        $body = [];
        foreach ($respuestas as $preguntaId => $valor) {
            $body["pregunta_{$preguntaId}"] = $valor;
        }

        return $this->actingAs($this->estudiante->user)->post(
            route('portal.estudiante.classroom.quiz.enviar', [$this->clase, $m, $intento]),
            $body
        );
    }

    private function entrega(MaterialClase $m): ?EntregaClassroom
    {
        return EntregaClassroom::where('material_id', $m->id)->where('matricula_id', $this->matricula->id)->first();
    }

    public function test_quiz_en_borrador_no_se_puede_abrir_ni_comenzar(): void
    {
        $q = $this->quiz(publicado: false);

        $this->actingAs($this->estudiante->user)
            ->get(route('portal.estudiante.classroom.quiz.iniciar', [$this->clase, $q['material']]))
            ->assertNotFound();
        $this->actingAs($this->estudiante->user)
            ->post(route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $q['material']]))
            ->assertNotFound();

        $this->assertSame(0, ZcIntento::count());
    }

    public function test_comenzar_reanuda_el_intento_en_curso_y_no_crea_otro(): void
    {
        $q   = $this->quiz();
        $url = route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $q['material']]);

        $this->actingAs($this->estudiante->user)->post($url);
        $this->actingAs($this->estudiante->user)->post($url);

        $this->assertSame(1, ZcIntento::where('quiz_id', $q['quiz']->id)->count());
    }

    public function test_pasado_el_tiempo_no_se_aceptan_respuestas_ni_el_envio_cuenta(): void
    {
        $q       = $this->quiz(['duracion_minutos' => 5]);
        $intento = $this->nuevoIntento($q['quiz'], now()->subMinutes(10));
        [$p1, $p2] = $q['preguntas'];

        $this->actingAs($this->estudiante->user)
            ->postJson(route('portal.estudiante.classroom.quiz.guardar', $intento), [
                'pregunta_id' => $p1->id, 'opcion_id' => $q['correctas'][$p1->id],
            ])->assertStatus(422);
        $this->assertSame(0, ZcRespuesta::count());

        // Envía "todo correcto" fuera de tiempo: se ignora lo enviado, puntaje 0.
        $this->enviar($q['material'], $intento, [
            $p1->id => $q['correctas'][$p1->id],
            $p2->id => $q['correctas'][$p2->id],
        ])->assertRedirect();

        $intento->refresh();
        $this->assertSame('finalizado', $intento->estado);
        $this->assertEquals(0, (float) $intento->puntuacion);
    }

    public function test_intento_vencido_al_abrir_si_llega_a_la_entrega_y_al_libro(): void
    {
        $q       = $this->quiz(['duracion_minutos' => 5]);
        $intento = $this->nuevoIntento($q['quiz'], now()->subMinutes(10));
        $p1      = $q['preguntas'][0];
        // Guardó la primera respuesta (correcta) antes de que venciera.
        ZcRespuesta::create(['intento_id' => $intento->id, 'pregunta_id' => $p1->id, 'opcion_id' => $q['correctas'][$p1->id]]);

        $this->actingAs($this->estudiante->user)
            ->get(route('portal.estudiante.classroom.quiz.tomar', [$this->clase, $q['material'], $intento]))
            ->assertRedirect();

        $entrega = $this->entrega($q['material']);
        $this->assertNotNull($entrega, 'El intento vencido debe crear la entrega');
        $this->assertSame('calificado', $entrega->estado);
        $this->assertEquals(10.0, (float) $entrega->calificacion); // 10 de 20 pts
        $this->assertEquals(50.0, (float) Calificacion::where('matricula_id', $this->matricula->id)->value('tareas'));
    }

    public function test_la_nota_es_el_mejor_intento_y_no_el_ultimo(): void
    {
        $q = $this->quiz();
        [$p1, $p2] = $q['preguntas'];

        $i1 = $this->nuevoIntento($q['quiz']);
        $this->enviar($q['material'], $i1, [$p1->id => $q['correctas'][$p1->id], $p2->id => $q['correctas'][$p2->id]]);
        $this->assertEquals(20.0, (float) $this->entrega($q['material'])->calificacion);

        $i2 = $this->nuevoIntento($q['quiz']);
        $this->enviar($q['material'], $i2, [$p1->id => $q['incorrectas'][$p1->id], $p2->id => $q['incorrectas'][$p2->id]]);

        $this->assertEquals(20.0, (float) $this->entrega($q['material'])->fresh()->calificacion, 'Un intento peor no debe bajar la nota');
    }

    public function test_quiz_con_abiertas_no_se_marca_calificado_ni_llega_al_libro(): void
    {
        $q       = $this->quiz(conAbierta: true);
        $intento = $this->nuevoIntento($q['quiz']);
        [$p1, $p2, $abierta] = $q['preguntas'];

        $this->enviar($q['material'], $intento, [
            $p1->id => $q['correctas'][$p1->id], $p2->id => $q['correctas'][$p2->id], $abierta->id => 'Mi explicación',
        ]);

        $entrega = $this->entrega($q['material']);
        $this->assertSame('entregado', $entrega->estado);
        $this->assertNull($entrega->calificacion);
        $this->assertNull(Calificacion::where('matricula_id', $this->matricula->id)->value('tareas'));
    }

    public function test_respuesta_con_opcion_de_otra_pregunta_o_de_otro_quiz_se_rechaza(): void
    {
        $q       = $this->quiz();
        $otro    = $this->quiz();
        $intento = $this->nuevoIntento($q['quiz']);
        [$p1, $p2] = $q['preguntas'];

        // Opción de OTRA pregunta del mismo quiz
        $this->actingAs($this->estudiante->user)
            ->postJson(route('portal.estudiante.classroom.quiz.guardar', $intento), [
                'pregunta_id' => $p1->id, 'opcion_id' => $q['correctas'][$p2->id],
            ])->assertStatus(422);

        // Pregunta de OTRO quiz
        $ajena = $otro['preguntas'][0];
        $this->actingAs($this->estudiante->user)
            ->postJson(route('portal.estudiante.classroom.quiz.guardar', $intento), [
                'pregunta_id' => $ajena->id, 'opcion_id' => $otro['correctas'][$ajena->id],
            ])->assertStatus(422);

        $this->assertSame(0, ZcRespuesta::count());

        // La opción correcta de la pregunta correcta sí se guarda
        $this->actingAs($this->estudiante->user)
            ->postJson(route('portal.estudiante.classroom.quiz.guardar', $intento), [
                'pregunta_id' => $p1->id, 'opcion_id' => $q['correctas'][$p1->id],
            ])->assertOk();
    }

    public function test_docente_no_puede_reemplazar_preguntas_si_ya_hay_intentos(): void
    {
        $q = $this->quiz();
        $this->nuevoIntento($q['quiz']);
        $preguntasAntes = ZcPregunta::where('quiz_id', $q['quiz']->id)->pluck('id')->all();

        $this->actingAs($this->docente->user)
            ->put(route('portal.docente.classroom.quiz.actualizar', [$this->clase, $q['material']]), [
                'intentos_max' => 3,
                'preguntas'    => [['enunciado' => 'Nueva', 'tipo' => 'multiple', 'puntos' => 5, 'opciones' => [['texto' => 'x', 'correcta' => 1]]]],
            ])
            ->assertRedirect();

        $this->assertEquals($preguntasAntes, ZcPregunta::where('quiz_id', $q['quiz']->id)->pluck('id')->all());
        $this->assertSame(3, (int) $q['quiz']->fresh()->intentos_max, 'Los ajustes sí se guardan');
    }
}
