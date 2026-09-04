<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Hallazgo Alto de la auditoría completa 2026-09-04 (H3): varios
 * sub-recursos de routes/admin/sistema.php y routes/admin/reportes.php
 * (school-years, periodos, especialidades técnicas, malla curricular,
 * log de actividad, generación de alertas, CRUD de calendario) solo
 * dependían del gate genérico admin.access, sin el permiso Spatie
 * específico que ya existía en RolesSeeder — cualquier rol admin-capaz
 * (incluso Biblioteca o Recepción) podía tocarlos por URL directa.
 */
class AdminSubrecursosPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function usuario(string $rol): User
    {
        return $this->usuarioConTenant($rol)[0];
    }

    /** @return array{0: User, 1: Tenant} */
    private function usuarioConTenant(string $rol): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Permisos',
            'dominio'            => 'colegiopermisos' . random_int(10000, 99999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);
        return [$user, $tenant];
    }

    public static function rutasBloqueadasProvider(): array
    {
        return [
            'school-years sin gestionar-school-years' => ['admin.school-years.index', 'get'],
            'periodos sin gestionar-periodos'         => ['admin.periodos.index', 'get'],
            'especialidades sin gestionar-asignaturas'=> ['admin.especialidades.index', 'get'],
            'malla curricular sin gestionar-asignaturas' => ['admin.malla.index', 'get'],
            'log de actividad sin acceso-direccion'   => ['admin.sistema.actividad', 'get'],
            'estadisticas sin ver-reportes-institucionales' => ['admin.sistema.estadisticas', 'get'],
            'generar alertas sin supervisar-registros'=> ['admin.alertas.generarAcademicas', 'post'],
            'crear evento calendario sin acceso-direccion-coordinacion' => ['admin.calendario.create', 'get'],
        ];
    }

    #[DataProvider('rutasBloqueadasProvider')]
    public function test_biblioteca_no_puede_acceder(string $routeName, string $method): void
    {
        // Biblioteca solo tiene ver-dashboard, gestionar-biblioteca y
        // ver-servicios — ninguno de los permisos que protegen estas rutas.
        $user = $this->usuario('Biblioteca');

        $this->actingAs($user)
            ->{$method}(route($routeName))
            ->assertForbidden();
    }

    public function test_administrador_si_puede_acceder_a_school_years(): void
    {
        $user = $this->usuario('Administrador');

        $this->actingAs($user)
            ->get(route('admin.school-years.index'))
            ->assertOk();
    }

    public function test_administrador_si_puede_acceder_a_periodos(): void
    {
        $user = $this->usuario('Administrador');

        $this->actingAs($user)
            ->get(route('admin.periodos.index'))
            ->assertOk();
    }

    public function test_administrador_si_puede_acceder_a_especialidades(): void
    {
        $user = $this->usuario('Administrador');

        $this->actingAs($user)
            ->get(route('admin.especialidades.index'))
            ->assertOk();
    }

    public function test_administrador_si_puede_acceder_al_log_de_actividad(): void
    {
        $user = $this->usuario('Administrador');

        $this->actingAs($user)
            ->get(route('admin.sistema.actividad'))
            ->assertOk();
    }

    public function test_coordinador_academico_si_puede_crear_evento_de_calendario(): void
    {
        $user = $this->usuario('Coordinador Académico');

        $this->actingAs($user)
            ->get(route('admin.calendario.create'))
            ->assertOk();
    }

    public function test_recepcion_no_puede_generar_alertas_ni_editar_calendario(): void
    {
        // Recepción tampoco tiene supervisar-registros ni
        // acceso-direccion-coordinacion.
        $user = $this->usuario('Recepción');

        $this->actingAs($user)
            ->post(route('admin.alertas.generarAcademicas'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.calendario.create'))
            ->assertForbidden();
    }

    public function test_coordinador_no_puede_recalcular_promociones_de_su_grupo(): void
    {
        // Coordinador Académico tiene ingresar-calificaciones (necesario
        // para capturar notas) pero calcular-promociones escribe en la
        // misma tabla `promociones` que el cierre de año oficial (solo
        // Dirección) — un coordinador no debe poder sobrescribirla desde
        // /registro. (Docente/Docente Académico/Técnico/Guía no se prueban
        // aquí porque EnsureAdminAccess los redirige fuera de /admin antes
        // de llegar a esta ruta — nunca podrían intentarlo siquiera.)
        [$docente, $tenant] = $this->usuarioConTenant('Coordinador Académico');

        app()->instance('tenant', $tenant);
        $schoolYear = \App\Models\SchoolYear::create([
            'nombre' => '2026-2027', 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(10), 'activo' => true,
        ]);
        $grado = \App\Models\Grado::create(['nombre' => '1ro', 'nivel' => 1, 'ciclo' => 'primer_ciclo', 'orden' => 1, 'activo' => true]);
        $seccion = \App\Models\Seccion::create(['nombre' => 'A', 'orden' => 1]);
        $grupo = \App\Models\Grupo::create([
            'school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true,
        ]);
        app()->forgetInstance('tenant');

        $this->actingAs($docente)
            ->post(route('admin.registro.calcular-promociones', $grupo))
            ->assertForbidden();
    }

    public function test_administrador_si_puede_recalcular_promociones_de_su_grupo(): void
    {
        [$admin, $tenant] = $this->usuarioConTenant('Administrador');

        app()->instance('tenant', $tenant);
        $schoolYear = \App\Models\SchoolYear::create([
            'nombre' => '2026-2027', 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(10), 'activo' => true,
        ]);
        $grado = \App\Models\Grado::create(['nombre' => '1ro', 'nivel' => 1, 'ciclo' => 'primer_ciclo', 'orden' => 1, 'activo' => true]);
        $seccion = \App\Models\Seccion::create(['nombre' => 'A', 'orden' => 1]);
        $grupo = \App\Models\Grupo::create([
            'school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)
            ->post(route('admin.registro.calcular-promociones', $grupo));

        // No debe ser 403 (bloqueado por permiso) — puede ser 200 con lista
        // vacía de resultados, ya que el grupo no tiene matrículas.
        $response->assertStatus(200);
    }
}
