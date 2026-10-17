<?php

namespace App\Http\Middleware;

use App\Support\Traductor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Traduce las páginas HTML al idioma elegido (ver App\Support\Traductor). En español no hace nada. Solo
 * toca respuestas HTML normales con estado 200: JSON, PDF, Excel, descargas y redirecciones pasan intactos.
 */
class TraducirRespuesta
{
    public function handle(Request $request, Closure $next)
    {
        $respuesta = $next($request);

        $idioma = app()->getLocale();
        if ($idioma === config('idiomas.predeterminado')
            || ! $respuesta instanceof Response
            || $respuesta->getStatusCode() !== 200
            || ! str_contains((string) $respuesta->headers->get('Content-Type', ''), 'text/html')) {
            return $respuesta;
        }

        $respuesta->setContent(Traductor::html((string) $respuesta->getContent(), $idioma));
        $respuesta->headers->set('Content-Language', $idioma);

        return $respuesta;
    }
}
