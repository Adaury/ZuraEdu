<?php

namespace Tests\Feature;

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
 * Auditoría Don Bosco (Sección 6, "Consistencia de registros"): había dos
 * caminos de "dar de baja" -- RegistroAcademicoController sí sincronizaba
 * estudiantes.estado, pero MatriculaController::cambiarEstado()/destroy()
 * (el botón "Estado" de la ficha de matrícula) no lo hacía en absoluto. Un
 * estudiante podía quedar con su matrícula 'retirada' pero
 * estudiantes.estado='activo' -- exactamente la queja textual del informe
 * original ("estudiantes retirados que continúan activos"). Ambos
 * controladores ahora comparten App\Traits\SincronizaEstadoEstudiante.
 */
class SincronizaEstadoEstudianteTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Tenant, 2: SchoolYear} */
    private function crearAdminConTenant(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Sync Estado',
            'dominio'            => 'colegiosyncestado' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return [$user, $tenant, $schoolYear];
    }

    private function crearMatriculaActiva(SchoolYear $schoolYear, ?Estudiante $estudiante = null): Matricula
    {
        self::$nivel++;
        $grado   = Grado::create(['nombre' => 'Grado Sync' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante ??= Estudiante::factory()->create(['estado' => 'activo']);

        return Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-08-15',
            'numero_orden' => 1, 'estado' => 'activa',
        ]);
    }

    // ── MatriculaController::cambiarEstado() ────────────────────────────

    public function test_retirar_matricula_desde_la_ficha_desactiva_al_estudiante(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.matriculas.estado', $matricula), ['estado' => 'retirada']);
        $response->assertRedirect();

        $matricula->refresh();
        $this->assertSame('retirada', $matricula->estado);
        $this->assertSame('inactivo', $matricula->estudiante->fresh()->estado);
    }

    public function test_reactivar_matricula_desde_la_ficha_reactiva_al_estudiante(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        $matricula->update(['estado' => 'retirada']);
        $matricula->estudiante->update(['estado' => 'inactivo']);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.matriculas.estado', $matricula), ['estado' => 'activa']);

        $response->assertRedirect();
        $this->assertSame('activo', $matricula->estudiante->fresh()->estado);
    }

    /**
     * Bug real encontrado al probar este endpoint por primera vez (nunca
     * tuvo un test): 'motivo' es nullable, pero si el request no envía la
     * clave en absoluto (como hace el formulario real cuando el textarea
     * queda vacío y el navegador a veces omite el campo, o cualquier
     * llamado directo sin ese campo), `$data['motivo']` no existe y el
     * controlador respondía 500 sin aplicar ningún cambio.
     */
    public function test_cambiar_estado_sin_enviar_motivo_no_falla(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.matriculas.estado', $matricula), ['estado' => 'retirada']);

        $response->assertRedirect();
        $this->assertSame('retirada', $matricula->fresh()->estado);
    }

    public function test_eliminar_matricula_sin_calificaciones_ni_asistencias_desactiva_al_estudiante(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        app()->forgetInstance('tenant');

        $this->actingAs($user)->delete(route('admin.matriculas.destroy', $matricula));

        $matricula->refresh();
        $this->assertSame('retirada', $matricula->estado);
        $this->assertSame('inactivo', $matricula->estudiante->fresh()->estado);
    }

    // ── Caso de seguridad: otra matrícula activa no debe desactivarse ───

    public function test_retirar_una_matricula_no_desactiva_al_estudiante_si_tiene_otra_matricula_activa(): void
    {
        [$user, $tenant, $sy1] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $sy2 = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => false,
        ]);
        $estudiante = Estudiante::factory()->create(['estado' => 'activo']);
        $matriculaAntigua = $this->crearMatriculaActiva($sy1, $estudiante);
        $matriculaVigente = $this->crearMatriculaActiva($sy2, $estudiante);
        app()->forgetInstance('tenant');

        // Se retira la matrícula del año anterior; la del año vigente sigue activa.
        $this->actingAs($user)->patch(route('admin.matriculas.estado', $matriculaAntigua), ['estado' => 'retirada']);

        $this->assertSame('activo', $estudiante->fresh()->estado, 'No debe desactivarse si aún tiene otra matrícula activa.');
        $this->assertSame('activa', $matriculaVigente->fresh()->estado);
    }

    // ── RegistroAcademicoController — regresión (ya funcionaba, confirmar que sigue igual) ──

    public function test_registrar_baja_desde_registro_academico_sigue_desactivando_al_estudiante(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        app()->forgetInstance('tenant');

        $this->actingAs($user)->post(route('admin.registro-academico.baja.registrar', $matricula), [
            'tipo' => 'retirada', 'fecha_baja' => today()->toDateString(), 'motivo_baja' => 'Mudanza',
        ]);

        $this->assertSame('inactivo', $matricula->estudiante->fresh()->estado);
    }

    public function test_reactivar_desde_registro_academico_sigue_reactivando_al_estudiante(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        $matricula->update(['estado' => 'retirada']);
        $matricula->estudiante->update(['estado' => 'inactivo']);
        app()->forgetInstance('tenant');

        $this->actingAs($user)->patch(route('admin.registro-academico.baja.reactivar', $matricula));

        $this->assertSame('activo', $matricula->estudiante->fresh()->estado);
    }

    public function test_registrar_traslado_desde_registro_academico_sigue_desactivando_al_estudiante(): void
    {
        [$user, $tenant, $sy] = $this->crearAdminConTenant();
        app()->instance('tenant', $tenant);
        $matricula = $this->crearMatriculaActiva($sy);
        $estudiante = $matricula->estudiante;
        app()->forgetInstance('tenant');

        $this->actingAs($user)->post(route('admin.registro-academico.traslado.registrar', $estudiante), [
            'institucion_traslado' => 'Otro Colegio', 'fecha_baja' => today()->toDateString(),
        ]);

        $this->assertSame('inactivo', $estudiante->fresh()->estado);
    }

    public function test_aislamiento_cross_tenant(): void
    {
        [$userA, $tenantA, $syA] = $this->crearAdminConTenant();
        [, $tenantB, $syB] = $this->crearAdminConTenant();

        app()->instance('tenant', $tenantB);
        $matriculaB = $this->crearMatriculaActiva($syB);
        app()->forgetInstance('tenant');

        // Un admin de otro tenant no debe poder ni encontrar la matrícula.
        $response = $this->actingAs($userA)->patch(route('admin.matriculas.estado', $matriculaB), ['estado' => 'retirada']);

        $response->assertStatus(404);

        // La request de arriba deja el tenant A vinculado en el contenedor
        // (ResolveTenant lo vincula durante el request y no se desvincula
        // solo) -- hay que volver a tenant B explícitamente para poder leer
        // su propio estudiante sin que el scope global lo filtre a cero.
        app()->instance('tenant', $tenantB);
        $this->assertSame('activo', $matriculaB->estudiante->fresh()->estado);
        app()->forgetInstance('tenant');
    }
}
