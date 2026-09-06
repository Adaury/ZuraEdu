<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use App\Models\EntregaTarea;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap de producto (docs/ZURAEDU_IMPLEMENTATION_ROADMAP.md, punto 6):
 * vista "Hoy" para el rol Padre — Carnet+ de hoy, tareas pendientes y
 * próximo pago, por hijo. Reutiliza el dashboard ya existente
 * (PortalPadreController::dashboard(), que ya calculaba promedio/alertas/
 * gamificación por hijo) — solo agrega 3 bulk-queries nuevas.
 */
class PortalPadreHoyTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Representante, 2: Tenant, 3: SchoolYear} */
    private function crearRepresentanteConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Padre',
            'dominio'            => 'colegiopadre' . random_int(10000, 99999),
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
            'nombres' => 'Rep', 'apellidos' => 'Prueba' . random_int(1, 9999),
            'telefono' => '8090000000',
        ]);
        app()->forgetInstance('tenant');

        return [$user, $representante, $tenant, $schoolYear];
    }

    /** Crea un hijo matriculado y vinculado al representante, con su propio user_id (para Carnet+). */
    private function crearHijo(Tenant $tenant, Representante $representante, SchoolYear $schoolYear): array
    {
        app()->instance('tenant', $tenant);

        self::$nivel++;
        $grado      = Grado::create(['nombre' => 'Grado P' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion    = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo      = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

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

    // ── 1. Carnet+ hoy ───────────────────────────────────────────────────

    public function test_hijo_con_entrada_registrada_hoy_la_muestra(): void
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

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', fn($h) => $h->first()->_carnetHoy !== null
            && $h->first()->_carnetHoy->tipo_evento === 'entrada');
        $response->assertSee('Entró a las');
    }

    public function test_hijo_sin_registro_de_acceso_hoy_muestra_estado_vacio(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        $this->crearHijo($tenant, $rep, $sy);

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', fn($h) => $h->first()->_carnetHoy === null);
        $response->assertSee('Sin registro de acceso hoy');
    }

    // ── 2. Tareas pendientes ─────────────────────────────────────────────

    public function test_hijo_con_tareas_pendientes_ve_el_conteo_correcto(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        [$estudiante, $matricula, $grupo] = $this->crearHijo($tenant, $rep, $sy);

        app()->instance('tenant', $tenant);
        $asignatura = Asignatura::create(['codigo' => 'AS' . random_int(1000, 9999), 'nombre' => 'Materia', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id, 'activo' => true,
        ]);
        Tarea::create([
            'asignacion_id' => $asignacion->id, 'titulo' => 'Tarea sin entregar', 'tipo' => 'tarea', 'activo' => true,
            'fecha_limite' => today()->addDays(2),
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', fn($h) => $h->first()->_tareasPendientes === 1);
        $response->assertSee('tarea pendiente');
    }

    public function test_hijo_al_dia_con_tareas_no_muestra_alerta(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        [$estudiante, $matricula, $grupo] = $this->crearHijo($tenant, $rep, $sy);

        app()->instance('tenant', $tenant);
        $asignatura = Asignatura::create(['codigo' => 'AS' . random_int(1000, 9999), 'nombre' => 'Materia', 'activo' => true]);
        $asignacion = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id, 'activo' => true,
        ]);
        $tarea = Tarea::create([
            'asignacion_id' => $asignacion->id, 'titulo' => 'Tarea ya entregada', 'tipo' => 'tarea', 'activo' => true,
            'fecha_limite' => today()->addDays(2),
        ]);
        EntregaTarea::create(['tarea_id' => $tarea->id, 'estudiante_id' => $estudiante->id, 'estado' => 'entregada']);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', fn($h) => $h->first()->_tareasPendientes === 0);
        $response->assertDontSee('tarea pendiente');
    }

    // ── 3. Próximo pago ──────────────────────────────────────────────────

    public function test_hijo_con_pago_proximo_lo_muestra(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        [, $matricula] = $this->crearHijo($tenant, $rep, $sy);

        app()->instance('tenant', $tenant);
        Pago::create([
            'matricula_id' => $matricula->id, 'concepto' => 'Mensualidad', 'monto' => 3500,
            'fecha_vencimiento' => today()->addDays(5), 'estado' => 'pendiente',
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', fn($h) => $h->first()->_proximoPago !== null
            && (float) $h->first()->_proximoPago->monto === 3500.0);
        $response->assertSee('RD$ 3,500');
    }

    // ── 4. Múltiples hijos — cada uno con su propio estado ──────────────

    public function test_multiples_hijos_muestran_su_propio_estado_independiente(): void
    {
        [$user, $rep, $tenant, $sy] = $this->crearRepresentanteConTenant();
        [$hijo1, $matricula1] = $this->crearHijo($tenant, $rep, $sy);
        [$hijo2, $matricula2] = $this->crearHijo($tenant, $rep, $sy);

        app()->instance('tenant', $tenant);
        Pago::create([
            'matricula_id' => $matricula1->id, 'concepto' => 'Mensualidad', 'monto' => 1000,
            'fecha_vencimiento' => today()->addDays(3), 'estado' => 'pendiente',
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', function ($hijos) use ($hijo1, $hijo2) {
            $h1 = $hijos->firstWhere('id', $hijo1->id);
            $h2 = $hijos->firstWhere('id', $hijo2->id);
            return $h1->_proximoPago !== null && $h2->_proximoPago === null;
        });
    }

    // ── 5. Aislamiento entre representantes/tenants ─────────────────────

    public function test_un_representante_no_ve_pendientes_de_hijos_de_otro_representante(): void
    {
        [$userA, $repA, $tenantA, $syA] = $this->crearRepresentanteConTenant();
        [$userB, $repB, $tenantB, $syB] = $this->crearRepresentanteConTenant();

        // Hijo de B tiene un pago pendiente.
        [, $matriculaB] = $this->crearHijo($tenantB, $repB, $syB);
        app()->instance('tenant', $tenantB);
        Pago::create([
            'matricula_id' => $matriculaB->id, 'concepto' => 'Mensualidad', 'monto' => 5000,
            'fecha_vencimiento' => today()->addDays(2), 'estado' => 'pendiente',
        ]);
        app()->forgetInstance('tenant');

        // Representante A no tiene hijos.
        $response = $this->actingAs($userA)->get(route('portal.padre.dashboard'));

        $response->assertOk();
        $response->assertViewHas('hijos', fn($h) => $h->isEmpty());
        $response->assertDontSee('RD$ 5,000');
    }
}
