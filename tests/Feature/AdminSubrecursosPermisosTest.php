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
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Permisos',
            'dominio'            => 'colegiopermisos' . random_int(10000, 99999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);
        return $user;
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
}
