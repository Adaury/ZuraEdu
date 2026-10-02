<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * TrustProxies confiaba en CUALQUIERA (`'*'`): un cliente mandaba `X-Forwarded-For: 1.2.3.4` y Laravel tomaba esa IP como suya (se comprobó
 * con Nginx + FastCGI reales). Con eso se esquivaba el límite de intentos de login por IP y se falsificaba la IP de la auditoría.
 * Ahora solo se confía en los proxies de `config/proxies.php` (`TRUSTED_PROXIES`, por defecto loopback).
 *
 * No necesitan base de datos: ejercitan el middleware global real con un cliente cuya `REMOTE_ADDR` se fija a mano.
 */
class TrustProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_ip', fn (Request $r) => response()->json(['ip' => $r->ip()]));
        Route::middleware('throttle:3,1')->get('/_limitada', fn () => response()->json(['ok' => true]));
    }

    private function ipVista(string $remoteAddr, ?string $xForwardedFor): ?string
    {
        $this->flushHeaders(); // withHeaders() acumula entre llamadas del mismo test: sin esto, una petición "sin cabecera" heredaría la anterior
        $cabeceras = $xForwardedFor === null ? [] : ['X-Forwarded-For' => $xForwardedFor];

        return $this->withServerVariables(['REMOTE_ADDR' => $remoteAddr])->withHeaders($cabeceras)->getJson('/_ip')->json('ip');
    }

    public function test_un_cliente_externo_no_puede_falsificar_su_ip_con_x_forwarded_for(): void
    {
        $this->assertSame('203.0.113.9', $this->ipVista('203.0.113.9', null));
        $this->assertSame('203.0.113.9', $this->ipVista('203.0.113.9', '1.2.3.4'), 'La cabecera de un cliente NO de confianza debe ignorarse.');
        $this->assertSame('203.0.113.9', $this->ipVista('203.0.113.9', '1.2.3.4, 5.6.7.8'));
    }

    public function test_el_servidor_web_en_loopback_si_es_de_confianza_y_su_cabecera_se_respeta(): void
    {
        $this->assertSame('198.51.100.7', $this->ipVista('127.0.0.1', '198.51.100.7'), 'La web Next.js llega desde 127.0.0.1 y reenvía la IP real del usuario.');
        $this->assertSame('198.51.100.7', $this->ipVista('::1', '198.51.100.7'));
        $this->assertSame('127.0.0.1', $this->ipVista('127.0.0.1', null));
    }

    public function test_con_una_cadena_falsa_mas_real_desde_un_proxy_de_confianza_se_toma_la_real_de_la_derecha(): void
    {
        $this->assertSame('198.51.100.7', $this->ipVista('127.0.0.1', '1.2.3.4, 198.51.100.7'));
    }

    public function test_la_lista_de_proxies_se_configura_y_lo_que_no_esta_en_ella_no_es_de_confianza(): void
    {
        config(['proxies.trusted' => ['10.0.0.0/8']]);

        $this->assertSame('198.51.100.7', $this->ipVista('10.1.2.3', '198.51.100.7'), 'Un proxy dentro del rango configurado se respeta.');
        $this->assertSame('127.0.0.1', $this->ipVista('127.0.0.1', '198.51.100.7'), 'Loopback ya no es de confianza si no está en la lista.');
        $this->assertSame('203.0.113.9', $this->ipVista('203.0.113.9', '198.51.100.7'));
    }

    public function test_con_la_lista_vacia_no_se_confia_en_nadie_ni_en_loopback(): void
    {
        config(['proxies.trusted' => []]);

        $this->assertSame('127.0.0.1', $this->ipVista('127.0.0.1', '198.51.100.7'));
    }

    public function test_el_valor_por_defecto_de_la_configuracion_es_solo_loopback_y_acepta_una_lista_con_espacios(): void
    {
        $this->assertSame(['127.0.0.1', '::1'], config('proxies.trusted'), 'Sin TRUSTED_PROXIES solo debe confiarse en loopback.');

        putenv('TRUSTED_PROXIES= 10.0.0.1 , 192.168.0.0/16 ,, ');
        try {
            $this->assertSame(['10.0.0.1', '192.168.0.0/16'], (require base_path('config/proxies.php'))['trusted']);
        } finally {
            putenv('TRUSTED_PROXIES');
        }
    }

    /** El efecto real que se esquivaba: rotar una IP falsa en cada intento para no llegar nunca al límite. */
    public function test_rotar_ips_falsas_no_esquiva_el_limite_de_peticiones_por_ip(): void
    {
        $codigos = [];
        foreach (range(1, 5) as $i) {
            $codigos[] = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
                ->withHeaders(['X-Forwarded-For' => "10.9.8.{$i}"])
                ->getJson('/_limitada')->status();
        }

        $this->assertSame([200, 200, 200, 429, 429], $codigos, 'Con IPs falsas distintas debe limitarse igual: la IP real es una sola.');
    }
}
