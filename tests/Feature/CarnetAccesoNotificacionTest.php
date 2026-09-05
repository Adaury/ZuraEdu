<?php

namespace Tests\Feature;

use App\Helpers\Setting;
use App\Jobs\EnviarNotificacionJob;
use App\Jobs\EnviarWhatsApp;
use App\Jobs\NotificarPadreAccesoJob;
use App\Models\CarnetIdentidad;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Roadmap P0 #2 (docs/ROADMAP_PRODUCTO_ZURAEDU_2026_2027.md, GAP 03):
 * NotificarPadreAccesoJob ya notificaba in-app al representante, pero
 * nunca por WhatsApp (el canal ya consolidado en pagos/SIGERD/
 * calificaciones). Además, CarnetApiController::scan() (app móvil) nunca
 * disparaba este job en absoluto — inconsistencia real con el kiosco admin
 * (CarnetCheckinController::scan()), que sí lo hacía.
 */
class CarnetAccesoNotificacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: Tenant, 1: CarnetIdentidad, 2: Representante} */
    private function escenarioConMatricula(?string $telefono = '8091234567'): array
    {
        $tenant = Tenant::create([
            'nombre_institucion' => 'Colegio Carnet',
            'dominio'            => 'colegiocarnet' . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        app()->instance('tenant', $tenant);
        Setting::setMany(['module_whatsapp' => '1', 'whatsapp_provider' => 'twilio']);

        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $grado   = Grado::create(['nombre' => 'Grado C' . random_int(1, 9999), 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);

        $estudiante   = Estudiante::factory()->create();
        $representante = Representante::factory()->create(['telefono' => $telefono]);
        $estudiante->representantes()->attach($representante->id, ['es_principal' => true]);

        $matricula = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $estudianteUser = User::factory()->create(['activo' => true]);
        $carnet = CarnetIdentidad::create([
            'tipo' => 'estudiante', 'user_id' => $estudianteUser->id, 'matricula_id' => $matricula->id,
            'numero_carnet' => 'C-TEST-' . random_int(1000, 9999),
            'qr_token' => 'qr_test_' . random_int(100000, 999999),
            'estado' => 'activo',
        ]);

        app()->forgetInstance('tenant');

        return [$tenant, $carnet, $representante];
    }

    private function staff(Tenant $tenant): User
    {
        $user = User::factory()->create(['activo' => true, 'tenant_id' => $tenant->id]);
        $user->assignRole('Recepción');
        return $user;
    }

    public function test_el_kiosco_admin_despacha_el_job_de_notificacion(): void
    {
        Queue::fake();
        [$tenant, $carnet] = $this->escenarioConMatricula();

        $this->actingAs($this->staff($tenant))
            ->postJson(route('admin.carnet.scan'), ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk();

        Queue::assertPushed(NotificarPadreAccesoJob::class, fn($job) => $job->carnetId === $carnet->id);
    }

    public function test_la_api_movil_ahora_tambien_despacha_el_job_de_notificacion(): void
    {
        // Regresión del hallazgo real: antes de este fix, esta ruta creaba
        // el CarnetAcceso pero NUNCA despachaba NotificarPadreAccesoJob —
        // inconsistencia con el kiosco admin (test anterior).
        Queue::fake();
        [$tenant, $carnet] = $this->escenarioConMatricula();

        Sanctum::actingAs($this->staff($tenant));

        $this->postJson('/api/v1/carnet/scan', ['qr_token' => $carnet->qr_token, 'tipo_evento' => 'entrada'])
            ->assertOk();

        Queue::assertPushed(NotificarPadreAccesoJob::class, fn($job) => $job->carnetId === $carnet->id);
    }

    public function test_el_job_envia_whatsapp_al_representante_con_telefono(): void
    {
        Queue::fake();
        [$tenant, $carnet, $representante] = $this->escenarioConMatricula('8095551234');

        app()->instance('tenant', $tenant);
        (new NotificarPadreAccesoJob(
            carnetId: $carnet->id, tipoEvento: 'entrada', estado: 'presente',
            hora: '07:15 AM', tenantId: $tenant->id,
        ))->handle();
        app()->forgetInstance('tenant');

        Queue::assertPushed(EnviarWhatsApp::class, fn($job) => $job->to === '8095551234');
    }

    public function test_el_job_no_envia_whatsapp_si_el_representante_no_tiene_telefono(): void
    {
        Queue::fake();
        [$tenant, $carnet] = $this->escenarioConMatricula(null);

        app()->instance('tenant', $tenant);
        (new NotificarPadreAccesoJob(
            carnetId: $carnet->id, tipoEvento: 'entrada', estado: 'presente',
            hora: '07:15 AM', tenantId: $tenant->id,
        ))->handle();
        app()->forgetInstance('tenant');

        Queue::assertNotPushed(EnviarWhatsApp::class);
    }

    public function test_el_job_sigue_enviando_la_notificacion_inapp_ademas_del_whatsapp(): void
    {
        Queue::fake();
        [$tenant, $carnet, $representante] = $this->escenarioConMatricula();

        app()->instance('tenant', $tenant);
        (new NotificarPadreAccesoJob(
            carnetId: $carnet->id, tipoEvento: 'salida', estado: 'salida_anticipada',
            hora: '02:30 PM', tenantId: $tenant->id,
        ))->handle();
        app()->forgetInstance('tenant');

        Queue::assertPushed(EnviarNotificacionJob::class, fn($job) => $job->userId === $representante->user_id);
    }
}
