<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Personal Administrativo supervisa registros: puede VER estudiantes, pero no matricular, inscribir ni editar. */
class PersonalAdministrativoPermisosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $tenant = Tenant::create(['nombre_institucion' => 'Colegio PA', 'dominio' => 'colegiopa' . random_int(10000, 99999), 'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free']);
        app()->instance('tenant', $tenant);
        $this->usuario = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $this->usuario->assignRole('Personal Administrativo');
    }

    public function test_puede_ver_la_lista_de_estudiantes(): void
    {
        $this->actingAs($this->usuario)->get(route('admin.estudiantes.index'))->assertOk();
    }

    public function test_no_puede_crear_estudiantes_ni_matricular_ni_inscribir(): void
    {
        $this->actingAs($this->usuario)->get(route('admin.estudiantes.create'))->assertForbidden();
        $this->actingAs($this->usuario)->get(route('admin.matriculas.index'))->assertForbidden();
        $this->actingAs($this->usuario)->get(route('admin.inscripciones.index'))->assertForbidden();
    }

    public function test_la_lista_no_ofrece_botones_de_crear_editar_ni_eliminar(): void
    {
        $e = \App\Models\Estudiante::factory()->create();
        $html = $this->actingAs($this->usuario)->get(route('admin.estudiantes.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('admin.estudiantes.create'), $html);
        $this->assertStringNotContainsString(route('admin.estudiantes.edit', $e), $html);

        $ficha = $this->actingAs($this->usuario)->get(route('admin.estudiantes.show', $e))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('admin.estudiantes.edit', $e), $ficha);
    }

    public function test_el_administrador_si_conserva_los_botones(): void
    {
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $this->usuario->tenant_id]);
        $admin->assignRole('Administrador');
        $html = $this->actingAs($admin)->get(route('admin.estudiantes.index'))->assertOk()->getContent();
        $this->assertStringContainsString(route('admin.estudiantes.create'), $html);
    }
}
