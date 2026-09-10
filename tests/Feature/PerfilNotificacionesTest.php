<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notificaciones Configurables (Fase 5, pieza 3) -- sección "Mis
 * Notificaciones" en /perfil, compartida por los 4 roles (admin/docente
 * usan layouts.admin, padre/estudiante usan layouts.portal).
 */
class PerfilNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearUsuario(string $rol, string $sufijo): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => "Colegio Perfil {$sufijo}",
            'dominio'            => 'colegioperfil' . strtolower($sufijo) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);

        return compact('tenant', 'user');
    }

    public function test_la_seccion_aparece_para_admin_y_para_portal(): void
    {
        ['user' => $admin] = $this->crearUsuario('Administrador', 'AdminView');
        $responseAdmin = $this->actingAs($admin)->get(route('perfil.show'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Mis Notificaciones');
        $responseAdmin->assertSee('Aula Virtual (ZuraClass)');

        ['user' => $rep] = $this->crearUsuario('Representante', 'PortalView');
        $responseRep = $this->actingAs($rep)->get(route('perfil.show'));
        $responseRep->assertOk();
        $responseRep->assertSee('Mis Notificaciones');
        $responseRep->assertSee('Aula Virtual (ZuraClass)');
    }

    public function test_el_usuario_guarda_sus_preferencias(): void
    {
        ['user' => $user] = $this->crearUsuario('Representante', 'Guardar');

        // Solo se envía 'push_academico' -- el navegador no manda los
        // checkboxes desmarcados, así que 'zuraclass' queda ausente.
        $this->actingAs($user)->post(route('perfil.notificaciones'), [
            'push_academico' => '1',
        ]);

        $user->refresh();
        $this->assertTrue($user->notif_push_prefs['academico']);
        $this->assertFalse($user->notif_push_prefs['zuraclass']);
    }

    public function test_una_categoria_apagada_por_el_centro_conserva_la_preferencia_del_usuario(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearUsuario('Representante', 'Conserva');

        app()->instance('tenant', $tenant);
        Setting::set('notif_push_pagos', '0');
        app()->forgetInstance('tenant');

        $user->notif_push_prefs = ['pagos' => true];
        $user->save();

        // El checkbox de 'pagos' llega deshabilitado -> el navegador NO lo
        // envía en el POST, igual que si el usuario nunca lo hubiera tocado.
        app()->instance('tenant', $tenant);
        $this->actingAs($user)->post(route('perfil.notificaciones'), [
            'push_academico' => '1',
        ]);
        app()->forgetInstance('tenant');

        $user->refresh();
        $this->assertTrue($user->notif_push_prefs['pagos'], 'La preferencia de una categoría apagada por el centro no debe pisarse.');
    }

    public function test_no_toca_las_preferencias_de_otro_usuario(): void
    {
        ['tenant' => $tenant, 'user' => $userA] = $this->crearUsuario('Representante', 'NoTocaA');
        $userB = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $userB->assignRole('Representante');

        $this->actingAs($userA)->post(route('perfil.notificaciones'), ['push_academico' => '1']);

        $userB->refresh();
        $this->assertNull($userB->notif_push_prefs);
    }

    public function test_invitado_no_puede_guardar_preferencias(): void
    {
        $response = $this->post(route('perfil.notificaciones'), ['push_academico' => '1']);

        $response->assertRedirect(route('login'));
    }
}
