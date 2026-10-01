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
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * La regla `exists:tabla,col` de Laravel consulta la tabla cruda: aceptaba el id de un estudiante de OTRO colegio y el de uno
 * borrado lógicamente, y el controlador guardaba la referencia ajena (p. ej. matricular a un estudiante de otro colegio).
 * Ahora `exists` comprueba dentro del colegio actual y sin borrados lógicos (TenantPresenceVerifier / TenantAwareValidator).
 * `unique` conserva su comportamiento a propósito.
 */
class ExistsTenantAwareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function colegio(string $etiqueta): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => "Colegio {$etiqueta}",
            'dominio'            => strtolower($etiqueta) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    private function estudianteDe(Tenant $t, array $extra = []): Estudiante
    {
        app()->instance('tenant', $t);

        return Estudiante::factory()->create($extra);
    }

    private function pasa(int $id): bool
    {
        return Validator::make(['estudiante_id' => $id], ['estudiante_id' => 'required|exists:estudiantes,id'])->passes();
    }

    public function test_exists_acepta_un_estudiante_del_propio_colegio(): void
    {
        $a = $this->colegio('A');
        $e = $this->estudianteDe($a);

        $this->assertTrue($this->pasa($e->id));
    }

    public function test_exists_rechaza_un_estudiante_de_otro_colegio(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        $ajeno = $this->estudianteDe($b);

        app()->instance('tenant', $a);
        $this->assertFalse($this->pasa($ajeno->id), 'Un estudiante de otro colegio no debe pasar exists.');
    }

    public function test_exists_rechaza_un_estudiante_borrado_logicamente(): void
    {
        $a = $this->colegio('A');
        $e = $this->estudianteDe($a);
        $e->delete();

        $this->assertFalse($this->pasa($e->id));
    }

    public function test_exists_no_cambia_sin_colegio_en_contexto_superadmin_comandos_jobs(): void
    {
        $b = $this->colegio('B');
        $e = $this->estudianteDe($b);

        app()->forgetInstance('tenant');
        $this->assertFalse(app()->bound('tenant'));
        $this->assertTrue($this->pasa($e->id), 'Sin colegio en contexto exists conserva el comportamiento anterior.');
    }

    public function test_exists_funciona_con_tablas_sin_tenant_ni_borrado_logico(): void
    {
        $this->colegio('A');
        app()->instance('tenant', Tenant::first());

        // `roles` no tiene tenant_id ni deleted_at (Spatie): debe seguir funcionando igual.
        $this->assertTrue(Validator::make(['r' => 'Administrador'], ['r' => 'exists:roles,name'])->passes());
        $this->assertFalse(Validator::make(['r' => 'NoExiste'], ['r' => 'exists:roles,name'])->passes());
    }

    public function test_exists_con_varios_ids_exige_que_todos_sean_del_colegio(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        $propio = $this->estudianteDe($a);
        $ajeno = $this->estudianteDe($b);

        app()->instance('tenant', $a);
        $v = fn (array $ids) => Validator::make(['ids' => $ids], ['ids' => 'array', 'ids.*' => 'exists:estudiantes,id'])->passes();
        $this->assertTrue($v([$propio->id]));
        $this->assertFalse($v([$propio->id, $ajeno->id]));
    }

    public function test_exists_con_condiciones_extra_de_Rule_exists_sigue_aplicando(): void
    {
        $a = $this->colegio('A');
        $e = $this->estudianteDe($a, ['estado' => 'inactivo']);

        $regla = ['estudiante_id' => [\Illuminate\Validation\Rule::exists('estudiantes', 'id')->where('estado', 'activo')]];
        $this->assertFalse(Validator::make(['estudiante_id' => $e->id], $regla)->passes());
        $this->assertTrue(Validator::make(['estudiante_id' => $e->id], ['estudiante_id' => [\Illuminate\Validation\Rule::exists('estudiantes', 'id')->where('estado', 'inactivo')]])->passes());
    }

    public function test_unique_conserva_su_comportamiento_ve_los_borrados_logicos(): void
    {
        $a = $this->colegio('A');
        $e = $this->estudianteDe($a, ['cedula' => '001-9999999-9']);
        $e->delete();

        // La restricción única de la BD también cuenta a los borrados: `unique` debe seguir avisando (si no, el INSERT daría 500).
        $v = Validator::make(['cedula' => '001-9999999-9'], ['cedula' => 'unique:estudiantes,cedula']);
        $this->assertTrue($v->fails());
    }

    /** El caso real: el formulario de matrícula aceptaba un estudiante de otro colegio. */
    public function test_matricular_un_estudiante_de_otro_colegio_se_rechaza_desde_la_web(): void
    {
        $a = $this->colegio('A');
        app()->instance('tenant', $a);
        $sy      = SchoolYear::create(['nombre' => '20' . random_int(26, 99) . '-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $admin   = User::factory()->create(['activo' => true, 'tenant_id' => $a->id]);
        $admin->assignRole('Administrador');
        $grado   = Grado::create(['nombre' => 'Grado EX' . random_int(1, 99999), 'nivel' => 150, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $propio  = Estudiante::factory()->create();

        $b = $this->colegio('B');
        $ajeno = $this->estudianteDe($b);

        app()->instance('tenant', $a);
        $datos = fn (int $estudianteId) => ['school_year_id' => $sy->id, 'estudiante_id' => $estudianteId, 'grupo_id' => $grupo->id, 'fecha_matricula' => '2025-09-01'];

        $this->actingAs($admin)->post(route('admin.matriculas.store'), $datos($ajeno->id))->assertSessionHasErrors('estudiante_id');
        $this->assertSame(0, Matricula::withoutGlobalScopes()->where('estudiante_id', $ajeno->id)->count(), 'No debe crearse la matrícula del estudiante ajeno.');

        // Control: un estudiante del propio colegio sí se matricula.
        $this->actingAs($admin)->post(route('admin.matriculas.store'), $datos($propio->id))->assertSessionHasNoErrors();
        $this->assertSame(1, Matricula::where('estudiante_id', $propio->id)->count());
    }
}
