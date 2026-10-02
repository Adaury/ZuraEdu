<?php

namespace App\Services\Odoo;

/**
 * Valida la URL de Odoo antes de que el SERVIDOR la llame (protección contra SSRF).
 *
 * El administrador escribe la URL; si el servidor la llamara sin más, podría apuntarla a su propia red (127.0.0.1, 10.x, 192.168.x, el
 * servicio de metadatos de la nube 169.254.169.254...) y usar el sistema para leer o golpear servicios internos. Por eso:
 *  - solo https (http únicamente hacia la red local de desarrollo si ODOO_PERMITIR_RED_LOCAL=true);
 *  - sin usuario:clave dentro de la URL;
 *  - se resuelve el nombre y se rechaza si CUALQUIER dirección es privada, local, reservada o de red compartida (100.64/10);
 *  - se devuelven las IP resueltas para que el cliente conecte a ESAS (evita que el DNS cambie entre la validación y la llamada).
 */
class OdooUrlSegura
{
    /** @return array{url: string, host: string, port: int, ips: array<int,string>} */
    public static function validar(string $url): array
    {
        $url = trim($url);
        $p   = parse_url($url);

        if ($p === false || empty($p['host']) || empty($p['scheme'])) {
            throw new OdooException('La URL de Odoo no es válida. Ejemplo: https://mi-colegio.odoo.com');
        }
        if (isset($p['user']) || isset($p['pass'])) {
            throw new OdooException('La URL de Odoo no debe llevar usuario ni clave.');
        }

        $esquema     = strtolower($p['scheme']);
        $host        = strtolower(trim($p['host'], '[]'));
        $permitirLoc = (bool) config('odoo.permitir_red_local');

        if (! in_array($esquema, ['https', 'http'], true)) {
            throw new OdooException('La URL de Odoo debe empezar por https://');
        }
        if ($esquema === 'http' && ! $permitirLoc) {
            throw new OdooException('La URL de Odoo debe usar https:// (conexión segura).');
        }

        $puerto = (int) ($p['port'] ?? ($esquema === 'https' ? 443 : 80));
        if ($puerto < 1 || $puerto > 65535) {
            throw new OdooException('El puerto de la URL de Odoo no es válido.');
        }

        // Nombres claramente internos
        if (! $permitirLoc && ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.localhost'))) {
            throw new OdooException('La URL de Odoo apunta a una dirección interna, no permitida.');
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } elseif (config('odoo.verificar_dns', true)) {
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $r) {
                $ips[] = $r['ip'] ?? $r['ipv6'] ?? null;
            }
            $ips = array_values(array_filter(array_unique($ips)));
            if (! $ips) {
                throw new OdooException('No se pudo resolver el servidor de Odoo. Revisa la URL.');
            }
        }

        if (! $permitirLoc) {
            foreach ($ips as $ip) {
                if (! self::esPublica($ip)) {
                    throw new OdooException('La URL de Odoo apunta a una dirección interna o reservada, no permitida.');
                }
            }
        }

        $ruta = isset($p['path']) ? rtrim($p['path'], '/') : '';

        return [
            'url'  => $esquema . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '') . $ruta,
            'host' => $host,
            'port' => $puerto,
            'ips'  => $ips,
        ];
    }

    public static function esPublica(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        // Red compartida de operador (CGNAT) 100.64.0.0/10: no la cubren las banderas de PHP.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $n = ip2long($ip);
            if ($n !== false && $n >= ip2long('100.64.0.0') && $n <= ip2long('100.127.255.255')) {
                return false;
            }
        }
        // IPv4 escrita dentro de IPv6 (::ffff:10.0.0.1): se valida la IPv4 de dentro.
        if (stripos($ip, '::ffff:') === 0 && filter_var(substr($ip, 7), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::esPublica(substr($ip, 7));
        }

        return true;
    }
}
