<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Canal private-carnet.{tenantId} — hallazgo Alto/Crítico de la ronda de
 * auditoría de módulos grandes del 2026-09-03: CarnetEscaneado (nombre,
 * foto, hora de entrada/salida) transmitía por un Channel PÚBLICO sin
 * autenticación — cualquiera con la app key pública de Reverb podía
 * suscribirse sin sesión. Ahora es un PrivateChannel autorizado por
 * tenant + permiso ver-servicios (ver [[project_auditoria_modulos_
 * grandes_2026_09_03.md]]).
 */
class CarnetChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // phpunit.xml fija BROADCAST_CONNECTION=null para no depender de un
        // Reverb real corriendo durante los tests. routes/channels.php ya se
        // había cargado durante el boot de la app CON ese driver 'null' —
        // Broadcast::channel() registra cada canal en la instancia del
        // broadcaster que esté activa EN ESE MOMENTO, así que solo cambiar
        // el config acá no mueve los canales ya registrados a una nueva
        // instancia 'reverb' (queda con su propio array de canales vacío,
        // y /broadcasting/auth rechaza todo con 403 sin invocar ningún
        // callback — confirmado con logging directo a archivo). Forzamos el
        // driver y volvemos a cargar el archivo para registrar los canales
        // en la instancia correcta. El paso de autorización solo firma
        // localmente, no necesita un servidor Reverb corriendo de verdad.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => 'Colegio Canal',
            'dominio'            => 'colegiocanal' . random_int(1000, 9999),
            'estado'             => 'activo',
            'tipo'               => 'privado',
            'plan'               => 'free',
        ]);
    }

    private function usuario(Tenant $tenant, string $rol): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole($rol);
        return $user;
    }

    public function test_invitado_sin_sesion_no_puede_autorizar_el_canal(): void
    {
        $tenant = $this->tenant();

        // Sin usuario autenticado, Laravel rechaza la autorización de
        // broadcasting con 403 (no 401) antes de invocar el callback del canal.
        $this->postJson('/broadcasting/auth', [
            'channel_name' => "private-carnet.{$tenant->id}",
            'socket_id'    => '123.456',
        ])->assertForbidden();
    }

    public function test_docente_sin_permiso_ver_servicios_no_puede_autorizar_el_canal(): void
    {
        $tenant = $this->tenant();
        $docente = $this->usuario($tenant, 'Docente');

        $this->actingAs($docente)->postJson(
            '/broadcasting/auth',
            ['channel_name' => "private-carnet.{$tenant->id}", 'socket_id' => '123.456']
        )->assertForbidden();
    }

    public function test_recepcion_del_mismo_tenant_si_puede_autorizar_el_canal(): void
    {
        // URL relativa: en entorno de testing ResolveTenant::isLocal() siempre
        // es true (app()->environment('testing')), así que el tenant se
        // resuelve por el tenant_id del usuario autenticado, no por el host —
        // no hace falta un dominio real para probar la lógica del canal.
        $tenant = $this->tenant();
        $recepcion = $this->usuario($tenant, 'Recepción');

        $this->actingAs($recepcion)->postJson(
            '/broadcasting/auth',
            ['channel_name' => "private-carnet.{$tenant->id}", 'socket_id' => '123.456']
        )->assertOk();
    }

    public function test_recepcion_no_puede_autorizar_el_canal_de_otro_tenant(): void
    {
        $miTenant = $this->tenant();
        $otroTenant = $this->tenant();
        $recepcion = $this->usuario($miTenant, 'Recepción');

        $this->actingAs($recepcion)->postJson(
            '/broadcasting/auth',
            ['channel_name' => "private-carnet.{$otroTenant->id}", 'socket_id' => '123.456']
        )->assertForbidden();
    }
}
