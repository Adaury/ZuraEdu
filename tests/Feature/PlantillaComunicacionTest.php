<?php

namespace Tests\Feature;

use App\Models\PlantillaComunicacion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD de Plantillas de Comunicación. La ruta vive bajo el Gate
 * 'solo-administrador' (mismo grupo que WhatsApp/Email-notif en
 * routes/admin/sistema.php:153), no un permiso Spatie -- solo el rol
 * Administrador puede entrar.
 */
class PlantillaComunicacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function crearAdmin(string $nombreTenant): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => $nombreTenant,
            'dominio'            => strtolower(preg_replace('/[^a-z0-9]/i', '', $nombreTenant)) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        TenantFeature::create(['tenant_id' => $tenant->id, 'feature' => 'modo_publico', 'activo' => true]);

        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Administrador');

        return compact('tenant', 'user');
    }

    public function test_el_indice_marca_personalizada_y_predeterminada(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Indice');

        app()->instance('tenant', $tenant);
        PlantillaComunicacion::create([
            'tenant_id' => $tenant->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp',
            'cuerpo' => 'Texto personalizado', 'activa' => true,
        ]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->get(route('admin.plantillas.index'));

        $response->assertOk();
        $response->assertSee('Personalizada');
        $response->assertSee('Predeterminada');
    }

    public function test_edit_de_evento_inexistente_da_404(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Plantillas 404 Evento');

        $response = $this->actingAs($user)->get(route('admin.plantillas.edit', ['evento_que_no_existe', 'whatsapp']));

        $response->assertNotFound();
    }

    public function test_edit_de_par_evento_canal_invalido_da_404(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Plantillas 404 Canal');

        // boletin_disponible solo admite canal 'email', no 'whatsapp'.
        $response = $this->actingAs($user)->get(route('admin.plantillas.edit', ['boletin_disponible', 'whatsapp']));

        $response->assertNotFound();
    }

    public function test_update_crea_la_fila_con_el_tenant_correcto(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Crear');

        $response = $this->actingAs($user)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), [
            'cuerpo' => 'Hola {{estudiante}}, tu pago fue confirmado.', 'activa' => '1',
        ]);

        $response->assertRedirect(route('admin.plantillas.index'));

        app()->instance('tenant', $tenant);
        $fila = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->first();
        app()->forgetInstance('tenant');

        $this->assertNotNull($fila);
        $this->assertSame($tenant->id, $fila->tenant_id);
        $this->assertSame('Hola {{estudiante}}, tu pago fue confirmado.', $fila->cuerpo);
    }

    public function test_update_sobre_fila_existente_no_duplica(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas No Duplicar');

        $this->actingAs($user)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), ['cuerpo' => 'Primero', 'activa' => '1']);
        $this->actingAs($user)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), ['cuerpo' => 'Segundo', 'activa' => '1']);

        app()->instance('tenant', $tenant);
        $count = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->count();
        $cuerpo = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->value('cuerpo');
        app()->forgetInstance('tenant');

        $this->assertSame(1, $count);
        $this->assertSame('Segundo', $cuerpo);
    }

    public function test_update_rechaza_variable_no_reconocida(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Plantillas Var Invalida');

        $response = $this->actingAs($user)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), [
            'cuerpo' => 'Hola {{cosa_inventada}}', 'activa' => '1',
        ]);

        $response->assertSessionHasErrors('cuerpo');
    }

    public function test_update_normaliza_espacios_dentro_de_las_llaves(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Normalizar');

        $this->actingAs($user)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), [
            'cuerpo' => 'Hola {{ estudiante }}', 'activa' => '1',
        ]);

        app()->instance('tenant', $tenant);
        $cuerpo = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->value('cuerpo');
        app()->forgetInstance('tenant');

        $this->assertSame('Hola {{estudiante}}', $cuerpo);
    }

    public function test_email_conserva_html_permitido_pero_pierde_script(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Email Html');

        $this->actingAs($user)->put(route('admin.plantillas.update', ['boletin_disponible', 'email']), [
            'asunto' => 'Asunto', 'cuerpo' => '<script>alert(1)</script><p>Hola <strong>{{estudiante}}</strong></p>', 'activa' => '1',
        ]);

        app()->instance('tenant', $tenant);
        $cuerpo = PlantillaComunicacion::where('evento', 'boletin_disponible')->where('canal', 'email')->value('cuerpo');
        app()->forgetInstance('tenant');

        $this->assertStringNotContainsString('<script>', $cuerpo);
        $this->assertStringContainsString('<strong>', $cuerpo);
    }

    public function test_whatsapp_pierde_todo_el_html(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Whatsapp Html');

        $this->actingAs($user)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), [
            'cuerpo' => '<b>Hola</b> {{estudiante}}', 'activa' => '1',
        ]);

        app()->instance('tenant', $tenant);
        $cuerpo = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->value('cuerpo');
        app()->forgetInstance('tenant');

        $this->assertSame('Hola {{estudiante}}', $cuerpo);
    }

    public function test_bienvenida_representante_sin_clave_temporal_da_error(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Plantillas Bienvenida');

        $response = $this->actingAs($user)->put(route('admin.plantillas.update', ['bienvenida_representante', 'email']), [
            'asunto' => 'Bienvenido', 'cuerpo' => 'Hola {{representante}}, ya puedes entrar con tu {{usuario}}.', 'activa' => '1',
        ]);

        $response->assertSessionHasErrors('cuerpo');
    }

    public function test_bienvenida_representante_con_clave_temporal_se_guarda(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Bienvenida Ok');

        $response = $this->actingAs($user)->put(route('admin.plantillas.update', ['bienvenida_representante', 'email']), [
            'asunto' => 'Bienvenido', 'cuerpo' => 'Usuario: {{usuario}} Clave: {{clave_temporal}}', 'activa' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        app()->instance('tenant', $tenant);
        $existe = PlantillaComunicacion::where('evento', 'bienvenida_representante')->where('canal', 'email')->exists();
        app()->forgetInstance('tenant');

        $this->assertTrue($existe);
    }

    public function test_toggle_invierte_el_estado_activa(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Toggle');

        app()->instance('tenant', $tenant);
        PlantillaComunicacion::create(['tenant_id' => $tenant->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp', 'cuerpo' => 'x', 'activa' => true]);
        app()->forgetInstance('tenant');

        $response = $this->actingAs($user)->patch(route('admin.plantillas.toggle', ['pago_confirmado', 'whatsapp']));

        $response->assertJson(['ok' => true, 'activa' => false]);
    }

    public function test_toggle_sin_fila_existente_da_404(): void
    {
        ['user' => $user] = $this->crearAdmin('Colegio Plantillas Toggle 404');

        $response = $this->actingAs($user)->patch(route('admin.plantillas.toggle', ['pago_confirmado', 'whatsapp']));

        $response->assertNotFound();
    }

    public function test_destroy_borra_la_fila(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Destroy');

        app()->instance('tenant', $tenant);
        PlantillaComunicacion::create(['tenant_id' => $tenant->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp', 'cuerpo' => 'x', 'activa' => true]);
        app()->forgetInstance('tenant');

        $this->actingAs($user)->delete(route('admin.plantillas.destroy', ['pago_confirmado', 'whatsapp']));

        app()->instance('tenant', $tenant);
        $existe = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->exists();
        app()->forgetInstance('tenant');

        $this->assertFalse($existe);
    }

    public function test_destroy_invalida_la_cache_del_servicio(): void
    {
        // Regresión: destroy() debe borrar vía una INSTANCIA de Eloquent, no
        // un delete masivo de query builder -- este último no dispara el
        // evento 'deleted' del que depende PlantillaComunicacion::booted()
        // para invalidar la caché de PlantillaComunicacionService, dejando
        // "Restaurar predeterminado" sirviendo el texto viejo hasta que
        // expire el caché de 300s.
        ['tenant' => $tenant, 'user' => $user] = $this->crearAdmin('Colegio Plantillas Destroy Cache');

        app()->instance('tenant', $tenant);
        PlantillaComunicacion::create(['tenant_id' => $tenant->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp', 'cuerpo' => 'TEXTO VIEJO', 'activa' => true]);
        $this->assertSame('TEXTO VIEJO', \App\Services\PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []));
        app()->forgetInstance('tenant');

        $this->actingAs($user)->delete(route('admin.plantillas.destroy', ['pago_confirmado', 'whatsapp']));

        app()->instance('tenant', $tenant);
        $resultado = \App\Services\PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []);
        app()->forgetInstance('tenant');

        $this->assertNull($resultado, 'Tras restaurar el predeterminado, render() debe volver a null (hardcode) sin esperar a que expire el caché.');
    }

    public function test_un_rol_sin_solo_administrador_recibe_403(): void
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Plantillas Sin Permiso',
            'dominio'            => 'colegioplantillassinpermiso' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Coordinador Académico'); // Gate 'solo-administrador' exige exactamente rol Administrador.

        $response = $this->actingAs($user)->get(route('admin.plantillas.index'));

        $response->assertForbidden();
    }

    public function test_aislamiento_cross_tenant_en_edit_y_update(): void
    {
        ['tenant' => $tenantA, 'user' => $userA] = $this->crearAdmin('Colegio Plantillas Aislar A');
        ['tenant' => $tenantB] = $this->crearAdmin('Colegio Plantillas Aislar B');

        app()->instance('tenant', $tenantB);
        PlantillaComunicacion::create(['tenant_id' => $tenantB->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp', 'cuerpo' => 'Texto de B', 'activa' => true]);
        app()->forgetInstance('tenant');

        // A edita el mismo evento+canal -- no debe ver ni pisar la fila de B.
        $this->actingAs($userA)->put(route('admin.plantillas.update', ['pago_confirmado', 'whatsapp']), ['cuerpo' => 'Texto de A', 'activa' => '1']);

        app()->instance('tenant', $tenantB);
        $cuerpoB = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->value('cuerpo');
        app()->forgetInstance('tenant');

        app()->instance('tenant', $tenantA);
        $cuerpoA = PlantillaComunicacion::where('evento', 'pago_confirmado')->where('canal', 'whatsapp')->value('cuerpo');
        app()->forgetInstance('tenant');

        $this->assertSame('Texto de B', $cuerpoB, 'La fila de B no debe alterarse por la edición de A.');
        $this->assertSame('Texto de A', $cuerpoA);
    }
}
