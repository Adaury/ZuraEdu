<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect($this->homeForUser(Auth::guard($guard)->user()));
            }
        }

        return $next($request);
    }

    private function homeForUser($user): string
    {
        if ($user->hasRole('Representante'))             return '/portal/padre';
        if ($user->hasRole('Estudiante'))                return '/portal/estudiante';
        if ($user->hasRole('Docente'))                   return '/portal/docente';
        if ($user->hasRole('Secretaria Docente'))        return '/admin/estudiantes';
        if ($user->hasRole('Secretaria'))                return '/admin/estudiantes';
        if ($user->hasRole('Personal Administrativo'))   return '/admin/reportes';
        return '/admin/dashboard';
    }
}
