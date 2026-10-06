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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Limpiar datos" usaba truncate(): el Administrador de un colegio vaciaba las tablas de TODOS los
 * colegios de la plataforma (hallazgo crítico N1, auditoría 2026-10-06). Ahora solo toca el suyo.
 */
class LimpiarDatosAislamientoTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function tenant(string $nombre): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $nombre,
            'dominio'            => strtolower(str_replace(' ', '', $nombre)) . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    /** Crea un grupo, un estudiante con usuario, su matrícula y un representante vinculado. */
    private function poblar(Tenant $t, string $sufijo): array
    {
        app()->instance('tenant', $t);

        $sy      = SchoolYear::create(['nombre' => '2026-' . $sufijo, 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado ' . $sufijo, 'nivel' => 101, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $userEst = User::factory()->create(['tenant_id' => $t->id]);
        $est     = Estudiante::factory()->create(['user_id' => $userEst->id]);
        $mat     = Matricula::create([
            'school_year_id' => $sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $repId = DB::table('representantes')->insertGetId(array_merge(
            ['tenant_id' => $t->id, 'user_id' => User::factory()->create(['tenant_id' => $t->id])->id, 'created_at' => now(), 'updated_at' => now()],
            $this->columnasRepresentante()
        ));
        DB::table('estudiante_representante')->insert(['estudiante_id' => $est->id, 'representante_id' => $repId]);

        return compact('grupo', 'est', 'mat', 'userEst');
    }

    private function columnasRepresentante(): array
    {
        $cols = [];
        foreach (['nombres' => 'Rep', 'apellidos' => 'Prueba', 'nombre' => 'Rep Prueba', 'apellido' => 'Prueba'] as $c => $v) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('representantes', $c)) {
                $cols[$c] = $v;
            }
        }
        return $cols;
    }

    public function test_limpiar_datos_solo_borra_el_colegio_del_administrador(): void
    {
        $a = $this->tenant('Colegio Uno');
        $b = $this->tenant('Colegio Dos');
        $datosA = $this->poblar($a, 'A');
        $datosB = $this->poblar($b, 'B');

        app()->instance('tenant', $a);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $a->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->post(route('admin.sistema.limpiar-datos'), ['confirmacion' => 'CONFIRMAR', 'scope' => 'todo'])
            ->assertSessionHas('success_danger');

        // Colegio del administrador: vacío.
        $this->assertSame(0, DB::table('estudiantes')->where('tenant_id', $a->id)->count());
        $this->assertSame(0, DB::table('matriculas')->where('tenant_id', $a->id)->count());
        $this->assertSame(0, DB::table('grupos')->where('tenant_id', $a->id)->count());
        $this->assertDatabaseMissing('estudiante_representante', ['estudiante_id' => $datosA['est']->id]);
        $this->assertDatabaseMissing('users', ['id' => $datosA['userEst']->id]);

        // Otro colegio: intacto.
        $this->assertSame(1, DB::table('estudiantes')->where('tenant_id', $b->id)->count());
        $this->assertSame(1, DB::table('matriculas')->where('tenant_id', $b->id)->count());
        $this->assertSame(1, DB::table('grupos')->where('tenant_id', $b->id)->count());
        $this->assertDatabaseHas('estudiante_representante', ['estudiante_id' => $datosB['est']->id]);
        $this->assertDatabaseHas('users', ['id' => $datosB['userEst']->id]);
    }

    public function test_scope_estudiantes_conserva_los_grupos_y_no_toca_otro_colegio(): void
    {
        $a = $this->tenant('Colegio Tres');
        $b = $this->tenant('Colegio Cuatro');
        $this->poblar($a, 'C');
        $this->poblar($b, 'D');

        app()->instance('tenant', $a);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $a->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->post(route('admin.sistema.limpiar-datos'), ['confirmacion' => 'CONFIRMAR', 'scope' => 'estudiantes'])
            ->assertSessionHas('success_danger');

        $this->assertSame(0, DB::table('estudiantes')->where('tenant_id', $a->id)->count());
        $this->assertSame(1, DB::table('grupos')->where('tenant_id', $a->id)->count());
        $this->assertSame(1, DB::table('estudiantes')->where('tenant_id', $b->id)->count());
        $this->assertSame(1, DB::table('matriculas')->where('tenant_id', $b->id)->count());
    }

    public function test_exige_la_palabra_de_confirmacion_y_no_borra_nada_sin_ella(): void
    {
        $a = $this->tenant('Colegio Cinco');
        $this->poblar($a, 'E');
        app()->instance('tenant', $a);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $a->id]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->post(route('admin.sistema.limpiar-datos'), ['confirmacion' => 'no', 'scope' => 'todo'])
            ->assertSessionHasErrors('confirmacion');

        $this->assertSame(1, DB::table('estudiantes')->where('tenant_id', $a->id)->count());
    }
}
