<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Jobs\EnviarNotificacionJob;
use App\Jobs\EnviarPushLoteJob;
use App\Models\DeviceToken;
use App\Models\Notificacion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Notificaciones Configurables (Fase 5, pieza 3) — el gating centralizado
 * en Notificacion::enviar()/enviarA() y EnviarNotificacionJob::handle().
 *
 * Mock del push: PushNotificationService usa el facade Http hacia
 * exp.host (patrón ya usado en PublicSiteChatTest/EnviarWhatsAppTenantTest).
 *
 * TRAMPA A EVITAR: PushNotificationService::dispatch() descarta cualquier
 * token que no empiece con "ExponentPushToken" ANTES de hacer la request
 * HTTP. Un DeviceToken de prueba con un token cualquiera hace que
 * Http::assertNothingSent() pase aunque el gating esté roto -- todo test
 * que verifique push SÍ/NO debe crear el token con el prefijo real.
 */
class NotificacionPreferenciasGatingTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenantConUsuario(): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Notif Gating',
            'dominio'            => 'colegionotifgating' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);

        return compact('tenant', 'user');
    }

    private function tokenExpo(User $user): void
    {
        DeviceToken::register($user->id, 'ExponentPushToken[' . \Illuminate\Support\Str::random(22) . ']', 'android');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['exp.host/*' => Http::response(['data' => []], 200)]);
    }

    public function test_institucion_apaga_inapp_no_crea_fila(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        Setting::set('notif_inapp_academico', '0');
        $this->tokenExpo($user);

        Notificacion::enviar($user->id, 'academica', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(0, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
        Http::assertNothingSent();
    }

    public function test_institucion_apaga_inapp_no_encola_el_job(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        Setting::set('notif_inapp_academico', '0');
        Queue::fake();
        config(['queue.default' => 'redis']);

        Notificacion::enviar($user->id, 'academica', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        Queue::assertNotPushed(EnviarNotificacionJob::class);
    }

    public function test_institucion_apaga_push_pero_inapp_sigue(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        Setting::set('notif_push_academico', '0');
        $this->tokenExpo($user);

        Notificacion::enviar($user->id, 'academica', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(1, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
        Http::assertNothingSent();
    }

    public function test_usuario_apaga_push_de_su_categoria(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        $user->notif_push_prefs = ['academico' => false];
        $user->save();
        $this->tokenExpo($user);

        Notificacion::enviar($user->id, 'academica', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(1, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
        Http::assertNothingSent();
    }

    public function test_apagar_una_categoria_no_afecta_a_las_demas(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        $user->notif_push_prefs = ['zuraclass' => false];
        $user->save();
        $this->tokenExpo($user);

        Notificacion::enviar($user->id, 'academica', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'exp.host'));
    }

    public function test_camino_feliz_crea_fila_y_envia_push(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        $this->tokenExpo($user);

        Notificacion::enviar($user->id, 'academica', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(1, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
        Http::assertSent(fn ($r) => str_contains($r->url(), 'exp.host'));
    }

    public function test_la_categoria_sistema_ignora_el_toggle_de_inapp(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        // 'sistema' nunca se escribe desde la UI, pero si alguien la inserta
        // a mano en system_settings, debe ignorarse igual (ver D3 del plan).
        Setting::set('notif_inapp_sistema', '0');

        Notificacion::enviar($user->id, 'general', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(1, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
    }

    public function test_el_job_replica_el_gating_para_los_dispatchers_directos(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearTenantConUsuario();
        Setting::set('notif_inapp_alertas', '0');

        (new EnviarNotificacionJob(
            userId: $user->id, tipo: 'carnet_acceso', titulo: 'T', mensaje: 'M', tenantId: $tenant->id,
        ))->handle();
        app()->forgetInstance('tenant');

        $this->assertSame(0, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
    }

    public function test_enviarA_con_inapp_apagado_no_inserta_nada(): void
    {
        ['tenant' => $tenant, 'user' => $userA] = $this->crearTenantConUsuario();
        $userB = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        Setting::set('notif_inapp_comunicacion', '0');
        Queue::fake();

        Notificacion::enviarA([$userA->id, $userB->id], 'comunicado', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(0, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
        Queue::assertNotPushed(EnviarPushLoteJob::class);
    }

    public function test_enviarA_despacha_un_solo_job_de_push_para_los_n_usuarios(): void
    {
        ['tenant' => $tenant, 'user' => $userA] = $this->crearTenantConUsuario();
        $userB = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $userC = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        Queue::fake();

        Notificacion::enviarA([$userA->id, $userB->id, $userC->id], 'comunicado', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        Queue::assertPushed(EnviarPushLoteJob::class, 1);
        Queue::assertPushed(EnviarPushLoteJob::class, function ($job) use ($userA, $userB, $userC) {
            return count($job->userIds) === 3
                && in_array($userA->id, $job->userIds)
                && in_array($userB->id, $job->userIds)
                && in_array($userC->id, $job->userIds);
        });
    }

    public function test_enviarA_no_encola_push_si_la_institucion_lo_apago(): void
    {
        ['tenant' => $tenant, 'user' => $userA] = $this->crearTenantConUsuario();
        $userB = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        Setting::set('notif_push_comunicacion', '0');
        Queue::fake();

        Notificacion::enviarA([$userA->id, $userB->id], 'comunicado', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(2, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
        Queue::assertNotPushed(EnviarPushLoteJob::class);
    }

    public function test_el_job_de_lote_filtra_a_los_usuarios_que_apagaron_push(): void
    {
        ['tenant' => $tenant, 'user' => $userA] = $this->crearTenantConUsuario();
        $userB = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $userB->notif_push_prefs = ['zuraclass' => false];
        $userB->save();
        $this->tokenExpo($userA);
        $this->tokenExpo($userB);

        (new EnviarPushLoteJob(
            userIds: [$userA->id, $userB->id], tipo: 'zura_tarea', titulo: 'T', mensaje: 'M', tenantId: $tenant->id,
        ))->handle();
        app()->forgetInstance('tenant');

        Http::assertSent(function ($request) use ($userA) {
            $body = $request->data();
            $tokens = array_column($body, 'to');
            $tokenA = \App\Models\DeviceToken::tokensDeUsuario($userA->id)[0];

            return in_array($tokenA, $tokens) && count($tokens) === 1;
        });
    }

    public function test_enviarA_deduplica_ids_repetidos(): void
    {
        ['tenant' => $tenant, 'user' => $userA] = $this->crearTenantConUsuario();
        $userB = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);

        Notificacion::enviarA([$userA->id, $userA->id, null, $userB->id], 'comunicado', 'Título', 'Mensaje');
        app()->forgetInstance('tenant');

        $this->assertSame(2, Notificacion::withoutTenant()->where('tenant_id', $tenant->id)->count());
    }
}
