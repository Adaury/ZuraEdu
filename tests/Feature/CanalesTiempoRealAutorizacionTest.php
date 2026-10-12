<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Asignatura;
use App\Models\ClaseVirtual;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Canales de tiempo real: el chat interno del personal no lo escucha un estudiante ni un
 * representante del mismo centro (solo quien entra al panel admin); un estudiante retirado
 * deja de recibir el aula y el grupo; y el soporte lo escucha el personal con acceso al panel.
 */
class CanalesTiempoRealAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // Ver CarnetChannelAuthorizationTest: los canales deben registrarse en el broadcaster 'reverb'.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');
    }

    private function tenant(): Tenant
    {
        $t = Tenant::create([
            'nombre_institucion' => 'Colegio Canales',
            'dominio'            => 'canales' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
        app()->instance('tenant', $t);

        return $t;
    }

    private function usuario(Tenant $t, string $rol): User
    {
        $u = User::factory()->create(['activo' => true, 'tenant_id' => $t->id]);
        $u->assignRole($rol);

        return $u;
    }

    private function auth(User $u, string $canal)
    {
        return $this->actingAs($u)->postJson('/broadcasting/auth', ['channel_name' => $canal, 'socket_id' => '123.456']);
    }

    public function test_el_chat_interno_solo_lo_escucha_el_personal_del_panel(): void
    {
        $t = $this->tenant();
        $canal = "private-tenant.{$t->id}.chat";

        foreach (['Estudiante', 'Representante', 'Docente'] as $rol) {
            $this->auth($this->usuario($t, $rol), $canal)->assertForbidden();
        }

        foreach (['Administrador', 'Secretaría', 'Recepción'] as $rol) {
            $this->auth($this->usuario($t, $rol), $canal)->assertOk();
        }
    }

    public function test_el_soporte_lo_escucha_el_personal_con_acceso_al_panel(): void
    {
        $t = $this->tenant();
        $canal = "private-tenant.{$t->id}.support";

        $this->auth($this->usuario($t, 'Estudiante'), $canal)->assertForbidden();
        $this->auth($this->usuario($t, 'Secretaría'), $canal)->assertOk();
        $this->auth($this->usuario($t, 'Administrador'), $canal)->assertOk();
    }

    public function test_un_estudiante_retirado_deja_de_recibir_el_aula_y_el_grupo(): void
    {
        $t       = $this->tenant();
        $sy      = SchoolYear::create(['nombre' => '2026-Canales', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $grado   = Grado::create(['nombre' => 'Grado CN', 'nivel' => 100 + random_int(1, 20), 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $sy->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $docente = Docente::factory()->create();
        $asig    = Asignacion::create([
            'school_year_id' => $sy->id, 'grupo_id' => $grupo->id,
            'asignatura_id'  => Asignatura::create(['codigo' => 'CN' . random_int(1000, 9999), 'nombre' => 'Lengua', 'area' => 'academica', 'activo' => true])->id,
            'docente_id' => $docente->id, 'activo' => true, 'area' => 'academica',
        ]);
        $clase = ClaseVirtual::create(['asignacion_id' => $asig->id, 'nombre' => 'Aula CN', 'activo' => true]);

        $mk = function () use ($t, $sy, $grupo) {
            $user = $this->usuario($t, 'Estudiante');
            $est  = Estudiante::factory()->create(['user_id' => $user->id]);
            $mat  = Matricula::create([
                'school_year_id' => $sy->id, 'estudiante_id' => $est->id, 'grupo_id' => $grupo->id,
                'fecha_matricula' => '2025-08-15', 'numero_orden' => random_int(1, 9999), 'estado' => 'activa',
            ]);

            return [$user, $mat];
        };

        [$activo]         = $mk();
        [$retirado, $mat] = $mk();

        foreach (["private-classroom.{$clase->id}", "private-grupo.{$grupo->id}", "presence-classroom-presence.{$clase->id}"] as $canal) {
            $this->auth($activo, $canal)->assertOk();
            $this->auth($retirado, $canal)->assertOk();   // todavía activo
        }

        $mat->update(['estado' => 'retirada']);

        foreach (["private-classroom.{$clase->id}", "private-grupo.{$grupo->id}", "presence-classroom-presence.{$clase->id}"] as $canal) {
            $this->auth($activo, $canal)->assertOk();
            $this->auth($retirado, $canal)->assertForbidden();
        }
    }
}
