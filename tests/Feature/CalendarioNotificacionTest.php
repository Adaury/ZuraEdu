<?php

namespace Tests\Feature;

use App\Jobs\NotificarEventoCalendarioJob;
use App\Mail\EventoCalendarioMail;
use App\Models\CalendarioAcademico;
use App\Models\CalendarioDestinatario;
use App\Models\Mensaje;
use App\Models\SchoolYear;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Calendario\CalendarioNotificador;
use App\Services\Calendario\IcsBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Calendario escolar → aviso a padres/docentes/personal (mensajería + notificación + correo con .ics) y enlace a Google Calendar.
 * Lo delicado: a quién se avisa (aislado por tenant, solo activos), quién puede descargar el .ics, y que un fallo de correo
 * no deje a la persona sin su mensaje interno.
 */
class CalendarioNotificacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
    }

    private function colegio(string $e): Tenant
    {
        $t = Tenant::create([
            'nombre_institucion' => "Colegio {$e}", 'dominio' => strtolower($e) . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $t);

        return $t;
    }

    private function usuario(string $rol, array $extra = []): User
    {
        $u = User::factory()->create(array_merge(['activo' => true], $extra));
        $u->assignRole($rol);

        return $u;
    }

    private function evento(User $autor, array $extra = []): CalendarioAcademico
    {
        $sy = SchoolYear::firstOrCreate(['nombre' => '2026-2027'], ['fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);

        return CalendarioAcademico::create(array_merge([
            'school_year_id' => $sy->id, 'titulo' => 'Reunión de padres, 3ro; sección A', 'descripcion' => "Traer cédula\nSalón 4",
            'tipo' => 'reunion', 'fecha_inicio' => '2026-11-05', 'hora_inicio' => '18:30', 'color' => '#1e3a6e',
            'aplica_a' => 'docentes', 'creado_por' => $autor->id, 'activo' => true,
        ], $extra));
    }

    // ── .ics ────────────────────────────────────────────────────────────────

    public function test_ics_con_hora_usa_utc_escapa_y_lleva_uid_y_sequence(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $e = $this->evento($admin);
        $e->increment('ics_sequence');

        $ics = (new IcsBuilder())->evento($e->fresh());

        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:Reunión de padres\, 3ro\; sección A', str_replace("\r\n ", '', $ics));
        $this->assertStringContainsString("SEQUENCE:1\r\n", $ics);
        $this->assertMatchesRegularExpression('/UID:calendario-' . $e->id . '-t\d+@/', $ics);
        // 18:30 en America/Santo_Domingo (UTC-4, sin horario de verano) = 22:30 UTC
        $this->assertStringContainsString('DTSTART:20261105T223000Z', $ics);
        $this->assertStringContainsString('DTEND:20261105T233000Z', $ics);
        $this->assertStringContainsString('\nSalón 4', str_replace("\r\n ", '', $ics));
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }

    public function test_ics_sin_hora_es_de_todo_el_dia_y_el_fin_es_exclusivo(): void
    {
        $this->colegio('A');
        $e = $this->evento($this->usuario('Administrador'), ['hora_inicio' => null, 'fecha_fin' => '2026-11-07']);

        $ics = (new IcsBuilder())->evento($e->fresh());

        $this->assertStringContainsString('DTSTART;VALUE=DATE:20261105', $ics);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20261108', $ics);
    }

    public function test_ics_no_deja_inyectar_lineas_ni_pasa_de_75_octetos(): void
    {
        $this->colegio('A');
        $e = $this->evento($this->usuario('Administrador'), ['titulo' => "Hola\r\nATTENDEE:mailto:x@y.z " . str_repeat('á', 80)]);

        $ics = (new IcsBuilder())->evento($e->fresh());

        $this->assertStringNotContainsString("\r\nATTENDEE:", $ics);
        foreach (explode("\r\n", $ics) as $linea) {
            $this->assertLessThanOrEqual(75, strlen($linea));
        }
    }

    public function test_enlace_google_calendar_lleva_titulo_y_fechas(): void
    {
        $this->colegio('A');
        $e = $this->evento($this->usuario('Administrador'), ['hora_inicio' => null]);

        $url = $e->enlaceGoogleCalendar();

        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?action=TEMPLATE', $url);
        $this->assertStringContainsString('dates=20261105%2F20261106', $url);
        $this->assertStringContainsString('text=Reuni%C3%B3n', $url);
    }

    // ── a quién se avisa ────────────────────────────────────────────────────

    public function test_resolver_respeta_grupos_activos_tenant_y_excluye_al_autor(): void
    {
        $a = $this->colegio('A');
        $admin   = $this->usuario('Administrador');
        $padre   = $this->usuario('Representante');
        $docente = $this->usuario('Docente');
        $alumno  = $this->usuario('Estudiante');
        $secre   = $this->usuario('Secretaria');
        $inactivo = $this->usuario('Representante', ['activo' => false]);

        $b = $this->colegio('B');
        $padreAjeno = $this->usuario('Representante');
        app()->instance('tenant', $a);

        $svc = new CalendarioNotificador();

        $ids = fn ($c) => $c->pluck('id')->sort()->values()->all();

        $this->assertSame([$padre->id], $ids($svc->resolver(['padres'], [], $admin->id)));
        $this->assertSame([$docente->id], $ids($svc->resolver(['docentes'], [], $admin->id)));
        // "personal" = quien no es padre/docente/estudiante; el autor queda fuera
        $this->assertSame([$secre->id], $ids($svc->resolver(['personal'], [], $admin->id)));
        // Personas elegidas una a una: un ID de OTRO colegio (y un inactivo) simplemente no se encuentra
        $this->assertSame([$docente->id], $ids($svc->resolver([], [$docente->id, $padreAjeno->id, $inactivo->id], $admin->id)));
        // Un grupo inventado se ignora
        $this->assertSame([], $ids($svc->resolver(['todos_los_usuarios'], [], $admin->id)));
        // Sin duplicados si coincide grupo y persona elegida
        $this->assertSame([$docente->id], $ids($svc->resolver(['docentes'], [$docente->id], $admin->id)));
        $this->assertNotContains($alumno->id, $ids($svc->resolver(['padres', 'docentes', 'personal'], [], $admin->id)));
    }

    public function test_notificar_registra_destinatarios_y_encola_en_lotes_de_50(): void
    {
        Queue::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        User::factory()->count(120)->create(['activo' => true])->each->assignRole('Representante');
        $e = $this->evento($admin);

        $n = (new CalendarioNotificador())->notificar($e, ['padres'], []);

        $this->assertSame(120, $n);
        $this->assertSame(120, CalendarioDestinatario::where('calendario_id', $e->id)->count());
        Queue::assertPushed(NotificarEventoCalendarioJob::class, 3); // 50 + 50 + 20
    }

    // ── padres de grupos específicos ────────────────────────────────────────

    private static int $nivelGrupo = 0;

    private function aula(string $seccion, int $nivel = 1, ?SchoolYear $anio = null): \App\Models\Grupo
    {
        $sy    = $anio ?? SchoolYear::firstOrCreate(['nombre' => '2026-2027'], ['fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $grado = \App\Models\Grado::firstOrCreate(['nivel' => $nivel], ['nombre' => "Grado {$nivel}", 'orden' => $nivel, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $sec   = \App\Models\Seccion::firstOrCreate(['nombre' => $seccion], ['orden' => 1]);

        return \App\Models\Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $sec->id, 'activo' => true]);
    }

    /** Matricula a un estudiante nuevo en el grupo y devuelve [estudiante, matrícula]. */
    private function alumnoEn(\App\Models\Grupo $g, string $estado = 'activa'): \App\Models\Estudiante
    {
        $e = \App\Models\Estudiante::factory()->create();
        \App\Models\Matricula::create([
            'school_year_id' => $g->school_year_id, 'estudiante_id' => $e->id, 'grupo_id' => $g->id,
            'fecha_matricula' => '2026-08-15', 'numero_orden' => ++self::$nivelGrupo, 'estado' => $estado,
        ]);

        return $e;
    }

    /** Crea un representante con su cuenta de usuario y lo vincula a los estudiantes. */
    private function padreDe(\App\Models\Estudiante ...$hijos): User
    {
        $user = $this->usuario('Representante');
        $rep  = \App\Models\Representante::factory()->create(['user_id' => $user->id]);
        foreach ($hijos as $h) {
            $rep->estudiantes()->attach($h->id);
        }

        return $user;
    }

    public function test_padres_de_un_grupo_solo_los_de_los_estudiantes_matriculados_ahi(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $a = $this->aula('A');
        $b = $this->aula('B');

        $padreA   = $this->padreDe($this->alumnoEn($a));
        $padreB   = $this->padreDe($this->alumnoEn($b));
        $sinGrupo = $this->padreDe(\App\Models\Estudiante::factory()->create());   // hijo sin matrícula
        $retirado = $this->padreDe($this->alumnoEn($a, 'retirada'));               // hijo ya no activo en ese grupo

        $ids = (new CalendarioNotificador())->resolver([], [], $admin->id, [$a->id])->pluck('id')->all();

        $this->assertSame([$padreA->id], $ids);
        $this->assertNotContains($padreB->id, $ids);
        $this->assertNotContains($sinGrupo->id, $ids);
        $this->assertNotContains($retirado->id, $ids);
    }

    public function test_un_padre_con_dos_hijos_en_los_grupos_elegidos_se_avisa_una_sola_vez(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $a = $this->aula('A');
        $b = $this->aula('B');
        $padre = $this->padreDe($this->alumnoEn($a), $this->alumnoEn($b));

        $ids = (new CalendarioNotificador())->resolver([], [], $admin->id, [$a->id, $b->id])->pluck('id')->all();

        $this->assertSame([$padre->id], $ids);
    }

    public function test_un_grupo_de_otro_colegio_no_avisa_a_nadie(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $aulaPropia = $this->aula('A');
        $this->padreDe($this->alumnoEn($aulaPropia));

        $this->colegio('B');
        $adminB = $this->usuario('Administrador');
        $aulaB = $this->aula('A');
        $padreB = $this->padreDe($this->alumnoEn($aulaB));

        // Desde el colegio A se pide el grupo del colegio B: no se encuentra
        app()->instance('tenant', \App\Models\Tenant::where('nombre_institucion', 'Colegio A')->first());
        $this->assertSame([], (new CalendarioNotificador())->resolver([], [], $admin->id, [$aulaB->id])->all());
        $this->assertNotContains($padreB->id, (new CalendarioNotificador())->resolver(['padres'], [], $admin->id, [$aulaB->id])->pluck('id')->all());
    }

    public function test_el_formulario_avisa_a_los_padres_del_grupo_elegido(): void
    {
        Queue::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $a = $this->aula('A');
        $b = $this->aula('B');
        $padreA = $this->padreDe($this->alumnoEn($a));
        $this->padreDe($this->alumnoEn($b));

        $this->actingAs($admin)->post(route('admin.calendario.store'), [
            'titulo' => 'Reunión del grupo A', 'tipo' => 'reunion', 'fecha_inicio' => '2026-11-20', 'aplica_a' => 'todos',
            'notificar' => 1, 'notificar_padres_grupos' => [$a->id],
        ])->assertRedirect()->assertSessionHas('success');

        $e = CalendarioAcademico::where('titulo', 'Reunión del grupo A')->firstOrFail();
        $this->assertSame([$padreA->id], $e->destinatarios()->pluck('user_id')->all());
        Queue::assertPushed(NotificarEventoCalendarioJob::class);
    }

    // ── padres de un grado completo ─────────────────────────────────────────

    public function test_padres_de_un_grado_incluye_todas_sus_secciones_y_ningun_otro_grado(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $g1a = $this->aula('A', 1);
        $g1b = $this->aula('B', 1);
        $g2a = $this->aula('A', 2);
        $padre1A = $this->padreDe($this->alumnoEn($g1a));
        $padre1B = $this->padreDe($this->alumnoEn($g1b));
        $padre2  = $this->padreDe($this->alumnoEn($g2a));
        $gradoId = $g1a->grado_id;

        $ids = (new CalendarioNotificador())->resolver([], [], $admin->id, [], [$gradoId])->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$padre1A->id, $padre1B->id])->sort()->values()->all(), $ids);
        $this->assertNotContains($padre2->id, $ids);
    }

    public function test_padres_de_un_grado_ignora_grupos_de_anos_anteriores(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $anterior = SchoolYear::create(['nombre' => '2025-2026', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false]);
        $viejo = $this->aula('A', 1, $anterior);
        $padreViejo = $this->padreDe($this->alumnoEn($viejo));   // matrícula del año pasado, aunque 'activa'
        $actual = $this->aula('A', 1);
        $padreActual = $this->padreDe($this->alumnoEn($actual));

        $ids = (new CalendarioNotificador())->resolver([], [], $admin->id, [], [$actual->grado_id])->pluck('id')->all();

        $this->assertSame([$padreActual->id], $ids);
        $this->assertNotContains($padreViejo->id, $ids);
    }

    public function test_un_grado_de_otro_colegio_no_avisa_a_nadie(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $this->padreDe($this->alumnoEn($this->aula('A', 1)));

        $this->colegio('B');
        $this->usuario('Administrador');
        $aulaB = $this->aula('A', 1);
        $this->padreDe($this->alumnoEn($aulaB));

        app()->instance('tenant', \App\Models\Tenant::where('nombre_institucion', 'Colegio A')->first());
        // El grado del colegio B no existe para el colegio A: no produce ningún grupo ni ningún destinatario
        $this->assertSame([], (new CalendarioNotificador())->gruposDeGrados([$aulaB->grado_id]));
        $this->assertSame([], (new CalendarioNotificador())->resolver([], [], $admin->id, [], [$aulaB->grado_id])->all());
    }

    public function test_el_formulario_avisa_a_los_padres_del_grado_elegido(): void
    {
        Queue::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $g1a = $this->aula('A', 1);
        $g1b = $this->aula('B', 1);
        $g2a = $this->aula('A', 2);
        $p1 = $this->padreDe($this->alumnoEn($g1a));
        $p2 = $this->padreDe($this->alumnoEn($g1b));
        $this->padreDe($this->alumnoEn($g2a));

        $this->actingAs($admin)->post(route('admin.calendario.store'), [
            'titulo' => 'Acto del grado 1', 'tipo' => 'actividad', 'fecha_inicio' => '2026-11-20', 'aplica_a' => 'todos',
            'notificar' => 1, 'notificar_padres_grados' => [$g1a->grado_id],
        ])->assertRedirect()->assertSessionHas('success');

        $e = CalendarioAcademico::where('titulo', 'Acto del grado 1')->firstOrFail();
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $e->destinatarios()->pluck('user_id')->all());
        Queue::assertPushed(NotificarEventoCalendarioJob::class);
    }

    public function test_el_formulario_rechaza_un_grado_inexistente(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);

        $this->actingAs($admin)->post(route('admin.calendario.store'), [
            'titulo' => 'X', 'tipo' => 'otro', 'fecha_inicio' => '2026-11-20', 'aplica_a' => 'todos',
            'notificar' => 1, 'notificar_padres_grados' => [999999],
        ])->assertSessionHasErrors('notificar_padres_grados.0');

        $this->assertSame(0, CalendarioAcademico::count());
    }

    public function test_el_formulario_rechaza_un_grupo_inexistente(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);

        $this->actingAs($admin)->post(route('admin.calendario.store'), [
            'titulo' => 'X', 'tipo' => 'otro', 'fecha_inicio' => '2026-11-20', 'aplica_a' => 'todos',
            'notificar' => 1, 'notificar_padres_grupos' => [999999],
        ])->assertSessionHasErrors('notificar_padres_grupos.0');

        $this->assertSame(0, CalendarioAcademico::count());
    }

    // ── envío ───────────────────────────────────────────────────────────────

    public function test_job_crea_mensaje_interno_y_envia_correo_con_ics_adjunto(): void
    {
        Mail::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $padre = $this->usuario('Representante', ['email' => 'padre@ejemplo.test']);
        $e = $this->evento($admin);
        CalendarioDestinatario::create(['calendario_id' => $e->id, 'user_id' => $padre->id]);

        NotificarEventoCalendarioJob::dispatchSync($e->id, [$padre->id]);

        $msg = Mensaje::where('destinatario_id', $padre->id)->first();
        $this->assertNotNull($msg, 'debe quedar en la bandeja de Mensajes');
        $this->assertSame($admin->id, $msg->remitente_id);
        $this->assertStringContainsString('Nuevo evento', $msg->asunto);

        Mail::assertSent(EventoCalendarioMail::class, function (EventoCalendarioMail $m) use ($padre) {
            return $m->hasTo('padre@ejemplo.test')
                && str_contains($m->envelope()->subject, 'Reunión')
                && count($m->attachments()) === 1;
        });

        $fila = CalendarioDestinatario::where('user_id', $padre->id)->first();
        $this->assertNotNull($fila->notificado_at);
        $this->assertNotNull($fila->correo_enviado_at);
    }

    public function test_si_el_correo_falla_el_mensaje_interno_ya_quedo_y_los_demas_siguen(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $u1 = $this->usuario('Docente', ['email' => 'uno@ejemplo.test']);
        $u2 = $this->usuario('Docente', ['email' => 'dos@ejemplo.test']);
        $e = $this->evento($admin);
        foreach ([$u1, $u2] as $u) {
            CalendarioDestinatario::create(['calendario_id' => $e->id, 'user_id' => $u->id]);
        }
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        NotificarEventoCalendarioJob::dispatchSync($e->id, [$u1->id, $u2->id]);

        $this->assertSame(2, Mensaje::whereIn('destinatario_id', [$u1->id, $u2->id])->count());
        $this->assertSame(2, CalendarioDestinatario::whereNotNull('notificado_at')->count());
        $this->assertSame(0, CalendarioDestinatario::whereNotNull('correo_enviado_at')->count());
    }

    public function test_job_no_avisa_dos_veces_a_la_misma_persona_en_un_reintento(): void
    {
        Mail::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $d = $this->usuario('Docente', ['email' => 'd@ejemplo.test']);
        $e = $this->evento($admin);
        CalendarioDestinatario::create(['calendario_id' => $e->id, 'user_id' => $d->id]);

        NotificarEventoCalendarioJob::dispatchSync($e->id, [$d->id]);
        NotificarEventoCalendarioJob::dispatchSync($e->id, [$d->id]);

        $this->assertSame(1, Mensaje::where('destinatario_id', $d->id)->count());
        Mail::assertSent(EventoCalendarioMail::class, 1);
    }

    // ── creación desde el formulario ────────────────────────────────────────

    public function test_admin_crea_evento_y_avisa_a_padres_y_a_un_docente(): void
    {
        Queue::fake();
        $this->colegio('A');
        $admin   = $this->usuario('Administrador');
        $docente = $this->usuario('Docente');
        $padre   = $this->usuario('Representante');
        SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);

        $this->actingAs($admin)->post(route('admin.calendario.store'), [
            'titulo' => 'Feria de ciencias', 'tipo' => 'actividad', 'fecha_inicio' => '2026-11-20', 'aplica_a' => 'todos',
            'notificar' => 1, 'notificar_grupos' => ['padres'], 'notificar_usuarios' => [$docente->id],
        ])->assertRedirect()->assertSessionHas('success');

        $e = CalendarioAcademico::where('titulo', 'Feria de ciencias')->firstOrFail();
        $this->assertEqualsCanonicalizing([$padre->id, $docente->id], $e->destinatarios()->pluck('user_id')->all());
        Queue::assertPushed(NotificarEventoCalendarioJob::class);
    }

    public function test_sin_marcar_avisar_no_se_notifica_a_nadie(): void
    {
        Queue::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $this->usuario('Representante');
        SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);

        $this->actingAs($admin)->post(route('admin.calendario.store'), [
            'titulo' => 'Solo calendario', 'tipo' => 'otro', 'fecha_inicio' => '2026-11-20', 'aplica_a' => 'todos',
            'notificar_grupos' => ['padres'],
        ])->assertRedirect();

        $this->assertSame(0, CalendarioDestinatario::count());
        Queue::assertNothingPushed();
    }

    public function test_editar_sube_sequence_y_reavisa_a_quien_ya_habia_recibido(): void
    {
        Queue::fake();
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $docente = $this->usuario('Docente');
        $e = $this->evento($admin);
        CalendarioDestinatario::create(['calendario_id' => $e->id, 'user_id' => $docente->id, 'notificado_at' => now(), 'correo_enviado_at' => now()]);

        $this->actingAs($admin)->put(route('admin.calendario.update', $e), [
            'titulo' => 'Reunión (cambió la hora)', 'tipo' => 'reunion', 'fecha_inicio' => '2026-11-05', 'hora_inicio' => '19:00',
            'aplica_a' => 'docentes', 'activo' => 1,
            'notificar' => 1, 'notificar_usuarios' => [$docente->id],
        ])->assertRedirect();

        $this->assertSame(1, $e->fresh()->ics_sequence);
        Queue::assertPushed(NotificarEventoCalendarioJob::class, fn ($j) => $j->actualizacion === true && $j->userIds === [$docente->id]);
    }

    // ── descarga del .ics: quién puede ──────────────────────────────────────

    public function test_ics_lo_descarga_quien_el_evento_le_corresponde_y_no_otro_rol(): void
    {
        $this->colegio('A');
        $admin   = $this->usuario('Administrador');
        $docente = $this->usuario('Docente');
        $alumno  = $this->usuario('Estudiante');
        $e = $this->evento($admin, ['aplica_a' => 'docentes']);

        $r = $this->actingAs($docente)->get(route('calendario.evento.ics', $e));
        $r->assertOk();
        $this->assertStringContainsString('text/calendar', $r->headers->get('Content-Type'));
        $this->assertStringContainsString('BEGIN:VEVENT', $r->getContent());

        $this->actingAs($alumno)->get(route('calendario.evento.ics', $e))->assertNotFound();
        $this->actingAs($alumno)->get(route('calendario.evento.google', $e))->assertNotFound();
    }

    public function test_un_destinatario_elegido_puede_descargar_aunque_su_rol_no_este_en_aplica_a(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $padre = $this->usuario('Representante');
        $e = $this->evento($admin, ['aplica_a' => 'docentes']);

        $this->actingAs($padre)->get(route('calendario.evento.ics', $e))->assertNotFound();

        CalendarioDestinatario::create(['calendario_id' => $e->id, 'user_id' => $padre->id]);

        $this->actingAs($padre)->get(route('calendario.evento.ics', $e))->assertOk();
        $this->actingAs($padre)->get(route('calendario.evento.google', $e))
            ->assertRedirectContains('https://calendar.google.com/calendar/render');
    }

    public function test_no_se_puede_pedir_el_ics_de_otro_colegio(): void
    {
        $a = $this->colegio('A');
        $adminA = $this->usuario('Administrador');
        $eventoA = $this->evento($adminA, ['aplica_a' => 'todos']);

        $this->colegio('B');
        $padreB = $this->usuario('Representante');

        $this->actingAs($padreB)->get(route('calendario.evento.ics', $eventoA))->assertNotFound();
    }

    public function test_el_calendario_del_portal_incluye_los_eventos_a_los_que_fue_elegido(): void
    {
        $this->colegio('A');
        $admin = $this->usuario('Administrador');
        $padre = $this->usuario('Representante');
        $e = $this->evento($admin, ['aplica_a' => 'docentes']);

        $ve = fn () => CalendarioAcademico::visiblesParaUsuario($padre->id, ['todos', 'estudiantes'])->pluck('id')->all();

        $this->assertSame([], $ve());
        CalendarioDestinatario::create(['calendario_id' => $e->id, 'user_id' => $padre->id]);
        $this->assertSame([$e->id], $ve());
    }
}
