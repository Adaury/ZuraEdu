<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Calificacion;
use App\Models\ClaseVirtual;
use App\Models\ClassroomMessage;
use App\Models\Docente;
use App\Models\EntregaClassroom;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MaterialClase;
use App\Models\Periodo;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\User;
use App\Models\ZcIntento;
use App\Models\ZcQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * ZuraClass / Classroom: el flujo REAL de uso de punta a punta, con los tres roles.
 * Administración crea el aula → el docente publica una tarea → el estudiante la entrega → el docente la califica y sincroniza el libro de notas
 * → el padre ve el aula de su hijo. Más: quiz con autocorrección, chat, duplicar aula y aislamiento entre grupos/familias.
 * (Las pruebas de seguridad puntuales —IDOR del chat, tipos de archivo, otro tenant— viven en las otras pruebas Classroom*.)
 */
class ClassroomFlujoCompletoTest extends TestCase
{
    use RefreshDatabase;

    private SchoolYear $sy;
    private Periodo $periodo;
    private Grupo $grupo;
    private Docente $docente;
    private Estudiante $estudiante;
    private Matricula $matricula;
    private Asignacion $asignacion;
    private ClaseVirtual $clase;
    private static int $n = 0;
    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        self::$n++;

        $this->sy = SchoolYear::create(['nombre' => '2026-Flujo', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $this->periodo = Periodo::create([
            'school_year_id' => $this->sy->id, 'numero' => 1, 'nombre' => 'Período 1',
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true, 'cerrado' => false,
        ]);
        $this->grupo = $this->nuevoGrupo('Grado Flujo');

        $this->docente = $this->nuevoDocente();
        $this->estudiante = Estudiante::factory()->create();
        $this->estudiante->user->assignRole('Estudiante');
        $this->estudiante->user->update(['activo' => true]);
        $this->matricula = $this->matricular($this->estudiante, $this->grupo);

        $this->asignacion = $this->nuevaAsignacion($this->grupo, $this->docente, 'FL1', 'Lengua Española');
        $this->clase = ClaseVirtual::create(['asignacion_id' => $this->asignacion->id, 'nombre' => 'Aula de Lengua', 'activo' => true]);
    }

    // ── fixtures ────────────────────────────────────────────────────────────

    private function nuevoGrupo(string $nombreGrado): Grupo
    {
        $grado = Grado::create(['nombre' => $nombreGrado, 'nivel' => 100 + (++self::$nivel), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);

        return Grupo::create(['school_year_id' => $this->sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
    }

    private function nuevoDocente(): Docente
    {
        $d = Docente::factory()->create();
        $d->user->assignRole('Docente');
        $d->user->update(['activo' => true]);

        return $d;
    }

    private function matricular(Estudiante $e, Grupo $g): Matricula
    {
        return Matricula::create([
            'school_year_id' => $this->sy->id, 'estudiante_id' => $e->id, 'grupo_id' => $g->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
    }

    private function nuevaAsignacion(Grupo $g, Docente $d, string $codigo, string $nombre): Asignacion
    {
        $asig = Asignatura::create(['codigo' => $codigo, 'nombre' => $nombre, 'area' => 'academica', 'activo' => true]);

        return Asignacion::create([
            'school_year_id' => $this->sy->id, 'grupo_id' => $g->id, 'asignatura_id' => $asig->id,
            'docente_id' => $d->id, 'activo' => true, 'area' => 'academica',
        ]);
    }

    /** El docente publica una tarea por la ruta real. */
    private function publicarTarea(string $titulo, int $puntos = 20, array $extra = []): MaterialClase
    {
        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.guardar_material', $this->clase), array_merge([
                'titulo' => $titulo, 'tipo' => 'tarea', 'puntos' => $puntos, 'periodo_id' => $this->periodo->id, 'publicado' => 1,
            ], $extra))
            ->assertSessionDoesntHaveErrors();

        return MaterialClase::where('clase_virtual_id', $this->clase->id)->where('titulo', $titulo)->firstOrFail();
    }

    private function entregar(MaterialClase $m, ?User $como = null)
    {
        return $this->actingAs($como ?? $this->estudiante->user)
            ->post(route('portal.estudiante.classroom.entregar', [$this->clase, $m]), [
                'contenido' => 'Mi respuesta', 'archivos' => [UploadedFile::fake()->create('tarea.pdf', 50, 'application/pdf')],
            ]);
    }

    private function calificar(EntregaClassroom $e, float $nota, bool $sincronizar = true)
    {
        return $this->actingAs($this->docente->user)
            ->patch(route('portal.docente.classroom.calificar_entrega', [$this->clase, $e]), [
                'calificacion' => $nota, 'comentario_docente' => 'Buen trabajo', 'sincronizar_notas' => $sincronizar ? 1 : 0,
            ]);
    }

    private function notaTareas(): ?float
    {
        $c = Calificacion::where('matricula_id', $this->matricula->id)->where('asignacion_id', $this->asignacion->id)->where('periodo_id', $this->periodo->id)->first();

        return $c?->tareas === null ? null : (float) $c->tareas;
    }

    // ── 1. Administración ───────────────────────────────────────────────────

    public function test_administracion_crea_edita_desactiva_y_elimina_un_aula(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Administrador');
        $otra = $this->nuevaAsignacion($this->nuevoGrupo('Grado Admin'), $this->docente, 'AD1', 'Matemática');

        $this->actingAs($admin)->get(route('admin.classroom.index'))->assertOk()->assertSee('Aula de Lengua');
        $this->actingAs($admin)->get(route('admin.classroom.create'))->assertOk();

        $this->actingAs($admin)->post(route('admin.classroom.store'), [
            'asignacion_id' => $otra->id, 'nombre' => 'Aula de Matemática', 'portada_color' => '#1e3a6e',
        ])->assertRedirect(route('admin.classroom.index'));
        $aula = ClaseVirtual::where('nombre', 'Aula de Matemática')->firstOrFail();
        $this->assertTrue($aula->activo);

        $this->actingAs($admin)->get(route('admin.classroom.show', $aula))->assertOk();
        $this->actingAs($admin)->get(route('admin.classroom.edit', $aula))->assertOk();

        $this->actingAs($admin)->put(route('admin.classroom.update', $aula), [
            'asignacion_id' => $otra->id, 'nombre' => 'Mate 1', 'portada_color' => '#2563eb', 'activo' => 1, 'permite_comentarios' => 1,
        ])->assertRedirect();
        $this->assertSame('Mate 1', $aula->fresh()->nombre);

        $this->actingAs($admin)->patch(route('admin.classroom.toggle-activo', $aula))->assertRedirect();
        $this->assertFalse($aula->fresh()->activo, 'desactivada');

        $this->actingAs($admin)->delete(route('admin.classroom.destroy', $aula))->assertRedirect();
        $this->assertNull(ClaseVirtual::find($aula->id));
    }

    public function test_un_docente_no_entra_a_la_administracion_de_aulas(): void
    {
        $r = $this->actingAs($this->docente->user)->get(route('admin.classroom.index'));
        $this->assertContains($r->getStatusCode(), [302, 403], 'no puede ver el panel de administración');
        if ($r->getStatusCode() === 302) {
            $this->assertStringNotContainsString('/admin/classroom', (string) $r->headers->get('Location'), 'lo manda a otra parte, no a la administración de aulas');
        }

        $antes = ClaseVirtual::count();
        $p = $this->actingAs($this->docente->user)->post(route('admin.classroom.store'), [
            'asignacion_id' => $this->asignacion->id, 'nombre' => 'X', 'portada_color' => '#000000',
        ]);
        $this->assertContains($p->getStatusCode(), [302, 403]);
        $this->assertSame($antes, ClaseVirtual::count(), 'no se creó ningún aula');
    }

    public function test_el_aula_rechaza_un_color_de_portada_invalido(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)->post(route('admin.classroom.store'), [
            'asignacion_id' => $this->asignacion->id, 'nombre' => 'X', 'portada_color' => 'rojo',
        ])->assertSessionHasErrors('portada_color');
    }

    // ── 2. Docente → estudiante: tarea, entrega, calificación, libro de notas ─

    public function test_flujo_tarea_publicar_entregar_calificar_y_sincronizar_notas(): void
    {
        // El docente ve su aula y puede abrirla
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.index'))->assertOk()->assertSee('Aula de Lengua');
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.show', $this->clase))->assertOk();

        // Código de clase
        $r = $this->actingAs($this->docente->user)->postJson(route('portal.docente.classroom.generar_codigo', $this->clase))->assertOk();
        $this->assertNotEmpty($r->json('codigo'));

        // Publica la tarea
        $tarea = $this->publicarTarea('Ensayo sobre la lectura');
        $this->assertTrue((bool) $tarea->publicado);

        // El estudiante ve el aula y la tarea
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.index'))->assertOk()->assertSee('Aula de Lengua');
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.show', $this->clase))->assertOk()->assertSee('Ensayo sobre la lectura');
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.pendientes'))->assertOk();

        // Entrega
        $this->entregar($tarea)->assertSessionDoesntHaveErrors();
        $entrega = EntregaClassroom::where('material_id', $tarea->id)->where('matricula_id', $this->matricula->id)->firstOrFail();
        $this->assertSame('entregado', $entrega->estado);
        $this->assertSame(1, $entrega->archivos()->count());

        // Sin permiso de reentrega, un segundo envío se rechaza y no cambia nada
        $this->entregar($tarea)->assertSessionHasErrors('error');
        $this->assertSame(1, $entrega->fresh()->intentos);

        // Comenta la tarea
        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.comentar', [$this->clase, $tarea]), ['contenido' => '¿Puedo entregar mañana?'])->assertSessionDoesntHaveErrors();

        // El docente ve las entregas y el detalle
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.entregas', [$this->clase, $tarea]))->assertOk();
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.entrega_detalle', [$this->clase, $tarea, $entrega]))->assertOk();

        // Califica 18/20 → el libro de notas recibe 90 (sobre 100)
        $this->calificar($entrega, 18)->assertSessionDoesntHaveErrors();
        $entrega->refresh();
        $this->assertSame('calificado', $entrega->estado);
        $this->assertEquals(18, $entrega->calificacion);
        $this->assertSame($this->docente->user_id, $entrega->revisado_por);
        $this->assertSame(90.0, $this->notaTareas());

        // El estudiante recibió la notificación y ve su nota
        $this->assertDatabaseHas('notificaciones', ['user_id' => $this->estudiante->user_id, 'tipo' => 'zura_calificado']);
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.show', $this->clase))->assertOk()->assertSee('Buen trabajo');

        // Resumen de calificaciones del docente
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.calificaciones', $this->clase))->assertOk();
    }

    public function test_dos_tareas_del_mismo_periodo_se_promedian_en_el_libro_de_notas(): void
    {
        $t1 = $this->publicarTarea('Tarea 1', 20);
        $t2 = $this->publicarTarea('Tarea 2', 20);
        $this->entregar($t1);
        $this->entregar($t2);

        $this->calificar(EntregaClassroom::where('material_id', $t1->id)->firstOrFail(), 18);   // 90 %
        $this->assertSame(90.0, $this->notaTareas());

        $this->calificar(EntregaClassroom::where('material_id', $t2->id)->firstOrFail(), 10);   // 50 % → promedio 70, no 50 (la última no pisa a la otra)
        $this->assertSame(70.0, $this->notaTareas());

        // «Sincronizar notas» completo no altera un promedio ya correcto
        $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.sincronizar_notas', $this->clase))->assertRedirect();
        $this->assertSame(70.0, $this->notaTareas());
    }

    public function test_calificar_sin_sincronizar_no_toca_el_libro_de_notas(): void
    {
        $t = $this->publicarTarea('Tarea sin sync', 20);
        $this->entregar($t);

        $this->calificar(EntregaClassroom::where('material_id', $t->id)->firstOrFail(), 15, sincronizar: false)->assertSessionDoesntHaveErrors();

        $this->assertNull($this->notaTareas());
    }

    public function test_la_calificacion_fuera_de_rango_se_rechaza(): void
    {
        $t = $this->publicarTarea('Tarea rango', 20);
        $this->entregar($t);
        $e = EntregaClassroom::where('material_id', $t->id)->firstOrFail();

        $this->calificar($e, 150)->assertSessionHasErrors('calificacion');
        $this->assertNull($e->fresh()->calificacion);
    }

    public function test_devolver_una_entrega_la_deja_para_correccion_y_no_sincroniza_nota(): void
    {
        $t = $this->publicarTarea('Tarea devolver', 20, ['permite_reentrega' => 1]);
        $this->entregar($t);
        $e = EntregaClassroom::where('material_id', $t->id)->firstOrFail();

        $this->actingAs($this->docente->user)
            ->patch(route('portal.docente.classroom.calificar_entrega', [$this->clase, $e]), ['calificacion' => 5, 'devolver' => 1, 'sincronizar_notas' => 1, 'comentario_docente' => 'Corrige la ortografía'])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('devuelto', $e->fresh()->estado);
        $this->assertNull($this->notaTareas(), 'una entrega devuelta no pasa al libro de notas');

        // Con reentrega permitida, el estudiante corrige y vuelve a entregar
        $this->entregar($t)->assertSessionDoesntHaveErrors();
        $this->assertSame(2, $e->fresh()->intentos);
    }

    public function test_una_tarea_con_fecha_vencida_se_entrega_como_atrasada(): void
    {
        $t = $this->publicarTarea('Tarea vieja', 20, ['fecha_limite' => now()->subDay()->format('Y-m-d H:i:s')]);

        $this->entregar($t)->assertSessionDoesntHaveErrors();

        $this->assertSame('atrasado', EntregaClassroom::where('material_id', $t->id)->firstOrFail()->estado);
    }

    public function test_un_anuncio_no_se_puede_entregar(): void
    {
        $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.guardar_material', $this->clase), ['titulo' => 'Aviso', 'tipo' => 'anuncio', 'publicado' => 1]);
        $anuncio = MaterialClase::where('titulo', 'Aviso')->firstOrFail();

        $this->entregar($anuncio)->assertStatus(422);
    }

    public function test_el_material_despublicado_no_lo_ve_el_estudiante(): void
    {
        $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.guardar_material', $this->clase), ['titulo' => 'Borrador secreto', 'tipo' => 'material', 'publicado' => 0]);

        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.show', $this->clase))->assertOk()->assertDontSee('Borrador secreto');
    }

    // ── 3. Quiz con autocorrección ──────────────────────────────────────────

    public function test_quiz_se_crea_se_resuelve_se_autocorrige_y_pasa_al_libro_de_notas(): void
    {
        $eval = $this->publicarTarea('Quiz de comprensión', 10, ['tipo' => 'evaluacion']);

        $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.quiz.guardar', [$this->clase, $eval]), [
            'intentos_max' => 1, 'autocorreccion' => 1,
            'preguntas' => [
                ['enunciado' => '¿Capital de RD?', 'tipo' => 'multiple', 'puntos' => 5, 'opciones' => [['texto' => 'Santo Domingo', 'correcta' => 1], ['texto' => 'Santiago']]],
                ['enunciado' => 'El mar Caribe es salado', 'tipo' => 'verdadero_falso', 'puntos' => 5, 'correcta_vf' => 'V'],
            ],
        ])->assertSessionDoesntHaveErrors();
        $quiz = ZcQuiz::where('material_id', $eval->id)->with('preguntas.opciones')->firstOrFail();
        $this->assertCount(2, $quiz->preguntas);
        [$p1, $p2] = [$quiz->preguntas[0], $quiz->preguntas[1]];

        // El estudiante comienza
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.quiz.iniciar', [$this->clase, $eval]))->assertOk();
        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $eval]))->assertRedirect();
        $intento = ZcIntento::where('quiz_id', $quiz->id)->where('matricula_id', $this->matricula->id)->firstOrFail();
        $this->assertSame('en_curso', $intento->estado);

        // Responde bien la 1.ª y mal la 2.ª (marca «Falso»)
        $buena = $p1->opciones->firstWhere('es_correcta', true);
        $falso = $p2->opciones->firstWhere('texto', 'Falso');
        $this->actingAs($this->estudiante->user)->postJson(route('portal.estudiante.classroom.quiz.guardar', $intento), ['pregunta_id' => $p1->id, 'opcion_id' => $buena->id])->assertOk();
        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.quiz.enviar', [$this->clase, $eval, $intento]), ["pregunta_{$p2->id}" => $falso->id])->assertRedirect();

        $intento->refresh();
        $this->assertSame('finalizado', $intento->estado);
        $this->assertEquals(5, $intento->puntuacion, '5 de 10 puntos');

        $entrega = EntregaClassroom::where('material_id', $eval->id)->where('matricula_id', $this->matricula->id)->firstOrFail();
        $this->assertSame('calificado', $entrega->estado);
        $this->assertEquals(5, $entrega->calificacion);
        $this->assertSame(50.0, $this->notaTareas(), '5/10 → 50 sobre 100 en el libro de notas');

        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.quiz.resultado', [$this->clase, $eval, $intento]))->assertOk();
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.quiz.resultados', [$this->clase, $eval]))->assertOk();

        // Un solo intento permitido: no puede comenzar otro
        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $eval]))->assertSessionHas('error');
        $this->assertSame(1, ZcIntento::where('quiz_id', $quiz->id)->count());
    }

    public function test_un_intento_finalizado_no_se_puede_reenviar_para_mejorar_la_nota(): void
    {
        $eval = $this->publicarTarea('Quiz único', 10, ['tipo' => 'evaluacion']);
        $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.quiz.guardar', [$this->clase, $eval]), [
            'intentos_max' => 1,
            'preguntas' => [['enunciado' => 'VF', 'tipo' => 'verdadero_falso', 'puntos' => 10, 'correcta_vf' => 'V']],
        ]);
        $quiz = ZcQuiz::where('material_id', $eval->id)->with('preguntas.opciones')->firstOrFail();
        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $eval]));
        $intento = ZcIntento::where('quiz_id', $quiz->id)->firstOrFail();
        $p = $quiz->preguntas[0];

        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.quiz.enviar', [$this->clase, $eval, $intento]), ["pregunta_{$p->id}" => $p->opciones->firstWhere('texto', 'Falso')->id]);
        $this->assertEquals(0, $intento->fresh()->puntuacion);

        // Intenta reenviar con la respuesta correcta sobre el MISMO intento ya cerrado
        $this->actingAs($this->estudiante->user)
            ->post(route('portal.estudiante.classroom.quiz.enviar', [$this->clase, $eval, $intento]), ["pregunta_{$p->id}" => $p->opciones->firstWhere('texto', 'Verdadero')->id])
            ->assertStatus(422);
        $this->assertEquals(0, $intento->fresh()->puntuacion);
    }

    // ── 4. Chat ─────────────────────────────────────────────────────────────

    public function test_el_chat_del_aula_lo_usan_docente_y_estudiante(): void
    {
        $this->actingAs($this->estudiante->user)->postJson(route('portal.estudiante.classroom.chat.store', $this->clase), ['mensaje' => 'Buenos días profe'])->assertSuccessful();
        $this->actingAs($this->docente->user)->postJson(route('portal.docente.classroom.chat.store', $this->clase), ['mensaje' => 'Buenos días, clase'])->assertSuccessful();

        $this->assertSame(2, ClassroomMessage::where('clase_virtual_id', $this->clase->id)->count());
        $this->actingAs($this->estudiante->user)->get(route('portal.estudiante.classroom.chat.index', $this->clase))->assertOk()->assertJsonFragment(['mensaje' => 'Buenos días, clase']);
        $this->actingAs($this->docente->user)->get(route('portal.docente.classroom.chat.index', $this->clase))->assertOk()->assertJsonFragment(['mensaje' => 'Buenos días profe']);
    }

    public function test_enviar_un_mensaje_del_chat_funciona_aunque_el_servidor_de_tiempo_real_este_caido(): void
    {
        // Reverb apuntando a un puerto cerrado: la emisión falla, el mensaje debe guardarse y la respuesta ser 201 (no 500)
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 1,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);

        $this->actingAs($this->estudiante->user)->postJson(route('portal.estudiante.classroom.chat.store', $this->clase), ['mensaje' => 'Hola con Reverb caído'])->assertCreated();

        $this->assertDatabaseHas('classroom_messages', ['clase_virtual_id' => $this->clase->id, 'mensaje' => 'Hola con Reverb caído']);
    }

    // ── 5. Duplicar aula ────────────────────────────────────────────────────

    public function test_duplicar_el_aula_copia_materiales_despublicados_y_no_copia_entregas(): void
    {
        $t = $this->publicarTarea('Tarea a copiar', 20);
        $this->entregar($t);
        $destino = $this->nuevaAsignacion($this->nuevoGrupo('Grado Destino'), $this->docente, 'DU1', 'Lengua B');

        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.duplicar', $this->clase), ['nombre' => 'Lengua (copia)', 'asignacion_id' => $destino->id])
            ->assertSessionDoesntHaveErrors();

        $copia = ClaseVirtual::where('nombre', 'Lengua (copia)')->firstOrFail();
        $this->assertSame($destino->id, $copia->asignacion_id);
        $m = MaterialClase::where('clase_virtual_id', $copia->id)->firstOrFail();
        $this->assertSame('Tarea a copiar', $m->titulo);
        $this->assertFalse((bool) $m->publicado, 'la copia inicia despublicada');
        $this->assertSame(0, EntregaClassroom::where('material_id', $m->id)->count(), 'sin entregas de estudiantes');
    }

    public function test_no_se_puede_duplicar_hacia_la_asignacion_de_otro_docente(): void
    {
        $ajeno = $this->nuevoDocente();
        $destinoAjeno = $this->nuevaAsignacion($this->nuevoGrupo('Grado Ajeno'), $ajeno, 'AJ1', 'Ciencias');

        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.duplicar', $this->clase), ['nombre' => 'Robo', 'asignacion_id' => $destinoAjeno->id])
            ->assertNotFound();
        $this->assertNull(ClaseVirtual::where('nombre', 'Robo')->first());
    }

    // ── 6. Padre ────────────────────────────────────────────────────────────

    private function padreDe(Estudiante $hijo): User
    {
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole('Representante');
        $rep = Representante::create(['user_id' => $u->id, 'cedula' => (string) random_int(100000000, 999999999), 'nombres' => 'Rep', 'apellidos' => 'Prueba' . random_int(1, 9999), 'telefono' => '8090000000']);
        if ($hijo->exists) {
            $rep->estudiantes()->attach($hijo->id, ['es_principal' => true]);
        }

        return $u;
    }

    public function test_el_padre_ve_el_aula_de_su_hijo_con_el_progreso_de_entregas(): void
    {
        $t = $this->publicarTarea('Tarea para el padre', 20);
        $this->entregar($t);
        $padre = $this->padreDe($this->estudiante);

        $this->actingAs($padre)->get(route('portal.padre.hijo.classroom.index', $this->estudiante))->assertOk()->assertSee('Aula de Lengua');
        $this->actingAs($padre)->get(route('portal.padre.hijo.classroom.show', [$this->estudiante, $this->clase]))->assertOk()->assertSee('Tarea para el padre');
    }

    public function test_un_padre_no_ve_el_aula_del_hijo_de_otra_familia(): void
    {
        $otroHijo = Estudiante::factory()->create();
        $this->matricular($otroHijo, $this->grupo);
        $padreAjeno = $this->padreDe($otroHijo);

        // Sabe el id del estudiante ajeno… pero no es su hijo
        $this->actingAs($padreAjeno)->get(route('portal.padre.hijo.classroom.index', $this->estudiante))->assertForbidden();
        $this->actingAs($padreAjeno)->get(route('portal.padre.hijo.classroom.show', [$this->estudiante, $this->clase]))->assertForbidden();
    }

    // ── 7. Aislamiento entre grupos y docentes ──────────────────────────────

    public function test_un_estudiante_de_otro_grupo_no_entra_ni_entrega(): void
    {
        $tarea = $this->publicarTarea('Tarea del grupo A', 20);
        $otro = Estudiante::factory()->create();
        $otro->user->assignRole('Estudiante');
        $otro->user->update(['activo' => true]);
        $this->matricular($otro, $this->nuevoGrupo('Grado Otro'));

        $this->actingAs($otro->user)->get(route('portal.estudiante.classroom.show', $this->clase))->assertForbidden();
        $this->entregar($tarea, $otro->user)->assertForbidden();
        $this->actingAs($otro->user)->get(route('portal.estudiante.classroom.index'))->assertOk()->assertDontSee('Aula de Lengua');
        $this->assertSame(0, EntregaClassroom::where('material_id', $tarea->id)->count());
    }

    public function test_un_docente_ajeno_no_puede_publicar_ni_calificar_en_un_aula_que_no_es_suya(): void
    {
        $tarea = $this->publicarTarea('Tarea protegida', 20);
        $this->entregar($tarea);
        $entrega = EntregaClassroom::where('material_id', $tarea->id)->firstOrFail();
        $ajeno = $this->nuevoDocente();

        $this->actingAs($ajeno->user)->post(route('portal.docente.classroom.guardar_material', $this->clase), ['titulo' => 'Intruso', 'tipo' => 'material'])->assertForbidden();
        $this->actingAs($ajeno->user)->patch(route('portal.docente.classroom.calificar_entrega', [$this->clase, $entrega]), ['calificacion' => 20, 'sincronizar_notas' => 1])->assertForbidden();
        $this->actingAs($ajeno->user)->get(route('portal.docente.classroom.show', $this->clase))->assertForbidden();

        $this->assertNull($entrega->fresh()->calificacion);
        $this->assertNull($this->notaTareas());
    }

    /** Un segundo docente con SU propia aula (la URL pasa la autorización de aula). */
    private function atacante(): array
    {
        $ajeno = $this->nuevoDocente();
        $asig = $this->nuevaAsignacion($this->nuevoGrupo('Grado Atacante'), $ajeno, 'AT' . self::$nivel, 'Otra materia');
        $claseAjena = ClaseVirtual::create(['asignacion_id' => $asig->id, 'nombre' => 'Aula del otro docente', 'activo' => true]);

        return [$ajeno, $claseAjena];
    }

    public function test_un_docente_no_califica_ni_devuelve_entregas_de_otro_docente_usando_su_propia_aula_en_la_url(): void
    {
        $tarea = $this->publicarTarea('Tarea de la víctima', 20);
        $this->entregar($tarea);
        $entrega = EntregaClassroom::where('material_id', $tarea->id)->firstOrFail();
        [$ajeno, $claseAjena] = $this->atacante();

        $this->actingAs($ajeno->user)
            ->patch(route('portal.docente.classroom.calificar_entrega', [$claseAjena, $entrega]), ['calificacion' => 20, 'sincronizar_notas' => 1])
            ->assertNotFound();
        $this->actingAs($ajeno->user)
            ->patch(route('portal.docente.classroom.devolver_entrega', [$claseAjena, $entrega]), ['comentario_docente' => 'x'])
            ->assertNotFound();

        $entrega->refresh();
        $this->assertNull($entrega->calificacion);
        $this->assertSame('entregado', $entrega->estado, 'la entrega ajena no cambió');
        $this->assertNull($this->notaTareas(), 'y no llegó nada al libro de notas');
    }

    public function test_un_docente_no_ve_el_detalle_de_una_entrega_ajena_usando_su_propia_aula(): void
    {
        $tarea = $this->publicarTarea('Tarea privada', 20);
        $this->entregar($tarea);
        $entrega = EntregaClassroom::where('material_id', $tarea->id)->firstOrFail();
        [$ajeno, $claseAjena] = $this->atacante();

        $this->actingAs($ajeno->user)->get(route('portal.docente.classroom.entrega_detalle', [$claseAjena, $tarea, $entrega]))->assertNotFound();
    }

    public function test_un_docente_no_borra_archivos_ni_recursos_de_otro_docente(): void
    {
        $tarea = $this->publicarTarea('Tarea con adjunto', 20);
        $this->actingAs($this->docente->user)
            ->post(route('portal.docente.classroom.subir_archivo', [$this->clase, $tarea]), ['archivo' => UploadedFile::fake()->create('guia.pdf', 20, 'application/pdf')])
            ->assertSuccessful();
        $archivo = \App\Models\ArchivoMaterial::where('material_id', $tarea->id)->firstOrFail();
        $recurso = \App\Models\ZcRecurso::create(['clase_virtual_id' => $this->clase->id, 'titulo' => 'Video', 'tipo' => 'enlace', 'url' => 'https://example.com', 'publico' => true, 'orden' => 1, 'creado_por' => $this->docente->user_id]);
        [$ajeno, $claseAjena] = $this->atacante();

        $this->actingAs($ajeno->user)->delete(route('portal.docente.classroom.eliminar_archivo', [$claseAjena, $archivo]))->assertNotFound();
        $this->actingAs($ajeno->user)->delete(route('portal.docente.classroom.recursos.eliminar', [$claseAjena, $recurso]))->assertNotFound();

        $this->assertNotNull(\App\Models\ArchivoMaterial::find($archivo->id));
        $this->assertNotNull(\App\Models\ZcRecurso::find($recurso->id));

        // El dueño sí puede
        $this->actingAs($this->docente->user)->delete(route('portal.docente.classroom.eliminar_archivo', [$this->clase, $archivo]))->assertRedirect();
        $this->assertNull(\App\Models\ArchivoMaterial::find($archivo->id));
    }

    public function test_un_docente_no_edita_ni_borra_el_quiz_de_otro_docente_usando_su_propia_aula(): void
    {
        $eval = $this->publicarTarea('Quiz ajeno', 10, ['tipo' => 'evaluacion']);
        $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.quiz.guardar', [$this->clase, $eval]), [
            'intentos_max' => 1, 'preguntas' => [['enunciado' => 'VF', 'tipo' => 'verdadero_falso', 'puntos' => 10, 'correcta_vf' => 'V']],
        ]);
        [$ajeno, $claseAjena] = $this->atacante();

        $this->actingAs($ajeno->user)->delete(route('portal.docente.classroom.quiz.eliminar', [$claseAjena, $eval]))->assertNotFound();
        $this->actingAs($ajeno->user)->get(route('portal.docente.classroom.quiz.resultados', [$claseAjena, $eval]))->assertNotFound();
        $this->assertNotNull(ZcQuiz::where('material_id', $eval->id)->first(), 'el quiz sigue ahí');
    }

    public function test_un_estudiante_no_puede_dirigir_la_nota_de_un_quiz_a_otra_actividad(): void
    {
        // Dos quizzes en la misma aula: el intento de A se «envía» apuntando a la URL de B
        $a = $this->publicarTarea('Quiz A', 10, ['tipo' => 'evaluacion']);
        $b = $this->publicarTarea('Quiz B', 10, ['tipo' => 'evaluacion']);
        foreach ([$a, $b] as $m) {
            $this->actingAs($this->docente->user)->post(route('portal.docente.classroom.quiz.guardar', [$this->clase, $m]), [
                'intentos_max' => 1, 'preguntas' => [['enunciado' => 'VF', 'tipo' => 'verdadero_falso', 'puntos' => 10, 'correcta_vf' => 'V']],
            ]);
        }
        $quizA = ZcQuiz::where('material_id', $a->id)->with('preguntas.opciones')->firstOrFail();
        $this->actingAs($this->estudiante->user)->post(route('portal.estudiante.classroom.quiz.comenzar', [$this->clase, $a]));
        $intentoA = ZcIntento::where('quiz_id', $quizA->id)->firstOrFail();
        $p = $quizA->preguntas[0];

        $this->actingAs($this->estudiante->user)
            ->post(route('portal.estudiante.classroom.quiz.enviar', [$this->clase, $b, $intentoA]), ["pregunta_{$p->id}" => $p->opciones->firstWhere('texto', 'Verdadero')->id])
            ->assertNotFound();

        $this->assertSame('en_curso', $intentoA->fresh()->estado, 'el intento no se cerró por la ruta equivocada');
        $this->assertSame(0, EntregaClassroom::where('material_id', $b->id)->count(), 'no se escribió nota en la otra actividad');
        $this->assertNull($this->notaTareas());
    }

    // ── 8. Período cerrado ──────────────────────────────────────────────────

    public function test_calificar_una_tarea_de_un_periodo_cerrado_no_modifica_el_libro_de_notas(): void
    {
        $t = $this->publicarTarea('Tarea periodo cerrado', 20);
        $this->entregar($t);
        $this->periodo->update(['cerrado' => true]);

        $this->calificar(EntregaClassroom::where('material_id', $t->id)->firstOrFail(), 18);

        $this->assertNull($this->notaTareas(), 'un período cerrado no debe recibir notas nuevas desde ZuraClass');
    }
}
