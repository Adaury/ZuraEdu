<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserActivo
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}
