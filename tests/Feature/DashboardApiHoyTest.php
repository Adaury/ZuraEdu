<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\Asistencia;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\Docente;
use App\Models\EntregaTarea;
use App\Models\Estudiante;
use App\Models\FranjaHoraria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Fase 6 del roadmap (Mobile): extender la vista "Hoy" (ya construida en
 * web para Docente [[project_...]] y Padre) a GET /api/v1/dashboard, que es
 * lo que consume la app móvil (mobile/app/(docente)/index.tsx,
 * mobile/app/(padre)/index.tsx). Mismas definiciones que las vistas web,
 * reutilizadas via App\Traits\HasDocenteHoy (docente) y reimplementadas
 * (padre, cuya lógica en PortalPadreController vive inline, no en helpers
 * extraíbles).
 */
class DashboardApiHoyTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        Carbon::setTestNow(Carbon::now()->next(Carbon::MONDAY)->setTime(7, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function diaHoyClave(): string
    {
        $mapa = [0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
                 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];

        return $mapa[now()->dayOfWeek] ?? 'lunes';
    }

    // ── Docente ──────────────────────────────────────────────────────────

    /** @return array{0: User, 1: Docente, 2: Tenant, 3: SchoolYear} */
    private function crearDocenteConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Api Hoy',
            'dominio'            => 'colegioapihoy' . random_int(10000, 99999),
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
            'nombres' => 'Docente', 'apellidos' => 'Api' . random_int(1, 9999), 'estado' => 'activo',
        ]);
        app()->forgetInstance('tenant');

        return [$user, $docente, $tenant, $schoolYear];
    }

    private function crearAsignacionConClaseHoy(
        Tenant $tenant, Docente $docente, SchoolYear $schoolYear,
        string $horaInicio = '08:00:00', string $horaFin = '08:50:00', int $numEstudiantes = 3
    ): array {
        app()->instance('tenant', $tenant);

        self::$nivel++;
        $grado      = Grado::create(['nombre' => 'Grado ApiH' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion    = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo      = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $asignatura = Asignatura::create(['codigo' => 'AH' . random_int(1000, 9999), 'nombre' => 'Materia Hoy ' . random_int(1, 9999), 'activo' => true]);

        $asignacion = Asignacion::create([
            'school_year_id' => $schoolYear->id, 'grupo_id' => $grupo->id,
            'asignatura_id' => $asignatura->id, 'docente_id' => $docente->id, 'activo' => true,
        ]);

        for ($i = 0; $i < $numEstudiantes; $i++) {
            $estudiante = Estudiante::factory()->create();
            Matricula::create([
                'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id,
                'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15',
                'numero_orden' => $i + 1, 'estado' => 'activa',
            ]);
        }

        $horario = Horario::firstOrCreate(
            ['school_year_id' => $schoolYear->id, 'estado' => 'publicado'],
            ['nombre' => 'Horario ' . $schoolYear->id]
        );
        $franja = FranjaHoraria::create(['numero' => random_int(1, 999), 'hora_inicio' => $horaInicio, 'hora_fin' => $horaFin, 'activa' => true]);
        HorarioDetalle::create([
            'horario_id' => $horario->id, 'asignacion_id' => $asignacion->id,
            'franja_id' => $franja->id, 'dia' => $this->diaHoyClave(),
        ]);

        app()->forgetInstance('tenant');

        return [$asignacion, $grupo];
    }

    public function test_docente_ve_proxima_clase_y_asistencia_pendiente_en_la_api(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        $horaFutura = now()->addHours(2)->format('H:i:s');
        $this->crearAsignacionConClaseHoy($tenant, $docente, $sy, $horaFutura, now()->addHours(3)->format('H:i:s'), 3);

        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('role', 'docente');
        $response->assertJsonPath('hoy.asistencia_pendiente_hoy', 1);
        $response->assertJsonPath('hoy.entregas_pendientes', 0);
        $response->assertJsonPath('hoy.al_dia', false);
        $this->assertNotNull($response->json('hoy.proxima_clase'));
    }

    public function test_docente_al_dia_no_tiene_pendientes_en_la_api(): void
    {
        [$user] = $this->crearDocenteConTenant();

        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('hoy.asistencia_pendiente_hoy', 0);
        $response->assertJsonPath('hoy.entregas_pendientes', 0);
        $response->assertJsonPath('hoy.al_dia', true);
    }

    public function test_entregas_sin_revisar_se_cuentan_en_la_api(): void
    {
        [$user, $docente, $tenant, $sy] = $this->crearDocenteConTenant();
        [$asignacion] = $this->crearAsignacionConClaseHoy($tenant, $docente, $sy);

        app()->instance('tenant', $tenant);
        $tarea = Tarea::create([
            'asignacion_id' => $asignacion->id, 'titulo' => 'Tarea Api', 'tipo' => 'tarea', 'activo' => true,
            'fecha_limite' => today()->addDays(3),
        ]);
        $e1 = Estudiante::factory()->create();
        $e2 = Estudiante::factory()->create();
        EntregaTarea::create(['tarea_id' => $tarea->id, 'estudiante_id' => $e1->id, 'estado' => 'entregada']);
        EntregaTarea::create(['tarea_id' => $tarea->id, 'estudiante_id' => $e2->id, 'estado' => 'revisada']);
        app()->forgetInstance('tenant');

        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('hoy.entregas_pendientes', 1);
    }

    // ── Padre ────────────────────────────────────────────────────────────

    /** @return array{0: User, 1: Representante, 2: Tenant, 3: SchoolYear} */
    private function crearRepresentanteConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Api Hoy Padre',
            'dominio'            => 'colegioapihoypadre' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Representante');
        $representante = Representante::create([
            'user_id' => $user->id, 'cedula' => (string) random_int(100000000, 999999999),
            'nombres' => 'Rep', 'apellidos' => 'Api' . random_int(1, 9999), 'telefono' => '8090000000',
        ]);
        app()->forgetInstance('tenant');

        return [$user, $representante, $tenant, $schoolYear];
    }

    private function crearHijo(Tenant $tenant, Representante $representante, SchoolYear $schoolYear): array
    {
        app()->instance('tenant', $tenant);

        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado ApiP' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $userHijo   = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $estudiante = Estudiante::factory()->create(['user_id' => $userHijo->id]);
        $representante->estudiantes()->attach($estudiante->id, ['es_principal' => true]);

        $matricula = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15',
            'numero_orden' => 1, 'estado' => 'activa',
        ]);

        app()->forgetInstance('tenant');

        return [$estudiante, $matricula, $grupo, $userHijo];
    }

    public function test_padre_ve_carnet_de_hoy_en_la_api(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        [$estudiante, , , $userHijo] = $this->crearHijo($tenant, $rep, $sy);

        app()->instance('tenant', $tenant);
        $carnet = CarnetIdentidad::create([
            'tipo' => 'estudiante', 'user_id' => $userHijo->id,
            'numero_carnet' => 'C-' . random_int(1000, 9999), 'qr_token' => 'qr' . random_int(100000, 999999),
            'estado' => 'activo',
        ]);
        CarnetAcceso::create(['carnet_identidad_id' => $carnet->id, 'tipo_evento' => 'entrada', 'estado' => 'presente']);
        app()->forgetInstance('tenant');

        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('hijos.0.hoy.carnet_hoy.tipo', 'entrada');
    }

    public function test_padre_ve_tareas_pendientes_y_proximo_pago_en_la_api(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        [$estudiante, $matricula, $grupo] = $this->crearHijo($tenant, $rep, $sy);

        app()->instance('tenant', $tenant);
        $asignatura = Asignatura::create(['codigo' => 'AP' . random_int(1000, 9999), 'nombre' => 'Materia', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id,
            'docente_id' => null, 'activo' => true,
        ]);
        Tarea::create(['asignacion_id' => $asignacion->id, 'titulo' => 'Tarea Hijo', 'tipo' => 'tarea', 'activo' => true, 'fecha_limite' => today()->addDays(2)]);
        Pago::create([
            'matricula_id' => $matricula->id, 'concepto' => 'Mensualidad', 'monto' => 3500,
            'fecha_vencimiento' => today()->addDays(5), 'estado' => 'pendiente',
        ]);
        app()->forgetInstance('tenant');

        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('hijos.0.hoy.tareas_pendientes', 1);
        $response->assertJsonPath('hijos.0.hoy.proximo_pago.concepto', 'Mensualidad');
    }

    public function test_padre_sin_pendientes_devuelve_nulls_y_ceros(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        $this->crearHijo($tenant, $rep, $sy);

        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('hijos.0.hoy.carnet_hoy', null);
        $response->assertJsonPath('hijos.0.hoy.tareas_pendientes', 0);
        $response->assertJsonPath('hijos.0.hoy.proximo_pago', null);
    }

    public function test_aislamiento_cross_tenant_en_hoy_del_docente(): void
    {
        [$userA, $docenteA, $tenantA, $syA] = $this->crearDocenteConTenant();
        [$userB, $docenteB, $tenantB, $syB] = $this->crearDocenteConTenant();

        $this->crearAsignacionConClaseHoy($tenantB, $docenteB, $syB, numEstudiantes: 5);

        Sanctum::actingAs($userA);
        $response = $this->getJson(route('api.dashboard'));

        $response->assertOk();
        $response->assertJsonPath('hoy.asistencia_pendiente_hoy', 0);
        $response->assertJsonPath('hoy.al_dia', true);
    }
}
