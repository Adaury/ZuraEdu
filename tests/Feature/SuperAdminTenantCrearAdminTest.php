<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Gap real reportado por el usuario: SuperAdmin\TenantController::store()
 * creaba el tenant pero NUNCA un usuario administrador — a diferencia del
 * autoservicio (/onboarding, vía TenantProvisioningService), que sí lo
 * hace. Sin esto, una institución creada por SuperAdmin no tenía forma de
 * entrar a su propio panel. Ahora store() también crea el usuario
 * Administrador con una contraseña generada, mostrada una sola vez en la
 * página de la institución para que el SuperAdmin se la envíe.
 */
class SuperAdminTenantCrearAdminTest extends TestCase
{
    use RefreshDatabase;

    private function crearSuperAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);

        $tenantPlataforma = Tenant::create([
            'nombre_institucion' => 'ZuraEdu Plataforma',
            'dominio'            => 'plataforma' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'pro',
        ]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenantPlataforma->id]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function datosBase(): array
    {
        return [
            'nombre_institucion' => 'Colegio Nueva Institución',
            'dominio'            => 'colegionueva' . random_int(10000, 99999),
            'tipo'               => 'privado',
            'estado'             => 'prueba',
            'plan'               => 'free',
            'max_estudiantes'    => 100,
            'max_docentes'       => 10,
            'nombre_admin'       => 'Ana Directora',
            'email_admin'        => 'ana' . random_int(10000, 99999) . '@colegio.test',
        ];
    }

    public function test_crear_institucion_crea_tambien_el_usuario_administrador(): void
    {
        $superAdmin = $this->crearSuperAdmin();
        $datos = $this->datosBase();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.tenants.store'), $datos);

        $tenant = Tenant::where('dominio', $datos['dominio'])->first();
        $this->assertNotNull($tenant);

        $response->assertRedirect(route('superadmin.tenants.show', $tenant));

        // withoutTenant(): tras el POST, app('tenant') sigue vinculado al
        // tenant del propio SuperAdmin (el que resolvió esa request) — el
        // admin recién creado pertenece al tenant NUEVO, así que se busca
        // sin el scope automático, igual que haría un SuperAdmin real.
        $admin = User::withoutTenant()->where('email', $datos['email_admin'])->first();
        $this->assertNotNull($admin, 'Debe crearse el usuario administrador.');
        $this->assertSame($tenant->id, $admin->tenant_id);
        $this->assertTrue($admin->hasRole('Administrador'));
        $this->assertTrue($admin->activo);
        $this->assertTrue($admin->must_change_password, 'Debe forzar cambio de contraseña en el primer login.');
    }

    public function test_la_contrasena_generada_se_muestra_una_sola_vez_en_la_pagina(): void
    {
        $superAdmin = $this->crearSuperAdmin();
        $datos = $this->datosBase();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.tenants.store'), $datos);
        $tenant = Tenant::where('dominio', $datos['dominio'])->first();

        $response->assertSessionHas('credenciales_admin');
        $cred = $response->getSession()->get('credenciales_admin');
        $this->assertSame($datos['email_admin'], $cred['email']);
        $this->assertNotEmpty($cred['password']);

        // La contraseña en sesión debe funcionar de verdad para iniciar sesión.
        $admin = User::withoutTenant()->where('email', $datos['email_admin'])->first();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($cred['password'], $admin->password));
    }

    public function test_el_administrador_creado_puede_iniciar_sesion_con_la_contrasena_generada(): void
    {
        $superAdmin = $this->crearSuperAdmin();
        $datos = $this->datosBase();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.tenants.store'), $datos);
        $cred = $response->getSession()->get('credenciales_admin');
        $tenant = Tenant::where('dominio', $datos['dominio'])->first();

        // El login real filtra por tenant (User usa BelongsToTenant) —
        // hay que resolverlo por el subdominio de la institución, igual
        // que en producción, no por el host de prueba genérico.
        $this->post('http://' . $tenant->dominio . '.zuraedu.test/login', [
            'email'    => $datos['email_admin'],
            'password' => $cred['password'],
        ]);

        $this->assertAuthenticatedAs(User::where('email', $datos['email_admin'])->first());
    }

    public function test_email_admin_duplicado_no_crea_la_institucion(): void
    {
        $superAdmin = $this->crearSuperAdmin();
        $existente = User::factory()->create(['email' => 'ya-existe@colegio.test']);

        $datos = $this->datosBase();
        $datos['email_admin'] = 'ya-existe@colegio.test';

        $response = $this->actingAs($superAdmin)->post(route('superadmin.tenants.store'), $datos);

        $response->assertSessionHasErrors('email_admin');
        $this->assertNull(Tenant::where('dominio', $datos['dominio'])->first());
    }
}
