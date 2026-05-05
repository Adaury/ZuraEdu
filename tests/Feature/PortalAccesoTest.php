<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de control de acceso a los portales.
 * Verifica que cada portal solo sea accesible por el rol correcto.
 */
class PortalAccesoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    // ── Portal Estudiante ─────────────────────────────────────────────────

    public function test_estudiante_puede_acceder_su_portal(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Estudiante');
        Estudiante::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('portal.estudiante.dashboard'))
            ->assertOk();
    }

    public function test_docente_no_puede_acceder_portal_estudiante(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Docente');

        $this->actingAs($user)
            ->get(route('portal.estudiante.dashboard'))
            ->assertForbidden();
    }

    public function test_invitado_redirige_a_login(): void
    {
        $this->get(route('portal.estudiante.dashboard'))
            ->assertRedirect(route('login'));
    }

    // ── Portal Padre ──────────────────────────────────────────────────────

    public function test_representante_puede_acceder_su_portal(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Representante');
        Representante::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('portal.padre.dashboard'))
            ->assertOk();
    }

    public function test_estudiante_no_puede_acceder_portal_padre(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Estudiante');

        $this->actingAs($user)
            ->get(route('portal.padre.dashboard'))
            ->assertForbidden();
    }

    // ── Portal Docente ────────────────────────────────────────────────────

    public function test_docente_puede_acceder_su_portal(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Docente');
        \App\Models\Docente::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('portal.docente.dashboard'))
            ->assertOk();
    }

    public function test_representante_no_puede_acceder_portal_docente(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Representante');

        $this->actingAs($user)
            ->get(route('portal.docente.dashboard'))
            ->assertForbidden();
    }

    // ── Admin Panel ───────────────────────────────────────────────────────

    public function test_estudiante_no_puede_acceder_panel_admin(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Estudiante');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect();
    }

    public function test_usuario_inactivo_es_desconectado(): void
    {
        $user = User::factory()->create(['activo' => false]);
        $user->assignRole('Administrador');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }
}
