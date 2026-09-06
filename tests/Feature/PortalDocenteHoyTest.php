<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\FranjaHoraria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Matricula;
use App\Models\PlanifAnual;
use App\Models\PlanifUnidad;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto (docs/ZURAEDU_ROLE_EXPERIENCE.md, GAP 07): vista "Hoy"
 * para el rol Docente. Reutiliza datos ya calculados en
 * PortalDocenteController::dashboard() (asignaciones, horario, rendimiento)
 * — solo agrega horario de hoy/próxima clase, conteo de asistencia
 * pendiente, entregas ZuraClass sin revisar, y PlanifUnidad vigente
 * (única fuente de "planificación del día" aprobada para esta versión).
 */
class PortalDocenteHoyTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // horario_detalles.dia es un ENUM que solo acepta lunes-viernes
        // (nunca sábado/domingo, confirmado en la migración) — se congela
        // "hoy" a un lunes fijo para que los tests sean deterministas sin
        // importar qué día real se ejecuten. 7:00 a.m., antes de cualquier
        // franja de clase creada en los fixtures (todas ≥ 08:00).
        Carbon::setTestNow(Carbon::now()->next(Carbon::MONDAY)->setTime(7, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Misma clave que PortalDocenteController::diaHoyClave() (privado, se replica aquí para los fixtures). */
    private function diaHoyClave(): string
    {
        $mapa = [0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
                 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];

        return $mapa[now()->dayOfWeek] ?? 'lunes';
    }

    /** @return array{0: User, 1: Docente, 2: Tenant, 3: SchoolYear} */
    private function crearDocenteConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Hoy',
            'dominio'            => 'colegiohoy' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);

        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Docente');
        $docente = Docente::create([
            'user_id' => $user->id, 'cedula' => (string) random_int(100000000, 999999999),
            'nombres' => 'Docente', 'apellidos' => 'Prueba' . random_int(1, 9999),
            'estado' => 'activo',
        ]);

        app()->forgetInstance('tenant');

        return [$user, $docente, $tenant, $schoolYear];
    }

    /**
     * Crea una asignación con clase HOY a las $horaInicio, con $numEstudiantes
     * matriculados activos. No crea Horario/HorarioDetalle si $conHorario es false.
     */
    private function crearAsignacionConClaseHoy(
        Tenant $tenant, Docente $docente, SchoolYear $schoolYear,
        string $horaInicio = '08:00:00', string $horaFin = '08:50:00',
        int $numEstudiantes = 3, bool $conHorario = true
    ): array {
        app()->instance('tenant', $tenant);

        self::$nivel++;
        $grado      = Grado::create(['nombre' => 'Grado H' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion    = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo      = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'AS' . random_int(1000, 9999), 'nombre' => 'Materia ' . random_int(1, 9999), 'activo' => true]);

        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id,
            'asignatura_id' => $asignatura->id, 'docente_id' => $docente->id, 'activo' => true,
        ]);

        for ($i = 0; $i < $numEstudiantes; $i++) {
            $estudiante = \App\Models\Estudiante::factory()->create();
            Matricula::create([
                'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id,
                'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15',
                'numero_orden' => $i + 1, 'estado' => 'activa',
            ]);
        }

        if ($conHorario) {
            $horario = Horario::firstOrCreate(
                ['school_year_id' => $schoolYear->id, 'estado' => 'publicado'],
                ['nombre' => 'Horario ' . $schoolYear->id]
            );
            $franja = FranjaHoraria::create([
                'numero' => random_int(1, 999), 'hora_inicio' => $horaInicio, 'hora_fin' => $horaFin,
                'activa' => true,
            ]);
            HorarioDetalle::create([
                'horario_id' => $horario->id, 'asignacion_id' => $asignacion->id,
                'franja_id' => $franja->id, 'dia' => $this->diaHoyClave(),
            ]);
        }

        app()->forgetInstance('tenant');

        return [$asignacion, $grupo];
    }

    // ── 1. Horario de hoy + próxima clase ──────────────────────────────

    public function test_docente_con_clase_hoy_ve_su_horario_y_proxima_clase(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        // Franja futura respecto a "ahora" para garantizar que aparezca como próxima clase.
        $horaFutura = now()->addHours(2)->format('H:i:s');
        [$asignacion] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, $horaFutura, now()->addHours(3)->format('H:i:s'));

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('horarioHoy', fn($h) => $h->isNotEmpty());
        $response->assertViewHas('proximaClase', fn($p) => $p !== null);
    }

    public function test_docente_sin_clases_hoy_ve_estado_vacio(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        // Asignación existe pero sin HorarioDetalle para hoy.
        $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, conHorario: false);

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('horarioHoy', fn($h) => $h->isEmpty());
        $response->assertViewHas('proximaClase', fn($p) => $p === null);
        $response->assertSee('No tienes clases programadas hoy');
    }

    // ── 2-3. Asistencia pendiente ───────────────────────────────────────

    public function test_asistencia_completa_hoy_no_genera_alerta(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        [$asignacion, $grupo] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, numEstudiantes: 2);

        app()->instance('tenant', $tenant);
        foreach (Matricula::where('grupo_id', $grupo->id)->get() as $mat) {
            Asistencia::create([
                'fecha' => today(), 'matricula_id' => $mat->id, 'asignacion_id' => $asignacion->id,
                'estado' => 'presente', 'registrado_por' => $user->id,
            ]);
        }
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('asistenciaPendienteHoy', 0);
    }

    public function test_asistencia_incompleta_hoy_genera_alerta_con_conteo_correcto(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        // 3 estudiantes matriculados, 0 asistencias registradas → 1 asignación pendiente.
        $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, numEstudiantes: 3);

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('asistenciaPendienteHoy', 1);
        $response->assertSee('sin asistencia hoy');
    }

    // ── 4. Múltiples grupos — agregado correcto ─────────────────────────

    public function test_multiples_grupos_hoy_agrega_el_conteo_de_pendientes_correctamente(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        [$asig1, $grupo1] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, '08:00:00', '08:50:00', 2);
        [$asig2, $grupo2] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, '09:00:00', '09:50:00', 2);

        // Solo la primera asignación tiene asistencia completa hoy.
        app()->instance('tenant', $tenant);
        foreach (Matricula::where('grupo_id', $grupo1->id)->get() as $mat) {
            Asistencia::create(['fecha' => today(), 'matricula_id' => $mat->id, 'asignacion_id' => $asig1->id, 'estado' => 'presente', 'registrado_por' => $user->id]);
        }
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('asistenciaPendienteHoy', 1); // solo asig2 pendiente
        $response->assertViewHas('horarioHoy', fn($h) => $h->count() === 2);
    }

    // ── 5. Entregas de ZuraClass sin revisar ────────────────────────────

    public function test_entregas_sin_revisar_se_cuentan_correctamente(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        [$asignacion] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, conHorario: false);

        app()->instance('tenant', $tenant);
        $tarea = Tarea::create([
            'asignacion_id' => $asignacion->id, 'titulo' => 'Tarea 1', 'tipo' => 'tarea', 'activo' => true,
            'fecha_limite' => today()->addDays(3),
        ]);
        $estudiante1 = \App\Models\Estudiante::factory()->create();
        $estudiante2 = \App\Models\Estudiante::factory()->create();
        EntregaTarea::create(['tarea_id' => $tarea->id, 'estudiante_id' => $estudiante1->id, 'estado' => 'entregada']);
        EntregaTarea::create(['tarea_id' => $tarea->id, 'estudiante_id' => $estudiante2->id, 'estado' => 'revisada']);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('entregasPendientes', 1); // solo la 'entregada' cuenta, no la 'revisada'
        $response->assertSee('sin revisar en ZuraClass');
    }

    // ── 6. Asignaciones sin notas (reutiliza $rendimiento) ─────────────

    public function test_asignacion_sin_notas_genera_alerta(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        // Estudiante matriculado sin ninguna CalificacionAcademica → sin_nota > 0.
        $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, conHorario: false, numEstudiantes: 1);

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('asignacionesSinNotas', fn($c) => $c->count() === 1);
        $response->assertSee('sin nota');
    }

    // ── 7. Estado positivo: docente al día ─────────────────────────────

    public function test_docente_sin_ningun_pendiente_ve_estado_positivo(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        // Sin asignaciones en absoluto → nada pendiente, nada que mostrar.

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('asistenciaPendienteHoy', 0);
        $response->assertViewHas('entregasPendientes', 0);
        $response->assertSee('Estás al día');
    }

    // ── 8. Planificación del día — solo PlanifUnidad vigente ───────────

    public function test_planificacion_del_dia_muestra_la_unidad_vigente(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        [$asignacion] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy);

        app()->instance('tenant', $tenant);
        $anual = PlanifAnual::create([
            'docente_id' => $docente->id, 'asignacion_id' => $asignacion->id,
            'school_year_id' => $sy->id, 'titulo' => 'Plan Anual',
        ]);
        PlanifUnidad::create([
            'planif_anual_id' => $anual->id, 'numero' => 1, 'titulo' => 'Unidad Vigente Hoy',
            'fecha_inicio' => today()->subDays(2), 'fecha_fin' => today()->addDays(2),
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertSee('Unidad Vigente Hoy');
    }

    public function test_sin_planificacion_vigente_muestra_mensaje_explicito(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        $this->crearAsignacionConClaseHoy($tenant, $docente, $sy);
        // Sin ninguna PlanifUnidad creada.

        $response = $this->actingAs($user)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertSee('Sin planificación registrada');
    }

    // ── 9. Aislamiento multi-tenant y entre docentes ───────────────────

    public function test_un_docente_no_ve_pendientes_de_otro_docente_ni_de_otro_tenant(): void
    {
        [$userA, $docenteA, $tenantA, $syA] = $this->crearDocenteConTenant();
        [$userB, $docenteB, $tenantB, $syB] = $this->crearDocenteConTenant();

        // Docente B tiene una clase hoy con asistencia pendiente.
        $this->crearAsignacionConClaseHoy($tenantB, $docenteB, $syB, numEstudiantes: 5);

        // Docente A no tiene ninguna asignación.
        $response = $this->actingAs($userA)->get(route('portal.docente.dashboard'));

        $response->assertOk();
        $response->assertViewHas('asistenciaPendienteHoy', 0);
        $response->assertViewHas('horarioHoy', fn($h) => $h->isEmpty());
        $response->assertSee('Estás al día');
    }
}
