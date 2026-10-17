<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Fija el idioma de la interfaz según la elección guardada en el cookie «idioma» (ver IdiomaController).
 * Sin elección usa el idioma predeterminado (español): no se adivina por el navegador, para que un colegio
 * dominicano no vea el sistema en inglés solo porque su equipo está configurado así.
 * Al cambiar el idioma de la aplicación, Carbon también formatea las fechas en ese idioma.
 */
class EstablecerIdioma
{
    public function handle(Request $request, Closure $next)
    {
        $elegido = $request->cookie(config('idiomas.cookie'));
        $idioma = is_string($elegido) && array_key_exists($elegido, config('idiomas.disponibles'))
            ? $elegido
            : config('idiomas.predeterminado');

        app()->setLocale($idioma);

        return $next($request);
    }
}
