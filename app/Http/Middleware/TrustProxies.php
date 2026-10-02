<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Antes era `'*'` (confiar en cualquiera): un cliente podía mandar `X-Forwarded-For: <IP falsa>` y Laravel la tomaba como suya,
     * esquivando el límite de login por IP y falsificando la IP de la auditoría. Ahora la lista sale de `config/proxies.php`
     * (`TRUSTED_PROXIES`, por defecto solo loopback). Ver ese archivo para balanceadores y CDN.
     *
     * Se lee en `proxies()` y no en la propiedad porque `env()` fuera de `config/` devuelve null con `config:cache`.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = null;

    protected function proxies()
    {
        return config('proxies.trusted', ['127.0.0.1', '::1']);
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
