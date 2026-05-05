<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Recuperar Contraseña — SGE</title>

    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">

    @php
        $ls   = \Illuminate\Support\Facades\DB::table('system_settings')->pluck('value','key');
        $lBg1 = $ls['login_color_bg1'] ?? '#0a0f2e';
        $lBg2 = $ls['login_color_bg2'] ?? '#1e3a8a';
        $lBg3 = $ls['login_color_bg3'] ?? '#1d4ed8';
        $lAcc = $ls['login_color_acc'] ?? '#10b981';
    @endphp

    <style>
        :root {
            --primary:      {{ $lBg2 }};
            --primary-dark: {{ $lBg1 }};
            --secondary:    {{ $lAcc }};
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            margin: 0;
            background: #f1f5f9;
        }

        /* ── Panel izquierdo ── */
        .panel-left {
            background: linear-gradient(140deg, {{ $lBg1 }} 0%, {{ $lBg2 }} 55%, {{ $lBg3 }} 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 3rem 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .panel-left::before, .panel-left::after {
            content: ''; position: absolute;
            border-radius: 50%; opacity: .06; background: #fff;
        }
        .panel-left::before { width: 420px; height: 420px; top: -120px; right: -140px; }
        .panel-left::after  { width: 300px; height: 300px; bottom: -80px; left: -100px; }
        .panel-left-content {
            position: relative; z-index: 1; text-align: center;
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; width: 100%;
        }
        .logo-badge {
            width: 70px; height: 70px;
            background: var(--secondary);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28pt; font-weight: 700; color: #fff;
            box-shadow: 0 8px 24px rgba(16,185,129,.45);
        }
        .school-name { color: #fff; font-weight: 700; font-size: 1.4rem; line-height: 1.3; margin-top: 1.25rem; margin-bottom: .4rem; }
        .school-subtitle { color: #a8c0e8; font-size: .95rem; }
        .info-box {
            margin-top: 2rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            max-width: 280px;
            text-align: left;
        }
        .info-box-title { color: #fff; font-weight: 700; font-size: .88rem; margin-bottom: .75rem; }
        .info-step {
            display: flex; gap: .65rem; align-items: flex-start;
            color: #d6e4f7; font-size: .8rem; padding: .35rem 0;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .info-step:last-child { border-bottom: none; }
        .info-step-num {
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--secondary); color: #fff;
            font-size: .65rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: .1rem;
        }
        .panel-left-footer {
            position: relative; z-index: 1;
            color: rgba(168,192,232,.65); font-size: .75rem; text-align: center; padding-top: 1rem;
        }

        /* ── Panel derecho ── */
        .panel-right {
            background: #fff;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 3rem 2rem;
        }
        .form-card { width: 100%; max-width: 420px; }
        .form-title { font-size: 1.6rem; font-weight: 700; color: #1a1a2e; margin-bottom: .3rem; }
        .form-lead  { color: #6b7280; font-size: .9rem; margin-bottom: 1.75rem; }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30,64,175,.15);
        }
        .input-group-text {
            background: #f8fafc; border-color: #dee2e6; color: #6b7280;
        }
        .btn-primary-custom {
            background: var(--primary); color: #fff;
            border: none; border-radius: .5rem;
            padding: .7rem 1rem; font-size: .95rem; font-weight: 600;
            width: 100%; transition: background .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-primary-custom:hover  { background: var(--primary-dark); color: #fff; }
        .btn-primary-custom:active { transform: scale(.99); }

        .back-link {
            display: flex; align-items: center; gap: .4rem;
            color: #6b7280; font-size: .85rem; text-decoration: none;
            transition: color .15s;
        }
        .back-link:hover { color: var(--primary); }

        @media (max-width: 991.98px) {
            .panel-left {
                min-height: unset; padding: 1.5rem 1.5rem 1.25rem;
                flex-direction: row; flex-wrap: wrap;
                justify-content: center; gap: .75rem;
            }
            .panel-left::before, .panel-left::after { display: none; }
            .panel-left-content {
                flex-direction: row; flex-wrap: wrap;
                justify-content: center; align-items: center; gap: .75rem; text-align: left;
            }
            .logo-badge { width: 48px; height: 48px; font-size: 18pt; border-radius: 10px; }
            .school-name { font-size: 1.05rem; margin-top: 0; margin-bottom: .1rem; }
            .school-subtitle { font-size: .8rem; }
            .info-box, .panel-left-footer { display: none; }
            .panel-right { min-height: unset; padding: 2rem 1.25rem 2.5rem; align-items: flex-start; }
            .panel-right .form-card { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

        {{-- ── Panel izquierdo ── --}}
        <div class="col-lg-6 panel-left">
            <div class="panel-left-content">
                <div class="logo-badge" style="background:{{ $lAcc }};box-shadow:0 8px 24px {{ $lAcc }}70;">
                    {{ strtoupper(substr($ls['system_abbr'] ?? 'SGE', 0, 4)) }}
                </div>
                <div>
                    <p class="school-name">{!! nl2br(e($ls['login_titulo'] ?? ($ls['system_name'] ?? 'Sistema de Gestión Escolar'))) !!}</p>
                    <p class="school-subtitle mb-0">{{ $ls['login_subtitulo'] ?? 'Sistema de Gestión Escolar' }}</p>
                </div>
                <div class="info-box">
                    <div class="info-box-title"><i class="bi bi-shield-lock me-2"></i>¿Cómo funciona?</div>
                    <div class="info-step">
                        <span class="info-step-num">1</span>
                        <span>Ingresa tu correo institucional</span>
                    </div>
                    <div class="info-step">
                        <span class="info-step-num">2</span>
                        <span>Recibirás un enlace seguro en tu bandeja</span>
                    </div>
                    <div class="info-step">
                        <span class="info-step-num">3</span>
                        <span>Haz clic en el enlace y elige una nueva contraseña</span>
                    </div>
                    <div class="info-step">
                        <span class="info-step-num">4</span>
                        <span>¡Listo! Inicia sesión normalmente</span>
                    </div>
                </div>
            </div>
            <p class="panel-left-footer">&copy; {{ date('Y') }} PSAC &middot; República Dominicana</p>
        </div>

        {{-- ── Panel derecho ── --}}
        <div class="col-lg-6 panel-right">
            <div class="form-card">

                <a href="{{ route('login') }}" class="back-link mb-4 d-inline-flex">
                    <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                </a>

                <h1 class="form-title">Recuperar contraseña</h1>
                <p class="form-lead">
                    Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                </p>

                {{-- Mensaje de éxito --}}
                @if (session('status'))
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span style="font-size:.875rem;">{{ session('status') }}</span>
                    </div>
                @endif

                {{-- Errores --}}
                @if ($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-3" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li style="font-size:.875rem;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                            Correo electrónico
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope-at"></i>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="tu@correo.com"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                autofocus
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-custom mb-3">
                        <i class="bi bi-send"></i>
                        Enviar enlace de recuperación
                    </button>

                    <p style="font-size:.8rem;color:#9ca3af;text-align:center;">
                        <i class="bi bi-info-circle me-1"></i>
                        Si el correo no llega en unos minutos, revisa la carpeta de spam o contacta al administrador del sistema.
                    </p>
                </form>

            </div>
        </div>

    </div>
</div>

<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
