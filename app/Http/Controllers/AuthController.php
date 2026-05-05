<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\ActivityLog;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = 'login.' . Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos."]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if ($user->pendiente_aprobacion) {
                Auth::logout();
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Tu solicitud de acceso está pendiente de aprobación. El administrador te notificará cuando tu cuenta esté activa.']);
            }

            if (! $user->activo) {
                Auth::logout();
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);
            }

            RateLimiter::clear($key);
            $request->session()->regenerate();

            try {
                ActivityLog::create([
                    'user_id'     => $user->id,
                    'accion'      => 'login',
                    'descripcion' => 'Inicio de sesion exitoso',
                    'ip'          => $request->ip(),
                ]);
            } catch (\Exception $e) {}

            // Si el usuario debe cambiar su contraseña (primera vez), redirigir
            if ($user->must_change_password) {
                return redirect()->route('password.change');
            }

            return redirect($this->redirectByRole($user));
        }

        RateLimiter::hit($key, 60);

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Credenciales incorrectas. Por favor verifique su correo y contrasena.']);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            try {
                ActivityLog::create([
                    'user_id'     => Auth::id(),
                    'accion'      => 'logout',
                    'descripcion' => 'Cierre de sesion',
                    'ip'          => $request->ip(),
                ]);
            } catch (\Exception $e) {}
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('status', 'Sesión cerrada correctamente. ¡Hasta pronto!');
    }

    // ── Registration ──────────────────────────────────────────────────────

    public function showRegister()
    {
        $demoActivo = \Illuminate\Support\Facades\DB::table('system_settings')
            ->where('key', 'demo_activo')->value('value') === '1';

        $usuariosDemo = [];
        if ($demoActivo) {
            $usuariosDemo = [
                'docente'    => User::where('email', 'docente@demo.com')->exists(),
                'estudiante' => User::where('email', 'estudiante@demo.com')->exists(),
                'padre'      => User::where('email', 'padre@demo.com')->exists(),
            ];
        }

        return view('auth.register', compact('demoActivo', 'usuariosDemo'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'apellidos'        => ['required', 'string', 'max:100'],
            'cedula'           => ['required', 'string', 'regex:/^\d{3}-\d{7}-\d{1}$/'],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'rol'              => ['required', 'in:Docente,Secretaria Docente,Encargado de Área,Personal Administrativo'],
            'area_trabajo'     => ['nullable', 'in:Académica,Técnica,Ambas'],
            'password'         => ['required', 'string', 'min:8', 'regex:/[0-9]/', 'confirmed'],
        ], [
            'cedula.regex'       => 'La cédula debe tener el formato RD: XXX-XXXXXXX-X',
            'email.unique'       => 'Este correo electrónico ya está registrado.',
            'rol.in'             => 'Selecciona un rol válido.',
            'area_trabajo.in'    => 'Selecciona un área válida.',
            'password.regex'     => 'La contraseña debe contener al menos un número.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'name'                 => $request->name,
            'apellidos'            => $request->apellidos,
            'cedula'               => $request->cedula,
            'telefono'             => $request->telefono,
            'email'                => $request->email,
            'password'             => Hash::make($request->password),
            'area_trabajo'         => $request->area_trabajo,
            'activo'               => false,
            'pendiente_aprobacion' => true,
        ]);

        $user->assignRole($request->rol);

        // Alertar a los administradores sobre la nueva solicitud
        try {
            $admins = \App\Models\User::role(['Administrador', 'Director'])->get();
            foreach ($admins as $admin) {
                \App\Models\AlertaSistema::create([
                    'tipo'             => 'registro_pendiente',
                    'titulo'           => 'Nueva solicitud de acceso',
                    'mensaje'          => "{$user->name} {$user->apellidos} solicita acceso al sistema como {$request->rol}.",
                    'nivel'            => 'info',
                    'destinatario_id'  => $admin->id,
                    'referencia_tipo'  => 'usuario',
                    'referencia_id'    => $user->id,
                    'leida'            => false,
                    'school_year_id'   => null,
                    'creado_por'       => null,
                ]);
            }
            // Invalidar caché de pendientes
            \Illuminate\Support\Facades\Cache::forget('usuarios_pendientes_count');
        } catch (\Throwable $e) {
            // No interrumpir el flujo
        }

        return redirect()->route('login')
            ->with('status', '¡Solicitud enviada! Tu cuenta está pendiente de aprobación. El administrador revisará tu solicitud y te notificará cuando puedas acceder al sistema.');
    }

    // ── Cambio forzado de contraseña (primera vez) ────────────────────────
    public function showChangePassword()
    {
        if (!Auth::check() || !Auth::user()->must_change_password) {
            return redirect('/');
        }
        return view('auth.change-password');
    }

    public function updateChangePassword(Request $request)
    {
        if (!Auth::check() || !Auth::user()->must_change_password) {
            return redirect('/');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = Auth::user();
        $user->update([
            'password'            => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect($this->redirectByRole($user))
            ->with('success', '¡Contraseña actualizada correctamente! Bienvenido/a al sistema.');
    }

    // ── Recuperación de contraseña ────────────────────────────────────────

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'Ingresa un correo electrónico válido.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Te enviamos un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada.');
        }

        if ($status === Password::INVALID_USER) {
            // No revelar si el email existe o no (seguridad)
            return back()->with('status', 'Si ese correo está registrado, recibirás el enlace en breve.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'Ingresa un correo electrónico válido.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'            => Hash::make($password),
                    'must_change_password' => false,
                ])->save();

                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', '¡Contraseña restablecida correctamente! Ya puedes iniciar sesión.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    // ── Demo Login ────────────────────────────────────────────────────────
    public function demoLogin(string $rol)
    {
        // Verificar si el demo está activo
        $demoActivo = \Illuminate\Support\Facades\DB::table('system_settings')
            ->where('key', 'demo_activo')->value('value');

        if ($demoActivo !== '1') {
            return redirect('/')->with('error', '⚠ El modo demo está desactivado en este momento.');
        }

        // Admin excluido del demo público
        $mapa = [
            'docente'    => 'docente@demo.com',
            'padre'      => 'padre@demo.com',
            'estudiante' => 'estudiante@demo.com',
        ];

        if (! array_key_exists($rol, $mapa)) {
            return redirect('/')->withErrors(['demo' => 'Rol de demo no válido.']);
        }

        $user = User::where('email', $mapa[$rol])->first();

        if (! $user) {
            return redirect('/')
                ->with('error', '⚠ Los usuarios demo no existen aún. Ve a Admin → Sistema → Demo y crea los usuarios.');
        }

        // Si hay alguien logueado, cerrar su sesión primero
        if (Auth::check()) {
            Auth::logout();
        }

        // Garantizar que el perfil existe antes de entrar al portal
        $this->ensureDemoProfile($rol, $user);

        Auth::login($user);
        request()->session()->regenerate();
        request()->session()->put('demo_mode', true);

        return redirect($this->redirectByRole($user));
    }

    private function ensureDemoProfile(string $rol, User $user): void
    {
        try {
            if ($rol === 'docente') {
                $exists = \App\Models\Docente::where('user_id', $user->id)->exists();
                if (! $exists) {
                    // Try to link an existing unlinked docente first
                    $docente = \App\Models\Docente::whereNull('user_id')->first();
                    if ($docente) {
                        $docente->update(['user_id' => $user->id, 'email' => $user->email]);
                    } else {
                        \App\Models\Docente::create([
                            'user_id'   => $user->id,
                            'nombres'   => 'Demo',
                            'apellidos' => 'Docente',
                            'email'     => $user->email,
                            'cedula'    => '000-0000001-0',
                            'estado'    => 'activo',
                        ]);
                    }
                }
            } elseif ($rol === 'estudiante') {
                $exists = \App\Models\Estudiante::where('user_id', $user->id)->exists();
                if (! $exists) {
                    $est = \App\Models\Estudiante::whereNull('user_id')->first();
                    if ($est) {
                        $est->update(['user_id' => $user->id]);
                    } else {
                        \App\Models\Estudiante::create([
                            'user_id'          => $user->id,
                            'nombres'          => 'Demo',
                            'apellidos'        => 'Estudiante',
                            'email'            => $user->email,
                            'cedula'           => '000-0000002-0',
                            'numero_matricula' => 'DEMO-001',
                            'fecha_nacimiento' => '2010-01-01',
                            'sexo'             => 'M',
                            'estado'           => 'activo',
                        ]);
                    }
                }
            } elseif ($rol === 'padre') {
                $exists = \App\Models\Representante::where('user_id', $user->id)->exists();
                if (! $exists) {
                    $rep = \App\Models\Representante::whereNull('user_id')->first();
                    if ($rep) {
                        $rep->update(['user_id' => $user->id]);
                    } else {
                        \App\Models\Representante::create([
                            'user_id'   => $user->id,
                            'nombres'   => 'Demo',
                            'apellidos' => 'Representante',
                            'email'     => $user->email,
                            'cedula'    => '000-0000003-0',
                            'telefono'  => '8091234567',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail silently — portal will show its own error if profile missing
        }
    }

    private function redirectByRole($user): string
    {
        if ($user->hasRole('super_admin'))               return '/superadmin';
        if ($user->hasRole('Representante'))             return '/portal/padre';
        if ($user->hasRole('Estudiante'))                return '/portal/estudiante';
        if ($user->hasRole('Administrador'))             return '/admin/dashboard';
        if ($user->hasRole('Director'))                  return '/admin/dashboard';
        if ($user->hasRole('Coordinador Academico'))     return '/admin/dashboard';
        if ($user->hasRole('Coordinador Primer Ciclo'))  return '/admin/dashboard';
        if ($user->hasRole('Coordinador Segundo Ciclo')) return '/admin/dashboard';
        if ($user->hasRole('Docente'))                   return '/portal/docente';
        if ($user->hasRole('Secretaria Docente'))        return '/admin/estudiantes';
        if ($user->hasRole('Secretaria'))                return '/admin/estudiantes';
        if ($user->hasRole('Personal Administrativo'))   return '/admin/reportes';
        return '/admin/dashboard';
    }
}
