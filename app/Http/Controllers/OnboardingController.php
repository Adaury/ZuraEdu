<?php

namespace App\Http\Controllers;

use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class OnboardingController extends Controller
{
    public function __construct(
        private TenantProvisioningService $provisioning
    ) {}

    public function show(Request $request)
    {
        if (Auth::check()) {
            return redirect('/admin/dashboard');
        }

        $plan = $request->query('plan', 'free');

        return view('onboarding.registro', compact('plan'));
    }

    public function store(Request $request)
    {
        // Rate limit: máx 5 registros por IP por hora
        $key = 'onboarding:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo en unos minutos.']);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'nombre_institucion' => ['required', 'string', 'min:3', 'max:120'],
            'nombre_admin'       => ['required', 'string', 'min:2', 'max:80'],
            'email'              => ['required', 'email:rfc', 'unique:users,email', 'max:180'],
            'password'           => ['required', 'confirmed', Password::min(8)],
            'tipo'               => ['required', 'in:publico,privado,instituto,tecnico'],
        ]);

        $result = $this->provisioning->provision([
            'nombre_institucion' => $request->nombre_institucion,
            'nombre_admin'       => $request->nombre_admin,
            'email'              => $request->email,
            'password'           => $request->password,
            'tipo'               => $request->tipo,
            'plan'               => 'free',
        ]);

        // Auto-login
        Auth::login($result['user']);
        $request->session()->regenerate();

        return redirect()->route('admin.onboarding.show', 1);
    }
}
