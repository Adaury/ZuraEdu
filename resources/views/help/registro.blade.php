<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Guía de Registro — {{ \App\Helpers\Setting::get('system_name', 'ZuraEdu') }}</title>

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
            background: #f0f4f8;
            color: #1e293b;
            min-height: 100vh;
        }

        /* ── TOPBAR ─────────────────────────────────────────────── */
        .help-topbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .help-topbar .logo-badge {
            width: 44px; height: 44px;
            background: var(--secondary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .9rem; color: #fff;
            box-shadow: 0 2px 10px rgba(192,57,43,.4);
            flex-shrink: 0;
        }
        .help-topbar .brand-text .name {
            font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.1;
        }
        .help-topbar .brand-text .sub {
            font-size: .7rem; color: rgba(255,255,255,.7); letter-spacing: .05em; text-transform: uppercase;
        }
        .help-topbar .page-title {
            margin-left: auto;
            font-size: .85rem; font-weight: 600; color: rgba(255,255,255,.85);
            display: flex; align-items: center; gap: .4rem;
        }
        .btn-back {
            display: inline-flex; align-items: center; gap: .4rem;
            background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
            color: #fff; border-radius: 8px; padding: .38rem .85rem;
            font-size: .8rem; font-weight: 600; text-decoration: none;
            transition: background .18s;
        }
        .btn-back:hover { background: rgba(255,255,255,.22); color: #fff; }

        /* ── TOC (table of contents) ──────────────────────────── */
        .toc-nav {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: .6rem 2rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            overflow-x: auto;
        }
        .toc-nav .toc-label {
            font-size: .72rem; font-weight: 700; color: #9ca3af;
            text-transform: uppercase; letter-spacing: .08em; flex-shrink: 0;
        }
        .toc-nav a {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .78rem; font-weight: 600; color: var(--primary);
            text-decoration: none; padding: .22rem .65rem;
            border-radius: 20px; border: 1px solid #e2e8f0;
            background: #f8fafc; white-space: nowrap;
            transition: background .15s, border-color .15s;
        }
        .toc-nav a:hover { background: #eff6ff; border-color: var(--primary); }
        .toc-nav a .step-num {
            width: 18px; height: 18px;
            background: var(--primary); color: #fff;
            border-radius: 50%; font-size: .65rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
        }

        /* ── MAIN LAYOUT ─────────────────────────────────────── */
        .help-body {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        .page-hero {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }
        .page-hero-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff; flex-shrink: 0;
        }
        .page-hero h1 { font-size: 1.45rem; font-weight: 800; color: var(--primary); margin-bottom: .3rem; }
        .page-hero p  { font-size: .9rem; color: #6b7280; margin: 0; }

        /* ── STEP CARDS ─────────────────────────────────────── */
        .step-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 6px rgba(0,0,0,.05);
            display: flex;
            gap: 1.5rem;
            scroll-margin-top: 120px;
        }
        .step-number {
            width: 52px; height: 52px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 800;
        }
        .step-content { flex: 1; min-width: 0; }
        .step-title {
            font-size: 1.1rem; font-weight: 700; color: var(--primary);
            margin-bottom: .3rem;
        }
        .step-desc { font-size: .88rem; color: #4b5563; margin-bottom: 1rem; }

        .step-instructions {
            list-style: none; padding: 0; margin: .75rem 0 0;
        }
        .step-instructions li {
            font-size: .855rem; color: #374151;
            padding: .3rem 0;
            display: flex; align-items: flex-start; gap: .5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .step-instructions li:last-child { border-bottom: none; }
        .step-instructions li i { flex-shrink: 0; margin-top: .1rem; }

        /* ── MOCK SCREENS ───────────────────────────────────── */
        .mock-screen {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: .78rem;
            position: relative;
            overflow: hidden;
        }
        .mock-screen::before {
            content: 'Vista del Sistema';
            position: absolute;
            top: 8px; right: 10px;
            font-size: .65rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .mock-topbar {
            height: 32px;
            background: linear-gradient(135deg, #1e3a6e, #0f1f3d);
            border-radius: 8px 8px 0 0;
            margin: -1rem -1rem .75rem;
            display: flex; align-items: center; padding: 0 .75rem; gap: .4rem;
        }
        .mock-topbar .dot { width: 8px; height: 8px; border-radius: 50%; }
        .mock-form-field {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: .35rem .6rem;
            margin-bottom: .4rem;
            color: #374151;
            font-size: .78rem;
        }
        .mock-form-field.focused { border-color: #1e3a6e; box-shadow: 0 0 0 2px rgba(30,58,110,.1); }
        .mock-form-label { font-size: .68rem; font-weight: 600; color: #6b7280; margin-bottom: .15rem; }
        .mock-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .85rem; border-radius: 6px; font-size: .75rem; font-weight: 600; }
        .mock-btn-primary { background: #1e3a6e; color: #fff; }
        .mock-btn-success { background: #16a34a; color: #fff; }
        .mock-badge { display: inline-block; padding: .15em .5em; border-radius: 99px; font-size: .65rem; font-weight: 700; }
        .mock-table { width: 100%; border-collapse: collapse; }
        .mock-table th { background: #1e3a6e; color: #fff; padding: .3rem .5rem; font-size: .68rem; font-weight: 600; text-align: center; }
        .mock-table td { padding: .28rem .5rem; border-bottom: 1px solid #f0f4f8; font-size: .72rem; }
        .mock-input { background: #fff; border: 1px solid #d1d5db; border-radius: 4px; padding: .15rem .35rem; width: 52px; text-align: center; font-size: .75rem; }
        .mock-input.ok   { background: #dcfce7; border-color: #86efac; color: #15803d; }

        /* Role cards */
        .mock-role-card {
            border: 2px solid #e5e7eb; border-radius: 10px;
            padding: .65rem .75rem; cursor: pointer; text-align: center;
            transition: border-color .15s;
        }
        .mock-role-card.selected { border-color: #1e3a6e; background: #eff6ff; }
        .mock-role-card .icon { font-size: 1.4rem; margin-bottom: .25rem; }
        .mock-role-card .label { font-size: .72rem; font-weight: 700; color: #374151; }
        .mock-role-card .desc  { font-size: .62rem; color: #9ca3af; }

        /* Area cards */
        .mock-area-card {
            border: 2px solid #e5e7eb; border-radius: 10px;
            padding: .6rem .7rem; text-align: center;
        }
        .mock-area-card.selected { border-color: #1e3a6e; background: #eff6ff; }

        /* Password strength */
        .strength-bar { display: flex; gap: 3px; margin-bottom: .4rem; }
        .strength-bar .seg { height: 5px; border-radius: 3px; flex: 1; }
        .strength-bar .seg.filled { background: #16a34a; }
        .strength-bar .seg.empty  { background: #e5e7eb; }

        /* Summary table */
        .mock-summary-row { display: flex; border-bottom: 1px solid #e5e7eb; }
        .mock-summary-row:last-child { border-bottom: none; }
        .mock-summary-label { font-size: .68rem; font-weight: 700; color: #6b7280; padding: .4rem .6rem; width: 45%; background: #f8fafc; }
        .mock-summary-val   { font-size: .72rem; color: #1e293b; padding: .4rem .6rem; }

        /* Pending banner */
        .mock-pending {
            background: #fef9c3; border: 1px solid #fde047;
            border-radius: 8px; padding: .6rem .85rem;
            font-size: .75rem; font-weight: 600; color: #854d0e;
            display: flex; align-items: center; gap: .5rem;
            margin-top: .65rem;
        }

        /* ── FAQ SECTION ─────────────────────────────────────── */
        .faq-section {
            background: #fff;
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 6px rgba(0,0,0,.05);
        }
        .faq-section h3 {
            font-size: 1.1rem; font-weight: 800; color: var(--primary);
            margin-bottom: 1rem;
        }
        .accordion-button:not(.collapsed) {
            background: #eff6ff; color: var(--primary);
        }
        .accordion-button:focus { box-shadow: 0 0 0 2px rgba(30,58,110,.2); }

        /* ── MOBILE ─────────────────────────────────────────── */
        @media (max-width: 640px) {
            .help-topbar { padding: .75rem 1rem; flex-wrap: wrap; }
            .help-topbar .page-title { display: none; }
            .help-body { padding: 1rem .75rem 3rem; }
            .step-card { flex-direction: column; gap: .75rem; }
            .page-hero { flex-direction: column; gap: .75rem; }
            .toc-nav { padding: .5rem 1rem; }
        }
    </style>
</head>
<body>

{{-- ── TOPBAR ──────────────────────────────────────────────── --}}
<header class="help-topbar">
    @php $sysName = \App\Helpers\Setting::get('system_name', 'ZuraEdu'); $sysAbbr = \App\Helpers\Setting::get('system_abbr', 'ZE'); @endphp
    <div class="logo-badge">{{ $sysAbbr }}</div>
    <div class="brand-text">
        <div class="name">{{ $sysName }}</div>
        <div class="sub">Sistema de Gestión Escolar</div>
    </div>
    <span class="page-title d-none d-md-flex">
        <i class="bi bi-book-half"></i> Guía de Registro
    </span>
    <a href="{{ route('register') }}" class="btn-back ms-auto ms-md-2">
        <i class="bi bi-arrow-left"></i> Volver al registro
    </a>
</header>

{{-- ── TABLE OF CONTENTS ────────────────────────────────────── --}}
<nav class="toc-nav" aria-label="Navegación de pasos">
    <span class="toc-label">Ir al paso:</span>
    <a href="#paso1"><span class="step-num">1</span> Datos Personales</a>
    <a href="#paso2"><span class="step-num">2</span> Selección de Rol</a>
    <a href="#paso3"><span class="step-num">3</span> Config. del Rol</a>
    <a href="#paso4"><span class="step-num">4</span> Acceso y Seguridad</a>
    <a href="#paso5"><span class="step-num">5</span> Confirmación</a>
    <a href="#faq"><i class="bi bi-question-circle"></i> FAQ</a>
</nav>

{{-- ── MAIN BODY ────────────────────────────────────────────── --}}
<main class="help-body">

    {{-- Hero --}}
    <div class="page-hero">
        <div class="page-hero-icon">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <div>
            <h1>Guía de Registro al Sistema SGE</h1>
            <p>Esta guía explica paso a paso cómo solicitar tu acceso al Sistema de Gestión Escolar. El proceso consta de <strong>5 pasos</strong> y concluye con una solicitud pendiente de aprobación por parte del administrador.</p>
        </div>
    </div>

    {{-- ═══════════════ PASO 1 ══════════════════════════════ --}}
    <div class="step-card" id="paso1">
        <div class="step-number">1</div>
        <div class="step-content">
            <h4 class="step-title">
                <i class="bi bi-person-vcard me-2" style="color:var(--secondary);"></i>
                Datos Personales
            </h4>
            <p class="step-desc">En el primer paso debes ingresar tus datos de identificación personal. Asegúrate de que toda la información sea correcta, ya que será revisada por el administrador.</p>

            {{-- Mock: Formulario de datos personales --}}
            <div class="mock-screen">
                <div class="mock-topbar">
                    <div class="dot" style="background:#ef4444;"></div>
                    <div class="dot" style="background:#f59e0b;"></div>
                    <div class="dot" style="background:#22c55e;"></div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="mock-form-label">Nombres *</div>
                        <div class="mock-form-field focused">María Fernanda</div>
                    </div>
                    <div class="col-6">
                        <div class="mock-form-label">Apellidos *</div>
                        <div class="mock-form-field">García López</div>
                    </div>
                    <div class="col-6">
                        <div class="mock-form-label">Cédula *</div>
                        <div class="mock-form-field">001-1234567-8</div>
                    </div>
                    <div class="col-6">
                        <div class="mock-form-label">Teléfono</div>
                        <div class="mock-form-field">809-555-1234</div>
                    </div>
                    <div class="col-12">
                        <div class="mock-form-label">Correo Electrónico *</div>
                        <div class="mock-form-field">m.garcia@psac.edu.do</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <span class="mock-btn mock-btn-primary">Siguiente <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>

            <ul class="step-instructions">
                <li><i class="bi bi-check2-circle text-success"></i>Ingresa tu nombre y apellidos completos tal como aparecen en tu cédula.</li>
                <li><i class="bi bi-info-circle text-primary"></i>La cédula debe tener el formato <strong>000-0000000-0</strong> — el sistema la formatea automáticamente al escribirla.</li>
                <li><i class="bi bi-envelope-at text-primary"></i>El correo debe ser institucional (<code>@psac.edu.do</code> o el asignado por el centro).</li>
                <li><i class="bi bi-arrow-right-circle text-success"></i>Haz clic en "Siguiente" cuando todos los campos requeridos (*) estén completos.</li>
            </ul>
        </div>
    </div>

    {{-- ═══════════════ PASO 2 ══════════════════════════════ --}}
    <div class="step-card" id="paso2">
        <div class="step-number">2</div>
        <div class="step-content">
            <h4 class="step-title">
                <i class="bi bi-people me-2" style="color:var(--secondary);"></i>
                Selección de Rol
            </h4>
            <p class="step-desc">Selecciona el rol que corresponde a tu función en el centro educativo. Cada rol tiene permisos y vistas diferentes dentro del sistema.</p>

            {{-- Mock: Tarjetas de rol --}}
            <div class="mock-screen">
                <div class="mock-topbar">
                    <div class="dot" style="background:#ef4444;"></div>
                    <div class="dot" style="background:#f59e0b;"></div>
                    <div class="dot" style="background:#22c55e;"></div>
                </div>
                <div class="mock-form-label mb-2">Selecciona tu rol en el centro:</div>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="mock-role-card selected">
                            <div class="icon">🎓</div>
                            <div class="label">Docente</div>
                            <div class="desc">Mis materias y grupos</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mock-role-card">
                            <div class="icon">📋</div>
                            <div class="label">Encargado de Área</div>
                            <div class="desc">Gestión del área</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mock-role-card">
                            <div class="icon">🗂️</div>
                            <div class="label">Secretaria Docente</div>
                            <div class="desc">Boletines y estudiantes</div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="step-instructions">
                <li><i class="bi bi-cursor-fill text-primary"></i>Haz clic en la tarjeta del rol que mejor describe tu función — se resaltará con un borde azul.</li>
                <li><i class="bi bi-mortarboard text-success"></i><strong>Docente:</strong> acceso a tus materias y grupos asignados para registrar calificaciones y asistencia.</li>
                <li><i class="bi bi-diagram-2 text-primary"></i><strong>Encargado de Área:</strong> gestión completa del área académica o técnica asignada.</li>
                <li><i class="bi bi-file-earmark-text text-success"></i><strong>Secretaria Docente:</strong> acceso a boletines y gestión administrativa de estudiantes.</li>
                <li><i class="bi bi-exclamation-triangle text-warning"></i><strong>Nota:</strong> El rol Administrador solo puede ser asignado por un administrador existente del sistema.</li>
            </ul>
        </div>
    </div>

    {{-- ═══════════════ PASO 3 ══════════════════════════════ --}}
    <div class="step-card" id="paso3">
        <div class="step-number">3</div>
        <div class="step-content">
            <h4 class="step-title">
                <i class="bi bi-sliders me-2" style="color:var(--secondary);"></i>
                Configuración del Rol
            </h4>
            <p class="step-desc">Según el rol seleccionado, completa la configuración específica. Esta información ayuda al administrador a asignarte los recursos correctos.</p>

            {{-- Mock: Configuración docente --}}
            <div class="mock-screen">
                <div class="mock-topbar">
                    <div class="dot" style="background:#ef4444;"></div>
                    <div class="dot" style="background:#f59e0b;"></div>
                    <div class="dot" style="background:#22c55e;"></div>
                </div>
                <div class="mock-form-label mb-2">
                    <span class="mock-badge" style="background:#dcfce7;color:#15803d;">Docente seleccionado</span>
                    &nbsp; ¿En qué área impartes clases?
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="mock-area-card selected">
                            <div style="font-size:1.2rem;margin-bottom:.25rem;">📚</div>
                            <div style="font-size:.72rem;font-weight:700;color:#374151;">Académica</div>
                            <div style="font-size:.62rem;color:#9ca3af;">Materias teóricas</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mock-area-card">
                            <div style="font-size:1.2rem;margin-bottom:.25rem;">🔧</div>
                            <div style="font-size:.72rem;font-weight:700;color:#374151;">Técnica</div>
                            <div style="font-size:.62rem;color:#9ca3af;">Materias técnicas (RA)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mock-area-card">
                            <div style="font-size:1.2rem;margin-bottom:.25rem;">⚡</div>
                            <div style="font-size:.72rem;font-weight:700;color:#374151;">Ambas</div>
                            <div style="font-size:.62rem;color:#9ca3af;">Académica y Técnica</div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="step-instructions">
                <li><i class="bi bi-book text-primary"></i><strong>Docente:</strong> selecciona el área donde impartes clases — Académica (competencias), Técnica (RA) o Ambas.</li>
                <li><i class="bi bi-diagram-2 text-primary"></i><strong>Encargado de Área:</strong> selecciona el área bajo tu responsabilidad (Académica o Técnica).</li>
                <li><i class="bi bi-check-circle text-success"></i><strong>Secretaria Docente:</strong> este paso no requiere configuración adicional — haz clic en "Siguiente".</li>
                <li><i class="bi bi-info-circle text-primary"></i>Si no estás seguro de tu área, consulta con el director o coordinador antes de registrarte.</li>
            </ul>
        </div>
    </div>

    {{-- ═══════════════ PASO 4 ══════════════════════════════ --}}
    <div class="step-card" id="paso4">
        <div class="step-number">4</div>
        <div class="step-content">
            <h4 class="step-title">
                <i class="bi bi-shield-lock me-2" style="color:var(--secondary);"></i>
                Acceso y Seguridad
            </h4>
            <p class="step-desc">Configura tu contraseña de acceso y el código del centro. Esta información es confidencial — nunca la compartas con terceros.</p>

            {{-- Mock: Campos de seguridad --}}
            <div class="mock-screen">
                <div class="mock-topbar">
                    <div class="dot" style="background:#ef4444;"></div>
                    <div class="dot" style="background:#f59e0b;"></div>
                    <div class="dot" style="background:#22c55e;"></div>
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="mock-form-label">Nueva Contraseña *</div>
                        <div class="mock-form-field">••••••••••</div>
                        <div class="strength-bar mt-1">
                            <div class="seg filled"></div>
                            <div class="seg filled"></div>
                            <div class="seg filled"></div>
                            <div class="seg filled"></div>
                        </div>
                        <div style="font-size:.65rem;color:#16a34a;font-weight:600;">Contraseña fuerte</div>
                    </div>
                    <div class="col-12">
                        <div class="mock-form-label">Confirmar Contraseña *</div>
                        <div class="mock-form-field mock-input ok" style="width:100%;">••••••••••</div>
                    </div>
                    <div class="col-12">
                        <div class="mock-form-label">Código de Acceso del Centro *</div>
                        <div class="mock-form-field">{{ $sysAbbr }}2026</div>
                        <div style="font-size:.65rem;color:#6b7280;margin-top:.2rem;">
                            <i class="bi bi-info-circle"></i> Proporcionado por el administrador del sistema
                        </div>
                    </div>
                </div>
            </div>

            <ul class="step-instructions">
                <li><i class="bi bi-key text-primary"></i>La contraseña debe tener <strong>mínimo 8 caracteres</strong> e incluir al menos <strong>1 número</strong>.</li>
                <li><i class="bi bi-bar-chart-fill text-success"></i>El indicador de fortaleza cambia de rojo (débil) → naranja (media) → verde (fuerte).</li>
                <li><i class="bi bi-building text-primary"></i>El <strong>Código de Acceso del Centro</strong> es proporcionado por el administrador del sistema.</li>
                <li><i class="bi bi-check2-all text-success"></i>Confirma la contraseña escribiéndola exactamente igual en el segundo campo.</li>
                <li><i class="bi bi-eye-slash text-secondary"></i>Usa el botón de ojo (<i class="bi bi-eye"></i>) para mostrar/ocultar la contraseña mientras la escribes.</li>
            </ul>
        </div>
    </div>

    {{-- ═══════════════ PASO 5 ══════════════════════════════ --}}
    <div class="step-card" id="paso5">
        <div class="step-number">5</div>
        <div class="step-content">
            <h4 class="step-title">
                <i class="bi bi-send-check me-2" style="color:var(--secondary);"></i>
                Confirmación y Espera
            </h4>
            <p class="step-desc">Revisa el resumen de tus datos y envía la solicitud. Tu cuenta quedará <strong>pendiente de aprobación</strong> hasta que un administrador la active.</p>

            {{-- Mock: Resumen y estado --}}
            <div class="mock-screen">
                <div class="mock-topbar">
                    <div class="dot" style="background:#ef4444;"></div>
                    <div class="dot" style="background:#f59e0b;"></div>
                    <div class="dot" style="background:#22c55e;"></div>
                </div>
                <div style="font-size:.72rem;font-weight:700;color:#374151;margin-bottom:.5rem;">Resumen de tu solicitud:</div>
                <div class="mock-summary-row"><div class="mock-summary-label">Nombre completo</div><div class="mock-summary-val">María Fernanda García López</div></div>
                <div class="mock-summary-row"><div class="mock-summary-label">Cédula</div><div class="mock-summary-val">001-1234567-8</div></div>
                <div class="mock-summary-row"><div class="mock-summary-label">Correo</div><div class="mock-summary-val">m.garcia@psac.edu.do</div></div>
                <div class="mock-summary-row"><div class="mock-summary-label">Rol solicitado</div><div class="mock-summary-val"><span class="mock-badge" style="background:#dbeafe;color:#1d4ed8;">Docente</span></div></div>
                <div class="mock-summary-row"><div class="mock-summary-label">Área</div><div class="mock-summary-val">Académica</div></div>

                <div class="mock-pending">
                    <i class="bi bi-hourglass-split" style="font-size:1rem;"></i>
                    <div>
                        <div>Estado: Pendiente de Aprobación</div>
                        <div style="font-weight:400;font-size:.68rem;margin-top:.1rem;">Un administrador revisará tu solicitud en 24-48 horas hábiles.</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <span class="mock-btn mock-btn-success"><i class="bi bi-send"></i> Enviar Solicitud</span>
                </div>
            </div>

            <ul class="step-instructions">
                <li><i class="bi bi-eye text-primary"></i>Verifica cuidadosamente que todos los datos mostrados en el resumen son correctos.</li>
                <li><i class="bi bi-send text-success"></i>Haz clic en <strong>"Enviar Solicitud"</strong> — recibirás una confirmación en pantalla.</li>
                <li><i class="bi bi-check2-circle text-success"></i>Si todo está bien, verás un mensaje verde de confirmación.</li>
                <li><i class="bi bi-hourglass-split text-warning"></i>Un administrador revisará y activará tu cuenta — este proceso puede tomar entre <strong>24-48 horas hábiles</strong>.</li>
                <li><i class="bi bi-box-arrow-in-right text-primary"></i>Una vez activada, podrás iniciar sesión en <a href="{{ route('login') }}" style="color:var(--primary);">la página de acceso</a> con tu correo y contraseña.</li>
            </ul>
        </div>
    </div>

    {{-- ═══════════════ FAQ ══════════════════════════════════ --}}
    <div class="faq-section" id="faq">
        <h3><i class="bi bi-question-circle-fill me-2" style="color:var(--secondary);"></i>Preguntas Frecuentes</h3>

        <div class="accordion accordion-flush" id="faqAccordion">

            <div class="accordion-item" style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:.6rem;overflow:hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
                        <i class="bi bi-clock me-2 text-primary"></i>
                        ¿Cuánto tiempo tarda la aprobación de la cuenta?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="font-size:.875rem;color:#374151;">
                        El proceso de aprobación toma entre <strong>24 y 48 horas hábiles</strong> desde que se envía la solicitud. Si transcurre ese tiempo sin respuesta, contacta directamente al administrador del sistema.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:.6rem;overflow:hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        <i class="bi bi-key me-2 text-warning"></i>
                        ¿Qué hago si no tengo el Código de Acceso del Centro?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="font-size:.875rem;color:#374151;">
                        El código es proporcionado por el administrador del sistema. Si no lo tienes, <strong>contacta al administrador</strong> antes de completar el registro.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:.6rem;overflow:hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        <i class="bi bi-envelope me-2 text-success"></i>
                        ¿Puedo registrarme con un correo personal (Gmail, Hotmail)?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="font-size:.875rem;color:#374151;">
                        El sistema <strong>recomienda usar el correo institucional</strong> (<code>@psac.edu.do</code> u otro asignado por el centro). Si no tienes uno, consulta con la administración. Es posible usar un correo personal, pero podría limitar ciertas funcionalidades o ser rechazado por el administrador.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:.6rem;overflow:hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        <i class="bi bi-person-check me-2 text-primary"></i>
                        ¿Qué pasa si ya tengo una cuenta creada?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="font-size:.875rem;color:#374151;">
                        Si ya tienes una cuenta, <strong>no es necesario registrarse de nuevo</strong>. Dirígete directamente a la <a href="{{ route('login') }}" style="color:var(--primary);">página de inicio de sesión</a> y usa tu correo y contraseña. Si olvidaste tu contraseña, contacta al administrador.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        <i class="bi bi-pencil-square me-2 text-secondary"></i>
                        ¿Puedo cambiar mi rol después del registro?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="font-size:.875rem;color:#374151;">
                        <strong>No directamente.</strong> Una vez creada la cuenta, el rol solo puede ser modificado por un <strong>administrador del sistema</strong>. Si necesitas cambiar tu rol, solicítalo al administrador del sistema a través de los canales correspondientes.
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer de la guía --}}
        <div class="mt-3 pt-3 border-top d-flex align-items-center gap-2" style="font-size:.78rem;color:#9ca3af;">
            <i class="bi bi-info-circle"></i>
            Guía actualizada para el año escolar 2025-2026. Sistema SGE — Politécnico Salesiano Arquides Calderón.
        </div>
    </div>

</main>

<!-- Bootstrap 5.3.2 JS Bundle -->
<script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// Smooth scroll para los anchors de TOC
document.querySelectorAll('.toc-nav a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

</body>
</html>
