<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\CalificacionAcademica;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido del usuario (2026-09-12, con captura "mal.PNG"): la pantalla web
 * "Boletines por grupo" mostraba, para 1ro-3ro, la tabla simple por período
 * (Materia/Promedio/Indicador con decimales) en vez del boletín consolidado
 * anual con Competencias Fundamentales ya diseñado. Se decidió (confirmado
 * con el usuario): para Primer Ciclo, TODA la pantalla de boletines usa el
 * diseño anual -- sin selector de período, números redondeados sin decimales.
 */
class BoletinesGrupoPrimerCicloTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearEscenario(string $codigo, string $ciclo): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio BG ' . $codigo,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $codigo)) . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $tenant);

        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-BG', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Primer Período', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado BG ' . $codigo, 'nivel' => 1, 'orden' => 1, 'ciclo' => $ciclo, 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $asignatura = Asignatura::create(['codigo' => 'ASIG' . $codigo, 'nombre' => 'Matemática', 'area' => 'academica', 'activo' => true]);
        $asignacion = Asignacion::create(['school_year_id' => $sy->id, 'grupo_id' => $grupo->id, 'asignatura_id' => $asignatura->id, 'activo' => true, 'area' => 'academica']);

        $estudiante = Estudiante::factory()->create();
        $matricula  = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);
        CalificacionAcademica::create([
            'matricula_id' => $matricula->id, 'asignacion_id' => $asignacion->id, 'school_year_id' => $sy->id,
            'nota_final' => 81.4, 'situacion' => 'A',
        ]);

        return compact('tenant', 'sy', 'periodo', 'grupo', 'matricula');
    }

    private function admin(Tenant $tenant): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');
        return $user;
    }

    public function test_primer_ciclo_muestra_diseno_anual_sin_decimales_y_sin_boton_de_periodo(): void
    {
        $e = $this->crearEscenario('PC1', 'primer_ciclo');
        $admin = $this->admin($e['tenant']);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.boletines.grupo', ['grupo_id' => $e['grupo']->id, 'periodo_id' => $e['periodo']->id]));

        $response->assertOk();
        $response->assertSee('Boletín Anual');
        $response->assertSee('Promedio General Anual');
        $response->assertSee('81'); // sin decimales (81.4 -> 81)
        $response->assertDontSee('81.4');
        $response->assertDontSee('PDF Período');
    }

    public function test_segundo_ciclo_mantiene_el_diseno_por_periodo_sin_cambios(): void
    {
        $e = $this->crearEscenario('SC1', 'segundo_ciclo');
        $admin = $this->admin($e['tenant']);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($admin)->get(route('admin.boletines.grupo', ['grupo_id' => $e['grupo']->id, 'periodo_id' => $e['periodo']->id]));

        $response->assertOk();
        $response->assertDontSee('Boletín Anual');
        $response->assertSee('PDF Período');
        $response->assertSee('Materia');
    }
}
