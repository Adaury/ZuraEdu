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
 * El grupo llega del navegador y es independiente del año escolar: antes se podía matricular (individual o masivamente)
 * y cambiar de grupo hacia un grupo de OTRO año, dejando la matrícula incoherente. Ahora el grupo debe ser del año de la matrícula.
 */
class MatriculaGrupoAnioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{admin: User, anioActivo: SchoolYear, anioViejo: SchoolYear, grupoActivo: Grupo, grupoViejo: Grupo} */
    private function escenario(): array
    {
        $t = Tenant::create([
            'nombre_institucion' => 'Colegio Grupo Año',
            'dominio'            => 'colegioga' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $t);

        $anioActivo = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $anioViejo  = SchoolYear::create(['nombre' => '2025-2026', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $t->id]);
        $admin->assignRole('Administrador');
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);

        $grupo = function (SchoolYear $anio, int $nivel) use ($seccion) {
            $grado = Grado::create(['nombre' => "Grado GA{$nivel}", 'nivel' => $nivel, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);

            return Grupo::create(['school_year_id' => $anio->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true, 'capacidad' => 30]);
        };

        return [
            'admin' => $admin, 'anioActivo' => $anioActivo, 'anioViejo' => $anioViejo,
            'grupoActivo' => $grupo($anioActivo, 111), 'grupoViejo' => $grupo($anioViejo, 112),
        ];
    }

    public function test_matricular_en_un_grupo_de_otro_anio_se_rechaza(): void
    {
        $e = $this->escenario();
        $est = Estudiante::factory()->create();

        $this->actingAs($e['admin'])->post(route('admin.matriculas.store'), [
            'school_year_id' => $e['anioActivo']->id, 'estudiante_id' => $est->id,
            'grupo_id' => $e['grupoViejo']->id, 'fecha_matricula' => '2026-09-01',
        ])->assertSessionHasErrors('grupo_id');

        $this->assertSame(0, Matricula::count());
    }

    public function test_matricular_en_un_grupo_del_mismo_anio_funciona(): void
    {
        $e = $this->escenario();
        $est = Estudiante::factory()->create();

        $this->actingAs($e['admin'])->post(route('admin.matriculas.store'), [
            'school_year_id' => $e['anioActivo']->id, 'estudiante_id' => $est->id,
            'grupo_id' => $e['grupoActivo']->id, 'fecha_matricula' => '2026-09-01',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Matricula::count());
    }

    public function test_la_matricula_masiva_rechaza_un_grupo_que_no_es_del_anio_activo(): void
    {
        $e = $this->escenario();
        $ests = Estudiante::factory()->count(2)->create();

        $this->actingAs($e['admin'])->post(route('admin.matriculas.masiva'), [
            'grupo_id' => $e['grupoViejo']->id, 'estudiante_ids' => $ests->pluck('id')->all(), 'fecha_matricula' => '2026-09-01',
        ])->assertSessionHasErrors('grupo_id');
        $this->assertSame(0, Matricula::count());

        $this->actingAs($e['admin'])->post(route('admin.matriculas.masiva'), [
            'grupo_id' => $e['grupoActivo']->id, 'estudiante_ids' => $ests->pluck('id')->all(), 'fecha_matricula' => '2026-09-01',
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, Matricula::count());
    }

    public function test_cambiar_de_grupo_no_puede_sacar_la_matricula_de_su_anio(): void
    {
        $e = $this->escenario();
        $otroActivo = Grupo::create([
            'school_year_id' => $e['anioActivo']->id,
            'grado_id' => Grado::create(['nombre' => 'Grado GA113', 'nivel' => 113, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true])->id,
            'seccion_id' => $e['grupoActivo']->seccion_id, 'activo' => true, 'capacidad' => 30,
        ]);
        $m = Matricula::create([
            'school_year_id' => $e['anioActivo']->id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'grupo_id' => $e['grupoActivo']->id, 'fecha_matricula' => '2026-09-01', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $this->actingAs($e['admin'])->patch(route('admin.matriculas.cambiarGrupo', $m), ['grupo_id' => $e['grupoViejo']->id])->assertSessionHasErrors('grupo_id');
        $this->assertSame($e['grupoActivo']->id, $m->fresh()->grupo_id, 'No debe haber cambiado de grupo.');

        $this->actingAs($e['admin'])->patch(route('admin.matriculas.cambiarGrupo', $m), ['grupo_id' => $otroActivo->id])->assertSessionHasNoErrors();
        $this->assertSame($otroActivo->id, $m->fresh()->grupo_id);
    }

    // ── doble clic / envíos simultáneos del mismo estudiante ─────────────────

    /** Una segunda matrícula idéntica ya confirmada por "otra petición" justo antes de que esta cree la suya. */
    public function test_doble_clic_el_mismo_estudiante_da_validacion_y_no_un_500(): void
    {
        $e = $this->escenario();
        $est = Estudiante::factory()->create();

        // Simula la carrera: la otra petición confirma su INSERT después del chequeo previo y antes del create de esta.
        $insertada = false;
        Matricula::creating(function (Matricula $m) use (&$insertada) {
            if (! $insertada) {
                $insertada = true;
                \DB::table('matriculas')->insert([
                    'tenant_id' => app('tenant')->id, 'school_year_id' => $m->school_year_id, 'estudiante_id' => $m->estudiante_id,
                    'grupo_id' => $m->grupo_id, 'fecha_matricula' => '2026-09-01', 'numero_orden' => 1, 'estado' => 'activa',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $r = $this->actingAs($e['admin'])->post(route('admin.matriculas.store'), [
            'school_year_id' => $e['anioActivo']->id, 'estudiante_id' => $est->id,
            'grupo_id' => $e['grupoActivo']->id, 'fecha_matricula' => '2026-09-01',
        ]);

        $r->assertSessionHasErrors('estudiante_id');   // mensaje de validación, no HTTP 500 (sin el arreglo: UniqueConstraintViolationException)
        // Nunca más de una. (Aquí puede ser 0: el test corre en UNA conexión y la app revierte su transacción llevándose también la
        // fila simulada de "la otra petición"; en producción esa fila ya está confirmada en otra conexión. La prueba con
        // peticiones simultáneas reales dio 1.)
        $this->assertLessThanOrEqual(1, Matricula::where('estudiante_id', $est->id)->count(), 'no debe haber duplicado');
    }

    public function test_reenviar_a_un_estudiante_ya_matriculado_da_validacion(): void
    {
        $e = $this->escenario();
        $est = Estudiante::factory()->create();
        $datos = ['school_year_id' => $e['anioActivo']->id, 'estudiante_id' => $est->id, 'grupo_id' => $e['grupoActivo']->id, 'fecha_matricula' => '2026-09-01'];

        $this->actingAs($e['admin'])->post(route('admin.matriculas.store'), $datos)->assertSessionHasNoErrors();
        $this->actingAs($e['admin'])->post(route('admin.matriculas.store'), $datos)->assertSessionHasErrors('estudiante_id');

        $this->assertSame(1, Matricula::where('estudiante_id', $est->id)->count());
    }
}
