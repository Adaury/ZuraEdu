<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * `unique:` consultaba TODOS los colegios, pero la BD exige unicidad POR colegio (`UNIQUE (tenant_id, cedula)`, etc.):
 * un colegio no podía registrar una cédula, un número de matrícula, un año escolar o la sección «A» si otro colegio ya la
 * tenía (y el mensaje revelaba que existía allí). Ahora la validación replica la restricción real de la BD
 * (TenantUniquePresenceVerifier): por colegio cuando la BD lo exige, global cuando la BD lo exige global (users.email).
 */
class UniqueTenantAwareTest extends TestCase
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

    private function libre(array $regla, mixed $valor): bool
    {
        return Validator::make(['campo' => $valor], ['campo' => $regla])->passes();
    }

    public function test_la_cedula_de_otro_colegio_no_bloquea_pero_la_del_propio_si(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        app()->instance('tenant', $b);
        Estudiante::factory()->create(['cedula' => '001-1111111-1']);

        app()->instance('tenant', $a);
        $this->assertTrue($this->libre(['unique:estudiantes,cedula'], '001-1111111-1'), 'Una cédula que solo existe en OTRO colegio debe estar libre.');

        Estudiante::factory()->create(['cedula' => '001-2222222-2']);
        $this->assertFalse($this->libre(['unique:estudiantes,cedula'], '001-2222222-2'), 'Una cédula del propio colegio debe estar ocupada.');
    }

    public function test_un_borrado_logico_del_propio_colegio_sigue_contando_porque_la_restriccion_de_la_bd_lo_cuenta(): void
    {
        $a = $this->colegio('A');
        app()->instance('tenant', $a);
        Estudiante::factory()->create(['cedula' => '001-3333333-3'])->delete();

        $this->assertFalse($this->libre(['unique:estudiantes,cedula'], '001-3333333-3'));
    }

    public function test_numero_de_matricula_y_cedula_de_docente_tambien_son_por_colegio(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        app()->instance('tenant', $b);
        Estudiante::factory()->create(['numero_matricula' => 'M-REPETIDA']);

        app()->instance('tenant', $a);
        $this->assertTrue($this->libre(['unique:estudiantes,numero_matricula'], 'M-REPETIDA'));
        Estudiante::factory()->create(['numero_matricula' => 'M-PROPIA']);
        $this->assertFalse($this->libre(['unique:estudiantes,numero_matricula'], 'M-PROPIA'));
    }

    public function test_el_nombre_de_una_seccion_de_otro_colegio_no_bloquea(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        app()->instance('tenant', $b);
        Seccion::create(['nombre' => 'Q', 'orden' => 1]);

        app()->instance('tenant', $a);
        $this->assertTrue($this->libre(['unique:secciones,nombre'], 'Q'), 'Dos colegios pueden tener la sección «Q».');
        Seccion::create(['nombre' => 'Q', 'orden' => 1]);
        $this->assertFalse($this->libre(['unique:secciones,nombre'], 'Q'), 'Dentro del mismo colegio no se repite.');
    }

    public function test_una_unicidad_global_de_la_bd_sigue_siendo_global_users_email(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        User::factory()->create(['tenant_id' => $b->id, 'email' => 'compartido@example.test']);

        app()->instance('tenant', $a);
        // La BD tiene UNIQUE(email) global: si la validación dijera "libre", el INSERT fallaría con un 500.
        $this->assertFalse($this->libre(['unique:users,email'], 'compartido@example.test'));
    }

    public function test_sin_restriccion_en_la_bd_pero_con_tenant_id_la_unicidad_es_por_colegio(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        $beca = fn (Tenant $t) => DB::table('becas')->insert(['tenant_id' => $t->id, 'nombre' => 'Beca Excelencia', 'tipo' => 'porcentaje', 'valor' => 50, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $beca($b);

        app()->instance('tenant', $a);
        $this->assertTrue($this->libre(['unique:becas,nombre'], 'Beca Excelencia'), 'becas.nombre no tiene restricción en la BD: es por colegio.');
        $beca($a);
        $this->assertFalse($this->libre(['unique:becas,nombre'], 'Beca Excelencia'));
    }

    public function test_ignorar_el_propio_id_al_editar_sigue_funcionando(): void
    {
        $a = $this->colegio('A');
        app()->instance('tenant', $a);
        $e = Estudiante::factory()->create(['cedula' => '001-4444444-4']);
        $otro = Estudiante::factory()->create(['cedula' => '001-5555555-5']);

        $this->assertTrue($this->libre(["unique:estudiantes,cedula,{$e->id}"], '001-4444444-4'), 'Editar sin cambiar la cédula no es un duplicado.');
        $this->assertFalse($this->libre(["unique:estudiantes,cedula,{$e->id}"], '001-5555555-5'), 'Pero no puede tomar la de otro estudiante.');
        unset($otro);
    }

    public function test_sin_colegio_en_contexto_conserva_el_comportamiento_global(): void
    {
        $b = $this->colegio('B');
        app()->instance('tenant', $b);
        Estudiante::factory()->create(['cedula' => '001-6666666-6']);

        app()->forgetInstance('tenant');
        $this->assertFalse($this->libre(['unique:estudiantes,cedula'], '001-6666666-6'));
    }

    /** El caso real que rompía a un segundo colegio: crear la sección «A» desde el panel. */
    public function test_un_segundo_colegio_puede_crear_una_seccion_que_otro_ya_tiene_desde_el_panel(): void
    {
        $a = $this->colegio('A');
        $b = $this->colegio('B');
        app()->instance('tenant', $b);
        Seccion::create(['nombre' => 'W', 'orden' => 1]);

        app()->instance('tenant', $a);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $a->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)->post(route('admin.secciones-academicas.store'), ['nombre' => 'W'])->assertSessionHasNoErrors();
        $this->assertSame(1, Seccion::where('nombre', 'W')->count(), 'El colegio A debe tener su propia sección «W».');

        $this->actingAs($admin)->post(route('admin.secciones-academicas.store'), ['nombre' => 'W'])->assertSessionHasErrors('nombre');
    }
}
