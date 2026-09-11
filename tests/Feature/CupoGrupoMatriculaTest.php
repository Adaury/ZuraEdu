<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría Don Bosco (Sección 2, "Concurrencia y rendimiento"):
 * grupos.capacidad existía pero ningún punto de entrada de matrícula lo
 * comprobaba -- un grupo podía sobre-matricularse sin límite. Cubre los 4
 * puntos de entrada señalados: MatriculaController::store()/storeMasivo()
 * e InscripcionController::asignar()/asignarMasivo().
 */
class CupoGrupoMatriculaTest extends TestCase
{
    use RefreshDatabase;

    private static int $nivel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(Tenant $tenant): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');
        return $user;
    }

    /** @return array{tenant: Tenant, sy: SchoolYear, grupo: Grupo} */
    private function crearGrupo(int $capacidad): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Cupo',
            'dominio'            => 'colegiocupo' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        self::$nivel++;
        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado Cupo' . random_int(1, 99999), 'nivel' => self::$nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true, 'capacidad' => $capacidad]);
        app()->forgetInstance('tenant');

        return compact('tenant', 'sy', 'grupo');
    }

    private function matricular(Tenant $tenant, SchoolYear $sy, Grupo $grupo, int $numero): Matricula
    {
        app()->instance('tenant', $tenant);
        $estudiante = Estudiante::factory()->create();
        $m = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => $numero, 'estado' => 'activa',
        ]);
        app()->forgetInstance('tenant');

        return $m;
    }

    // ── MatriculaController::store() ────────────────────────────────────

    public function test_store_rechaza_matricular_en_un_grupo_lleno(): void
    {
        ['tenant' => $tenant, 'sy' => $sy, 'grupo' => $grupo] = $this->crearGrupo(capacidad: 1);
        $this->matricular($tenant, $sy, $grupo, 1); // ya al 100% de cupo

        app()->instance('tenant', $tenant);
        $nuevoEstudiante = Estudiante::factory()->create();
        app()->forgetInstance('tenant');

        $admin = $this->admin($tenant);
        $response = $this->actingAs($admin)->post(route('admin.matriculas.store'), [
            'school_year_id' => $sy->id, 'estudiante_id' => $nuevoEstudiante->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => today()->toDateString(),
        ]);

        $response->assertSessionHasErrors('grupo_id');
        $this->assertDatabaseMissing('matriculas', ['estudiante_id' => $nuevoEstudiante->id]);
    }

    public function test_store_permite_matricular_si_hay_cupo(): void
    {
        ['tenant' => $tenant, 'sy' => $sy, 'grupo' => $grupo] = $this->crearGrupo(capacidad: 2);
        $this->matricular($tenant, $sy, $grupo, 1); // 1 de 2

        app()->instance('tenant', $tenant);
        $nuevoEstudiante = Estudiante::factory()->create();
        app()->forgetInstance('tenant');

        $admin = $this->admin($tenant);
        $response = $this->actingAs($admin)->post(route('admin.matriculas.store'), [
            'school_year_id' => $sy->id, 'estudiante_id' => $nuevoEstudiante->id,
            'grupo_id' => $grupo->id, 'fecha_matricula' => today()->toDateString(),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('matriculas', ['estudiante_id' => $nuevoEstudiante->id, 'estado' => 'activa']);
    }

    // ── MatriculaController::storeMasivo() ──────────────────────────────

    public function test_store_masivo_solo_matricula_hasta_donde_alcanza_el_cupo(): void
    {
        ['tenant' => $tenant, 'sy' => $sy, 'grupo' => $grupo] = $this->crearGrupo(capacidad: 2);
        $this->matricular($tenant, $sy, $grupo, 1); // 1 de 2, queda 1 cupo

        app()->instance('tenant', $tenant);
        $e1 = Estudiante::factory()->create();
        $e2 = Estudiante::factory()->create();
        app()->forgetInstance('tenant');

        $admin = $this->admin($tenant);
        $response = $this->actingAs($admin)->post(route('admin.matriculas.masiva'), [
            'grupo_id' => $grupo->id, 'estudiante_ids' => [$e1->id, $e2->id],
            'fecha_matricula' => today()->toDateString(),
        ]);

        $response->assertSessionHas('warning');
        $this->assertEquals(2, Matricula::where('grupo_id', $grupo->id)->where('estado', 'activa')->count(), 'El grupo no debe superar su capacidad de 2.');
    }

    // ── InscripcionController::asignar() ─────────────────────────────────

    public function test_asignar_rechaza_si_el_grupo_esta_lleno(): void
    {
        ['tenant' => $tenant, 'sy' => $sy, 'grupo' => $grupo] = $this->crearGrupo(capacidad: 1);
        $this->matricular($tenant, $sy, $grupo, 1);

        app()->instance('tenant', $tenant);
        $estudiante = Estudiante::factory()->create();
        $inscripcion = Inscripcion::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $estudiante->id, 'estado' => 'pendiente',
            'fecha_inscripcion' => today(),
        ]);
        app()->forgetInstance('tenant');

        $admin = $this->admin($tenant);
        $response = $this->actingAs($admin)->post(route('admin.inscripciones.asignar', $inscripcion), [
            'grupo_id' => $grupo->id,
        ]);

        $response->assertSessionHasErrors('grupo_id');
        $this->assertDatabaseMissing('matriculas', ['estudiante_id' => $estudiante->id]);
    }

    // ── InscripcionController::asignarMasivo() ───────────────────────────

    public function test_asignar_masivo_solo_asigna_hasta_donde_alcanza_el_cupo(): void
    {
        ['tenant' => $tenant, 'sy' => $sy, 'grupo' => $grupo] = $this->crearGrupo(capacidad: 1);

        app()->instance('tenant', $tenant);
        $e1 = Estudiante::factory()->create();
        $e2 = Estudiante::factory()->create();
        $i1 = Inscripcion::create(['school_year_id' => $sy->id, 'estudiante_id' => $e1->id, 'estado' => 'pendiente', 'fecha_inscripcion' => today()]);
        $i2 = Inscripcion::create(['school_year_id' => $sy->id, 'estudiante_id' => $e2->id, 'estado' => 'pendiente', 'fecha_inscripcion' => today()]);
        app()->forgetInstance('tenant');

        $admin = $this->admin($tenant);
        $response = $this->actingAs($admin)->post(route('admin.inscripciones.asignar-masivo'), [
            'ids' => [$i1->id, $i2->id], 'grupo_id' => $grupo->id,
        ]);

        $response->assertSessionHas('warning');
        $this->assertEquals(1, Matricula::where('grupo_id', $grupo->id)->where('estado', 'activa')->count(), 'El grupo no debe superar su capacidad de 1.');
    }
}
