<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un Director (o administrador) que además da clases tiene rol docente: no debe quedar fuera del panel
 * /admin (antes se le mandaba siempre al portal docente), y desde el menú llega a su portal docente.
 */
class DirectorDocenteAccesoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_director_con_rol_docente_entra_al_panel_y_ve_el_enlace_a_su_portal(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Director');
        $user->assignRole('Docente');
        Docente::factory()->create(['user_id' => $user->id]);

        $r = $this->actingAs($user)->get(route('admin.dashboard'));

        $r->assertOk();
        $r->assertSee(route('portal.docente.dashboard'), false);
    }

    public function test_un_docente_sin_rol_administrativo_sigue_yendo_a_su_portal(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Docente Académico');

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('portal.docente.dashboard'));
    }
}
