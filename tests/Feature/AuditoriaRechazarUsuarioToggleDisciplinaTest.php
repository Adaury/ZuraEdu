<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Estudiante;
use App\Models\FaltaDisciplinaria;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pendientes de la campaña de auditoría: rechazar una solicitud de acceso borraba al usuario sin rastro ni motivo (y servía para
 * borrar cualquier cuenta), y marcar una falta disciplinaria como resuelta no dejaba registro.
 */
class AuditoriaRechazarUsuarioToggleDisciplinaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Auditoria',
            'dominio'            => 'colegioauditoria' . random_int(10000, 99999),
            'estado' => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $this->admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $this->admin->assignRole('Administrador');
        app()->instance('tenant', $tenant);
    }

    public function test_rechazar_solicitud_pendiente_la_elimina_y_guarda_el_motivo_en_el_log(): void
    {
        $pendiente = User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'pendiente_aprobacion' => true, 'activo' => false]);

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.rechazar', $pendiente), ['motivo' => 'Cédula no coincide'])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $pendiente->id]);
        $log = ActivityLog::where('accion', 'usuario.solicitud_rechazada')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Cédula no coincide', $log->descripcion);
    }

    public function test_rechazar_no_sirve_para_borrar_una_cuenta_ya_activa(): void
    {
        $activo = User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'pendiente_aprobacion' => false, 'activo' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.rechazar', $activo), ['motivo' => 'x'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $activo->id]);
        $this->assertSame(0, ActivityLog::where('accion', 'usuario.solicitud_rechazada')->count());
    }

    public function test_toggle_resuelto_deja_rastro_con_el_valor_anterior_y_el_nuevo(): void
    {
        $falta = FaltaDisciplinaria::create([
            'tenant_id' => $this->admin->tenant_id, 'estudiante_id' => Estudiante::factory()->create()->id,
            'tipo' => 'falta_grave', 'descripcion' => 'Prueba', 'fecha' => '2026-09-01', 'resuelto' => false,
        ]);

        \App\Models\TenantFeature::create(['tenant_id' => $this->admin->tenant_id, 'feature' => 'disciplina', 'activo' => true]);

        $r = $this->actingAs($this->admin)->patch(route('admin.disciplina.toggle-resuelto', $falta));
        $r->assertRedirect();

        $this->assertTrue($falta->fresh()->resuelto);
        $log = ActivityLog::where('accion', 'disciplina.falta_resuelto_cambiado')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('No → Sí', $log->descripcion);
    }
}
