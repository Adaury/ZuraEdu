<?php

namespace App\Services\Odoo;

use App\Models\OdooConexion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo del API externo de Odoo por JSON-RPC (`POST {url}/jsonrpc`), el que aceptan Odoo 14 a 19 (self-hosted y Odoo Online).
 * Autenticación: base de datos + usuario + clave de API (se crea en Odoo: Preferencias → Seguridad de la cuenta → Nueva clave de API).
 * La clave nunca se escribe en logs ni en mensajes de error.
 */
class OdooClient
{
    private ?int $uid = null;

    public function __construct(private readonly OdooConexion $conexion)
    {
        $this->uid = $conexion->uid_odoo ? (int) $conexion->uid_odoo : null;
    }

    /** Versión del servidor (no requiere credenciales): sirve para comprobar que la URL es un Odoo de verdad. */
    public function version(): array
    {
        $r = $this->llamar('common', 'version', []);

        return is_array($r) ? $r : [];
    }

    /** Valida base de datos + usuario + clave y devuelve el uid. Lanza OdooException (credenciales=true) si no coinciden. */
    public function autenticar(): int
    {
        $uid = $this->llamar('common', 'authenticate', [
            $this->conexion->base_datos, $this->conexion->usuario, $this->conexion->api_key, new \stdClass(),
        ]);

        if (! is_int($uid) || $uid <= 0) {
            throw new OdooException('Odoo rechazó las credenciales: revisa la base de datos, el usuario y la clave de API.', true);
        }

        return $this->uid = $uid;
    }

    /** execute_kw genérico. */
    public function ejecutar(string $modelo, string $metodo, array $args = [], array $kwargs = []): mixed
    {
        $uid = $this->uid ?? $this->autenticar();

        return $this->llamar('object', 'execute_kw', [
            $this->conexion->base_datos, $uid, $this->conexion->api_key, $modelo, $metodo, $args, (object) $kwargs,
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function buscarLeer(string $modelo, array $dominio, array $campos, int $limite = 1): array
    {
        $r = $this->ejecutar($modelo, 'search_read', [$dominio], ['fields' => $campos, 'limit' => $limite]);

        return is_array($r) ? $r : [];
    }

    public function crear(string $modelo, array $valores): int
    {
        $id = $this->ejecutar($modelo, 'create', [$valores]);
        if (! is_int($id) || $id <= 0) {
            throw new OdooException("Odoo no devolvió el id del registro creado en {$modelo}.");
        }

        return $id;
    }

    public function escribir(string $modelo, array $ids, array $valores): bool
    {
        return (bool) $this->ejecutar($modelo, 'write', [$ids, $valores]);
    }

    // ── transporte ──────────────────────────────────────────────────────────

    private function llamar(string $servicio, string $metodo, array $args): mixed
    {
        $destino = OdooUrlSegura::validar($this->conexion->url);   // se revalida en CADA llamada: la URL pudo cambiar
        $opciones = ['allow_redirects' => false];                   // un redireccionamiento podría saltarse la validación de arriba

        if ($destino['ips'] && defined('CURLOPT_RESOLVE')) {
            // Conectar a la IP que acabamos de validar (evita que el DNS cambie entre la validación y la conexión).
            $opciones['curl'] = [CURLOPT_RESOLVE => ["{$destino['host']}:{$destino['port']}:{$destino['ips'][0]}"]];
        }

        try {
            $resp = Http::timeout(config('odoo.timeout', 20))->connectTimeout(10)->acceptJson()->asJson()
                ->withOptions($opciones)
                ->post($destino['url'] . '/jsonrpc', [
                    'jsonrpc' => '2.0', 'method' => 'call', 'id' => random_int(1, 1_000_000),
                    'params'  => ['service' => $servicio, 'method' => $metodo, 'args' => $args],
                ]);
        } catch (ConnectionException $e) {
            throw new OdooException('No se pudo conectar con Odoo (red, DNS o tiempo de espera agotado).', false, $e);
        }

        if ($resp->status() >= 300 && $resp->status() < 400) {
            throw new OdooException('Odoo respondió con una redirección; usa la URL final (la que ves en el navegador, con https://).');
        }
        if (! $resp->successful()) {
            throw new OdooException("Odoo respondió con el error HTTP {$resp->status()}. ¿La URL es la de un Odoo?");
        }

        $cuerpo = $resp->json();
        if (! is_array($cuerpo)) {
            throw new OdooException('La respuesta no parece venir de un Odoo (no es JSON-RPC). Revisa la URL.');
        }
        if (isset($cuerpo['error'])) {
            $msg = $cuerpo['error']['data']['message'] ?? $cuerpo['error']['message'] ?? 'error desconocido';
            // Odoo devuelve "Access Denied" o 'database "x" does not exist' cuando las credenciales o la base no coinciden.
            // OJO: "Record does not exist" (un registro borrado en Odoo) NO es un problema de credenciales.
            $esAcceso = stripos((string) $msg, 'access denied') !== false || preg_match('/database\b.*does not exist/i', (string) $msg) === 1;

            throw new OdooException('Odoo respondió: ' . mb_substr((string) $msg, 0, 300), $esAcceso);
        }

        return $cuerpo['result'] ?? null;
    }
}
