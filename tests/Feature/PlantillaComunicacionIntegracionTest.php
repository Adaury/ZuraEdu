<?php

namespace Tests\Feature;

use App\Events\PagoConfirmado;
use App\Jobs\EnviarWhatsApp;
use App\Listeners\NotificarPagoConfirmado;
use App\Mail\BoletinDisponible;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\PlantillaComunicacion;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Prueba la tesis central de las Plantillas de Comunicación: un evento REAL
 * del sistema (pago confirmado, boletín disponible) usa el texto
 * personalizado del tenant cuando existe, y el hardcode de siempre cuando no
 * — sin que el flujo original (WhatsAppService, Mailables) se entere de la
 * diferencia.
 */
class PlantillaComunicacionIntegracionTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $sufijo = 'A'): Tenant
    {
        $tenant = Tenant::create([
            'nombre_institucion' => "Colegio Integracion {$sufijo}",
            'dominio'            => 'colegiointegracion' . strtolower($sufijo) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);

        // WhatsAppService::send() exige este flag (Setting::moduleEnabled),
        // por defecto false -- sin esto, send() retorna antes de despachar
        // EnviarWhatsApp y ninguna plantilla llega a evaluarse.
        app()->instance('tenant', $tenant);
        \App\Helpers\Setting::set('module_whatsapp', '1');
        app()->forgetInstance('tenant');

        return $tenant;
    }

    private function crearPago(Tenant $tenant): Pago
    {
        app()->instance('tenant', $tenant);

        $schoolYear = SchoolYear::create([
            'nombre' => '20' . random_int(26, 99) . '-' . chr(random_int(65, 90)) . random_int(0, 9),
            'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true,
        ]);
        $grado   = Grado::create(['nombre' => 'Grado I' . random_int(1, 9999), 'nivel' => 1, 'orden' => 1, 'ciclo' => 'primer_ciclo', 'activo' => true]);
        $seccion = Seccion::firstOrCreate(['nombre' => 'A'], ['orden' => 1]);
        $grupo   = Grupo::create(['school_year_id' => $schoolYear->id, 'grado_id' => $grado->id, 'seccion_id' => $seccion->id, 'activo' => true]);
        $estudiante = Estudiante::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Pérez']);
        $representante = Representante::factory()->create(['telefono' => '8095551234']);
        $estudiante->representantes()->attach($representante->id, ['es_principal' => true]);
        $matricula  = Matricula::create([
            'school_year_id' => $schoolYear->id, 'estudiante_id' => $estudiante->id, 'grupo_id' => $grupo->id,
            'fecha_matricula' => '2025-08-15', 'numero_orden' => 1, 'estado' => 'activa',
        ]);

        $pago = Pago::create([
            'matricula_id' => $matricula->id, 'concepto' => 'Mensualidad Marzo', 'monto' => 4500,
            'fecha_vencimiento' => '2026-01-31', 'fecha_pago' => '2026-01-15',
            'metodo_pago' => 'transferencia', 'estado' => 'pagado',
        ]);

        app()->forgetInstance('tenant');

        return $pago;
    }

    private function dispararPagoConfirmado(Pago $pago): void
    {
        // Se llama directo al listener (no event()) porque
        // NotificarPagoConfirmado implementa ShouldQueue -- despachar el
        // EVENTO encolaría el LISTENER completo, y Queue::fake() (necesario
        // para capturar el EnviarWhatsApp interno) también interceptaría esa
        // capa externa, impidiendo que el listener llegue a ejecutarse.
        (new NotificarPagoConfirmado())->handle(new PagoConfirmado($pago));
    }

    public function test_pago_confirmado_usa_la_plantilla_personalizada_cuando_existe(): void
    {
        $tenant = $this->crearTenant();
        $pago = $this->crearPago($tenant);

        app()->instance('tenant', $tenant);
        PlantillaComunicacion::create([
            'tenant_id' => $tenant->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp',
            'cuerpo' => 'TEXTO PERSONALIZADO para {{estudiante}}, monto {{monto}}.', 'activa' => true,
        ]);
        app()->forgetInstance('tenant');

        Queue::fake();
        $this->dispararPagoConfirmado($pago);

        Queue::assertPushed(EnviarWhatsApp::class, function ($job) {
            return str_contains($job->message, 'TEXTO PERSONALIZADO para Pérez, Ana');
        });
    }

    public function test_pago_confirmado_usa_el_hardcode_sin_plantilla(): void
    {
        $tenant = $this->crearTenant();
        $pago = $this->crearPago($tenant);

        Queue::fake();
        $this->dispararPagoConfirmado($pago);

        Queue::assertPushed(EnviarWhatsApp::class, function ($job) {
            return str_contains($job->message, 'ha sido confirmado') && ! str_contains($job->message, 'TEXTO PERSONALIZADO');
        });
    }

    public function test_pago_confirmado_con_plantilla_inactiva_usa_el_hardcode(): void
    {
        $tenant = $this->crearTenant();
        $pago = $this->crearPago($tenant);

        app()->instance('tenant', $tenant);
        PlantillaComunicacion::create([
            'tenant_id' => $tenant->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp',
            'cuerpo' => 'TEXTO PERSONALIZADO', 'activa' => false,
        ]);
        app()->forgetInstance('tenant');

        Queue::fake();
        $this->dispararPagoConfirmado($pago);

        Queue::assertPushed(EnviarWhatsApp::class, function ($job) {
            return str_contains($job->message, 'ha sido confirmado') && ! str_contains($job->message, 'TEXTO PERSONALIZADO');
        });
    }

    public function test_la_plantilla_de_un_tenant_no_afecta_el_envio_de_otro(): void
    {
        $tenantA = $this->crearTenant('A');
        $tenantB = $this->crearTenant('B');
        $pagoB = $this->crearPago($tenantB);

        app()->instance('tenant', $tenantA);
        PlantillaComunicacion::create([
            'tenant_id' => $tenantA->id, 'evento' => 'pago_confirmado', 'canal' => 'whatsapp',
            'cuerpo' => 'TEXTO DE A', 'activa' => true,
        ]);
        app()->forgetInstance('tenant');

        Queue::fake();
        $this->dispararPagoConfirmado($pagoB);

        Queue::assertPushed(EnviarWhatsApp::class, function ($job) {
            return ! str_contains($job->message, 'TEXTO DE A');
        });
    }

    public function test_boletin_disponible_usa_asunto_y_cuerpo_personalizados(): void
    {
        $tenant = $this->crearTenant();

        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2026-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Primer Período', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true]);
        $estudiante = Estudiante::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Pérez']);

        PlantillaComunicacion::create([
            'tenant_id' => $tenant->id, 'evento' => 'boletin_disponible', 'canal' => 'email',
            'asunto' => 'Asunto personalizado para {{estudiante}}',
            'cuerpo' => '<p>Cuerpo personalizado de {{estudiante}}</p>', 'activa' => true,
        ]);

        $mailable = new BoletinDisponible($estudiante, $periodo, 'https://x.test/boletin');
        app()->forgetInstance('tenant');

        $this->assertSame('Asunto personalizado para Pérez, Ana', $mailable->envelope()->subject);
        $this->assertStringContainsString('Cuerpo personalizado de Pérez, Ana', $mailable->render());
    }

    public function test_boletin_disponible_sin_plantilla_usa_el_diseno_de_siempre(): void
    {
        $tenant = $this->crearTenant();

        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2026-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Primer Período', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true]);
        $estudiante = Estudiante::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Pérez']);

        $mailable = new BoletinDisponible($estudiante, $periodo, 'https://x.test/boletin');
        app()->forgetInstance('tenant');

        $this->assertStringContainsString('Boletín disponible', $mailable->envelope()->subject);
        $this->assertStringContainsString('Pérez, Ana', $mailable->envelope()->subject);
    }

    /**
     * Garantía arquitectónica central: la plantilla se resuelve en el
     * CONSTRUCTOR del Mailable (donde el tenant sí está bindeado), no en
     * envelope()/content() (que Laravel ejecuta en el worker de cola, donde
     * el tenant podría no estarlo). Si alguien mueve resolverPlantilla() a
     * content() por error, este test debe fallar.
     */
    public function test_el_mailable_sigue_devolviendo_el_texto_personalizado_sin_tenant_bindeado_al_renderizar(): void
    {
        $tenant = $this->crearTenant();

        app()->instance('tenant', $tenant);
        $sy = SchoolYear::create(['nombre' => '2026-A', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2026-06-30', 'activo' => true]);
        $periodo = Periodo::create(['school_year_id' => $sy->id, 'numero' => 1, 'nombre' => 'Primer Período', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-10-31', 'activo' => true]);
        $estudiante = Estudiante::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Pérez']);

        PlantillaComunicacion::create([
            'tenant_id' => $tenant->id, 'evento' => 'boletin_disponible', 'canal' => 'email',
            'cuerpo' => '<p>Cuerpo persistente de {{estudiante}}</p>', 'activa' => true,
        ]);

        // Construir el Mailable CON tenant bindeado (como ocurriría en el
        // request/comando que dispara el envío)...
        $mailable = new BoletinDisponible($estudiante, $periodo, 'https://x.test/boletin');

        // ...pero simular que, para cuando el worker de cola lo renderiza,
        // el tenant YA NO está bindeado.
        app()->forgetInstance('tenant');

        $this->assertStringContainsString('Cuerpo persistente de Pérez, Ana', $mailable->render());
    }
}
