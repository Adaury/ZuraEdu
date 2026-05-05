<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel ZuraEdu') — SuperAdmin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    <style>
        :root { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }

        body {
            background: #f1f5f9;
            margin: 0;
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ──────────────────────────────────── */
        .sa-sidebar {
            width: 240px;
            min-height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sa-logo {
            padding: 1.25rem 1.25rem .75rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .sa-logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #fff; font-weight: 900;
            flex-shrink: 0;
        }
        .sa-logo-text { line-height: 1.2; }
        .sa-logo-text strong { font-size: .9rem; color: #fff; display: block; }
        .sa-logo-text span   { font-size: .68rem; color: #a5b4fc; }

        .sa-nav { padding: .75rem 0; flex: 1; }
        .sa-section-title {
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #6366f1;
            padding: .75rem 1.1rem .3rem;
        }
        .sa-nav a {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .5rem 1.1rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: .83rem;
            font-weight: 500;
            border-radius: 0;
            transition: background .15s, color .15s;
        }
        .sa-nav a:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
        .sa-nav a.active {
            background: rgba(99,102,241,.2);
            color: #a5b4fc;
            border-right: 3px solid #6366f1;
        }
        .sa-nav a i { font-size: .95rem; width: 18px; text-align: center; flex-shrink: 0; }

        .sa-sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.08);
            padding: .85rem 1.1rem;
        }
        .sa-user {
            display: flex; align-items: center; gap: .6rem;
            font-size: .78rem; color: #94a3b8;
        }
        .sa-user-avatar {
            width: 30px; height: 30px;
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .72rem; font-weight: 700;
            flex-shrink: 0;
        }

        /* ── Main ──────────────────────────────────────── */
        .sa-main {
            margin-left: 240px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .sa-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .7rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .sa-topbar-title {
            font-size: .85rem;
            font-weight: 700;
            color: #0f172a;
        }
        .sa-topbar-actions { display: flex; align-items: center; gap: .75rem; }

        .sa-content {
            padding: 1.5rem;
            flex: 1;
        }

        @media (max-width: 768px) {
            .sa-sidebar { width: 0; overflow: hidden; }
            .sa-main { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- ── Sidebar ─────────────────────────────────────────────── --}}
<aside class="sa-sidebar">
    <div class="sa-logo">
        <div class="sa-logo-icon">Z</div>
        <div class="sa-logo-text">
            <strong>ZuraEdu</strong>
            <span>Panel de Plataforma</span>
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

        <div class="sa-section-title" style="margin-top:.5rem;">Filtros rápidos</div>
        <a href="{{ route('superadmin.tenants.index', ['estado' => 'activo']) }}">
            <i class="bi bi-check-circle-fill" style="color:#22c55e;"></i>Activas
        </a>
        <a href="{{ route('superadmin.tenants.index', ['estado' => 'prueba']) }}">
            <i class="bi bi-hourglass-split" style="color:#f59e0b;"></i>En Prueba
        </a>
        <a href="{{ route('superadmin.tenants.index', ['estado' => 'suspendido']) }}">
            <i class="bi bi-x-circle-fill" style="color:#ef4444;"></i>Suspendidas
        </a>
        <a href="{{ route('superadmin.tenants.index', ['plan' => 'premium']) }}">
            <i class="bi bi-star-fill" style="color:#f59e0b;"></i>Plan Premium
        </a>
        <a href="{{ route('superadmin.tenants.index', ['plan' => 'pro']) }}">
            <i class="bi bi-star-half" style="color:#3b82f6;"></i>Plan Pro
        </a>
    </nav>

    <div class="sa-sidebar-footer">
        <div class="sa-user">
            <div class="sa-user-avatar">
                {{ strtoupper(substr(Auth::user()->name ?? 'S', 0, 1)) }}
            </div>
            <div>
                <div style="color:#e2e8f0;font-weight:600;">{{ Auth::user()->name ?? 'SuperAdmin' }}</div>
                <div style="font-size:.65rem;color:#6366f1;">super_admin</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" style="background:none;border:none;color:#64748b;font-size:.75rem;padding:0;cursor:pointer;width:100%;text-align:left;">
                <i class="bi bi-box-arrow-left me-1"></i>Cerrar sesión
            </button>
        </form>
    </div>
</aside>

{{-- ── Main ────────────────────────────────────────────────── --}}
<div class="sa-main">

    {{-- Topbar --}}
    <div class="sa-topbar">
        <div class="sa-topbar-title">
            <i class="bi bi-shield-fill-check me-2" style="color:#6366f1;"></i>
            @yield('title', 'Panel ZuraEdu')
        </div>
        <div class="sa-topbar-actions">
            @if(session('sa_tenant_id'))
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:.75rem;color:#6366f1;font-weight:600;">
                    <i class="bi bi-building me-1"></i>{{ session('sa_tenant_nombre') }}
                </span>
                <form method="POST" action="{{ route('superadmin.tenants.exit-panel') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" style="font-size:.72rem;border-radius:6px;">
                        <i class="bi bi-x me-1"></i>Salir del panel
                    </button>
                </form>
            </div>
            @endif
            <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;border-radius:6px;">
                <i class="bi bi-building-fill me-1"></i>Instituciones
            </a>
        </div>
    </div>

    {{-- Alertas de sesión --}}
    <div style="padding:0 1.5rem;">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;">
            <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3 mb-0" style="font-size:.85rem;">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
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
</body>
</html>
