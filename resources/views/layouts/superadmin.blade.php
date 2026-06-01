<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel ZuraEdu') — SuperAdmin</title>
    <script>
        (function(){
            var t = localStorage.getItem('sge-theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        :root {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --sa-sidebar-w: 250px;
            --sa-topbar-h: 58px;
            --sa-primary:  #6366f1;
            --sa-primary-dark: #4f46e5;
            --sa-primary-light: #818cf8;
            --sa-glow: rgba(99,102,241,.38);
        }

        body {
            background: #f1f5f9;
            margin: 0;
            min-height: 100vh;
            color: #1e293b;
        }

        /* ══ Sidebar ════════════════════════════════════════════ */
        .sa-sidebar {
            width: var(--sa-sidebar-w);
            min-height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1040;
            overflow-x: hidden;
            overflow-y: hidden;
            border-right: 1px solid rgba(99,102,241,.12);
            box-shadow: 4px 0 28px rgba(0,0,0,.32);
        }

        /* ── Logo ── */
        .sa-logo {
            padding: 1.15rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex;
            align-items: center;
            gap: .85rem;
            background: rgba(255,255,255,.02);
            flex-shrink: 0;
        }
        .sa-logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: .92rem; color: #fff; font-weight: 900;
            flex-shrink: 0;
            box-shadow: 0 4px 18px var(--sa-glow), 0 0 0 1px rgba(255,255,255,.1);
        }
        .sa-logo-text { line-height: 1.2; }
        .sa-logo-text strong { font-size: 1rem; font-weight: 800; color: #f1f5f9; display: block; letter-spacing: .02em; }
        .sa-logo-text span   { font-size: .62rem; color: #64748b; letter-spacing: .08em; text-transform: uppercase; }

        /* ── Nav ── */
        .sa-nav {
            flex: 1;
            overflow-y: auto;
            padding: .5rem 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.08) transparent;
        }
        .sa-nav::-webkit-scrollbar { width: 3px; }
        .sa-nav::-webkit-scrollbar-track { background: transparent; }
        .sa-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

        .sa-section-title {
            font-size: .58rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--sa-primary-light);
            padding: .9rem 1.1rem .2rem;
        }
        .sa-nav a {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .48rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 500;
            transition: background .15s, color .15s;
            margin: 1px .6rem;
            width: calc(100% - 1.2rem);
            border-radius: 8px;
        }
        .sa-nav a:hover  { background: rgba(255,255,255,.06); color: #e2e8f0; }
        .sa-nav a.active {
            background: rgba(99,102,241,.18);
            color: #a5b4fc;
            font-weight: 600;
            border-right: 3px solid #6366f1;
        }
        .sa-nav a i { font-size: .95rem; width: 18px; text-align: center; flex-shrink: 0; opacity: .75; }
        .sa-nav a:hover i, .sa-nav a.active i { opacity: 1; }

        /* ── Footer ── */
        .sa-sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.07);
            padding: .85rem 1rem;
            flex-shrink: 0;
            background: rgba(0,0,0,.2);
        }
        .sa-user {
            display: flex; align-items: center; gap: .65rem;
        }
        .sa-user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .78rem; font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 2px 8px var(--sa-glow);
        }
        .sa-user-name  { font-size: .78rem; font-weight: 700; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sa-user-role  { font-size: .65rem; color: #818cf8; }
        .sa-btn-logout {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #64748b;
            border-radius: 8px;
            padding: .28rem .45rem;
            font-size: .82rem;
            cursor: pointer;
            transition: background .15s, color .15s;
            margin-top: .6rem;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .sa-btn-logout:hover { background: rgba(239,68,68,.12); color: #fca5a5; border-color: rgba(239,68,68,.3); }

        /* ══ Main area ══════════════════════════════════════════ */
        .sa-main {
            margin-left: var(--sa-sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ── */
        .sa-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 1.5rem;
            height: var(--sa-topbar-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 1px 8px rgba(0,0,0,.06);
            gap: 1rem;
        }
        .sa-topbar-title {
            font-size: .92rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .sa-topbar-actions { display: flex; align-items: center; gap: .65rem; }

        /* ── Badge tenant activo ── */
        .sa-tenant-chip {
            display: inline-flex; align-items: center; gap: .4rem;
            background: #ede9fe; color: #4f46e5;
            border: 1px solid #c4b5fd;
            border-radius: 20px;
            padding: .25rem .75rem;
            font-size: .76rem; font-weight: 600;
        }

        /* ── Content ── */
        .sa-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* ── Alertas flash ── */
        .sa-alerts { padding: 0 1.75rem; }

        /* ── Botones topbar ── */
        .sa-topbar .btn-outline-secondary {
            border-color: #e2e8f0; color: #64748b; font-size: .78rem; border-radius: 8px;
        }
        .sa-topbar .btn-outline-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; color: #1e293b; }
        .sa-topbar .btn-outline-danger { font-size: .76rem; border-radius: 8px; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sa-sidebar { transform: translateX(-100%); }
            .sa-main    { margin-left: 0; }
        }

        /* ══ Dark Mode ══════════════════════════════════════════ */
        [data-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        [data-theme="dark"] .sa-topbar {
            background: #1e293b;
            border-bottom-color: rgba(99,102,241,.18);
            box-shadow: 0 1px 8px rgba(0,0,0,.28);
        }
        [data-theme="dark"] .sa-topbar-title { color: #f1f5f9; }
        [data-theme="dark"] .sa-topbar .btn-outline-secondary {
            border-color: rgba(99,102,241,.3); color: #94a3b8;
        }
        [data-theme="dark"] .sa-topbar .btn-outline-secondary:hover {
            background: rgba(99,102,241,.12); border-color: #6366f1; color: #c7d2fe;
        }
        [data-theme="dark"] .sa-tenant-chip {
            background: rgba(99,102,241,.15); color: #a5b4fc; border-color: rgba(99,102,241,.3);
        }
        [data-theme="dark"] .sa-content { color: #e2e8f0; }
        [data-theme="dark"] .alert-success { background: #14532d; border-color: #166534; color: #86efac; }
        [data-theme="dark"] .alert-info    { background: #0c4a6e; border-color: #075985; color: #7dd3fc; }
        [data-theme="dark"] .alert-danger  { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }

        /* ── Toggle dark mode btn ── */
        #saDarkToggle {
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: .92rem;
            transition: background .15s, border-color .15s, color .15s;
        }
        #saDarkToggle:hover { background: #f1f5f9; color: #6366f1; }
        [data-theme="dark"] #saDarkToggle {
            border-color: rgba(99,102,241,.3); color: #818cf8;
        }
        [data-theme="dark"] #saDarkToggle:hover { background: rgba(99,102,241,.12); color: #c7d2fe; }
    </style>
    @stack('styles')
</head>
<body>

{{-- ── Sidebar ─────────────────────────────────────────────── --}}
<aside class="sa-sidebar">
    <div class="sa-logo">
        <div class="sa-logo-icon">ZE</div>
        <div class="sa-logo-text">
            <strong>ZuraEdu</strong>
            <span>Plataforma</span>
        </div>
    </div>

    <nav class="sa-nav">
        <div class="sa-section-title">Plataforma</div>
        <a href="{{ route('superadmin.tenants.index') }}"
           class="{{ request()->routeIs('superadmin.tenants.index') || request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-building-fill"></i>Instituciones
        </a>
        <a href="{{ route('superadmin.tenants.create') }}"
           class="{{ request()->routeIs('superadmin.tenants.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle-fill"></i>Nueva Institución
        </a>

        <div class="sa-section-title" style="margin-top:.25rem;">Filtros rápidos</div>
        <a href="{{ route('superadmin.tenants.index', ['estado' => 'activo']) }}">
            <i class="bi bi-check-circle-fill" style="color:#22c55e;opacity:1;"></i>Activas
        </a>
        <a href="{{ route('superadmin.tenants.index', ['estado' => 'prueba']) }}">
            <i class="bi bi-hourglass-split" style="color:#f59e0b;opacity:1;"></i>En Prueba
        </a>
        <a href="{{ route('superadmin.tenants.index', ['estado' => 'suspendido']) }}">
            <i class="bi bi-x-circle-fill" style="color:#ef4444;opacity:1;"></i>Suspendidas
        </a>
        <a href="{{ route('superadmin.tenants.index', ['plan' => 'premium']) }}">
            <i class="bi bi-star-fill" style="color:#f59e0b;opacity:1;"></i>Plan Premium
        </a>
        <a href="{{ route('superadmin.tenants.index', ['plan' => 'pro']) }}">
            <i class="bi bi-star-half" style="color:#818cf8;opacity:1;"></i>Plan Pro
        </a>
        @if(session('sa_tenant_id'))
        <div class="sa-section-title" style="margin-top:.25rem;">Institución activa</div>
        <a href="{{ route('admin.ejecutivo.index') }}"
           class="{{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;opacity:1;"></i>Dashboard Ejecutivo
        </a>
        @endif
    </nav>

    <div class="sa-sidebar-footer">
        <div class="sa-user">
            <div class="sa-user-avatar">
                {{ strtoupper(substr(Auth::user()->name ?? 'S', 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div class="sa-user-name">{{ Auth::user()->name ?? 'SuperAdmin' }}</div>
                <div class="sa-user-role">super_admin</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sa-btn-logout">
                <i class="bi bi-box-arrow-left"></i>Cerrar sesión
            </button>
        </form>
    </div>
</aside>

{{-- ── Main ────────────────────────────────────────────────── --}}
<div class="sa-main">

    {{-- Topbar --}}
    <div class="sa-topbar">
        <div class="sa-topbar-title">
            <i class="bi bi-shield-fill-check" style="color:#6366f1;font-size:1.05rem;"></i>
            @yield('title', 'Panel ZuraEdu')
        </div>
        <div class="sa-topbar-actions">
            @if(session('sa_tenant_id'))
            <div class="sa-tenant-chip">
                <i class="bi bi-building"></i>{{ session('sa_tenant_nombre') }}
            </div>
            <form method="POST" action="{{ route('superadmin.tenants.exit-panel') }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x me-1"></i>Salir del panel
                </button>
            </form>
            @endif
            <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-building-fill me-1"></i>Instituciones
            </a>
            <button id="saDarkToggle" title="Cambiar tema">
                <i class="bi bi-moon-stars-fill" id="saDarkIcon"></i>
            </button>
        </div>
    </div>

    {{-- Alertas flash --}}
    <div class="sa-alerts">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;border-radius:10px;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;border-radius:10px;">
            <i class="bi bi-info-circle-fill me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;border-radius:10px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;border-radius:10px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    {{-- Contenido --}}
    <div class="sa-content">
        @yield('content')
    </div>

</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
<script>
(function () {
    var btn  = document.getElementById('saDarkToggle');
    var icon = document.getElementById('saDarkIcon');
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('sge-theme', t);
        icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    applyTheme(localStorage.getItem('sge-theme') || 'light');
    btn.addEventListener('click', function () {
        applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
})();
</script>

{{-- ── ZuraEdu Realtime — Echo + Reverb ──────────────────────────────────── --}}
@auth
<script>
window._REVERB_KEY    = '{{ config("broadcasting.connections.reverb.key") }}';
window._REVERB_HOST   = '{{ config("broadcasting.connections.reverb.options.host") }}';
window._REVERB_PORT   = {{ config("broadcasting.connections.reverb.options.port", 8080) }};
window._REVERB_SCHEME = '{{ config("broadcasting.connections.reverb.options.scheme", "http") }}';
window._SGE_USER_ID   = {{ auth()->id() }};
window._SGE_ROL       = 'SuperAdmin';
window._SGE_TENANT_ID = {{ tenant_id() ?? 'null' }};
window._SGE_GRUPO_IDS = [];
window._SGE_CLASE_IDS = [];
window._SGE_DEBUG     = {{ config('app.debug') ? 'true' : 'false' }};
</script>
@stack('realtime-data')
@vite('resources/js/echo.js')
@endauth

<div id="sge-toast-container" aria-live="polite" aria-atomic="false"
     style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;max-width:340px;"></div>

</body>
</html>
