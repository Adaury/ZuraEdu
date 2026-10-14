<?php

namespace Tests\Feature;

use App\Models\Docente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Sesión de la app móvil: un usuario desactivado no conserva acceso con un token ya emitido, el login
 * entrega todos los roles (la app decide el portal con ellos) y los tokens vencen.
 */
class ApiSesionMovilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function docente(string $rol = 'Docente'): Docente
    {
        $d = Docente::factory()->create();
        $d->user->assignRole($rol);
        $d->user->update(['activo' => true, 'password' => bcrypt('Secreto-123')]);

        return $d;
    }

    public function test_un_usuario_desactivado_pierde_el_acceso_y_su_token_se_revoca(): void
    {
        $user = $this->docente()->user;
        $token = $user->createToken('app-mobile')->plainTextToken;

        $this->withToken($token)->getJson(route('api.notificaciones.index'))->assertOk();

        $user->update(['activo' => false]);
        $this->app['auth']->forgetGuards();   // en pruebas el guard conserva al usuario de la petición anterior

        $this->withToken($token)->getJson(route('api.notificaciones.index'))->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());
    }

    public function test_un_usuario_con_aprobacion_pendiente_tampoco_usa_un_token_anterior(): void
    {
        $user = $this->docente()->user;
        $token = $user->createToken('app-mobile')->plainTextToken;
        $user->forceFill(['pendiente_aprobacion' => true])->save();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson(route('api.notificaciones.index'))->assertUnauthorized();
    }

    public function test_el_login_devuelve_todos_los_roles_del_usuario(): void
    {
        $d = $this->docente('Docente Académico');

        $r = $this->postJson(route('api.auth.login'), ['email' => $d->user->email, 'password' => 'Secreto-123'])->assertOk();

        $this->assertContains('Docente Académico', $r->json('user.roles'));
    }

    public function test_el_login_limita_los_intentos_por_correo_sin_bloquear_a_los_demas_de_la_misma_ip(): void
    {
        $victima = $this->docente()->user;
        $otro = $this->docente()->user;

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.auth.login'), ['email' => $victima->email, 'password' => 'mala'])->assertUnauthorized();
        }
        // El sexto intento (incluso con la clave correcta) queda bloqueado para ese correo
        $this->postJson(route('api.auth.login'), ['email' => $victima->email, 'password' => 'Secreto-123'])->assertStatus(429);

        // Otro usuario del mismo colegio (misma IP) entra sin problema
        $this->postJson(route('api.auth.login'), ['email' => $otro->email, 'password' => 'Secreto-123'])->assertOk();
    }

    public function test_un_correo_inexistente_recibe_el_mismo_error_que_una_clave_incorrecta(): void
    {
        $user = $this->docente()->user;

        $a = $this->postJson(route('api.auth.login'), ['email' => 'no.existe@colegio.test', 'password' => 'x'])->assertUnauthorized();
        $b = $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'x'])->assertUnauthorized();

        $this->assertSame($a->json('message'), $b->json('message'));
    }

    public function test_los_tokens_de_la_api_tienen_vencimiento(): void
    {
        $this->assertGreaterThan(0, (int) config('sanctum.expiration'));
    }
}
