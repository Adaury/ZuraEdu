<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hallazgos N2 de la auditoría 2026-10-06: borrar el chat de todo el colegio exigía solo el acceso
 * admin genérico, y /demo/{rol} no tenía límite de intentos.
 */
class AuditoriaN2RutasPublicasTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->tenant = Tenant::create([
            'nombre_institucion' => 'Colegio N2',
            'dominio'            => 'colegion2' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $this->tenant);
    }

    private function usuario(string $rol): User
    {
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $this->tenant->id]);
        $u->assignRole($rol);
        return $u;
    }

    public function test_solo_direccion_puede_borrar_el_chat_del_colegio(): void
    {
        $this->actingAs($this->usuario('Registrador Académico'))
            ->delete(route('admin.tenant-chat.clear'))
            ->assertForbidden();

        $this->actingAs($this->usuario('Administrador'))
            ->delete(route('admin.tenant-chat.clear'))
            ->assertSuccessful();
    }

    public function test_demo_login_tiene_limite_de_intentos(): void
    {
        $r = null;
        for ($i = 0; $i < 21; $i++) {
            $r = $this->get(route('demo.login', 'docente'));
        }
        $r->assertStatus(429);
    }
}
