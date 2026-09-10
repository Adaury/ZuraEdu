<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Mail\UsuarioAprobado;
use App\Models\Notificacion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Bug real encontrado en la auditoría de Notificaciones Configurables
 * (Fase 5, pieza 3): 'email_notif_aprobacion' se guardaba desde
 * /admin/sistema/email-notif y se pintaba en la UI, pero
 * UsuarioController::aprobar() nunca lo leía -- el email de aprobación se
 * enviaba siempre, y el flash mentía prometiéndolo incluso cuando el toggle
 * estaba apagado.
 */
class EmailNotifAprobacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearAdminYPendiente(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Aprobacion',
            'dominio'            => 'colegioaprobacion' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        $admin = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $admin->assignRole('Administrador');

        $pendiente = User::factory()->create([
            'activo' => false, 'pendiente_aprobacion' => true, 'tenant_id' => $tenant->id,
        ]);

        return compact('tenant', 'admin', 'pendiente');
    }

    public function test_no_encola_el_email_de_aprobacion_si_el_toggle_esta_apagado(): void
    {
        ['tenant' => $tenant, 'admin' => $admin, 'pendiente' => $pendiente] = $this->crearAdminYPendiente();
        Setting::set('email_notif_aprobacion', '0');
        Mail::fake();

        $this->actingAs($admin)->post(route('admin.usuarios.aprobar', $pendiente));

        Mail::assertNotQueued(UsuarioAprobado::class);

        // La notificación in-app (canal independiente) sí debe llegar.
        app()->instance('tenant', $tenant);
        $this->assertSame(1, Notificacion::withoutTenant()->where('user_id', $pendiente->id)->where('tipo', 'general')->count());
        app()->forgetInstance('tenant');
    }

    public function test_encola_el_email_de_aprobacion_por_defecto(): void
    {
        ['admin' => $admin, 'pendiente' => $pendiente] = $this->crearAdminYPendiente();
        // Sin tocar la clave en system_settings -- default-on.
        Mail::fake();

        $this->actingAs($admin)->post(route('admin.usuarios.aprobar', $pendiente));

        Mail::assertQueued(UsuarioAprobado::class);
    }

    public function test_el_flash_no_promete_el_envio_si_el_toggle_esta_apagado(): void
    {
        ['admin' => $admin, 'pendiente' => $pendiente] = $this->crearAdminYPendiente();
        Setting::set('email_notif_aprobacion', '0');
        Mail::fake();

        $response = $this->actingAs($admin)->post(route('admin.usuarios.aprobar', $pendiente));

        $response->assertSessionHas('success');
        $this->assertStringNotContainsString('Se envió notificación por correo', session('success'));
    }
}
