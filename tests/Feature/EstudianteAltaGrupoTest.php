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
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Alta de estudiante con grupo (wizard) e importación con grupo. Hallado con altas simultáneas reales:
 *  - 30 altas hacia un grupo de capacidad 25 lo dejaron con 50 matriculados y SIN número de lista (no había cupo ni bloqueo);
 *  - dos altas con el mismo número de matrícula a la vez daban HTTP 500 en la segunda (pasaban `unique` a la vez).
 * Ahora el alta es atómica (estudiante + matrícula), bloquea el grupo, respeta cupo y año, numera la lista y los choques de unicidad
 * salen como mensaje de validación.
 */
class EstudianteAltaGrupoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\PreventRequestForgery::class);
    }

    /** @return array{admin: User, anio: SchoolYear, viejo: SchoolYear, grupo: Grupo, grupoViejo: Grupo} */
    private function escenario(int $capacidad = 35): array
    {
        $t = Tenant::create([
            'nombre_institucion' => 'Colegio Alta', 'dominio' => 'alta' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $t);

        $anio  = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $viejo = SchoolYear::create(['nombre' => '2025-2026', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => false]);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $t->id]);
        $admin->assignRole('Administrador');
        $grado = Grado::create(['nombre' => 'Grado Alta', 'nivel' => 121, 'orden' => 121, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $sec   = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $mk    = fn (SchoolYear $a) => Grupo::create(['school_year_id' => $a->id, 'grado_id' => $grado->id, 'seccion_id' => $sec->id, 'activo' => true, 'capacidad' => $capacidad]);

        return ['admin' => $admin, 'anio' => $anio, 'viejo' => $viejo, 'grupo' => $mk($anio), 'grupoViejo' => $mk($viejo)];
    }

    private function datos(string $n, array $extra = []): array
    {
        return array_merge([
            'numero_matricula' => "ALTA-{$n}", 'nombres' => "Nombre{$n}", 'apellidos' => 'Prueba', 'fecha_nacimiento' => '2012-05-05',
            'sexo' => 'M', 'estado' => 'activo',
        ], $extra);
    }

    public function test_alta_con_grupo_crea_estudiante_y_matricula_con_numero_de_lista(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('1', ['grupo_id' => $e['grupo']->id]))
            ->assertRedirect(route('admin.estudiantes.index'))->assertSessionHasNoErrors();
        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('2', ['grupo_id' => $e['grupo']->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame([1, 2], Matricula::where('grupo_id', $e['grupo']->id)->orderBy('numero_orden')->pluck('numero_orden')->all());
    }

    public function test_alta_en_un_grupo_lleno_se_rechaza_y_no_deja_al_estudiante_a_medias(): void
    {
        $e = $this->escenario(1);
        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('1', ['grupo_id' => $e['grupo']->id]))->assertSessionHasNoErrors();

        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('2', ['grupo_id' => $e['grupo']->id]))
            ->assertSessionHasErrors('grupo_id');

        $this->assertSame(1, Matricula::where('grupo_id', $e['grupo']->id)->count(), 'el grupo no pasa de su capacidad');
        $this->assertNull(Estudiante::where('numero_matricula', 'ALTA-2')->first(), 'el alta rechazada no deja un estudiante sin matrícula');
    }

    public function test_alta_en_un_grupo_de_otro_anio_se_rechaza(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('1', ['grupo_id' => $e['grupoViejo']->id]))
            ->assertSessionHasErrors('grupo_id');

        $this->assertNull(Estudiante::where('numero_matricula', 'ALTA-1')->first());
        $this->assertSame(0, Matricula::count());
    }

    public function test_alta_con_un_grupo_de_otro_colegio_se_rechaza(): void
    {
        $e = $this->escenario();
        $miColegio = app('tenant');

        $otro = Tenant::create(['nombre_institucion' => 'Otro', 'dominio' => 'otro' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $otro);
        $anioOtro = SchoolYear::create(['nombre' => '2026-2027', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2027-06-30', 'activo' => true]);
        $grado = Grado::create(['nombre' => 'Grado Otro', 'nivel' => 122, 'orden' => 122, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $grupoAjeno = Grupo::create(['school_year_id' => $anioOtro->id, 'grado_id' => $grado->id, 'seccion_id' => Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1])->id, 'activo' => true, 'capacidad' => 35]);

        app()->instance('tenant', $miColegio);
        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('1', ['grupo_id' => $grupoAjeno->id]))
            ->assertSessionHasErrors('grupo_id');

        $this->assertNull(Estudiante::where('numero_matricula', 'ALTA-1')->first());
    }

    public function test_alta_sin_grupo_sigue_funcionando(): void
    {
        $e = $this->escenario();

        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('1'))->assertSessionHasNoErrors();

        $this->assertNotNull(Estudiante::where('numero_matricula', 'ALTA-1')->first());
        $this->assertSame(0, Matricula::count());
    }

    /** La otra petición confirma el mismo número de matrícula justo después de validar y antes de este INSERT. */
    public function test_numero_de_matricula_repetido_por_carrera_da_validacion_y_no_un_500(): void
    {
        $e = $this->escenario();

        $insertada = false;
        Estudiante::creating(function (Estudiante $est) use (&$insertada) {
            if (! $insertada) {
                $insertada = true;
                \DB::table('estudiantes')->insert([
                    'tenant_id' => app('tenant')->id, 'numero_matricula' => $est->numero_matricula, 'nombres' => 'Otro', 'apellidos' => 'Hilo',
                    'fecha_nacimiento' => '2012-05-05', 'sexo' => 'M', 'estado' => 'activo', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $this->actingAs($e['admin'])->post(route('admin.estudiantes.store'), $this->datos('1'))
            ->assertSessionHasErrors('numero_matricula');   // sin el arreglo: UniqueConstraintViolationException (HTTP 500)
    }

    public function test_importar_csv_con_grupo_respeta_el_cupo_numera_y_avisa(): void
    {
        $e = $this->escenario(2);
        $csv = "nombres,apellidos,fecha_nacimiento,sexo\nAna,Uno,2012-01-01,F\nBeto,Dos,2012-02-02,M\nCarla,Tres,2012-03-03,F\n";

        $r = $this->actingAs($e['admin'])->post(route('admin.estudiantes.importStore'), [
            'archivo'  => UploadedFile::fake()->createWithContent('estudiantes.csv', $csv),
            'grupo_id' => $e['grupo']->id,
        ]);

        $r->assertSessionHas('errores_import', fn ($errores) => count(array_filter($errores, fn ($x) => str_contains($x, 'creado sin matrícula'))) === 1);
        $this->assertSame(3, Estudiante::whereIn('nombres', ['Ana', 'Beto', 'Carla'])->count(), 'los tres estudiantes se crean');
        $this->assertSame([1, 2], Matricula::where('grupo_id', $e['grupo']->id)->orderBy('numero_orden')->pluck('numero_orden')->all(), 'solo caben 2, con número de lista');
    }
}
