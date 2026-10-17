<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdiomaController extends Controller
{
    /** POST /idioma/{codigo} — recuerda el idioma elegido (cookie de 1 año) y vuelve a la página anterior. */
    public function cambiar(Request $request, string $codigo)
    {
        abort_unless(array_key_exists($codigo, config('idiomas.disponibles')), 404);

        return back()->withCookie(cookie(config('idiomas.cookie'), $codigo, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    }
}
