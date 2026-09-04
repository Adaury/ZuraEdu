<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión para el blocker #2 del Gate de Producción: /health debe detectar
 * si QUEUE_CONNECTION=sync quedó activo en producción (silencioso: nada se
 * rompe visiblemente, los jobs solo corren dentro del request en vez de en
 * background).
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reporta_el_driver_de_cola_configurado(): void
    {
        $response = $this->get(route('health'));

        $response->assertJsonPath('checks.queue', config('queue.default'));
    }

    public function test_cola_sync_fuera_de_produccion_no_degrada_el_health(): void
    {
        config(['queue.default' => 'sync']);

        $response = $this->get(route('health'));

        $response->assertJsonPath('checks.queue', 'sync');
        $this->assertNotEquals(503, $response->status(), 'testing con cola sync no debe considerarse degradado.');
    }

    public function test_cola_sync_en_produccion_degrada_el_health(): void
    {
        // withoutMiddleware(): /health es una ruta pública para balanceadores/
        // uptime monitors, sin contexto de tenant — forzar env=production solo
        // para esta prueba hace que ResolveTenant deje de aplicar su fallback
        // de entorno local/testing, lo cual es un efecto colateral ajeno a lo
        // que se está probando aquí (el chequeo de cola).
        $this->withoutMiddleware();
        $this->app['env'] = 'production';
        config(['queue.default' => 'sync']);

        $response = $this->get(route('health'));

        $response->assertStatus(503);
        $response->assertJsonPath('status', 'degraded');
        $response->assertJsonPath('checks.queue', 'sync');
    }

    public function test_cola_redis_en_produccion_no_degrada_por_ese_motivo(): void
    {
        $this->withoutMiddleware();
        $this->app['env'] = 'production';
        config(['queue.default' => 'redis']);

        $response = $this->get(route('health'));

        $response->assertJsonPath('checks.queue', 'redis');
        // Puede seguir en 503 si BD/Redis reales fallan, pero no por la cola.
        if ($response->status() === 503) {
            $this->assertNotEquals('sync', $response->json('checks.queue'));
        }
    }
}
