<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    // Minutes of inactivity before auto-logout
    protected int $timeout = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = $request->session()->get('_last_activity');

            if ($lastActivity && (time() - $lastActivity) > ($this->timeout * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')
                    ->withErrors(['email' => 'Tu sesión expiró por inactividad. Por favor inicia sesión de nuevo.']);
            }

            $request->session()->put('_last_activity', time());
        }

        return $next($request);
    }
}
