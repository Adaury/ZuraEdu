<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super_admin')) {
            abort(403, 'Acceso restringido al panel de la plataforma.');
        }

        return $next($request);
    }
}
