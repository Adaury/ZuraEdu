@php
    $ls = \Illuminate\Support\Facades\DB::table('system_settings')->pluck('value','key');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Solicitar Acceso — {{ $ls['system_abbr'] ?? $ls['system_name'] ?? 'SGE' }}</title>

    <!-- Google Fonts: Inter -->
    
    
    

    <!-- Bootstrap 5.3.2 CSS -->
    <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">

    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">

    <style>
        :root {
            --primary:      #1e3a6e;
            --primary-dark: #0f1f3d;
            --secondary:    #c0392b;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f4f8;
        }

        /* ── LEFT PANEL ─────────────────────────────────────────────── */
        .panel-left {
            background: linear-gradient(160deg, var(--primary) 0%, var(--primary-dark) 100%);
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
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.06;
            background: #ffffff;
        }
        .panel-left::before { width: 420px; height: 420px; top: -120px; right: -140px; }
        .panel-left::after  { width: 300px; height: 300px; bottom: -80px; left: -100px; }

        .panel-left-content {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .logo-badge {
            width: 70px; height: 70px;
            background-color: var(--secondary);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28pt;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -1px;
            box-shadow: 0 8px 24px rgba(192,57,43,0.45);
            flex-shrink: 0;
        }

        .school-name {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.4rem;
            line-height: 1.3;
            margin-top: 1.25rem;
            margin-bottom: 0.4rem;
        }

        .school-subtitle {
            color: #a8c0e8;
            font-size: 0.95rem;
            font-weight: 400;
            letter-spacing: 0.02em;
        }

        .steps-info {
            margin-top: 2rem;
            width: 100%;
            max-width: 280px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .55rem 0;
            border-bottom: 1px solid rgba(255,255,255,.07);
            color: #d6e4f7;
            font-size: .875rem;
        }
        .step-item:last-child { border-bottom: none; }

        .step-num {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-size: .72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .step-num.active {
            background: var(--secondary);
            box-shadow: 0 2px 8px rgba(192,57,43,.4);
        }
        .step-num.done {
            background: #22c55e;
        }

        .panel-left-footer {
            position: relative;
            z-index: 1;
            color: rgba(168,192,232,.65);
            font-size: .75rem;
            text-align: center;
            padding-top: 1rem;
        }

        /* ── RIGHT PANEL ─────────────────────────────────────────────── */
        .panel-right {
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }

        .form-card {
            width: 100%;
            max-width: 480px;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: .3rem;
        }

        .form-lead {
            color: #6b7280;
            font-size: .875rem;
            margin-bottom: 1.5rem;
        }

        /* ── Progress bar ────────────────────────────────────────────── */
        .progress-wrap {
            margin-bottom: 1.75rem;
        }

        .progress-steps {
            display: flex;
            gap: .5rem;
            margin-bottom: .6rem;
        }

        .progress-step-bar {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            transition: background .35s;
        }
        .progress-step-bar.done  { background: #22c55e; }
        .progress-step-bar.active { background: var(--primary); }

        .progress-label {
            font-size: .78rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* ── Wizard steps ────────────────────────────────────────────── */
        .wizard-step {
            display: none;
            animation: fadeSlideIn .3s ease both;
        }
        .wizard-step.active { display: block; }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateX(12px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeSlideBack {
            from { opacity: 0; transform: translateX(-12px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .wizard-step.going-back {
            animation: fadeSlideBack .3s ease both;
        }

        /* ── Input styles ────────────────────────────────────────────── */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30,58,110,.15);
        }

        .input-group .form-control:focus { z-index: 3; }

        .input-group-text {
            background-color: #f8fafc;
            border-color: #dee2e6;
            color: #6b7280;
        }

        /* ── Buttons ─────────────────────────────────────────────────── */
        .btn-primary-custom {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: .5rem;
            padding: .65rem 1.25rem;
            font-size: .9rem;
            font-weight: 600;
            transition: background-color .2s, transform .1s;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
        }
        .btn-primary-custom:hover  { background-color: var(--primary-dark); color: #fff; }
        .btn-primary-custom:active { transform: scale(.99); }

        .btn-outline-secondary-custom {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            border-radius: .5rem;
            padding: .65rem 1.25rem;
            font-size: .9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            transition: background .18s;
        }
        .btn-outline-secondary-custom:hover { background: #f9fafb; }

        /* ── Password strength ──────────────────────────────────────── */
        .strength-bar-wrap {
            display: flex;
            gap: .3rem;
            margin-top: .4rem;
        }

        .strength-segment {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            transition: background .25s;
        }

        .strength-label {
            font-size: .74rem;
            font-weight: 600;
            margin-top: .3rem;
        }

        /* ── Rol area field toggle ─────────────────────────────────── */
        #area-group {
            transition: opacity .25s;
        }

        /* ── Back-to-login link ────────────────────────────────────── */
        .back-link {
            color: #6b7280;
            font-size: .82rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin-bottom: 1.25rem;
            transition: color .18s;
        }
        .back-link:hover { color: var(--primary); }

        /* ── Field error inline ─────────────────────────────────────── */
        .field-error {
            font-size: .78rem;
            color: #dc3545;
            margin-top: .25rem;
            display: none;
        }
        .field-error.visible { display: block; }

        /* ── Toggle eye ─────────────────────────────────────────────── */
        .btn-toggle-eye {
            background-color: #f8fafc;
            border-color: #dee2e6;
            color: #6b7280;
            transition: color .15s;
        }
        .btn-toggle-eye:hover  { color: var(--primary); background-color: #f0f4f8; border-color: #dee2e6; }
        .btn-toggle-eye:focus  { box-shadow: none; }

        /* ── Info box ───────────────────────────────────────────────── */
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: .5rem;
            padding: .75rem 1rem;
            font-size: .82rem;
            color: #1e40af;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            margin-bottom: 1.25rem;
        }
        .info-box i { flex-shrink: 0; margin-top: .05rem; }

        /* ── Demo buttons ────────────────────────────────────────────── */
        .demo-section { margin-top: 2rem; width: 100%; max-width: 280px; }
        .demo-section-title { font-size: .68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: rgba(168,192,232,.7); margin-bottom: .75rem; }
        .demo-btn {
            display: flex; align-items: center; gap: .65rem;
            background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.14);
            border-radius: 9px; padding: .55rem .9rem; width: 100%; margin-bottom: .5rem;
            color: #e8f0fb; font-size: .82rem; font-weight: 600; text-decoration: none;
            transition: background .18s, border-color .18s;
        }
        .demo-btn:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.28); color: #fff; }
        .demo-btn-icon { width: 30px; height: 30px; border-radius: 7px; display: flex;
            align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }

        /* Mobile demo section (shown below form) */
        .demo-mobile-section {
            margin-top: 1.75rem; padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb; display: none;
        }
        .demo-mobile-title { font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .07em; color: #9ca3af; text-align: center; margin-bottom: .9rem; }
        .demo-mobile-btn {
            display: flex; align-items: center; gap: .6rem;
            background: #f8fafc; border: 1.5px solid #e5e7eb; border-radius: 9px;
            padding: .55rem .9rem; width: 100%; margin-bottom: .5rem;
            color: #374151; font-size: .82rem; font-weight: 600; text-decoration: none;
            transition: background .18s, border-color .18s;
        }
        .demo-mobile-btn:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
        .demo-mobile-icon { width: 28px; height: 28px; border-radius: 6px; display: flex;
            align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; color: #fff; }

        /* ── MOBILE ─────────────────────────────────────────────────── */
        @media (max-width: 991.98px) {
            .panel-left {
                min-height: unset;
                padding: 1.5rem 1.5rem 1.25rem;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: .75rem;
            }
            .panel-left::before, .panel-left::after { display: none; }
            .panel-left-content {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                align-items: center;
                gap: .75rem;
                text-align: left;
            }
            .logo-badge { width: 48px; height: 48px; font-size: 18pt; border-radius: 10px; }
            .school-name { font-size: 1.05rem; margin-top: 0; margin-bottom: .1rem; }
            .school-subtitle { font-size: .8rem; }
            .steps-info, .panel-left-footer, .demo-section { display: none; }
            .demo-mobile-section { display: block; }
            .panel-right { min-height: unset; padding: 2rem 1.25rem 2.5rem; align-items: flex-start; }
            .panel-right .form-card { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">

        {{-- ── LEFT PANEL ──────────────────────────────────────── --}}
        <div class="col-lg-5 panel-left">
            <div class="panel-left-content">

                <div class="logo-badge">{{ strtoupper(substr($ls['system_abbr'] ?? $ls['system_name'] ?? 'SGE', 0, 4)) }}</div>

                <div>
                    <p class="school-name">{!! nl2br(e($ls['login_titulo'] ?? $ls['system_name'] ?? 'Sistema de Gestión Escolar')) !!}</p>
                    <p class="school-subtitle mb-0">{{ $ls['login_subtitulo'] ?? $ls['system_sub'] ?? 'Sistema de Gestión Escolar' }}</p>
                </div>

                <div class="steps-info">
                    <div class="step-item">
                        <div class="step-num active" id="left-step-1">1</div>
                        <span>Datos Personales</span>
                    </div>
                    <div class="step-item">
                        <div class="step-num" id="left-step-2">2</div>
                        <span>Centro y Rol</span>
                    </div>
                    <div class="step-item">
                        <div class="step-num" id="left-step-3">3</div>
                        <span>Acceso y Contraseña</span>
                    </div>
                </div>

            </div>

            {{-- Demo section (desktop sidebar) --}}
            @if($demoActivo)
            <div class="demo-section">
                <div class="demo-section-title"><i class="bi bi-play-circle me-1"></i>O prueba el sistema</div>
                @if($usuariosDemo['docente'] ?? false)
                <a href="{{ route('demo.login', 'docente') }}" class="demo-btn">
                    <div class="demo-btn-icon" style="background:#1d4ed8;"><i class="bi bi-person-badge-fill"></i></div>
                    <div>
                        <div>Acceder como Docente</div>
                        <div style="font-size:.71rem;font-weight:400;color:rgba(200,220,255,.7);">Ver el panel del docente</div>
                    </div>
                </a>
                @endif
                @if($usuariosDemo['estudiante'] ?? false)
                <a href="{{ route('demo.login', 'estudiante') }}" class="demo-btn">
                    <div class="demo-btn-icon" style="background:#059669;"><i class="bi bi-mortarboard-fill"></i></div>
                    <div>
                        <div>Acceder como Estudiante</div>
                        <div style="font-size:.71rem;font-weight:400;color:rgba(200,220,255,.7);">Ver el portal estudiantil</div>
                    </div>
                </a>
                @endif
                @if($usuariosDemo['padre'] ?? false)
                <a href="{{ route('demo.login', 'padre') }}" class="demo-btn">
                    <div class="demo-btn-icon" style="background:#b45309;"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div>Acceder como Representante</div>
                        <div style="font-size:.71rem;font-weight:400;color:rgba(200,220,255,.7);">Ver el portal del padre</div>
                    </div>
                </a>
                @endif
            </div>
            @endif

            <p class="panel-left-footer">
                &copy; {{ date('Y') }} {{ $ls['system_abbr'] ?? $ls['system_name'] ?? 'SGE' }}
                @if(!empty($ls['system_sub'])) &middot; {{ $ls['system_sub'] }} @endif
            </p>
        </div>{{-- /.panel-left --}}

        {{-- ── RIGHT PANEL ─────────────────────────────────────── --}}
        <div class="col-lg-7 panel-right">
            <div class="form-card">

                <a href="{{ route('login') }}" class="back-link">
                    <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
                </a>

                <h1 class="form-title">Solicitar Acceso</h1>
                <p class="form-lead">Completa los 3 pasos para enviar tu solicitud al administrador</p>

                <div class="text-end mb-2">
                    <a href="{{ route('help.registro') }}" target="_blank"
                       style="font-size:.78rem;color:var(--primary);text-decoration:none;">
                        <i class="bi bi-book me-1"></i>Ver guía de registro
                    </a>
                </div>

                {{-- Server-side validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3" role="alert">
                        <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Corrige los siguientes errores:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach ($errors->all() as $error)
                                <li style="font-size:.85rem;">{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Progress bar --}}
                <div class="progress-wrap">
                    <div class="progress-steps">
                        <div class="progress-step-bar active" id="bar-1"></div>
                        <div class="progress-step-bar" id="bar-2"></div>
                        <div class="progress-step-bar" id="bar-3"></div>
                    </div>
                    <span class="progress-label" id="progress-label">Paso 1 de 3 — Datos Personales</span>
                </div>

                {{-- ════════════ FORM ════════════ --}}
                <form method="POST" action="{{ route('register.post') }}" id="register-form" novalidate>
                    @csrf

                    {{-- ══════════ STEP 1: Datos Personales ══════════ --}}
                    <div class="wizard-step active" id="step-1">

                        {{-- Nombre --}}
                        <div class="mb-3">
                            <label for="name" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Nombre(s) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Ej. María Elena"
                                       value="{{ old('name') }}"
                                       autocomplete="given-name">
                            </div>
                            <div class="field-error" id="err-name">Ingresa tu(s) nombre(s).</div>
                            @error('name')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Apellidos --}}
                        <div class="mb-3">
                            <label for="apellidos" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Apellido(s) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="apellidos" name="apellidos"
                                       class="form-control @error('apellidos') is-invalid @enderror"
                                       placeholder="Ej. García Rodríguez"
                                       value="{{ old('apellidos') }}"
                                       autocomplete="family-name">
                            </div>
                            <div class="field-error" id="err-apellidos">Ingresa tu(s) apellido(s).</div>
                            @error('apellidos')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Cédula --}}
                        <div class="mb-3">
                            <label for="cedula" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Cédula de Identidad <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" id="cedula" name="cedula"
                                       class="form-control @error('cedula') is-invalid @enderror"
                                       placeholder="001-1234567-8"
                                       value="{{ old('cedula') }}"
                                       maxlength="13"
                                       autocomplete="off">
                            </div>
                            <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem;">Formato: XXX-XXXXXXX-X</div>
                            <div class="field-error" id="err-cedula">Ingresa una cédula válida (formato: 001-1234567-8).</div>
                            @error('cedula')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Teléfono --}}
                        <div class="mb-3">
                            <label for="telefono" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Teléfono
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" id="telefono" name="telefono"
                                       class="form-control @error('telefono') is-invalid @enderror"
                                       placeholder="809-000-0000"
                                       value="{{ old('telefono') }}"
                                       autocomplete="tel">
                            </div>
                            @error('telefono')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-4">
                            <label for="email_step1" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Correo Electrónico <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                                <input type="email" id="email_step1" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="tu.nombre@colegio.edu.do o correo personal"
                                       value="{{ old('email') }}"
                                       autocomplete="email">
                            </div>
                            <div style="font-size:.73rem;color:#6b7280;margin-top:.2rem;">
                                <i class="bi bi-info-circle me-1"></i>Puedes usar tu correo institucional o cualquier correo personal.
                            </div>
                            <div class="field-error" id="err-email">Ingresa un correo electrónico válido.</div>
                            @error('email')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn-primary-custom" id="btn-next-1">
                                Siguiente <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                    </div>{{-- /#step-1 --}}

                    {{-- ══════════ STEP 2: Centro y Rol ══════════ --}}
                    <div class="wizard-step" id="step-2">

                        {{-- Rol --}}
                        <div class="mb-3">
                            <label for="rol" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Rol en el centro educativo <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <select id="rol" name="rol"
                                        class="form-select @error('rol') is-invalid @enderror">
                                    <option value="">— Selecciona un rol —</option>
                                    <option value="Docente"             {{ old('rol') === 'Docente'              ? 'selected' : '' }}>Docente</option>
                                    <option value="Secretaria Docente"  {{ old('rol') === 'Secretaria Docente'   ? 'selected' : '' }}>Secretaria Docente</option>
                                    <option value="Encargado de Área"   {{ old('rol') === 'Encargado de Área'    ? 'selected' : '' }}>Encargado de Área</option>
                                    <option value="Personal Administrativo" {{ old('rol') === 'Personal Administrativo' ? 'selected' : '' }}>Personal Administrativo</option>
                                </select>
                            </div>
                            <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem;">
                                El rol "Administrador" solo puede ser asignado por un administrador existente.
                            </div>
                            <div class="field-error" id="err-rol">Selecciona tu rol.</div>
                            @error('rol')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Área de trabajo (condicional) --}}
                        <div class="mb-3" id="area-group" style="display:none;">
                            <label for="area_trabajo" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Área de trabajo <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                                <select id="area_trabajo" name="area_trabajo"
                                        class="form-select @error('area_trabajo') is-invalid @enderror">
                                    <option value="">— Selecciona un área —</option>
                                    <option value="Académica"  {{ old('area_trabajo') === 'Académica'  ? 'selected' : '' }}>Académica</option>
                                    <option value="Técnica"    {{ old('area_trabajo') === 'Técnica'    ? 'selected' : '' }}>Técnica</option>
                                    <option value="Ambas"      {{ old('area_trabajo') === 'Ambas'      ? 'selected' : '' }} id="area-ambas">Ambas (Académica y Técnica)</option>
                                </select>
                            </div>
                            <div class="field-error" id="err-area">Selecciona el área de trabajo.</div>
                            @error('area_trabajo')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn-outline-secondary-custom" id="btn-prev-2">
                                <i class="bi bi-arrow-left"></i> Anterior
                            </button>
                            <button type="button" class="btn-primary-custom" id="btn-next-2">
                                Siguiente <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                    </div>{{-- /#step-2 --}}

                    {{-- ══════════ STEP 3: Acceso y Contraseña ══════════ --}}
                    <div class="wizard-step" id="step-3">

                        <div class="info-box">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>
                                Tu correo electrónico (<strong id="email-preview"></strong>) será
                                tu usuario de acceso al sistema.
                            </div>
                        </div>

                        {{-- Contraseña --}}
                        <div class="mb-3">
                            <label for="password" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Contraseña <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Mínimo 8 caracteres, al menos 1 número"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-toggle-eye rounded-end"
                                        id="togglePassword" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                            </div>

                            {{-- Strength indicator --}}
                            <div class="strength-bar-wrap mt-2">
                                <div class="strength-segment" id="seg-1"></div>
                                <div class="strength-segment" id="seg-2"></div>
                                <div class="strength-segment" id="seg-3"></div>
                                <div class="strength-segment" id="seg-4"></div>
                            </div>
                            <div class="strength-label" id="strength-label" style="color:#6b7280;">
                                Ingresa una contraseña
                            </div>

                            <div class="field-error" id="err-password">
                                La contraseña debe tener al menos 8 caracteres y un número.
                            </div>
                            @error('password')<div class="text-danger" style="font-size:.78rem;margin-top:.25rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Confirmar contraseña --}}
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label" style="font-size:.875rem;font-weight:500;color:#374151;">
                                Confirmar contraseña <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control"
                                       placeholder="Repite la contraseña"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-toggle-eye rounded-end"
                                        id="togglePasswordConf" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                                    <i class="bi bi-eye" id="togglePasswordConfIcon"></i>
                                </button>
                            </div>
                            <div class="field-error" id="err-password-conf">Las contraseñas no coinciden.</div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn-outline-secondary-custom" id="btn-prev-3">
                                <i class="bi bi-arrow-left"></i> Anterior
                            </button>
                            <button type="submit" class="btn-primary-custom" id="btn-submit">
                                <i class="bi bi-send-check"></i>
                                Enviar Solicitud
                            </button>
                        </div>

                        <p style="color:#9ca3af;font-size:.76rem;text-align:center;margin-top:1rem;">
                            <i class="bi bi-shield-check me-1"></i>
                            Tu solicitud quedará pendiente de aprobación por el administrador.
                        </p>

                    </div>{{-- /#step-3 --}}

                </form>

                {{-- Demo section (mobile only, shown via CSS) --}}
                @if($demoActivo)
                <div class="demo-mobile-section">
                    <div class="demo-mobile-title"><i class="bi bi-play-circle me-1"></i>O prueba el sistema sin registrarte</div>
                    @if($usuariosDemo['docente'] ?? false)
                    <a href="{{ route('demo.login', 'docente') }}" class="demo-mobile-btn">
                        <div class="demo-mobile-icon" style="background:#1d4ed8;"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <div>Entrar como Docente</div>
                            <div style="font-size:.73rem;font-weight:400;color:#6b7280;">Explora el panel del docente</div>
                        </div>
                        <i class="bi bi-arrow-right ms-auto text-muted" style="font-size:.8rem;"></i>
                    </a>
                    @endif
                    @if($usuariosDemo['estudiante'] ?? false)
                    <a href="{{ route('demo.login', 'estudiante') }}" class="demo-mobile-btn">
                        <div class="demo-mobile-icon" style="background:#059669;"><i class="bi bi-mortarboard-fill"></i></div>
                        <div>
                            <div>Entrar como Estudiante</div>
                            <div style="font-size:.73rem;font-weight:400;color:#6b7280;">Explora el portal estudiantil</div>
                        </div>
                        <i class="bi bi-arrow-right ms-auto text-muted" style="font-size:.8rem;"></i>
                    </a>
                    @endif
                    @if($usuariosDemo['padre'] ?? false)
                    <a href="{{ route('demo.login', 'padre') }}" class="demo-mobile-btn">
                        <div class="demo-mobile-icon" style="background:#b45309;"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div>Entrar como Representante</div>
                            <div style="font-size:.73rem;font-weight:400;color:#6b7280;">Explora el portal del padre</div>
                        </div>
                        <i class="bi bi-arrow-right ms-auto text-muted" style="font-size:.8rem;"></i>
                    </a>
                    @endif
                </div>
                @endif

            </div>{{-- /.form-card --}}
        </div>{{-- /.panel-right --}}

    </div>{{-- /.row --}}
</div>{{-- /.container-fluid --}}

<!-- Bootstrap 5.3.2 JS Bundle -->
<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────
    var currentStep = 1;
    var totalSteps  = 3;

    // ── DOM refs ─────────────────────────────────────────────────────────
    var steps   = [null,
        document.getElementById('step-1'),
        document.getElementById('step-2'),
        document.getElementById('step-3'),
    ];
    var bars    = [null,
        document.getElementById('bar-1'),
        document.getElementById('bar-2'),
        document.getElementById('bar-3'),
    ];
    var leftNums = [null,
        document.getElementById('left-step-1'),
        document.getElementById('left-step-2'),
        document.getElementById('left-step-3'),
    ];
    var progressLabel = document.getElementById('progress-label');

    var stepLabels = ['', 'Datos Personales', 'Centro y Rol', 'Acceso y Contraseña'];

    // ── Navigation helpers ───────────────────────────────────────────────
    function showStep(next, goingBack) {
        steps[currentStep].classList.remove('active', 'going-back');
        steps[next].classList.remove('going-back');
        if (goingBack) steps[next].classList.add('going-back');
        steps[next].classList.add('active');

        currentStep = next;
        updateProgress();
    }

    function updateProgress() {
        for (var i = 1; i <= totalSteps; i++) {
            bars[i].classList.remove('active', 'done');
            if (i < currentStep) bars[i].classList.add('done');
            else if (i === currentStep) bars[i].classList.add('active');

            leftNums[i].classList.remove('active', 'done');
            if (i < currentStep) leftNums[i].classList.add('done');
            else if (i === currentStep) leftNums[i].classList.add('active');
        }
        progressLabel.textContent = 'Paso ' + currentStep + ' de ' + totalSteps + ' — ' + stepLabels[currentStep];
    }

    // ── Validation helpers ───────────────────────────────────────────────
    function showError(id, show) {
        var el = document.getElementById(id);
        if (!el) return;
        if (show) el.classList.add('visible');
        else el.classList.remove('visible');
    }

    function validateStep1() {
        var ok = true;

        var name = document.getElementById('name').value.trim();
        showError('err-name', !name);
        if (!name) ok = false;

        var apellidos = document.getElementById('apellidos').value.trim();
        showError('err-apellidos', !apellidos);
        if (!apellidos) ok = false;

        var cedula = document.getElementById('cedula').value.trim();
        var cedulaRx = /^\d{3}-\d{7}-\d{1}$/;
        showError('err-cedula', !cedulaRx.test(cedula));
        if (!cedulaRx.test(cedula)) ok = false;

        var email = document.getElementById('email_step1').value.trim();
        var emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        showError('err-email', !emailRx.test(email));
        if (!emailRx.test(email)) ok = false;

        return ok;
    }

    function validateStep2() {
        var ok = true;

        var rol = document.getElementById('rol').value;
        showError('err-rol', !rol);
        if (!rol) ok = false;

        var needsArea = (rol === 'Docente' || rol === 'Encargado de Área');
        if (needsArea) {
            var area = document.getElementById('area_trabajo').value;
            showError('err-area', !area);
            if (!area) ok = false;
        }

        return ok;
    }

    function validateStep3() {
        var ok = true;

        var pwd = document.getElementById('password').value;
        var pwdValid = pwd.length >= 8 && /[0-9]/.test(pwd);
        showError('err-password', !pwdValid);
        if (!pwdValid) ok = false;

        var conf = document.getElementById('password_confirmation').value;
        showError('err-password-conf', pwd !== conf);
        if (pwd !== conf) ok = false;

        return ok;
    }

    // ── Cédula auto-format ───────────────────────────────────────────────
    document.getElementById('cedula').addEventListener('input', function () {
        var raw = this.value.replace(/\D/g, '');
        var fmt = '';
        if (raw.length <= 3) {
            fmt = raw;
        } else if (raw.length <= 10) {
            fmt = raw.slice(0, 3) + '-' + raw.slice(3);
        } else {
            fmt = raw.slice(0, 3) + '-' + raw.slice(3, 10) + '-' + raw.slice(10, 11);
        }
        this.value = fmt;
    });

    // ── Rol → area toggle ────────────────────────────────────────────────
    var rolSelect   = document.getElementById('rol');
    var areaGroup   = document.getElementById('area-group');
    var areaSelect  = document.getElementById('area_trabajo');
    var areaAmbas   = document.getElementById('area-ambas');

    rolSelect.addEventListener('change', function () {
        var val = this.value;
        if (val === 'Docente') {
            areaGroup.style.display = '';
            if (areaAmbas) areaAmbas.style.display = '';   // Docente can pick "Ambas"
        } else if (val === 'Encargado de Área') {
            areaGroup.style.display = '';
            if (areaAmbas) areaAmbas.style.display = 'none'; // Encargado: only Académica/Técnica
            if (areaSelect.value === 'Ambas') areaSelect.value = '';
        } else {
            areaGroup.style.display = 'none';
            areaSelect.value = '';
        }
    });

    // Trigger on page load if old() value present
    rolSelect.dispatchEvent(new Event('change'));

    // ── Step navigation ──────────────────────────────────────────────────
    document.getElementById('btn-next-1').addEventListener('click', function () {
        if (validateStep1()) showStep(2, false);
    });

    document.getElementById('btn-prev-2').addEventListener('click', function () {
        showStep(1, true);
    });

    document.getElementById('btn-next-2').addEventListener('click', function () {
        if (validateStep2()) {
            // Show email in step 3 info box
            document.getElementById('email-preview').textContent =
                document.getElementById('email_step1').value.trim();
            showStep(3, false);
        }
    });

    document.getElementById('btn-prev-3').addEventListener('click', function () {
        showStep(2, true);
    });

    // ── Password visibility toggles ──────────────────────────────────────
    function makeToggle(btnId, inputId, iconId) {
        var btn  = document.getElementById(btnId);
        var inp  = document.getElementById(inputId);
        var icon = document.getElementById(iconId);
        if (!btn || !inp || !icon) return;
        btn.addEventListener('click', function () {
            var isPass = inp.type === 'password';
            inp.type = isPass ? 'text' : 'password';
            icon.classList.toggle('bi-eye',       !isPass);
            icon.classList.toggle('bi-eye-slash',  isPass);
            btn.setAttribute('aria-pressed', String(isPass));
        });
    }

    makeToggle('togglePassword',   'password',             'togglePasswordIcon');
    makeToggle('togglePasswordConf','password_confirmation','togglePasswordConfIcon');

    // ── Password strength ─────────────────────────────────────────────────
    var segments = [
        document.getElementById('seg-1'),
        document.getElementById('seg-2'),
        document.getElementById('seg-3'),
        document.getElementById('seg-4'),
    ];
    var strengthLabel = document.getElementById('strength-label');

    var colors  = ['', '#ef4444', '#f97316', '#eab308', '#22c55e'];
    var labels  = ['', 'Muy débil', 'Débil', 'Aceptable', 'Fuerte'];

    function calcStrength(pwd) {
        if (!pwd) return 0;
        var score = 0;
        if (pwd.length >= 8)  score++;
        if (pwd.length >= 12) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^a-zA-Z0-9]/.test(pwd)) score++;
        return Math.min(score, 4);
    }

    document.getElementById('password').addEventListener('input', function () {
        var level = calcStrength(this.value);
        segments.forEach(function (seg, i) {
            seg.style.background = (i < level) ? colors[level] : '#e5e7eb';
        });
        strengthLabel.textContent = this.value ? labels[level] : 'Ingresa una contraseña';
        strengthLabel.style.color = this.value ? colors[level] : '#6b7280';
    });

    // ── Final form submission guard ───────────────────────────────────────
    document.getElementById('register-form').addEventListener('submit', function (e) {
        if (!validateStep3()) {
            e.preventDefault();
        }
    });

    // ── If returning from server error, jump to the correct step ─────────
    var hasServerErrors = {{ $errors->any() ? 'true' : 'false' }};
    if (hasServerErrors) {
        // Determine which step has the error
        var step2Fields = ['rol', 'area_trabajo'];
        var step3Fields = ['password'];
        var errKeys     = @json($errors->keys());

        var targetStep = 1; // default
        errKeys.forEach(function(k) {
            if (step3Fields.indexOf(k) !== -1) targetStep = Math.max(targetStep, 3);
            else if (step2Fields.indexOf(k) !== -1) targetStep = Math.max(targetStep, 2);
        });

        for (var s = 1; s < targetStep; s++) {
            steps[s].classList.remove('active');
        }
        steps[targetStep].classList.add('active');
        currentStep = targetStep;
        updateProgress();

        // Pre-show email if on step 3
        if (targetStep === 3) {
            document.getElementById('email-preview').textContent =
                document.getElementById('email_step1').value.trim();
        }
    }

})();
</script>

</body>
</html>
