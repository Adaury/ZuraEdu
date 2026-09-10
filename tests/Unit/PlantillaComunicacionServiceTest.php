<?php

namespace Tests\Unit;

use App\Models\PlantillaComunicacion;
use App\Models\Tenant;
use App\Services\PlantillaComunicacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PlantillaComunicacionService::render()/renderEmail() -- el motor de
 * sustitución de las Plantillas de Comunicación (WhatsApp/email editables
 * por tenant, Fase 5). El sistema hardcodeado actual sigue siendo el
 * fallback permanente: null = "el caller debe usar su texto de siempre".
 */
class PlantillaComunicacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $sufijo = 'A'): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => "Colegio {$sufijo}",
            'dominio'            => 'colegioplantilla' . strtolower($sufijo) . random_int(10000, 99999),
            'estado'             => 'activo', 'tipo' => 'privado', 'plan' => 'free',
        ]);
    }

    private function crearPlantilla(Tenant $tenant, string $evento, string $canal, ?string $cuerpo, bool $activa = true, ?string $asunto = null): PlantillaComunicacion
    {
        app()->instance('tenant', $tenant);
        $fila = PlantillaComunicacion::create([
            'tenant_id' => $tenant->id, 'evento' => $evento, 'canal' => $canal,
            'asunto' => $asunto, 'cuerpo' => $cuerpo, 'activa' => $activa,
        ]);
        app()->forgetInstance('tenant');

        return $fila;
    }

    public function test_render_devuelve_null_sin_plantilla(): void
    {
        $t = $this->tenant();
        app()->instance('tenant', $t);

        $this->assertNull(PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', ['centro' => 'X']));

        app()->forgetInstance('tenant');
    }

    public function test_render_sustituye_variables_con_plantilla_activa(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', 'Hola {{estudiante}}, tu pago de {{monto}} fue confirmado.');

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', [
            'estudiante' => 'Ana Pérez', 'monto' => 'RD$ 4,500.00',
        ]);
        app()->forgetInstance('tenant');

        $this->assertSame('Hola Ana Pérez, tu pago de RD$ 4,500.00 fue confirmado.', $resultado);
    }

    public function test_render_devuelve_null_cuando_la_plantilla_esta_inactiva(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', 'Texto personalizado', activa: false);

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []);
        app()->forgetInstance('tenant');

        $this->assertNull($resultado);
    }

    public function test_un_placeholder_desconocido_se_elimina_del_texto_renderizado(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', 'Hola {{no_existe}} mundo.');

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []);
        app()->forgetInstance('tenant');

        $this->assertSame('Hola mundo.', $resultado);
        $this->assertStringNotContainsString('{{', $resultado);
    }

    public function test_una_variable_no_provista_por_el_caller_se_elimina(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', '{{estudiante}} - {{monto}}');

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', ['estudiante' => 'Ana']);
        app()->forgetInstance('tenant');

        $this->assertSame('Ana -', $resultado);
    }

    public function test_un_valor_de_datos_con_placeholder_literal_no_se_reexpande(): void
    {
        // Garantía central de seguridad: strtr() sustituye en una sola
        // pasada. Si el nombre de un estudiante contuviera literalmente
        // "{{clave_temporal}}", NO debe expandirse a la contraseña real.
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', 'Estudiante: {{estudiante}}');

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', [
            'estudiante'     => 'Ana {{clave_temporal}}',
            'clave_temporal' => 'SECRETO123',
        ]);
        app()->forgetInstance('tenant');

        $this->assertStringNotContainsString('SECRETO123', $resultado);
    }

    public function test_variables_con_prefijo_compartido_no_colisionan(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', '{{monto}} de {{monto_total}}');

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', [
            'monto' => 'RD$100', 'monto_total' => 'RD$900',
        ]);
        app()->forgetInstance('tenant');

        $this->assertSame('RD$100 de RD$900', $resultado);
    }

    public function test_canal_email_escapa_html_de_los_valores(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'boletin_disponible', 'email', 'Hola {{estudiante}}', asunto: null);

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::renderEmail('boletin_disponible', [
            'estudiante' => '<script>alert(1)</script>',
        ]);
        app()->forgetInstance('tenant');

        $this->assertStringNotContainsString('<script>', $resultado['cuerpo']);
        $this->assertStringContainsString('&lt;script&gt;', $resultado['cuerpo']);
    }

    public function test_canal_whatsapp_no_escapa_html(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', 'Hola {{estudiante}}');

        app()->instance('tenant', $t);
        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', [
            'estudiante' => 'Ana & Cía',
        ]);
        app()->forgetInstance('tenant');

        $this->assertSame('Hola Ana & Cía', $resultado);
    }

    public function test_render_devuelve_null_sin_tenant_bindeado(): void
    {
        // Simula un webhook de plataforma o un comando de consola sin
        // LoopsPerTenant -- tenant_id() caería a 1 como fallback, por eso
        // el servicio usa tenant() (que sí es null) para esta comprobación.
        $this->assertFalse(app()->bound('tenant'));

        $resultado = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', ['centro' => 'X']);

        $this->assertNull($resultado);
    }

    public function test_renderEmail_soporta_asunto_y_cuerpo_independientes(): void
    {
        $t = $this->tenant();
        $this->crearPlantilla($t, 'boletin_disponible', 'email', null, asunto: 'Asunto personalizado');

        app()->instance('tenant', $t);
        $soloAsunto = PlantillaComunicacionService::renderEmail('boletin_disponible', []);
        app()->forgetInstance('tenant');

        $this->assertSame('Asunto personalizado', $soloAsunto['asunto']);
        $this->assertNull($soloAsunto['cuerpo']);
    }

    public function test_aislamiento_cross_tenant_incluido_el_memo_estatico(): void
    {
        $a = $this->tenant('A');
        $b = $this->tenant('B');
        $this->crearPlantilla($a, 'pago_confirmado', 'whatsapp', 'Texto de A');
        $this->crearPlantilla($b, 'pago_confirmado', 'whatsapp', 'Texto de B');

        // Simula un worker/comando: procesa A primero (puebla el memo
        // estático), luego B, sin ningún flush de por medio.
        app()->instance('tenant', $a);
        $resultadoA = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []);
        app()->forgetInstance('tenant');

        app()->instance('tenant', $b);
        $resultadoB = PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []);
        app()->forgetInstance('tenant');

        $this->assertSame('Texto de A', $resultadoA);
        $this->assertSame('Texto de B', $resultadoB, 'Tenant B no debe heredar el texto cacheado de tenant A.');
    }

    public function test_guardar_invalida_la_cache_y_el_siguiente_render_ve_el_texto_nuevo(): void
    {
        $t = $this->tenant();
        $fila = $this->crearPlantilla($t, 'pago_confirmado', 'whatsapp', 'Texto viejo');

        app()->instance('tenant', $t);
        $this->assertSame('Texto viejo', PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []));

        $fila->update(['cuerpo' => 'Texto nuevo']);

        $this->assertSame('Texto nuevo', PlantillaComunicacionService::render('pago_confirmado', 'whatsapp', []));
        app()->forgetInstance('tenant');
    }

    public function test_normalizar_colapsa_espacios_dentro_de_las_llaves(): void
    {
        $this->assertSame('Hola {{estudiante}}', PlantillaComunicacionService::normalizar('Hola {{ estudiante }}'));
        $this->assertSame('{{monto}}', PlantillaComunicacionService::normalizar('{{monto }}'));
    }

    public function test_variablesDesconocidas_detecta_las_que_no_pertenecen_al_evento(): void
    {
        $desconocidas = PlantillaComunicacionService::variablesDesconocidas(
            'Hola {{estudiante}}, ver {{cosa_inventada}} y {{monto}}',
            'pago_confirmado'
        );

        $this->assertSame(['cosa_inventada'], $desconocidas);
    }

    public function test_variablesDesconocidas_no_reporta_falsos_positivos(): void
    {
        $desconocidas = PlantillaComunicacionService::variablesDesconocidas(
            'Hola {{estudiante}}, tu pago de {{monto}} el {{fecha}}',
            'pago_confirmado'
        );

        $this->assertEmpty($desconocidas);
    }
}
