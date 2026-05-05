<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') — PSAC | Politécnico Salesiano Arquides Calderón</title>

    {{-- Dynamic favicon (cached 10 min) --}}
    @php $faviconPath = \Illuminate\Support\Facades\Cache::remember('system_favicon', 600, fn () => \Illuminate\Support\Facades\DB::table('system_settings')->where('key','system_favicon')->value('value')); @endphp
    @if($faviconPath)
    <link rel="icon" href="{{ asset('storage/' . $faviconPath) }}" type="image/x-icon">
    @else
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @endif

    <!-- Progress bar de navegación -->
    <style>
        #nprogress-bar {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; z-index: 99999;
            background: var(--primary, #2563eb);
            box-shadow: 0 0 10px rgba(37,99,235,.7);
            transform: scaleX(0); transform-origin: left;
            transition: transform .1s linear;
            pointer-events: none;
        }
    </style>

    <!-- Bootstrap 5 — local -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Bootstrap Icons — local -->
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    <!-- Inter font — sistema (sin dependencia de Google) -->
    <style>
        @import url('data:text/css,');
        :root { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>

    <!-- Tailwind CSS — Play CDN con preflight desactivado para convivir con Bootstrap -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['attribute', '[data-theme="dark"]'],
            corePlugins: { preflight: false }
        }
    </script>
    <!-- x-cloak: ocultar elementos Alpine hasta que inicialice -->
    <style>[x-cloak] { display: none !important; }</style>

    @stack('styles')

    <style>
        /* ── CSS Variables ─────────────────────────────── */
        :root {
            --primary:         #3B82F6;
            --primary-dark:    #2563EB;
            --primary-light:   #60A5FA;
            --secondary:       #10b981;
            --accent:          #10b981;
            --accent-light:    #d1fae5;
            --sidebar-bg:      #0f172a;
            --sidebar-width:   260px;
            --topbar-height:   60px;
            --sidebar-text:    #94a3b8;
            --sidebar-hover:   rgba(255,255,255,.06);
            --sidebar-active:  var(--primary);
            --role-color:      #3B82F6;
            --role-glow:       rgba(59,130,246,.35);
            --role-grad1:      #0f172a;
            --role-grad2:      #1e3a8a;
        }
        /* ── Docente → violeta ── */
        body.role-docente {
            --primary:      #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light:#a78bfa;
            --role-color:   #7c3aed;
            --role-glow:    rgba(124,58,237,.35);
            --role-grad1:   #1e0a3c;
            --role-grad2:   #6d28d9;
        }
        /* ── Coordinador → índigo ── */
        body.role-coordinador {
            --primary:      #4f46e5;
            --primary-dark: #4338ca;
            --primary-light:#818cf8;
            --role-color:   #4f46e5;
            --role-glow:    rgba(79,70,229,.35);
            --role-grad1:   #1e1b4b;
            --role-grad2:   #4338ca;
        }

        /* ── Dark mode overrides ───────────────────────── */
        [data-theme="dark"] {
            --primary:       #3b82f6;
            --primary-dark:  #1e40af;
            --primary-light: #60a5fa;
        }
        [data-theme="dark"] body {
            background: #0f172a;
            color: #e2e8f0;
        }
        [data-theme="dark"] .topbar {
            background: linear-gradient(135deg, #020617 0%, #0f172a 100%);
            border-bottom: none;
        }
        [data-theme="dark"] .topbar-title { color: rgba(255,255,255,.85); }
        [data-theme="dark"] .topbar-user .dropdown-toggle { color: #fff; }
        [data-theme="dark"] .topbar-user .dropdown-toggle:hover { background: rgba(255,255,255,.14); }
        [data-theme="dark"] .main-content { background: #0f172a; }
        [data-theme="dark"] .sidebar { background: linear-gradient(180deg,#020617 0%,#0a0f1e 100%); box-shadow: 4px 0 32px rgba(0,0,0,.6); }
        [data-theme="dark"] .card,
        [data-theme="dark"] .card-panel,
        [data-theme="dark"] .import-card,
        [data-theme="dark"] .stat-card { background: #1e293b !important; border-color: #334155 !important; color: #e2e8f0; }
        [data-theme="dark"] .table { color: #e2e8f0; }
        [data-theme="dark"] .table thead th { background: #1e3a8a !important; }
        [data-theme="dark"] .table tbody tr:hover td { background: #1e293b; }
        [data-theme="dark"] .table td, [data-theme="dark"] .table th { border-color: #334155; }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus { background: #1e293b; }
        [data-theme="dark"] .form-control::placeholder { color: #60a5fa; }
        [data-theme="dark"] .dropdown-menu { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .dropdown-item { color: #cbd5e1; }
        [data-theme="dark"] .dropdown-item:hover { background: #334155; color: #f1f5f9; }
        [data-theme="dark"] .alert { border-color: #334155; }
        [data-theme="dark"] .modal-content { background: #1e293b; color: #e2e8f0; border-color: #334155; }
        [data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer { border-color: #334155; }
        [data-theme="dark"] .badge.bg-white { background: #334155 !important; }
        [data-theme="dark"] hr { border-color: #334155; }
        [data-theme="dark"] footer { border-color: #334155 !important; }
        [data-theme="dark"] .text-muted { color: #60a5fa !important; }
        [data-theme="dark"] .bg-white { background: #1e293b !important; }
        [data-theme="dark"] .schoolyear-badge { background: #1e3a8a; color: #93c5fd; border-color: #3b82f6; }

        /* ══════════════════════════════════════════════════
           DARK MODE — COBERTURA COMPLETA
        ══════════════════════════════════════════════════ */

        /* ── Tipografía ──────────────────────────────────── */
        [data-theme="dark"] h1,[data-theme="dark"] h2,
        [data-theme="dark"] h3,[data-theme="dark"] h4,
        [data-theme="dark"] h5,[data-theme="dark"] h6 { color: #e2e8f0; }
        [data-theme="dark"] p       { color: #cbd5e1; }
        [data-theme="dark"] small,
        [data-theme="dark"] .small  { color: #60a5fa; }
        [data-theme="dark"] strong  { color: #f1f5f9; }
        [data-theme="dark"] li      { color: #cbd5e1; }

        /* ── Links ───────────────────────────────────────── */
        [data-theme="dark"] a:not(.btn):not(.nav-link):not(.dropdown-item):not([class*="sidebar"]):not(.qlink) {
            color: #93c5fd;
        }
        [data-theme="dark"] a:not(.btn):not(.nav-link):not(.dropdown-item):not([class*="sidebar"]):not(.qlink):hover {
            color: #bfdbfe;
        }

        /* ── Card ────────────────────────────────────────── */
        [data-theme="dark"] .card-header {
            background: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #334155 !important;
        }
        [data-theme="dark"] .card-body  { color: #e2e8f0; }
        [data-theme="dark"] .card-footer {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #cbd5e1;
        }
        [data-theme="dark"] .card-title  { color: #f1f5f9 !important; }
        [data-theme="dark"] .card-subtitle { color: #60a5fa !important; }

        /* ── Forms ───────────────────────────────────────── */
        [data-theme="dark"] label,
        [data-theme="dark"] .form-label { color: #cbd5e1 !important; }
        [data-theme="dark"] .form-text  { color: #60a5fa !important; }
        [data-theme="dark"] .input-group-text {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #60a5fa !important;
        }
        [data-theme="dark"] .form-check-label { color: #cbd5e1 !important; }
        [data-theme="dark"] .form-hint  { color: #60a5fa !important; }
        [data-theme="dark"] fieldset legend { color: #e2e8f0; }

        /* ── Tablas ──────────────────────────────────────── */
        [data-theme="dark"] td,
        [data-theme="dark"] th { color: #e2e8f0; }
        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255,255,255,.04) !important;
            color: #e2e8f0;
        }
        [data-theme="dark"] .table-hover > tbody > tr:hover > * {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] option {
            background: #1e293b;
            color: #e2e8f0;
        }

        /* ── Breadcrumb ──────────────────────────────────── */
        [data-theme="dark"] .breadcrumb { background: transparent; }
        [data-theme="dark"] .breadcrumb-item a { color: #93c5fd !important; }
        [data-theme="dark"] .breadcrumb-item.active { color: #60a5fa !important; }
        [data-theme="dark"] .breadcrumb-item + .breadcrumb-item::before { color: #475569; }

        /* ── Botones outline ─────────────────────────────── */
        [data-theme="dark"] .btn-outline-secondary {
            color: #60a5fa; border-color: #475569;
        }
        [data-theme="dark"] .btn-outline-secondary:hover {
            background: #334155; color: #e2e8f0; border-color: #475569;
        }
        [data-theme="dark"] .btn-outline-primary {
            color: #93c5fd; border-color: #3b82f6;
        }
        [data-theme="dark"] .btn-outline-primary:hover {
            background: #1e40af; color: #fff; border-color: #3b82f6;
        }
        [data-theme="dark"] .btn-outline-danger  { color: #f87171; border-color: #ef4444; }
        [data-theme="dark"] .btn-outline-success { color: #4ade80; border-color: #22c55e; }
        [data-theme="dark"] .btn-outline-warning { color: #fbbf24; border-color: #f59e0b; }
        [data-theme="dark"] .btn-outline-info    { color: #67e8f9; border-color: #06b6d4; }
        [data-theme="dark"] .btn-light {
            background: #334155 !important; border-color: #475569 !important; color: #e2e8f0 !important;
        }
        [data-theme="dark"] .btn-light:hover {
            background: #475569 !important; color: #f1f5f9 !important;
        }
        [data-theme="dark"] .btn-close { filter: invert(1) grayscale(1) brightness(2); }

        /* ── Paginación ──────────────────────────────────── */
        [data-theme="dark"] .pagination .page-link {
            background: #1e293b; border-color: #334155; color: #93c5fd;
        }
        [data-theme="dark"] .pagination .page-link:hover { background: #334155; }
        [data-theme="dark"] .pagination .page-item.active .page-link {
            background: var(--primary); border-color: var(--primary); color: #fff;
        }
        [data-theme="dark"] .pagination .page-item.disabled .page-link {
            background: #0f172a; color: #475569; border-color: #334155;
        }

        /* ── Nav tabs ────────────────────────────────────── */
        [data-theme="dark"] .nav-tabs  { border-color: #334155; }
        [data-theme="dark"] .nav-tabs .nav-link { color: #60a5fa; border-color: transparent; }
        [data-theme="dark"] .nav-tabs .nav-link:hover { color: #e2e8f0; border-color: #334155; }
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: #1e293b; border-color: #334155 #334155 #1e293b; color: #f1f5f9;
        }
        [data-theme="dark"] .tab-content { color: #e2e8f0; }

        /* ── Badges ──────────────────────────────────────── */
        [data-theme="dark"] .badge.bg-light     { background: #334155 !important; color: #e2e8f0 !important; }
        [data-theme="dark"] .badge.bg-secondary { background: #475569 !important; color: #f1f5f9 !important; }
        [data-theme="dark"] .badge.bg-dark      { background: #1e293b !important; color: #e2e8f0 !important; }

        /* ── List group ──────────────────────────────────── */
        [data-theme="dark"] .list-group-item {
            background: #1e293b !important; border-color: #334155 !important; color: #e2e8f0;
        }
        [data-theme="dark"] .list-group-item:hover { background: #334155 !important; }

        /* ── Utilities Bootstrap ─────────────────────────── */
        [data-theme="dark"] .bg-light      { background: #1e293b !important; }
        [data-theme="dark"] .bg-secondary  { background: #334155 !important; }
        [data-theme="dark"] .text-dark     { color: #e2e8f0 !important; }
        [data-theme="dark"] .text-black    { color: #f1f5f9 !important; }
        [data-theme="dark"] .text-body     { color: #e2e8f0 !important; }
        [data-theme="dark"] .border        { border-color: #334155 !important; }
        [data-theme="dark"] .border-bottom { border-color: #334155 !important; }
        [data-theme="dark"] .border-top    { border-color: #334155 !important; }
        [data-theme="dark"] .border-end    { border-color: #334155 !important; }
        [data-theme="dark"] .border-start  { border-color: #334155 !important; }
        [data-theme="dark"] .border-light  { border-color: #334155 !important; }

        /* ── Scrollbar ───────────────────────────────────── */
        [data-theme="dark"] ::-webkit-scrollbar-track { background: #0f172a; }
        [data-theme="dark"] ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* ── Colores inline hardcodeados ─────────────────── */
        /* Cubre los style="color:#xxx" más comunes en las vistas */
        [data-theme="dark"] [style*="color:#1e293b"],
        [data-theme="dark"] [style*="color: #1e293b"] { color: #e2e8f0 !important; }
        [data-theme="dark"] [style*="color:#374151"],
        [data-theme="dark"] [style*="color: #374151"] { color: #cbd5e1 !important; }
        [data-theme="dark"] [style*="color:#4b5563"],
        [data-theme="dark"] [style*="color: #4b5563"] { color: #60a5fa !important; }
        [data-theme="dark"] [style*="color:#111827"],
        [data-theme="dark"] [style*="color: #111827"] { color: #f1f5f9 !important; }
        [data-theme="dark"] [style*="color:#1a1a2e"],
        [data-theme="dark"] [style*="color: #1a1a2e"] { color: #e2e8f0 !important; }
        [data-theme="dark"] [style*="background:#fff"],
        [data-theme="dark"] [style*="background: #fff"],
        [data-theme="dark"] [style*="background:#ffffff"],
        [data-theme="dark"] [style*="background-color:#fff"],
        [data-theme="dark"] [style*="background-color: #fff"] {
            background: #1e293b !important;
        }
        [data-theme="dark"] [style*="background:#f9fafb"],
        [data-theme="dark"] [style*="background:#f8faff"],
        [data-theme="dark"] [style*="background:#f0f4f8"] {
            background: #1e293b !important;
        }
        [data-theme="dark"] [style*="border-color:#e5e7eb"],
        [data-theme="dark"] [style*="border: 1px solid #e5e7eb"],
        [data-theme="dark"] [style*="border:1px solid #e5e7eb"] {
            border-color: #334155 !important;
        }

        /* ── Componentes del SGE ─────────────────────────── */
        [data-theme="dark"] .section-title      { color: #93c5fd !important; border-color: #3b82f6 !important; }
        [data-theme="dark"] .sidebar-card-title { color: #93c5fd !important; border-color: #3b82f6 !important; }
        [data-theme="dark"] .cfg-card {
            background: #1e293b !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .cfg-card-header {
            border-color: #334155 !important;
        }
        [data-theme="dark"] .cfg-card-header h6 { color: #e2e8f0 !important; }
        [data-theme="dark"] .cfg-card-header small { color: #60a5fa !important; }
        [data-theme="dark"] .toggle-row {
            background: #1e293b !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .toggle-row:hover {
            background: #273549 !important; border-color: #3b82f6 !important;
        }
        [data-theme="dark"] .toggle-row .toggle-info h6 { color: #e2e8f0 !important; }
        [data-theme="dark"] .config-card {
            background: #1e293b !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .logo-preview-wrap {
            background: #0f172a !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .preview-box {
            border-color: #334155 !important;
        }
        [data-theme="dark"] .preview-box .pv-body {
            background: #1e293b !important;
        }
        [data-theme="dark"] .preview-box .pv-inst { color: #93c5fd !important; }
        [data-theme="dark"] .preview-box .pv-sub  { color: #60a5fa !important; }
        [data-theme="dark"] .quick-link-btn,
        [data-theme="dark"] .qlink {
            background: #1e293b !important; border-color: #334155 !important; color: #93c5fd !important;
        }
        [data-theme="dark"] .quick-link-btn:hover,
        [data-theme="dark"] .qlink:hover {
            background: #273549 !important; border-color: #3b82f6 !important;
        }
        [data-theme="dark"] .note-text { color: #60a5fa !important; }
        [data-theme="dark"] .form-hint { color: #60a5fa !important; }
        [data-theme="dark"] .empty-state-enhanced .empty-title   { color: #e2e8f0 !important; }
        [data-theme="dark"] .empty-state-enhanced .empty-desc    { color: #60a5fa !important; }
        [data-theme="dark"] .empty-state-enhanced .empty-illustration {
            background: #1e293b !important; color: #475569 !important;
        }
        [data-theme="dark"] .boletin-mini {
            background: #1e293b !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .student-list-item {
            background: #1e293b !important; border-color: #334155 !important;
        }
        [data-theme="dark"] .student-list-item:hover {
            background: #273549 !important; border-color: #3b82f6 !important;
        }
        [data-theme="dark"] .grupo-card-boletin {
            border-color: #334155 !important; color: #e2e8f0 !important;
        }
        [data-theme="dark"] .grupo-card-boletin:hover {
            border-color: #3b82f6 !important; background: #1e293b !important;
        }
        [data-theme="dark"] .periodo-selector .periodo-btn {
            background: #1e293b !important; border-color: #334155 !important; color: #60a5fa !important;
        }
        [data-theme="dark"] .periodo-selector .periodo-btn:hover {
            border-color: #3b82f6 !important; color: #93c5fd !important;
        }
        [data-theme="dark"] .periodo-selector .periodo-btn.active {
            border-color: var(--primary) !important;
            background: var(--primary) !important;
            color: #fff !important;
        }
        [data-theme="dark"] .save-status--idle    { background: #1e293b; color: #60a5fa; }
        [data-theme="dark"] .save-status--saving  { background: #2d2008; color: #fbbf24; }
        [data-theme="dark"] .save-status--saved   { background: #052e16; color: #4ade80; }
        [data-theme="dark"] .save-status--error   { background: #2d0a0a; color: #f87171; }
        [data-theme="dark"] .skeleton {
            background: linear-gradient(90deg, #1e293b 25%, #273549 50%, #1e293b 75%) !important;
        }
        [data-theme="dark"] .skeleton-item { border-color: #334155 !important; }
        [data-theme="dark"] .table-hover > tbody > tr {
            transition: background .12s ease;
        }
        [data-theme="dark"] .table-hover > tbody > tr:hover {
            background-color: #334155 !important;
            box-shadow: inset 3px 0 0 var(--primary);
        }
        [data-theme="dark"] .alert-info {
            background: #0c1d3a !important;
            border-color: #1e40af !important;
            color: #93c5fd !important;
        }
        [data-theme="dark"] .alert-success {
            background: #052e16 !important;
            border-color: #15803d !important;
            color: #4ade80 !important;
        }
        [data-theme="dark"] .alert-danger {
            background: #2d0a0a !important;
            border-color: #991b1b !important;
            color: #f87171 !important;
        }
        [data-theme="dark"] .alert-warning {
            background: #2d1f04 !important;
            border-color: #92400e !important;
            color: #fbbf24 !important;
        }

        /* ── Global Search ──────────────────────────────── */
        .topbar-search { position: relative; flex: 1; max-width: 380px; }
        .topbar-search input {
            width: 100%; padding: .38rem .9rem .38rem 2.2rem;
            border: 1.5px solid rgba(255,255,255,.18); border-radius: 20px;
            font-size: .82rem; background: rgba(255,255,255,.1); color: #fff;
            outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .topbar-search input::placeholder { color: rgba(255,255,255,.45); }
        .topbar-search input:focus {
            border-color: rgba(255,255,255,.38);
            box-shadow: 0 0 0 3px rgba(255,255,255,.08);
            background: rgba(255,255,255,.16);
        }
        .topbar-search .gs-icon {
            position: absolute; left: .65rem; top: 50%;
            transform: translateY(-50%); color: rgba(255,255,255,.55); font-size: .85rem; pointer-events: none;
        }
        #gsDropdown {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            z-index: 1100; max-height: 400px; overflow-y: auto; display: none;
        }
        #gsDropdown.open { display: block; }
        .gs-group-header {
            font-size: .68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: #60a5fa;
            padding: .5rem .85rem .25rem; background: #f8faff; border-top: 1px solid #f1f5f9;
        }
        .gs-group-header:first-child { border-top: none; }
        .gs-item {
            display: flex; align-items: center; gap: .65rem;
            padding: .5rem .85rem; cursor: pointer; text-decoration: none;
            color: #1e293b; transition: background .12s;
        }
        .gs-item:hover, .gs-item.active { background: #f0f4ff; color: var(--primary); }
        .gs-item-icon {
            width: 28px; height: 28px; border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; color: #fff; flex-shrink: 0;
        }
        .gs-item-label { font-size: .82rem; font-weight: 600; }
        .gs-item-sub   { font-size: .73rem; color: #6b7280; }
        .gs-empty { padding: .85rem; text-align: center; font-size: .82rem; color: #60a5fa; }
        [data-theme="dark"] .topbar-search input { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.15); color: #fff; }
        [data-theme="dark"] .topbar-search input::placeholder { color: rgba(255,255,255,.35); }
        [data-theme="dark"] #gsDropdown { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .gs-group-header { background: #0f172a; color: #60a5fa; border-color: #334155; }
        [data-theme="dark"] .gs-item { color: #e2e8f0; }
        [data-theme="dark"] .gs-item:hover { background: #334155; }

        /* ══════════════════════════════════════════════════
           DARK MODE — Cobertura de estilos INLINE
           Technique: CSS attribute selector [style*="..."]
           Cubre 93+ vistas sin modificar ningún archivo.
        ══════════════════════════════════════════════════ */

        /* ── Fondos blancos puros inline ─────────────────── */
        [data-theme="dark"] [style*="background:#fff"],
        [data-theme="dark"] [style*="background: #fff"],
        [data-theme="dark"] [style*="background:#ffffff"],
        [data-theme="dark"] [style*="background: #ffffff"],
        [data-theme="dark"] [style*="background:white"],
        [data-theme="dark"] [style*="background: white"] {
            background: #1e293b !important;
        }

        /* ── Fondos muy claros / azul-blancos estructurales ─ */
        [data-theme="dark"] [style*="background:#f8f9fa"],
        [data-theme="dark"] [style*="background: #f8f9fa"],
        /* near-whites adicionales */
        [data-theme="dark"] [style*="background:#fafbff"],
        [data-theme="dark"] [style*="background: #fafbff"],
        [data-theme="dark"] [style*="background:#f0f4fb"],
        [data-theme="dark"] [style*="background: #f0f4fb"],
        [data-theme="dark"] [style*="background:#f0f9ff"],
        [data-theme="dark"] [style*="background: #f0f9ff"],
        [data-theme="dark"] [style*="background:#e0edff"],
        [data-theme="dark"] [style*="background: #e0edff"],
        /* rojos muy claros / near-whites rojizos */
        [data-theme="dark"] [style*="background:#fef2f2"],
        [data-theme="dark"] [style*="background: #fef2f2"],
        [data-theme="dark"] [style*="background:#ffe4e6"],
        [data-theme="dark"] [style*="background: #ffe4e6"],
        [data-theme="dark"] [style*="background:#fff1f2"],
        [data-theme="dark"] [style*="background: #fff1f2"],
        [data-theme="dark"] [style*="background:#fff5f5"],
        [data-theme="dark"] [style*="background: #fff5f5"],
        [data-theme="dark"] [style*="background:#fff8f8"],
        [data-theme="dark"] [style*="background: #fff8f8"],
        [data-theme="dark"] [style*="background:#f8faff"],
        [data-theme="dark"] [style*="background: #f8faff"],
        [data-theme="dark"] [style*="background:#f8fafc"],
        [data-theme="dark"] [style*="background: #f8fafc"],
        [data-theme="dark"] [style*="background:#f9fafb"],
        [data-theme="dark"] [style*="background: #f9fafb"],
        [data-theme="dark"] [style*="background:#f1f5f9"],
        [data-theme="dark"] [style*="background: #f1f5f9"],
        [data-theme="dark"] [style*="background:#f0f4f8"],
        [data-theme="dark"] [style*="background: #f0f4f8"],
        [data-theme="dark"] [style*="background:#fafafa"],
        [data-theme="dark"] [style*="background: #fafafa"],
        [data-theme="dark"] [style*="background:#e5e7eb"],
        [data-theme="dark"] [style*="background: #e5e7eb"],
        [data-theme="dark"] [style*="background:#e2e8f0"],
        [data-theme="dark"] [style*="background: #e2e8f0"],
        [data-theme="dark"] [style*="background:#eff6ff"],
        [data-theme="dark"] [style*="background: #eff6ff"],
        [data-theme="dark"] [style*="background:#eef3fb"],
        [data-theme="dark"] [style*="background: #eef3fb"],
        [data-theme="dark"] [style*="background:#f3f4f6"],
        [data-theme="dark"] [style*="background: #f3f4f6"],
        [data-theme="dark"] [style*="background:#f0fdf4"],
        [data-theme="dark"] [style*="background: #f0fdf4"],
        [data-theme="dark"] [style*="background:#fef9c3"],
        [data-theme="dark"] [style*="background: #fef9c3"],
        [data-theme="dark"] [style*="background:#fff7ed"],
        [data-theme="dark"] [style*="background: #fff7ed"],
        [data-theme="dark"] [style*="background:#fff0f0"],
        [data-theme="dark"] [style*="background: #fff0f0"],
        [data-theme="dark"] [style*="background:#fdf4ff"],
        [data-theme="dark"] [style*="background: #fdf4ff"],
        [data-theme="dark"] [style*="background:#f3e8ff"],
        [data-theme="dark"] [style*="background: #f3e8ff"],
        [data-theme="dark"] [style*="background:#e0e7ff"],
        [data-theme="dark"] [style*="background: #e0e7ff"] {
            background: #0f172a !important;
        }

        /* ── Texto oscuro inline (invisible sobre fondo oscuro) */
        [data-theme="dark"] [style*="color:#111827"],
        [data-theme="dark"] [style*="color: #111827"],
        [data-theme="dark"] [style*="color:#1e293b"],
        [data-theme="dark"] [style*="color: #1e293b"],
        [data-theme="dark"] [style*="color:#374151"],
        [data-theme="dark"] [style*="color: #374151"],
        [data-theme="dark"] [style*="color:#4b5563"],
        [data-theme="dark"] [style*="color: #4b5563"],
        [data-theme="dark"] [style*="color:#334155"],
        [data-theme="dark"] [style*="color: #334155"] {
            color: #cbd5e1 !important;
        }
        [data-theme="dark"] [style*="color:#6b7280"],
        [data-theme="dark"] [style*="color: #6b7280"],
        [data-theme="dark"] [style*="color:#64748b"],
        [data-theme="dark"] [style*="color: #64748b"],
        [data-theme="dark"] [style*="color:#9ca3af"],
        [data-theme="dark"] [style*="color: #9ca3af"],
        [data-theme="dark"] [style*="color:#94a3b8"],
        [data-theme="dark"] [style*="color: #94a3b8"],
        [data-theme="dark"] [style*="color:#d1d5db"],
        [data-theme="dark"] [style*="color: #d1d5db"] {
            color: #60a5fa !important;
        }

        /* ── Bordes claros/neutros inline ───────────────── */
        [data-theme="dark"] [style*="border-color:#e2e8f0"],
        [data-theme="dark"] [style*="border-color: #e2e8f0"],
        [data-theme="dark"] [style*="border-color:#e5e7eb"],
        [data-theme="dark"] [style*="border-color: #e5e7eb"],
        [data-theme="dark"] [style*="border-color:#f0f4f8"],
        [data-theme="dark"] [style*="border-color: #f0f4f8"],
        [data-theme="dark"] [style*="border:1px solid #e2e8f0"],
        [data-theme="dark"] [style*="border: 1px solid #e2e8f0"],
        [data-theme="dark"] [style*="border:1.5px solid #e2e8f0"],
        [data-theme="dark"] [style*="border:1px solid #e5e7eb"],
        [data-theme="dark"] [style*="border: 1px solid #e5e7eb"],
        [data-theme="dark"] [style*="border:1px solid #dde3ef"],
        [data-theme="dark"] [style*="border:1px solid #c7d6f0"],
        [data-theme="dark"] [style*="border:1px solid #cbd5e1"],
        [data-theme="dark"] [style*="border:1.5px dashed #d1d5db"],
        [data-theme="dark"] [style*="border-bottom:1px solid #e2e8f0"],
        [data-theme="dark"] [style*="border-bottom: 1px solid #e2e8f0"],
        [data-theme="dark"] [style*="border-bottom:1px solid #f1f5f9"],
        [data-theme="dark"] [style*="border-top:1px solid #e2e8f0"],
        [data-theme="dark"] [style*="border-top:1px solid #f1f5f9"] {
            border-color: #334155 !important;
        }

        /* ── Bordes de color (badges pastel) ────────────── */
        [data-theme="dark"] [style*="border:1px solid #bfdbfe"],
        [data-theme="dark"] [style*="border: 1px solid #bfdbfe"],
        [data-theme="dark"] [style*="border:1px solid #c7d2fe"],
        [data-theme="dark"] [style*="border:1px solid #93c5fd"],
        [data-theme="dark"] [style*="border:1px solid #bae6fd"] {
            border-color: rgba(59,130,246,.4) !important;
        }
        [data-theme="dark"] [style*="border:1px solid #a7f3d0"],
        [data-theme="dark"] [style*="border:1px solid #86efac"],
        [data-theme="dark"] [style*="border:1px solid #bbf7d0"] {
            border-color: rgba(16,185,129,.4) !important;
        }
        [data-theme="dark"] [style*="border:1px solid #fecaca"],
        [data-theme="dark"] [style*="border:1px solid #fecdd3"],
        [data-theme="dark"] [style*="border:2px solid #fee2e2"],
        [data-theme="dark"] [style*="border:2px solid #fca5a5"] {
            border-color: rgba(239,68,68,.4) !important;
        }
        [data-theme="dark"] [style*="border:1px solid #ddd6fe"],
        [data-theme="dark"] [style*="border:1px solid #e9d5ff"] {
            border-color: rgba(139,92,246,.4) !important;
        }
        [data-theme="dark"] [style*="border:1px solid #fcd34d"],
        [data-theme="dark"] [style*="border:1px solid #fde68a"],
        [data-theme="dark"] [style*="border:2px solid #f59e0b"] {
            border-color: rgba(245,158,11,.4) !important;
        }

        /* ── Hover de tablas (class-based) ──────────────── */
        [data-theme="dark"] .table-hover tbody tr:hover,
        [data-theme="dark"] .table-hover tbody tr:hover td,
        [data-theme="dark"] .table-hover tbody tr:hover th {
            background: #334155 !important;
            color: #f1f5f9 !important;
        }

        /* ── rgba blancos (overlay claro sobre fondo oscuro) */
        [data-theme="dark"] [style*="background:rgba(255,255,255,.9)"],
        [data-theme="dark"] [style*="background:rgba(255,255,255,0.9)"],
        [data-theme="dark"] [style*="background:rgba(255,255,255,.95)"],
        [data-theme="dark"] [style*="background:rgba(255,255,255,1)"] {
            background: rgba(30,41,59,.95) !important;
        }

        /* ── Cards con fondo #fff en card-header inline ──── */
        [data-theme="dark"] .card-header[style*="background:#fff"],
        [data-theme="dark"] .card-header[style*="background: #fff"],
        [data-theme="dark"] .card-body[style*="background:#fff"],
        [data-theme="dark"] .card-body[style*="background: #fff"] {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }

        /* ── Chips / badges pastel de colores (estado, tipo) ── */
        /* Verde claro → verde oscuro */
        [data-theme="dark"] [style*="background:#dcfce7"],
        [data-theme="dark"] [style*="background: #dcfce7"],
        [data-theme="dark"] [style*="background:#d1fae5"],
        [data-theme="dark"] [style*="background: #d1fae5"],
        [data-theme="dark"] [style*="background:#bbf7d0"],
        [data-theme="dark"] [style*="background: #bbf7d0"] {
            background: rgba(16,185,129,.18) !important;
        }
        [data-theme="dark"] [style*="background:#dcfce7"] [style*="color:#"],
        [data-theme="dark"] [style*="color:#15803d"],
        [data-theme="dark"] [style*="color: #15803d"],
        [data-theme="dark"] [style*="color:#166534"],
        [data-theme="dark"] [style*="color: #166534"] { color: #6ee7b7 !important; }

        /* Azul claro → azul oscuro */
        [data-theme="dark"] [style*="background:#dbeafe"],
        [data-theme="dark"] [style*="background: #dbeafe"],
        [data-theme="dark"] [style*="background:#bfdbfe"],
        [data-theme="dark"] [style*="background: #bfdbfe"] {
            background: rgba(59,130,246,.18) !important;
        }
        [data-theme="dark"] [style*="color:#1d4ed8"],
        [data-theme="dark"] [style*="color: #1d4ed8"],
        [data-theme="dark"] [style*="color:#1e40af"],
        [data-theme="dark"] [style*="color: #1e40af"],
        [data-theme="dark"] [style*="color:#2563eb"],
        [data-theme="dark"] [style*="color: #2563eb"] { color: #93c5fd !important; }

        /* Púrpura/violeta claro → oscuro */
        [data-theme="dark"] [style*="background:#ede9fe"],
        [data-theme="dark"] [style*="background: #ede9fe"],
        [data-theme="dark"] [style*="background:#ddd6fe"],
        [data-theme="dark"] [style*="background: #ddd6fe"] {
            background: rgba(139,92,246,.18) !important;
        }
        [data-theme="dark"] [style*="color:#5b21b6"],
        [data-theme="dark"] [style*="color: #5b21b6"],
        [data-theme="dark"] [style*="color:#6d28d9"],
        [data-theme="dark"] [style*="color: #6d28d9"],
        [data-theme="dark"] [style*="color:#7c3aed"],
        [data-theme="dark"] [style*="color: #7c3aed"] { color: #c4b5fd !important; }

        /* Ámbar/naranja claro → oscuro */
        [data-theme="dark"] [style*="background:#fffbeb"],
        [data-theme="dark"] [style*="background: #fffbeb"],
        [data-theme="dark"] [style*="background:#fef3c7"],
        [data-theme="dark"] [style*="background: #fef3c7"],
        [data-theme="dark"] [style*="background:#fed7aa"],
        [data-theme="dark"] [style*="background: #fed7aa"] {
            background: rgba(245,158,11,.18) !important;
        }
        [data-theme="dark"] [style*="color:#92400e"],
        [data-theme="dark"] [style*="color: #92400e"],
        [data-theme="dark"] [style*="color:#78350f"],
        [data-theme="dark"] [style*="color: #78350f"],
        [data-theme="dark"] [style*="color:#b45309"],
        [data-theme="dark"] [style*="color: #b45309"],
        [data-theme="dark"] [style*="color:#d97706"],
        [data-theme="dark"] [style*="color: #d97706"] { color: #fcd34d !important; }

        /* Rojo claro → oscuro */
        [data-theme="dark"] [style*="background:#fee2e2"],
        [data-theme="dark"] [style*="background: #fee2e2"],
        [data-theme="dark"] [style*="background:#fecaca"],
        [data-theme="dark"] [style*="background: #fecaca"] {
            background: rgba(239,68,68,.18) !important;
        }
        [data-theme="dark"] [style*="color:#b91c1c"],
        [data-theme="dark"] [style*="color: #b91c1c"],
        [data-theme="dark"] [style*="color:#dc2626"],
        [data-theme="dark"] [style*="color: #dc2626"],
        [data-theme="dark"] [style*="color:#ef4444"],
        [data-theme="dark"] [style*="color: #ef4444"] { color: #fca5a5 !important; }

        /* Rosa/pink claro → oscuro */
        [data-theme="dark"] [style*="background:#fce7f3"],
        [data-theme="dark"] [style*="background: #fce7f3"],
        [data-theme="dark"] [style*="background:#fbcfe8"],
        [data-theme="dark"] [style*="background: #fbcfe8"] {
            background: rgba(236,72,153,.18) !important;
        }
        [data-theme="dark"] [style*="color:#be185d"],
        [data-theme="dark"] [style*="color: #be185d"],
        [data-theme="dark"] [style*="color:#9d174d"],
        [data-theme="dark"] [style*="color: #9d174d"] { color: #f9a8d4 !important; }

        /* Púrpura/indigo adicional */
        [data-theme="dark"] [style*="background:#c7d2fe"],
        [data-theme="dark"] [style*="background: #c7d2fe"] {
            background: rgba(139,92,246,.18) !important;
        }

        /* Cyan/teal claro → oscuro */
        [data-theme="dark"] [style*="background:#cffafe"],
        [data-theme="dark"] [style*="background: #cffafe"],
        [data-theme="dark"] [style*="background:#e0f2fe"],
        [data-theme="dark"] [style*="background: #e0f2fe"],
        [data-theme="dark"] [style*="background:#ccfbf1"],
        [data-theme="dark"] [style*="background: #ccfbf1"] {
            background: rgba(6,182,212,.18) !important;
        }
        [data-theme="dark"] [style*="color:#0e7490"],
        [data-theme="dark"] [style*="color: #0e7490"],
        [data-theme="dark"] [style*="color:#0891b2"],
        [data-theme="dark"] [style*="color: #0891b2"],
        [data-theme="dark"] [style*="color:#0f766e"],
        [data-theme="dark"] [style*="color: #0f766e"] { color: #67e8f9 !important; }

        /* ── Texto oscuro adicional no cubierto arriba ─────── */
        /* Rojos oscuros */
        [data-theme="dark"] [style*="color:#991b1b"],
        [data-theme="dark"] [style*="color: #991b1b"],
        [data-theme="dark"] [style*="color:#c0392b"],
        [data-theme="dark"] [style*="color: #c0392b"],
        [data-theme="dark"] [style*="color:#7f1d1d"],
        [data-theme="dark"] [style*="color: #7f1d1d"] { color: #fca5a5 !important; }

        /* Azules muy oscuros */
        [data-theme="dark"] [style*="color:#1e3a6e"],
        [data-theme="dark"] [style*="color: #1e3a6e"],
        [data-theme="dark"] [style*="color:#1e3a8a"],
        [data-theme="dark"] [style*="color: #1e3a8a"],
        [data-theme="dark"] [style*="color:#1e40af"],
        [data-theme="dark"] [style*="color: #1e40af"] { color: #93c5fd !important; }

        /* Verdes muy oscuros */
        [data-theme="dark"] [style*="color:#065f46"],
        [data-theme="dark"] [style*="color: #065f46"],
        [data-theme="dark"] [style*="color:#14532d"],
        [data-theme="dark"] [style*="color: #14532d"],
        [data-theme="dark"] [style*="color:#16a34a"],
        [data-theme="dark"] [style*="color: #16a34a"] { color: #6ee7b7 !important; }

        /* Púrpuras oscuros */
        [data-theme="dark"] [style*="color:#6b21a8"],
        [data-theme="dark"] [style*="color: #6b21a8"],
        [data-theme="dark"] [style*="color:#4c1d95"],
        [data-theme="dark"] [style*="color: #4c1d95"],
        [data-theme="dark"] [style*="color:#3730a3"],
        [data-theme="dark"] [style*="color: #3730a3"],
        [data-theme="dark"] [style*="color:#6366f1"],
        [data-theme="dark"] [style*="color: #6366f1"] { color: #c4b5fd !important; }

        /* Ámbar/marrón oscuro */
        [data-theme="dark"] [style*="color:#854d0e"],
        [data-theme="dark"] [style*="color: #854d0e"],
        [data-theme="dark"] [style*="color:#713f12"],
        [data-theme="dark"] [style*="color: #713f12"],
        [data-theme="dark"] [style*="color:#f59e0b"],
        [data-theme="dark"] [style*="color: #f59e0b"] { color: #fcd34d !important; }

        /* Verdes y rojos brillantes (íconos/indicadores de estado) */
        [data-theme="dark"] [style*="color:#10b981"],
        [data-theme="dark"] [style*="color: #10b981"],
        [data-theme="dark"] [style*="color:#22c55e"],
        [data-theme="dark"] [style*="color: #22c55e"] { color: #6ee7b7 !important; }
        [data-theme="dark"] [style*="color:#ef4444"],
        [data-theme="dark"] [style*="color: #ef4444"],
        [data-theme="dark"] [style*="color:#dc2626"],
        [data-theme="dark"] [style*="color: #dc2626"] { color: #fca5a5 !important; }

        /* ── Filas de tabla con fondo claro inline ────────── */
        [data-theme="dark"] tr[style*="background:#fff"],
        [data-theme="dark"] tr[style*="background: #fff"],
        [data-theme="dark"] tr[style*="background:#f8faff"],
        [data-theme="dark"] tr[style*="background: #f8faff"],
        [data-theme="dark"] thead[style*="background:#f8faff"],
        [data-theme="dark"] thead[style*="background: #f8faff"] {
            background: #1e3a8a !important;
        }
        [data-theme="dark"] tr[style*="background:#fff"] td,
        [data-theme="dark"] tr[style*="background: #fff"] td,
        [data-theme="dark"] tr[style*="background:#f8faff"] td,
        [data-theme="dark"] thead[style*="background:#f8faff"] th {
            background: inherit !important;
            color: #e2e8f0 !important;
        }

        /* ── Nombres de estudiantes — azul distintivo ─────── */
        /* Visible sobre fondo blanco (modo claro) Y fondo oscuro (modo oscuro) */
        .est-nombre-tabla,
        .nombre-estudiante,
        .est-nombre,
        .col-nombre-text {
            color: #1d4ed8;
            font-weight: 700;
        }
        [data-theme="dark"] .est-nombre-tabla,
        [data-theme="dark"] .nombre-estudiante,
        [data-theme="dark"] .est-nombre,
        [data-theme="dark"] .col-nombre-text { color: #93c5fd !important; }

        .num-orden,
        .est-num,
        .num-tabla {
            color: #2563eb;
            font-weight: 700;
        }
        [data-theme="dark"] .num-orden,
        [data-theme="dark"] .est-num,
        [data-theme="dark"] .num-tabla { color: #93c5fd !important; }

        /* ── Dark mode toggle button ─────────────────────── */
        .dark-toggle {
            background: transparent; border: none;
            color: rgba(255,255,255,.7); font-size: 1.05rem;
            padding: .3rem .4rem; border-radius: 8px;
            cursor: pointer; transition: color .18s, background .18s;
            line-height: 1;
        }
        .dark-toggle:hover { color: #fff; background: rgba(255,255,255,.12); }
        [data-theme="dark"] .dark-toggle { color: #fcd34d; }
        [data-theme="dark"] .dark-toggle:hover { background: rgba(255,255,255,.08); }

        /* ── Bell / alertas en topbar oscuro ─────────────── */
        #adminBell { color: rgba(255,255,255,.7) !important; border-radius: 8px; transition: color .15s, background .15s; }
        #adminBell:hover { color: #fff !important; background: rgba(255,255,255,.12) !important; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ════════════════════════════════════════════════
           SIDEBAR — Diseño oscuro moderno
        ════════════════════════════════════════════════ */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg,#0f172a 0%,#111827 100%);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            overflow-x: hidden;
            overflow-y: hidden;
            border-right: 1px solid rgba(255,255,255,.06);
            box-shadow: 4px 0 32px rgba(0,0,0,.35);
        }

        /* ── Logo Area ────────────────────────────────── */
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1.15rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            text-decoration: none;
            flex-shrink: 0;
            background: rgba(255,255,255,.02);
        }

        .logo-badge {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--role-color) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .92rem;
            color: #fff;
            letter-spacing: .02em;
            flex-shrink: 0;
            box-shadow: 0 4px 18px var(--role-glow), 0 0 0 1px rgba(255,255,255,.1);
        }

        .logo-text .system-name {
            font-size: 1rem;
            font-weight: 800;
            color: #f1f5f9;
            line-height: 1.1;
            letter-spacing: .02em;
        }
        .logo-text .system-sub {
            font-size: .65rem;
            color: #64748b;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        /* ── Navigation ───────────────────────────────── */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: .5rem 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.08) transparent;
        }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

        /* ── Sidebar submenus (custom, no Bootstrap Collapse) ─── */
        .sidebar-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height .35s ease;
        }
        .sidebar-submenu.sidebar-submenu-open {
            max-height: 600px;
        }

        .nav-section-title {
            font-size: .58rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #475569;
            padding: .9rem 1.1rem .2rem;
            margin-top: .1rem;
        }

        .nav-item a,
        .nav-item button.nav-link-btn {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .48rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 500;
            border-radius: 0;
            transition: all .17s ease;
            width: 100%;
            background: transparent;
            border: none;
            cursor: pointer;
            text-align: left;
            margin: 1px .6rem;
            width: calc(100% - 1.2rem);
            border-radius: 8px;
        }

        .nav-item a:hover,
        .nav-item button.nav-link-btn:hover {
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
        }

        .nav-item a.active,
        .nav-item a[aria-current="page"] {
            background: var(--role-color);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 4px 14px var(--role-glow), inset 0 1px 0 rgba(255,255,255,.15);
        }

        .nav-item a i,
        .nav-item button.nav-link-btn i {
            font-size: 1rem;
            flex-shrink: 0;
            width: 18px;
            text-align: center;
            opacity: .75;
        }
        .nav-item a.active i,
        .nav-item a:hover i { opacity: 1; }

        /* ── User Footer ──────────────────────────────── */
        .sidebar-user {
            border-top: 1px solid rgba(255,255,255,.07);
            padding: .85rem 1rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-shrink: 0;
            background: rgba(0,0,0,.2);
        }

        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--role-color), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 2px 8px var(--role-glow);
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name {
            font-size: .78rem;
            font-weight: 700;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role {
            font-size: .65rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-logout {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #64748b;
            border-radius: 8px;
            padding: .3rem .5rem;
            font-size: .85rem;
            flex-shrink: 0;
            transition: all .18s;
            line-height: 1;
        }
        .btn-logout:hover {
            background: rgba(239,68,68,.2);
            border-color: rgba(239,68,68,.3);
            color: #fca5a5;
        }

        /* ════════════════════════════════════════════════
           TOPBAR — Gradiente oscuro (igual al portal)
        ════════════════════════════════════════════════ */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: linear-gradient(135deg, var(--role-grad1) 0%, var(--role-grad2) 100%);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            z-index: 1030;
            box-shadow: 0 2px 16px rgba(0,0,0,.28);
            gap: 1rem;
            transition: left .3s cubic-bezier(.4,0,.2,1);
        }

        .topbar-hamburger {
            display: none;
            background: transparent;
            border: none;
            color: rgba(255,255,255,.7);
            font-size: 1.3rem;
            padding: .25rem;
            line-height: 1;
            cursor: pointer;
            border-radius: 6px;
            transition: color .18s, background .18s;
        }
        .topbar-hamburger:hover { color: #fff; background: rgba(255,255,255,.12); }

        .topbar-title {
            font-size: .95rem;
            font-weight: 600;
            color: rgba(255,255,255,.8);
            flex: 1;
            letter-spacing: .01em;
        }

        .schoolyear-badge {
            background: rgba(255,255,255,.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 20px;
            padding: .28rem .85rem;
            font-size: .74rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .topbar-user .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: .55rem;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            font-size: .83rem;
            font-weight: 600;
            padding: .3rem .75rem .3rem .4rem;
            border-radius: 20px;
            transition: background .18s;
        }
        .topbar-user .dropdown-toggle:hover { background: rgba(255,255,255,.22); color: #fff; }
        .topbar-user .dropdown-toggle::after { display: none; }

        .topbar-avatar {
            width: 34px; height: 34px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 800;
            color: #fff;
        }

        /* ════════════════════════════════════════════════
           MAIN CONTENT
        ════════════════════════════════════════════════ */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 1.75rem;
            min-height: calc(100vh - var(--topbar-height));
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
            animation: mainFadeIn .22s ease;
        }
        @keyframes mainFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ════════════════════════════════════════════════
           OVERLAY (mobile)
        ════════════════════════════════════════════════ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 1039;
        }

        /* ════════════════════════════════════════════════
           MOBILE
        ════════════════════════════════════════════════ */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-overlay.open {
                display: block;
            }
            .topbar {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .topbar-hamburger {
                display: flex;
                align-items: center;
            }
        }

        /* ── Misc helpers ─────────────────────────────── */
        .dropdown-item { font-size: .84rem; }
        .dropdown-item i { width: 18px; }
        /* Ensure topbar dropdown appears above sidebar (z-index:1040) */
        .topbar-user .dropdown-menu { z-index: 1050 !important; }

        /* ── Responsive: contenido en móvil ──────────── */
        @media (max-width: 767.98px) {
            .main-content { padding: 1rem; }
            .topbar { padding: 0 .75rem; gap: .5rem; }
            .topbar-search { max-width: 160px; }
            .topbar-search input { font-size: .78rem; padding: .32rem .7rem .32rem 1.9rem; }
            .schoolyear-badge { display: none !important; }
            /* Tablas: scroll horizontal garantizado */
            .table-responsive { -webkit-overflow-scrolling: touch; }
            /* Botones de acción apilados */
            .btn-group-responsive { flex-direction: column !important; }
            .btn-group-responsive .btn { width: 100%; border-radius: .375rem !important; margin-bottom: .25rem; }
            /* Cards con menos padding */
            .card-panel, .card { padding: 1rem !important; }
            /* Filtros en columna */
            .filter-row { flex-direction: column !important; }
            .filter-row > * { width: 100% !important; max-width: 100% !important; min-width: 0 !important; }
        }
        @media (max-width: 575.98px) {
            .topbar-search { max-width: 120px; }
            .topbar-search input::placeholder { font-size: 0; }
        }

        /* ── Skip link (TAREA 1.2) ──────────────────── */
        .skip-link {
            position: fixed;
            top: -100%;
            left: 1rem;
            z-index: 99999;
            background: var(--primary);
            color: #fff;
            padding: .5rem 1.25rem;
            border-radius: 0 0 8px 8px;
            font-size: .875rem;
            font-weight: 600;
            transition: top .15s;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
        }
        .skip-link:focus {
            top: 0;
            outline: 3px solid var(--accent, #f59e0b);
            outline-offset: 2px;
            color: #fff;
        }

        /* Accessibility: focus-visible mejorado (TAREA 1.4) */
        :focus-visible {
            outline: 2px solid var(--accent, #f59e0b) !important;
            outline-offset: 2px;
            border-radius: 4px;
        }
        .btn:focus-visible,
        .btn-action:focus-visible {
            outline: 2px solid var(--primary, #1e3a6e) !important;
            outline-offset: 2px;
            z-index: 1;
            position: relative;
        }

        /* ── Animations (TAREA 1.5) ─────────────────────────────── */
        .panel-fade-in {
            animation: panelFadeIn .25s ease-out both;
        }
        @keyframes panelFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .list-item-enter {
            animation: listItemEnter .2s ease-out both;
        }
        @keyframes listItemEnter {
            from { opacity: 0; transform: translateX(-6px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: skeletonShimmer 1.5s infinite;
            border-radius: 6px;
            display: block;
        }
        @keyframes skeletonShimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-line        { height: 13px; margin-bottom: 8px; }
        .skeleton-line.short  { width: 55%; }
        .skeleton-line.long   { width: 88%; }
        .skeleton-avatar      { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; }
        .skeleton-item {
            display: flex; align-items: center; gap: .85rem;
            padding: .75rem 1rem; border: 1px solid #e5e7eb;
            border-radius: 8px; margin-bottom: .5rem;
        }

        /* Success flash en fila */
        .success-flash {
            animation: successFlash .7s ease-out;
        }
        @keyframes successFlash {
            0%   { background: rgba(34,197,94,.18); }
            60%  { background: rgba(34,197,94,.08); }
            100% { background: transparent; }
        }

        /* Ripple en botón guardar */
        .btn-save-ripple { position: relative; overflow: hidden; }
        .btn-save-ripple::after {
            content: '';
            position: absolute; inset: 0;
            background: rgba(255,255,255,.28);
            border-radius: inherit;
            transform: scale(0); opacity: 1;
            transition: transform .4s ease, opacity .4s ease;
            pointer-events: none;
        }
        .btn-save-ripple.ripple-active::after {
            transform: scale(2.2); opacity: 0;
        }

        /* Hover mejorado en tablas */
        .table-hover > tbody > tr {
            transition: background .12s ease, box-shadow .12s ease;
        }
        .table-hover > tbody > tr:hover {
            background-color: #eff6ff !important;
            box-shadow: inset 3px 0 0 var(--primary, #2563eb);
        }

        /* Animación de publicar */
        .btn-published {
            animation: publishPulse .45s ease-out;
        }
        @keyframes publishPulse {
            0%   { transform: scale(1); }
            40%  { transform: scale(1.06); }
            100% { transform: scale(1); }
        }

        /* Toast container global */
        #sge-toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            max-width: 340px;
            pointer-events: none;
        }
        #sge-toast-container > * { pointer-events: all; }
        @keyframes toastEnter {
            from { opacity: 0; transform: translateX(40px) scale(.9); }
            to   { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes toastExit {
            from { opacity: 1; transform: translateX(0); max-height: 100px; }
            to   { opacity: 0; transform: translateX(40px); max-height: 0; }
        }

        /* Save status indicator */
        .save-status {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .76rem; font-weight: 600;
            padding: .25rem .75rem; border-radius: 99px;
            transition: background .3s, color .3s;
            white-space: nowrap;
        }
        .save-status--idle    { background: #f3f4f6; color: #6b7280; }
        .save-status--saving  { background: #fef3c7; color: #92400e; }
        .save-status--saved   { background: #dcfce7; color: #15803d; }
        .save-status--error   { background: #fee2e2; color: #991b1b; }
        .save-status--saving i { animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Unsaved indicator */
        .unsaved-indicator {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .72rem; font-weight: 700;
            padding: .2rem .65rem; border-radius: 99px;
            background: #fef3c7; color: #92400e;
            animation: pulseWarning 1.8s ease-in-out infinite;
        }
        @keyframes pulseWarning {
            0%, 100% { opacity: 1; }
            50%       { opacity: .55; }
        }

        /* Attendance badges */
        .attendance-done-badge {
            font-size: .72rem; font-weight: 700;
            padding: .22em .7em; border-radius: 20px;
            background: #dcfce7; color: #15803d;
            display: inline-flex; align-items: center; gap: .3rem;
            white-space: nowrap;
        }
        .attendance-pending-badge {
            font-size: .72rem; font-weight: 700;
            padding: .22em .7em; border-radius: 20px;
            background: #fef3c7; color: #92400e;
            display: inline-flex; align-items: center; gap: .3rem;
            white-space: nowrap;
            animation: pulseWarning 2s ease-in-out infinite;
        }

        /* NI input validation */
        .ni-invalid {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 2px rgba(220,38,38,.2) !important;
            animation: shakeInput .3s ease-out;
        }
        @keyframes shakeInput {
            0%, 100% { transform: translateX(0); }
            25%       { transform: translateX(-3px); }
            75%       { transform: translateX(3px); }
        }

        /* Empty state enhanced */
        .empty-state-enhanced {
            text-align: center; padding: 3.5rem 2rem; color: #6b7280;
        }
        .empty-state-enhanced .empty-illustration {
            width: 76px; height: 76px; background: #f0f4f8; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin: 0 auto 1.25rem; color: #60a5fa;
        }
        .empty-state-enhanced .empty-title {
            font-size: 1rem; font-weight: 700; color: #374151; margin-bottom: .4rem;
        }
        .empty-state-enhanced .empty-desc {
            font-size: .83rem; max-width: 360px; margin: 0 auto 1.25rem; line-height: 1.6;
        }
        .empty-state-enhanced .empty-actions {
            display: flex; gap: .5rem; justify-content: center; flex-wrap: wrap;
        }
    </style>
</head>
@php
$bodyRoleClass = '';
if(auth()->check()) {
    $r = auth()->user();
    if($r->hasRole('Docente'))      $bodyRoleClass = 'role-docente';
    elseif($r->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo'])) $bodyRoleClass = 'role-coordinador';
}
@endphp
<body class="{{ $bodyRoleClass }}">

    <div id="nprogress-bar"></div>

    {{-- ── Banner Modo Demo ──────────────────────────────────────────── --}}
    @if(session('demo_mode'))
    <div id="demo-banner" style="background:linear-gradient(90deg,#92400e,#b45309);color:#fff;text-align:center;padding:.55rem 1rem;font-size:.8rem;font-weight:600;position:sticky;top:0;z-index:9999;display:flex;align-items:center;justify-content:center;gap:.75rem;flex-wrap:wrap;box-shadow:0 2px 8px rgba(0,0,0,.25);">
        <span style="display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-shield-exclamation" style="font-size:.95rem;"></i>
            <strong>MODO DEMO</strong> — Estás explorando el sistema con datos de ejemplo. Los cambios críticos están bloqueados.
        </span>
        @if($errors->has('demo_mode'))
        <span style="background:rgba(0,0,0,.25);border-radius:6px;padding:.2rem .6rem;font-size:.75rem;">
            🔒 {{ $errors->first('demo_mode') }}
        </span>
        @endif
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);color:#fff;border-radius:6px;padding:.25rem .75rem;font-size:.73rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-box-arrow-right me-1"></i>Salir del demo
            </button>
        </form>
    </div>
    @endif

    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    <!-- ════════════════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menú principal">

        <!-- Logo -->
        <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
            @if(!empty($systemSettings['system_logo']))
                <img src="{{ Storage::url($systemSettings['system_logo']) }}"
                     alt="{{ $systemSettings['system_abbr'] }}"
                     style="width:38px;height:38px;border-radius:8px;object-fit:contain;background:#fff;padding:2px;">
            @else
                <div class="logo-badge">{{ $systemSettings['system_abbr'] ?? 'PSAC' }}</div>
            @endif
            <div class="logo-text">
                <div class="system-name">{{ $systemSettings['system_name'] ?? 'PSAC' }}</div>
                <div class="system-sub">{{ $systemSettings['system_sub'] ?? 'Gestión Escolar' }}</div>
            </div>
        </a>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            @php
                $u = Auth::user();
                $isAdmin        = $u->hasRole('Administrador');
                $isDir          = $u->hasRole('Director');
                $isCoord        = $u->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']);
                $isDocente      = $u->hasRole('Docente');
                $isSecre        = $u->hasAnyRole(['Secretaría','Secretaria Docente','Secretaria']);
                $isPersonalAdm  = $u->hasRole('Personal Administrativo');
                $canSupervisar  = $isAdmin || $isDir || $isPersonalAdm;
                $canConfig      = $isAdmin;
                $canAcad        = $isAdmin || $isDir || $isCoord || $isSecre || $isPersonalAdm;
                $canCalif       = $isAdmin || $isDir || $isCoord || $isDocente;
                $docenteArea    = null;
                if ($isDocente) {
                    try {
                        $docenteArea = \Illuminate\Support\Facades\Cache::remember(
                            'docente_area_' . $u->id, 300,
                            fn() => \App\Models\Docente::where('user_id', $u->id)->value('area')
                        );
                    } catch (\Exception $e) {}
                }
                $showSegTecnica = $isAdmin || $isDir || $isCoord || $isSecre || $isPersonalAdm
                    || ($isDocente && in_array($docenteArea, ['tecnica','ambas']));
            @endphp

            {{-- Dashboard --}}
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.kpis.index') }}" class="{{ request()->routeIs('admin.kpis*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>KPIs Director
                    </a>
                </li>
                @endif
            </ul>

            {{-- ══ GESTIÓN ACADÉMICA ══ --}}
            @if($canAcad || $isDocente)
            <div class="nav-section-title">Gestión Académica</div>
            <ul class="list-unstyled mb-0">
                @if($canAcad)
                <li class="nav-item">
                    <a href="{{ route('admin.estudiantes.index') }}" class="{{ request()->routeIs('admin.estudiantes.index') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i>Estudiantes
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.matriculas.index') }}" class="{{ request()->routeIs('admin.matriculas*') ? 'active' : '' }}">
                        <i class="bi bi-card-list"></i>Matrículas
                    </a>
                </li>
                @endif
                @if($canCalif || $isDocente)
                <li class="nav-item">
                    <a href="{{ route('admin.asistencia.index') }}" class="{{ request()->routeIs('admin.asistencia*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>Asistencia
                    </a>
                </li>
                @if(!$isDocente)
                @php $horarioActive = request()->routeIs('admin.horarios*'); @endphp
                <li class="nav-item">
                    <button class="nav-link-btn w-100 text-start d-flex align-items-center justify-content-between"
                            type="button" data-sidebar-toggle="subHorarios"
                            aria-expanded="{{ $horarioActive ? 'true' : 'false' }}">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-calendar-week"></i>Horarios</span>
                        <i class="bi bi-chevron-down" style="font-size:.65rem;transition:transform .2s;{{ $horarioActive ? 'transform:rotate(180deg)' : '' }}"></i>
                    </button>
                    <div class="sidebar-submenu {{ $horarioActive ? 'sidebar-submenu-open' : '' }}" id="subHorarios">
                        <ul class="list-unstyled ps-3 mb-0" style="border-left:2px solid rgba(255,255,255,.12);margin-left:1.25rem;margin-top:.25rem;">
                            <li><a href="{{ route('admin.horarios.index') }}" class="{{ request()->routeIs('admin.horarios.index') || request()->routeIs('admin.horarios.show') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-calendar3"></i>Horarios</a></li>
                            <li><a href="{{ route('admin.horarios.vista-maestra') }}" class="{{ request()->routeIs('admin.horarios.vista-maestra') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-grid-3x3-gap-fill"></i>Vista Maestra</a></li>
                            <li><a href="{{ route('admin.horarios.suplencias') }}" class="{{ request()->routeIs('admin.horarios.suplencias*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-person-fill-exclamation"></i>Suplencias</a></li>
                            <li><a href="{{ route('admin.horarios.disponibilidad') }}" class="{{ request()->routeIs('admin.horarios.disponibilidad') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-person-check"></i>Disponibilidad</a></li>
                        </ul>
                    </div>
                </li>
                @else
                <li class="nav-item">
                    <a href="{{ route('admin.horarios.mi-horario') }}" class="{{ request()->routeIs('admin.horarios.mi-horario') ? 'active' : '' }}">
                        <i class="bi bi-calendar-week-fill"></i>Mi Horario
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.index') }}" class="{{ request()->routeIs('admin.calificaciones.index') || request()->routeIs('admin.calificaciones.planilla*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check"></i>Registro de Notas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.index') }}" class="{{ request()->routeIs('admin.boletines.index') || request()->routeIs('admin.boletines.ver') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>Boletines
                    </a>
                </li>
                @endif
                @if($showSegTecnica)
                <li class="nav-item">
                    <a href="{{ route('admin.planificacion.index') }}" class="{{ request()->routeIs('admin.planificacion*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>Planificaciones Técnicas
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ GESTIÓN INSTITUCIONAL ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Gestión Institucional</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.docentes.index') }}" class="{{ request()->routeIs('admin.docentes*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i>Docentes
                    </a>
                </li>
                @if($canAcad)
                <li class="nav-item">
                    <a href="{{ route('admin.grupos.index') }}" class="{{ request()->routeIs('admin.grupos*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap"></i>Grupos / Cursos
                    </a>
                </li>
                @endif
                @if($isAdmin || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.indicadores.index') }}" class="{{ request()->routeIs('admin.indicadores*') ? 'active' : '' }}">
                        <i class="bi bi-check2-square"></i>Indicadores de Logro
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.resumen') }}" class="{{ request()->routeIs('admin.calificaciones.resumen') ? 'active' : '' }}">
                        <i class="bi bi-table"></i>Resumen de Notas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.calificaciones.ranking') }}" class="{{ request()->routeIs('admin.calificaciones.ranking') ? 'active' : '' }}">
                        <i class="bi bi-trophy"></i>Ranking Académico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.observaciones.index') }}" class="{{ request()->routeIs('admin.observaciones*') ? 'active' : '' }}">
                        <i class="bi bi-chat-square-text"></i>Observaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.disciplina.index') }}" class="{{ request()->routeIs('admin.disciplina*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation"></i>Disciplina
                    </a>
                </li>
                @php $otrasActive = request()->routeIs('admin.reconocimientos*') || request()->routeIs('admin.gamificacion*') || request()->routeIs('admin.proyectos*') || request()->routeIs('admin.salud*') || request()->routeIs('admin.tutorias*') || request()->routeIs('admin.seguimiento-social*') || request()->routeIs('admin.reuniones*') || request()->routeIs('admin.evaluaciones-docentes*'); @endphp
                <li class="nav-item">
                    <button class="nav-link-btn w-100 text-start d-flex align-items-center justify-content-between"
                            type="button" data-sidebar-toggle="subOtrasFunciones"
                            aria-expanded="{{ $otrasActive ? 'true' : 'false' }}">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-three-dots"></i>Más funciones</span>
                        <i class="bi bi-chevron-down" style="font-size:.65rem;transition:transform .2s;{{ $otrasActive ? 'transform:rotate(180deg)' : '' }}"></i>
                    </button>
                    <div class="sidebar-submenu {{ $otrasActive ? 'sidebar-submenu-open' : '' }}" id="subOtrasFunciones">
                        <ul class="list-unstyled ps-3 mb-0" style="border-left:2px solid rgba(255,255,255,.12);margin-left:1.25rem;margin-top:.25rem;">
                            <li><a href="{{ route('admin.tutorias.index') }}" class="{{ request()->routeIs('admin.tutorias*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-person-hearts"></i>Tutorías</a></li>
                            <li><a href="{{ route('admin.seguimiento-social.index') }}" class="{{ request()->routeIs('admin.seguimiento-social*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-people"></i>Seguimiento Social</a></li>
                            <li><a href="{{ route('admin.salud.incidentes') }}" class="{{ request()->routeIs('admin.salud*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-heart-pulse"></i>Salud Escolar</a></li>
                            <li><a href="{{ route('admin.evaluaciones-docentes.index') }}" class="{{ request()->routeIs('admin.evaluaciones-docentes*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-clipboard2-check"></i>Eval. Docentes</a></li>
                            <li><a href="{{ route('admin.reuniones.index') }}" class="{{ request()->routeIs('admin.reuniones*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-journal-text"></i>Actas Reuniones</a></li>
                            <li><a href="{{ route('admin.proyectos.index') }}" class="{{ request()->routeIs('admin.proyectos*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-lightbulb"></i>Proyectos</a></li>
                            <li><a href="{{ route('admin.reconocimientos.index') }}" class="{{ request()->routeIs('admin.reconocimientos*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-trophy"></i>Reconocimientos</a></li>
                            <li><a href="{{ route('admin.gamificacion.index') }}" class="{{ request()->routeIs('admin.gamificacion*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-controller"></i>Gamificación</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
            @endif

            {{-- ══ MI ESPACIO (solo Docente) ══ --}}
            @if($isDocente)
            <div class="nav-section-title">Mi Espacio</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.horarios.mi-horario') }}" class="{{ request()->routeIs('admin.horarios.mi-horario') ? 'active' : '' }}">
                        <i class="bi bi-calendar-week-fill"></i>Mi Horario
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.perfiles.miPerfil') }}" class="{{ request()->routeIs('admin.perfiles.miPerfil') ? 'active' : '' }}">
                        <i class="bi bi-person-circle"></i>Mi Perfil
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ RENDIMIENTO INSTITUCIONAL ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Rendimiento</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.dashboard') }}" class="{{ request()->routeIs('admin.rendimiento.dashboard') && !request('ciclo') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line"></i>Dashboard General
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.semaforo') }}" class="{{ request()->routeIs('admin.rendimiento.semaforo') ? 'active' : '' }}">
                        <i class="bi bi-circle-fill" style="color:#22c55e;font-size:.6rem;"></i>&nbsp;Semáforo
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.porArea') }}" class="{{ request()->routeIs('admin.rendimiento.porArea') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>Por Área
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.recuperaciones') }}" class="{{ request()->routeIs('admin.rendimiento.recuperaciones') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:.75rem;"></i>&nbsp;Recuperaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.rezagados') }}" class="{{ request()->routeIs('admin.rendimiento.rezagados') ? 'active' : '' }}">
                        <i class="bi bi-person-x-fill" style="color:#d97706;font-size:.75rem;"></i>&nbsp;Rezagados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.comparativo') }}" class="{{ request()->routeIs('admin.rendimiento.comparativo') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-steps"></i>Comparativo Períodos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.rankingAsignaturas') }}" class="{{ request()->routeIs('admin.rendimiento.rankingAsignaturas') ? 'active' : '' }}">
                        <i class="bi bi-trophy"></i>Ranking Asignaturas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.rendimiento.tendencia') }}" class="{{ request()->routeIs('admin.rendimiento.tendencia') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>Tendencia por Grupo
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.malla.matriz') }}" class="{{ request()->routeIs('admin.malla.matriz') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3"></i>Matriz Curricular
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ PLANIFICACIÓN DOCENTE ══ --}}
            @if($isAdmin || $isDir || $isCoord || $isDocente)
            <div class="nav-section-title">Planificación Docente</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.planes-clase.index') }}" class="{{ request()->routeIs('admin.planes-clase*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>Planes de Clase
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.instrumentos.index') }}" class="{{ request()->routeIs('admin.instrumentos*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Instrumentos de Evaluación
                    </a>
                </li>
                @if($isAdmin || $isDir || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.classroom.index') }}" class="{{ request()->routeIs('admin.classroom*') ? 'active' : '' }}">
                        <i class="bi bi-easel2-fill"></i>Classroom Virtual
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ SUPERVISIÓN ══ --}}
            @if($canSupervisar || $isDir || $isCoord)
            <div class="nav-section-title">Supervisión</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.reportes.index') }}" class="{{ request()->routeIs('admin.reportes*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard2-data"></i>Reportes Institucionales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.registro.index') }}" class="{{ request()->routeIs('admin.registro*') ? 'active' : '' }}">
                        <i class="bi bi-journal-bookmark-fill"></i>Registro Académico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.competencias.index') }}" class="{{ request()->routeIs('admin.competencias*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Competencias / IL
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.cierre-ano.index') }}" class="{{ request()->routeIs('admin.cierre-ano*') ? 'active' : '' }}">
                        <i class="bi bi-lock-fill"></i>Cierre de Año
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.exportacion-masiva.index') }}" class="{{ request()->routeIs('admin.exportacion-masiva*') ? 'active' : '' }}">
                        <i class="bi bi-file-zip"></i>Exportación Masiva
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ CALENDARIO Y ALERTAS ══ --}}
            @if($isAdmin || $isDir || $isCoord || $isDocente)
            <div class="nav-section-title">Calendario</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.calendario.index') }}" class="{{ request()->routeIs('admin.calendario*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event"></i>Calendario Académico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.alertas.index') }}" class="{{ request()->routeIs('admin.alertas.index') ? 'active' : '' }}" style="justify-content:space-between;">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-bell"></i>Notificaciones
                        </span>
                        @if(!empty($alertasNoLeidas) && $alertasNoLeidas > 0)
                            <span class="badge rounded-pill text-bg-danger" style="font-size:.62rem;padding:.2rem .5rem;">{{ $alertasNoLeidas }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        @endif

            {{-- ══ COMUNICADOS Y MENSAJES ══ --}}
            <div class="nav-section-title">Comunicados y Mensajes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.mensajes.index') }}" class="{{ request()->routeIs('admin.mensajes*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes
                        @php $msgNoLeidos = \App\Models\Mensaje::recibidos(auth()->id())->noLeidos()->count(); @endphp
                        @if($msgNoLeidos > 0)
                        <span class="badge rounded-pill text-bg-primary ms-auto" style="font-size:.62rem;padding:.2rem .5rem;">{{ $msgNoLeidos }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.mis') }}" class="{{ request()->routeIs('admin.comunicados.mis') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i>Mis Comunicados
                    </a>
                </li>
                @if($isAdmin || $isDir || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.comunicados.index') }}" class="{{ request()->routeIs('admin.comunicados.index') || request()->routeIs('admin.comunicados.create') || request()->routeIs('admin.comunicados.edit') ? 'active' : '' }}">
                        <i class="bi bi-megaphone-fill"></i>Gestionar Comunicados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.avisos-emergencia.index') }}" class="{{ request()->routeIs('admin.avisos-emergencia*') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-octagon-fill" style="color:#ef4444;"></i>Avisos Emergencia
                    </a>
                </li>
                @endif
            </ul>

            {{-- ══ PAGOS Y COLEGIATURAS ══ --}}
            @php $moduloPagos = \App\Models\ConfigInstitucional::moduloActivo('pagos'); @endphp
            @if($moduloPagos && ($isAdmin || $isDir))
            <div class="nav-section-title">Pagos y Colegiaturas</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.index') }}" class="{{ request()->routeIs('admin.pagos.index') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i>Gestión de Pagos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.deudores') }}" class="{{ request()->routeIs('admin.pagos.deudores') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-circle"></i>Deudores
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.becas.index') }}" class="{{ request()->routeIs('admin.becas*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>Becas y Descuentos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.config') }}" class="{{ request()->routeIs('admin.pagos.config') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i>Config. Pagos
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ INSCRIPCIONES ══ --}}
            @if($isAdmin || $isDir || $isSecre)
            @php $pmPendientes = \App\Models\PreMatricula::where('estado','pendiente')->count(); @endphp
            <div class="nav-section-title">Inscripciones</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.pre-matriculas.index') }}"
                       class="{{ request()->routeIs('admin.pre-matriculas*') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-person-lines-fill"></i>Pre-matrículas</span>
                        @if($pmPendientes > 0)
                        <span style="background:#f59e0b;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;flex-shrink:0;margin-left:.4rem;">{{ $pmPendientes }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ SERVICIOS INSTITUCIONALES ══ --}}
            @if($isAdmin || $isDir || $isSecre || $isCoord)
            <div class="nav-section-title">Servicios Institucionales</div>
            <ul class="list-unstyled mb-0">
                @if($isAdmin || $isDir || $isSecre)
                <li class="nav-item">
                    <a href="{{ route('admin.cafeteria.ventas') }}" class="{{ request()->routeIs('admin.cafeteria*') ? 'active' : '' }}">
                        <i class="bi bi-shop"></i>Cafetería
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.equipos.index') }}" class="{{ request()->routeIs('admin.equipos.index') || request()->routeIs('admin.equipos.create') || request()->routeIs('admin.equipos.edit') ? 'active' : '' }}">
                        <i class="bi bi-laptop"></i>Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.equipos.prestamos.index') }}" class="{{ request()->routeIs('admin.equipos.prestamos*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>Préstamos de Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.biblioteca.index') }}" class="{{ request()->routeIs('admin.biblioteca.index') || request()->routeIs('admin.biblioteca.libros*') ? 'active' : '' }}">
                        <i class="bi bi-book-half"></i>Biblioteca
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.biblioteca.prestamos.index') }}" class="{{ request()->routeIs('admin.biblioteca.prestamos*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>Préstamos de Libros
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.inventario.index') }}" class="{{ request()->routeIs('admin.inventario*') ? 'active' : '' }}">
                        <i class="bi bi-archive"></i>Inventario Escolar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.recursos.index') }}" class="{{ request()->routeIs('admin.recursos*') && !request()->routeIs('admin.recursos.disponibilidad') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>Recursos y Aulas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.recursos.disponibilidad') }}" class="{{ request()->routeIs('admin.recursos.disponibilidad') ? 'active' : '' }}">
                        <i class="bi bi-calendar2-check"></i>Disponibilidad Aulas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.transporte.index') }}" class="{{ request()->routeIs('admin.transporte*') ? 'active' : '' }}">
                        <i class="bi bi-bus-front"></i>Transporte Escolar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.galeria.index') }}" class="{{ request()->routeIs('admin.galeria*') ? 'active' : '' }}">
                        <i class="bi bi-images"></i>Galería
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.eventos.index') }}" class="{{ request()->routeIs('admin.eventos*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event-fill"></i>Eventos
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.nomina.index') }}" class="{{ request()->routeIs('admin.nomina*') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i>Nómina de Empleados
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ CONFIGURACIÓN ══ --}}
            @if($canConfig || $isDir)
            <div class="nav-section-title">Configuración</div>
            <ul class="list-unstyled mb-0">
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.asignaturas.index') }}" class="{{ request()->routeIs('admin.asignaturas*') ? 'active' : '' }}">
                        <i class="bi bi-book"></i>Asignaturas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.asignaciones.index') }}" class="{{ request()->routeIs('admin.asignaciones*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i>Asignaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.periodos.index') }}" class="{{ request()->routeIs('admin.periodos*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3"></i>Períodos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.school-years.index') }}" class="{{ request()->routeIs('admin.school-years*') ? 'active' : '' }}">
                        <i class="bi bi-mortarboard"></i>Año Escolar
                    </a>
                </li>
                @endif
                @if($isAdmin)
                <li class="nav-item">
                    <a href="{{ route('admin.config.calificacion') }}" class="{{ request()->routeIs('admin.config.calificacion*') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i>Config. Notas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.boletines.config') }}" class="{{ request()->routeIs('admin.boletines.config*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-medical"></i>Config. Boletín
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.config.ra') }}" class="{{ request()->routeIs('admin.config.ra*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-steps"></i>Config. RA
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ PÁGINA DE INICIO ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Página de Inicio</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.landing') }}" class="{{ request()->routeIs('admin.sistema.landing') ? 'active' : '' }}">
                        <i class="bi bi-display"></i>Editor Landing
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.homepage.edit') }}" class="{{ request()->routeIs('admin.homepage*') ? 'active' : '' }}">
                        <i class="bi bi-layout-text-window-reverse"></i>Branding / Institución
                    </a>
                </li>
                @if($isAdmin)
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.login-config') }}" class="{{ request()->routeIs('admin.sistema.login-config') ? 'active' : '' }}">
                        <i class="bi bi-palette"></i>Config. Login
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ SISTEMA ══ --}}
            @if($isAdmin)
            <div class="nav-section-title">Sistema</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.index') || request()->routeIs('admin.usuarios.create') || request()->routeIs('admin.usuarios.edit') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.usuarios.pendientes') }}" class="{{ request()->routeIs('admin.usuarios.pendientes') ? 'active' : '' }}" style="display:flex;align-items:center;justify-content:space-between;">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-person-check"></i>Accesos Pendientes</span>
                        @if(!empty($usuariosPendientes) && $usuariosPendientes > 0)
                            <span class="badge rounded-pill text-bg-warning" style="font-size:.62rem;padding:.2rem .5rem;">{{ $usuariosPendientes }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.index') }}" class="{{ request()->routeIs('admin.sistema.index') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i>Configuración
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.whatsapp') }}" class="{{ request()->routeIs('admin.sistema.whatsapp') ? 'active' : '' }}">
                        <i class="bi bi-whatsapp"></i>WhatsApp
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.email-notif') }}" class="{{ request()->routeIs('admin.sistema.email-notif') ? 'active' : '' }}">
                        <i class="bi bi-envelope-check"></i>Email / Notif.
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.backup') }}" class="{{ request()->routeIs('admin.sistema.backup*') ? 'active' : '' }}">
                        <i class="bi bi-archive"></i>Backup
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.actividad') }}" class="{{ request()->routeIs('admin.sistema.actividad') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>Log de Actividad
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.estadisticas') }}" class="{{ request()->routeIs('admin.sistema.estadisticas') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Estadísticas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sistema.demo-trial') }}" class="{{ request()->routeIs('admin.sistema.demo-trial') ? 'active' : '' }}">
                        <i class="bi bi-play-circle"></i>Demo & Prueba
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ SUPER ADMIN — solo visible para super_admin ══ --}}
            @if(Auth::user()->hasRole('super_admin'))
            <div class="nav-section-title" style="color:#a78bfa;">ZuraEdu Platform</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('superadmin.tenants.index') }}" class="{{ request()->routeIs('superadmin*') ? 'active' : '' }}" style="{{ request()->routeIs('superadmin*') ? '' : 'color:#c4b5fd;' }}">
                        <i class="bi bi-building-fill-gear"></i>Panel de Instituciones
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ SOPORTE ══ --}}
            <div class="nav-section-title">Soporte</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.soporte.index') }}" class="{{ request()->routeIs('admin.soporte*') ? 'active' : '' }}">
                        <i class="bi bi-headset"></i>Tickets de Soporte
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.ayuda') }}" class="{{ request()->routeIs('admin.ayuda') ? 'active' : '' }}">
                        <i class="bi bi-question-circle"></i>Centro de Ayuda
                    </a>
                </li>
            </ul>

        </nav>

        <!-- User Footer -->
        <div class="sidebar-user">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->name ?? 'Usuario' }}</div>
                <div class="user-role">{{ Auth::user()->getRoleNames()->first() ?? 'Usuario' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>

    </aside>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-label="Cerrar menú"></div>

    <!-- ════════════════════════════════════════════════
         TOPBAR
    ════════════════════════════════════════════════ -->
    <header class="topbar" role="banner">
        <button class="topbar-hamburger" id="hamburgerBtn" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>

        <div class="topbar-title d-none d-md-block">
            <i class="bi bi-chevron-right me-1" style="font-size:.7rem;opacity:.5;"></i>
            @yield('page-title', 'Dashboard')
        </div>

        <!-- Global Search -->
        <div class="topbar-search">
            <i class="bi bi-search gs-icon"></i>
            <input type="text" id="globalSearchInput"
                   placeholder="Buscar estudiantes, docentes, grupos…"
                   autocomplete="off" aria-label="Búsqueda global">
            <div id="gsDropdown"></div>
        </div>

        <!-- School Year Badge -->
        @isset($schoolYear)
            <span class="schoolyear-badge d-none d-lg-inline">
                <i class="bi bi-calendar2-check me-1"></i>
                {{ $schoolYear->nombre ?? $schoolYear->name ?? 'Año Escolar' }}
            </span>
        @endisset

        <!-- Campanita alertas -->
        @php $alertasTopbar = $alertasNoLeidas ?? 0; @endphp
        <div style="position:relative;">
            <button id="adminBell" title="Alertas del sistema"
                    style="background:none;border:none;color:#6b7280;font-size:1.15rem;cursor:pointer;padding:.35rem .5rem;border-radius:8px;position:relative;transition:color .15s;"
                    onclick="window.location.href='{{ route('admin.alertas.index') }}'">
                <i class="bi bi-bell"></i>
                <span id="adminBellBadge"
                      style="position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border-radius:99px;font-size:.55rem;font-weight:700;min-width:14px;height:14px;display:{{ $alertasTopbar > 0 ? 'flex' : 'none' }};align-items:center;justify-content:center;padding:0 3px;line-height:1;">
                    {{ $alertasTopbar > 9 ? '9+' : $alertasTopbar }}
                </span>
            </button>
        </div>

        <!-- Dark mode toggle -->
        <button class="dark-toggle" id="darkToggleBtn" title="Modo oscuro / claro">
            <i class="bi bi-moon-stars-fill" id="darkToggleIcon"></i>
        </button>

        <!-- User dropdown -->
        <div class="topbar-user dropdown">
            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    data-bs-strategy="fixed" data-bs-offset="0,4" aria-expanded="false">
                @if(Auth::user()->photo_url)
                    <img src="{{ Auth::user()->photo_url }}" alt="Foto"
                         style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;">
                @else
                <div class="topbar-avatar">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                </div>
                @endif
                <span class="d-none d-md-inline">{{ Auth::user()->name ?? 'Usuario' }}</span>
                <i class="bi bi-chevron-down" style="font-size:.7rem;color:rgba(255,255,255,.55);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width:180px;">
                <li>
                    <span class="dropdown-item-text text-muted" style="font-size:.75rem;">
                        {{ Auth::user()->email ?? '' }}
                    </span>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a href="{{ route('perfil.show') }}" class="dropdown-item">
                        <i class="bi bi-person-circle me-2 text-primary"></i>Mi Perfil
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <!-- ════════════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════════ -->
    <main class="main-content" id="main-content" role="main">

        {{-- ── Barra de Período de Prueba ──────────────────────────── --}}
        @php
            try {
                $trialSetting = \Illuminate\Support\Facades\DB::table('system_settings')
                    ->whereIn('key', ['trial_activo','trial_inicio','trial_dias','trial_mensaje'])
                    ->pluck('value','key');
                $showTrial = ($trialSetting['trial_activo'] ?? '0') === '1'
                    && !empty($trialSetting['trial_inicio']);
                if ($showTrial) {
                    $tInicio  = \Carbon\Carbon::parse($trialSetting['trial_inicio']);
                    $tDias    = (int)($trialSetting['trial_dias'] ?? 30);
                    $tExpira  = $tInicio->copy()->addDays($tDias);
                    $tRestantes = max(0, (int) now()->diffInDays($tExpira, false));
                    $tExpirado  = now()->gt($tExpira);
                    $tPct = $tDias > 0 ? max(2, round(($tRestantes / $tDias) * 100)) : 0;
                    $tMensaje = $trialSetting['trial_mensaje'] ?? 'Estás usando una versión de prueba del sistema.';
                }
            } catch(\Exception $e) { $showTrial = false; }
        @endphp
        @if(!empty($showTrial))
        <div style="background:{{ $tExpirado ? '#fef2f2' : '#fffbeb' }};border-bottom:2px solid {{ $tExpirado ? '#fca5a5' : '#fcd34d' }};padding:.6rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin:-1.5rem -1.5rem 1.5rem;">
            <div style="flex-shrink:0;">
                <i class="bi bi-{{ $tExpirado ? 'x-octagon-fill text-danger' : 'hourglass-split' }}" style="font-size:1.1rem;color:{{ $tExpirado ? '#dc2626' : '#b45309' }};"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="font-size:.8rem;font-weight:700;color:{{ $tExpirado ? '#991b1b' : '#92400e' }};">
                    @if($tExpirado)
                        Período de prueba expirado — Contacta al administrador del sistema
                    @else
                        {{ $tMensaje }} — <strong>{{ $tRestantes }} días restantes</strong>
                    @endif
                </div>
                @if(!$tExpirado)
                <div style="background:#e5e7eb;border-radius:99px;height:5px;margin-top:4px;overflow:hidden;max-width:220px;">
                    <div style="height:100%;border-radius:99px;width:{{ $tPct }}%;background:{{ $tPct > 50 ? '#10b981' : ($tPct > 20 ? '#f59e0b' : '#ef4444') }};"></div>
                </div>
                @endif
            </div>
            <div style="font-size:.73rem;color:#6b7280;white-space:nowrap;">
                Expira: {{ $tExpira->format('d/m/Y') }}
            </div>
            @can('admin.sistema.demo-trial')
            <a href="{{ route('admin.sistema.demo-trial') }}" style="font-size:.73rem;color:var(--primary);font-weight:600;text-decoration:none;white-space:nowrap;">
                Gestionar →
            </a>
            @endcan
        </div>
        @endif

        {{-- ── Banner SuperAdmin: modo panel de institución ──────── --}}
        @if(Auth::check() && Auth::user()->hasRole('super_admin') && session('sa_tenant_id'))
        <div style="background:linear-gradient(90deg,#4f46e5,#7c3aed);color:#fff;padding:.6rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin:-1.5rem -1.5rem 1.5rem;border-bottom:2px solid #6366f1;">
            <i class="bi bi-shield-fill-check" style="font-size:1.1rem;flex-shrink:0;"></i>
            <div style="flex:1;min-width:200px;font-size:.83rem;font-weight:600;">
                <span style="opacity:.8;">SuperAdmin · Administrando:</span>
                <strong class="ms-1">{{ session('sa_tenant_nombre') }}</strong>
            </div>
            <div class="d-flex align-items-center gap-2" style="flex-shrink:0;">
                <a href="{{ route('superadmin.tenants.show', session('sa_tenant_id')) }}"
                   style="font-size:.75rem;font-weight:700;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.4);padding:.25rem .7rem;border-radius:6px;background:rgba(255,255,255,.1);">
                    <i class="bi bi-building me-1"></i>Ficha
                </a>
                <a href="{{ route('admin.homepage.edit') }}"
                   style="font-size:.75rem;font-weight:700;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.4);padding:.25rem .7rem;border-radius:6px;background:rgba(255,255,255,.1);">
                    <i class="bi bi-palette-fill me-1"></i>Homepage
                </a>
                <form method="POST" action="{{ route('superadmin.tenants.exit-panel') }}" class="d-inline">
                    @csrf
                    <button type="submit"
                        style="font-size:.75rem;font-weight:700;color:#4f46e5;background:#fff;border:none;padding:.25rem .7rem;border-radius:6px;cursor:pointer;">
                        <i class="bi bi-box-arrow-left me-1"></i>Salir al panel ZuraEdu
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Banner de vencimiento de suscripción ──────────────── --}}
        @php
            $showSuscBanner = false;
            try {
                if (
                    app()->bound('tenant') &&
                    ! auth()->user()->hasRole('super_admin') &&
                    isset($currentTenant) && $currentTenant?->fecha_vencimiento
                ) {
                    $diasVence = (int) now()->diffInDays($currentTenant->fecha_vencimiento, false);
                    $showSuscBanner = $diasVence <= 14;
                }
            } catch (\Exception $e) {}
        @endphp
        @if($showSuscBanner)
        @php
            if ($diasVence <= 0)       { $bColor = '#fef2f2'; $bBorder = '#fca5a5'; $bText = '#991b1b'; $bIcon = 'x-octagon-fill'; }
            elseif ($diasVence <= 3)   { $bColor = '#fef2f2'; $bBorder = '#fca5a5'; $bText = '#991b1b'; $bIcon = 'exclamation-octagon-fill'; }
            elseif ($diasVence <= 7)   { $bColor = '#fffbeb'; $bBorder = '#fcd34d'; $bText = '#92400e'; $bIcon = 'exclamation-triangle-fill'; }
            else                       { $bColor = '#eff6ff'; $bBorder = '#93c5fd'; $bText = '#1e40af'; $bIcon = 'info-circle-fill'; }
        @endphp
        <div style="background:{{ $bColor }};border-bottom:2px solid {{ $bBorder }};padding:.6rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin:-1.5rem -1.5rem 1.5rem;">
            <i class="bi bi-{{ $bIcon }}" style="font-size:1.1rem;color:{{ $bText }};flex-shrink:0;"></i>
            <div style="flex:1;min-width:200px;font-size:.82rem;font-weight:600;color:{{ $bText }};">
                @if($diasVence <= 0)
                    ¡Tu suscripción <strong>ha vencido hoy</strong>! El acceso puede suspenderse en cualquier momento.
                @elseif($diasVence === 1)
                    Tu suscripción vence <strong>mañana</strong>. Renueva para no perder el acceso.
                @else
                    Tu suscripción vence en <strong>{{ $diasVence }} días</strong> ({{ $currentTenant->fecha_vencimiento->format('d/m/Y') }}).
                @endif
            </div>
            <div class="d-flex align-items-center gap-2" style="flex-shrink:0;">
                <a href="mailto:soporte@zuraedu.com"
                   style="font-size:.75rem;font-weight:700;color:{{ $bText }};text-decoration:none;border:1px solid {{ $bBorder }};padding:.25rem .7rem;border-radius:6px;background:rgba(255,255,255,.6);">
                    <i class="bi bi-envelope me-1"></i>Renovar
                </a>
                <button type="button" onclick="this.closest('div[style]').remove()"
                    style="background:none;border:none;color:{{ $bText }};opacity:.6;cursor:pointer;font-size:1rem;padding:0 .2rem;">
                    ✕
                </button>
            </div>
        </div>
        @endif

        {{-- ── Flash de feature deshabilitada ────────────────────── --}}
        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-3 rounded-3" role="alert" style="font-size:.88rem;">
            <i class="bi bi-shield-exclamation fs-5 flex-shrink-0"></i>
            <span>{{ session('warning') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')

        {{-- ── FOOTER ─────────────────────────────────────────────── --}}
        <footer style="
            margin-top: 3rem;
            padding: 1rem 0 .5rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        ">
            <p style="font-size:.78rem;color:#9ca3af;margin:0;">
                &copy; {{ date('Y') }}
                <strong style="color:var(--primary);">AprendeTicPaulino</strong>
                &mdash; Todos los derechos reservados.
            </p>
        </footer>
    </main>

    <!-- Bootstrap 5 JS — local -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Alpine.js — local -->
    <script defer src="{{ asset('vendor/alpinejs/alpine.min.js') }}"></script>

    <script>
        // ── Dark mode ──────────────────────────────────
        (function() {
            const root    = document.documentElement;
            const stored  = localStorage.getItem('sge-theme') || 'light';
            root.setAttribute('data-theme', stored);
        })();
    </script>

    <script>
        // ── Sidebar toggle (mobile) ────────────────────
        const sidebar         = document.getElementById('sidebar');
        const overlay         = document.getElementById('sidebarOverlay');
        const hamburgerBtn    = document.getElementById('hamburgerBtn');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        // Close sidebar on window resize above breakpoint
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) closeSidebar();
        });

        // ── Sidebar scroll persistence ─────────────────
        // Keeps scroll position when navigating between pages.
        // Only resets to active item if the user hasn't manually scrolled.
        (function() {
            const nav = document.querySelector('.sidebar-nav');
            if (!nav) return;
            const KEY = 'sge-sidebar-scroll';

            // Restore saved position immediately (before paint)
            // Always scroll active item into center on every load
            const active = nav.querySelector('.nav-item a.active');
            if (active) {
                const offset = active.getBoundingClientRect().top
                             - nav.getBoundingClientRect().top
                             + nav.scrollTop
                             - nav.clientHeight / 3;
                nav.scrollTop = Math.max(0, offset);
            } else if (sessionStorage.getItem(KEY) !== null) {
                nav.scrollTop = parseInt(sessionStorage.getItem(KEY), 10);
            }

            // Save scroll position on every link click (before unload)
            nav.addEventListener('click', e => {
                const link = e.target.closest('a[href]');
                if (link) sessionStorage.setItem(KEY, nav.scrollTop);
            });

            // Also save when browser navigates away (back/forward)
            window.addEventListener('pagehide', () => {
                sessionStorage.setItem(KEY, nav.scrollTop);
            });
        })();

        // ── Sidebar submenus (custom toggle, no Bootstrap Collapse) ───
        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.sidebarToggle;
                const target   = document.getElementById(targetId);
                if (!target) return;
                const isOpen = target.classList.toggle('sidebar-submenu-open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                const chevron = btn.querySelector('.bi-chevron-down');
                if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
                if (isOpen) {
                    const nav = document.querySelector('.sidebar-nav');
                    if (nav) {
                        setTimeout(() => {
                            const btnBottom = btn.getBoundingClientRect().bottom;
                            const navBottom = nav.getBoundingClientRect().bottom;
                            if (btnBottom > navBottom - 60) {
                                nav.scrollBy({ top: target.scrollHeight + 24, behavior: 'smooth' });
                            }
                        }, 360);
                    }
                }
            });
        });

        // ── Dark mode toggle ──────────────────────────
        const darkBtn  = document.getElementById('darkToggleBtn');
        const darkIcon = document.getElementById('darkToggleIcon');
        function applyTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            if (darkIcon) {
                darkIcon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        }
        applyTheme(localStorage.getItem('sge-theme') || 'light');
        if (darkBtn) {
            darkBtn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme');
                const next    = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('sge-theme', next);
                applyTheme(next);
            });
        }

        // ── Global Search ─────────────────────────────
        (function() {
            const input    = document.getElementById('globalSearchInput');
            const dropdown = document.getElementById('gsDropdown');
            if (!input || !dropdown) return;

            const ROUTE = '{{ route("admin.search") }}';
            let timer, activeIdx = -1, items = [];

            function render(results) {
                if (!results.length) {
                    dropdown.innerHTML = '<div class="gs-empty"><i class="bi bi-search me-1"></i>Sin resultados</div>';
                    dropdown.classList.add('open');
                    return;
                }
                const groups = {};
                results.forEach(r => { if (!groups[r.grupo]) groups[r.grupo] = []; groups[r.grupo].push(r); });
                let html = '';
                Object.keys(groups).forEach(g => {
                    html += `<div class="gs-group-header">${g}</div>`;
                    groups[g].forEach((r, i) => {
                        html += `<a href="${r.url}" class="gs-item" data-idx="${items.length}">
                            <div class="gs-item-icon" style="background:${r.color}"><i class="bi ${r.icon}"></i></div>
                            <div><div class="gs-item-label">${r.label}</div><div class="gs-item-sub">${r.sub}</div></div>
                        </a>`;
                        items.push(r);
                    });
                });
                dropdown.innerHTML = html;
                dropdown.classList.add('open');
                activeIdx = -1;
            }

            function close() { dropdown.classList.remove('open'); activeIdx = -1; items = []; }

            input.addEventListener('input', () => {
                clearTimeout(timer);
                const q = input.value.trim();
                items = [];
                if (q.length < 2) { close(); return; }
                timer = setTimeout(() => {
                    fetch(`${ROUTE}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json()).then(d => render(d.results || []))
                        .catch(() => {});
                }, 280);
            });

            input.addEventListener('keydown', e => {
                const els = dropdown.querySelectorAll('.gs-item');
                if (e.key === 'ArrowDown') {
                    activeIdx = Math.min(activeIdx + 1, els.length - 1);
                    els.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    e.preventDefault();
                } else if (e.key === 'ArrowUp') {
                    activeIdx = Math.max(activeIdx - 1, -1);
                    els.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    e.preventDefault();
                } else if (e.key === 'Enter' && activeIdx >= 0 && els[activeIdx]) {
                    els[activeIdx].click();
                } else if (e.key === 'Escape') {
                    close(); input.blur();
                }
            });

            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) close();
            });
        })();
    </script>

    <script>
    // ── Barra de progreso de navegación ───────────────────────────────────
    (function () {
        var bar  = document.getElementById('nprogress-bar');
        var prog = 0;
        var raf;

        function set(n) {
            prog = Math.min(n, 1);
            bar.style.transform = 'scaleX(' + prog + ')';
        }

        function start() {
            set(0);
            bar.style.transition = 'transform .1s linear';
            bar.style.opacity = '1';
            // Simula progreso: rápido hasta 30%, lento hasta 85%
            var steps = [.15, .25, .35, .50, .65, .75, .82, .85];
            var i = 0;
            (function tick() {
                if (i < steps.length) {
                    setTimeout(function() { set(steps[i++]); tick(); }, 80 + i * 60);
                }
            })();
        }

        function done() {
            clearTimeout(raf);
            bar.style.transition = 'transform .15s ease';
            set(1);
            setTimeout(function () {
                bar.style.opacity = '0';
                setTimeout(function() { set(0); bar.style.transition = 'none'; }, 200);
            }, 200);
        }

        // Inicia al hacer clic en cualquier enlace interno
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href[0] === '#' || href.startsWith('javascript') ||
                a.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) return;
            // No iniciar en formularios de logout (POST)
            if (a.closest('form')) return;
            start();
        });

        // También inicia en submit de formularios de navegación (GET)
        document.addEventListener('submit', function (e) {
            if (e.target.method && e.target.method.toLowerCase() === 'get') start();
        });

        window.addEventListener('pageshow', done);
        // Si ya cargó (navegación SPA-like no aplica, pero por seguridad)
        if (document.readyState === 'complete') done();
        else window.addEventListener('load', done);
    })();

    // ── Prefetch al hover (desktop) ───────────────────────────────────────
    // Al pasar el cursor >100ms sobre un enlace del sidebar, precarga la página.
    (function () {
        if ('connection' in navigator && navigator.connection.saveData) return; // No en modo ahorro
        var prefetched = new Set();

        function prefetch(href) {
            if (prefetched.has(href)) return;
            prefetched.add(href);
            var l = document.createElement('link');
            l.rel = 'prefetch';
            l.href = href;
            document.head.appendChild(l);
        }

        document.querySelectorAll('.sidebar-nav a[href]').forEach(function (a) {
            var t;
            a.addEventListener('mouseenter', function () {
                t = setTimeout(function () { prefetch(a.href); }, 100);
            });
            a.addEventListener('mouseleave', function () { clearTimeout(t); });
        });
    })();
    </script>

    @stack('scripts')

    <div id="sge-toast-container" aria-live="polite" aria-atomic="false"></div>

    <script>
    // ── SGEToast — Sistema global de notificaciones ──────────────────────────
    window.SGEToast = {
        _icons: {
            success: 'bi-check-circle-fill',
            danger:  'bi-exclamation-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info:    'bi-info-circle-fill',
        },
        _bg:   { success:'#dcfce7', danger:'#fee2e2', warning:'#fef3c7', info:'#dbeafe' },
        _text: { success:'#15803d', danger:'#991b1b', warning:'#92400e', info:'#1d4ed8' },

        show(message, type = 'success', duration = 4500) {
            const c   = document.getElementById('sge-toast-container');
            if (!c) return;
            const id  = 'sgt-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            Object.assign(div.style, {
                background: this._bg[type] || this._bg.info,
                color:      this._text[type] || this._text.info,
                borderRadius: '10px',
                padding: '.7rem 1rem',
                display: 'flex', alignItems: 'flex-start', gap: '.7rem',
                boxShadow: '0 4px 20px rgba(0,0,0,.13)',
                fontSize: '.84rem', fontWeight: '600',
                animation: 'toastEnter .3s cubic-bezier(.34,1.56,.64,1) both',
                maxWidth: '340px', wordBreak: 'break-word',
                border: '1px solid ' + (this._text[type] || this._text.info) + '33',
            });
            div.innerHTML = `
                <i class="bi ${this._icons[type] || this._icons.info}" style="font-size:1rem;flex-shrink:0;margin-top:.1rem;"></i>
                <span style="flex:1;line-height:1.4;">${message}</span>
                <button onclick="SGEToast._remove('${id}')" title="Cerrar"
                        style="background:none;border:none;color:inherit;font-size:.95rem;cursor:pointer;opacity:.65;padding:0;line-height:1;flex-shrink:0;margin-top:.05rem;">
                    <i class="bi bi-x-lg"></i>
                </button>`;
            c.appendChild(div);
            if (duration > 0) setTimeout(() => this._remove(id), duration);
        },

        _remove(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.animation = 'toastExit .22s ease-in both';
            setTimeout(() => el?.remove(), 230);
        },

        success(msg, d) { this.show(msg, 'success', d); },
        error(msg, d)   { this.show(msg, 'danger',  d); },
        warning(msg, d) { this.show(msg, 'warning', d); },
        info(msg, d)    { this.show(msg, 'info',    d); },
    };
    </script>

{{-- ════════════════════════════════════════════════════════════════
     CHATBOX IA — Gemini
════════════════════════════════════════════════════════════════ --}}
<style>
/* ── Floating button ───────────────────────────────────────────── */
#chat-fab {
    position: fixed;
    bottom: 1.75rem;
    right: 1.75rem;
    width: 54px; height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4285f4, #1a73e8);
    color: #fff;
    border: none;
    box-shadow: 0 4px 20px rgba(66,133,244,.5);
    font-size: 1.4rem;
    cursor: pointer;
    z-index: 1050;
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s, box-shadow .2s;
}
#chat-fab:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(66,133,244,.6); }
#chat-fab .badge-dot {
    position: absolute; top: 6px; right: 6px;
    width: 10px; height: 10px;
    background: #34a853; border-radius: 50%;
    border: 2px solid #fff;
}

/* ── Chat window ───────────────────────────────────────────────── */
#chat-window {
    position: fixed;
    bottom: 5.5rem;
    right: 1.75rem;
    width: 360px;
    max-height: 540px;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 12px 50px rgba(0,0,0,.18);
    display: flex; flex-direction: column;
    z-index: 1049;
    overflow: hidden;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1), opacity .2s;
    transform: scale(.85) translateY(30px);
    opacity: 0;
    pointer-events: none;
}
#chat-window.open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: all;
}

/* Header */
#chat-header {
    background: linear-gradient(135deg, #4285f4, #1a73e8);
    color: #fff;
    padding: .85rem 1.1rem;
    display: flex; align-items: center; gap: .7rem;
    flex-shrink: 0;
}
#chat-header .chat-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
}
#chat-header .chat-title  { font-weight: 700; font-size: .9rem; line-height: 1.2; }
#chat-header .chat-status { font-size: .72rem; opacity: .8; }
#chat-close {
    margin-left: auto;
    background: none; border: none; color: #fff;
    font-size: 1.1rem; cursor: pointer; opacity: .8;
    line-height: 1;
}
#chat-close:hover { opacity: 1; }

/* Messages area */
#chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex; flex-direction: column; gap: .65rem;
    background: #f8faff;
    scroll-behavior: smooth;
}
#chat-messages::-webkit-scrollbar { width: 4px; }
#chat-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }

.chat-msg {
    max-width: 82%;
    padding: .55rem .85rem;
    border-radius: 14px;
    font-size: .84rem;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-wrap;
}
.chat-msg.user {
    align-self: flex-end;
    background: #1a73e8;
    color: #fff;
    border-bottom-right-radius: 4px;
}
.chat-msg.bot {
    align-self: flex-start;
    background: #fff;
    color: #1e293b;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.chat-msg.typing {
    background: #fff;
    border: 1px solid #e5e7eb;
    align-self: flex-start;
    padding: .55rem 1rem;
    border-bottom-left-radius: 4px;
}
.typing-dots span {
    display: inline-block;
    width: 7px; height: 7px;
    background: #9ca3af;
    border-radius: 50%;
    margin: 0 2px;
    animation: bounce 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes bounce {
    0%,80%,100% { transform: translateY(0); }
    40%          { transform: translateY(-6px); }
}

/* Input area */
#chat-input-area {
    padding: .75rem;
    border-top: 1px solid #e5e7eb;
    display: flex; gap: .5rem; align-items: flex-end;
    background: #fff;
    flex-shrink: 0;
}
#chat-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: .5rem .85rem;
    font-size: .84rem;
    resize: none;
    outline: none;
    max-height: 100px;
    overflow-y: auto;
    line-height: 1.4;
    font-family: inherit;
    transition: border-color .15s;
}
#chat-input:focus { border-color: #4285f4; }
#chat-send {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #1a73e8;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
    transition: background .15s, transform .1s;
}
#chat-send:hover { background: #1557b0; transform: scale(1.05); }
#chat-send:disabled { background: #d1d5db; cursor: not-allowed; transform: none; }

/* Suggestion chips */
.chat-sug {
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 99px;
    padding: .25rem .65rem;
    font-size: .72rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    white-space: nowrap;
    transition: background .1s, border-color .1s;
}
.chat-sug:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
}

@media (max-width: 480px) {
    #chat-window { width: calc(100vw - 2rem); right: 1rem; bottom: 5rem; }
}
</style>

{{-- Floating button --}}
<button id="chat-fab" title="Asistente IA" onclick="toggleChat()">
    <i class="bi bi-stars"></i>
    <span class="badge-dot"></span>
</button>

{{-- Chat window --}}
<div id="chat-window">
    <div id="chat-header">
        <div class="chat-avatar"><i class="bi bi-stars"></i></div>
        <div>
            <div class="chat-title">Asistente PSAC</div>
            <div class="chat-status">Powered by Google Gemini</div>
        </div>
        <div style="display:flex;align-items:center;gap:.35rem;margin-left:auto;">
            <button onclick="clearChat()" title="Limpiar conversación"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:7px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.78rem;cursor:pointer;opacity:.8;">
                <i class="bi bi-trash3"></i>
            </button>
            <button id="chat-close" onclick="toggleChat()" title="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div id="chat-messages">
        <div class="chat-msg bot">¡Hola! Soy el asistente del PSAC. Puedo ayudarte con el sistema: asistencia, calificaciones, matrículas, boletines y más. ¿En qué te ayudo?</div>
    </div>

    {{-- Sugerencias rápidas --}}
    <div id="chat-suggestions" style="padding:.55rem .75rem;border-top:1px solid #e5e7eb;background:#f8faff;display:flex;gap:.4rem;flex-wrap:wrap;">
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo registro asistencia?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo genero un boletín?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo matriculo un estudiante?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo asigno un docente?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo agrego calificaciones?</button>
        <button class="chat-sug" onclick="useSuggestion(this)">¿Cómo exporto un reporte?</button>
    </div>

    <div id="chat-input-area">
        <textarea id="chat-input"
                  rows="1"
                  placeholder="Escribe tu consulta..."
                  onkeydown="handleChatKey(event)"
                  oninput="autoResize(this)"></textarea>
        <button id="chat-send" onclick="sendChat()" title="Enviar">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<script>
(function() {
    const ROUTE_CHAT = "{{ route('admin.chat.send') }}";
    const CSRF       = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let chatOpen    = false;
    let isTyping    = false;
    let chatHistory = [];

    window.toggleChat = function() {
        chatOpen = !chatOpen;
        document.getElementById('chat-window').classList.toggle('open', chatOpen);
        if (chatOpen) {
            setTimeout(() => document.getElementById('chat-input').focus(), 250);
        }
    };

    window.autoResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    };

    window.handleChatKey = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChat();
        }
    };

    function renderMarkdown(text) {
        // Escapar HTML para evitar XSS, luego renderizar markdown básico
        const safe = text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return safe
            .replace(/^#{1,3}\s+(.+)$/gm, '<strong>$1</strong>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`\n]+)`/g, '<code style="background:#f1f5f9;padding:.1em .35em;border-radius:4px;font-size:.82em;font-family:monospace;">$1</code>')
            .replace(/^[-•*]\s+(.+)$/gm, '• $1')
            .replace(/\n/g, '<br>');
    }

    function appendMsg(text, role) {
        const msgs = document.getElementById('chat-messages');
        const div  = document.createElement('div');
        div.className = 'chat-msg ' + role;
        if (role === 'bot') {
            div.innerHTML = renderMarkdown(text);
        } else {
            div.textContent = text;
        }
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return div;
    }

    function showTyping() {
        const msgs = document.getElementById('chat-messages');
        const div  = document.createElement('div');
        div.className = 'chat-msg typing';
        div.id = 'chat-typing';
        div.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function removeTyping() {
        const t = document.getElementById('chat-typing');
        if (t) t.remove();
    }

    window.sendChat = async function() {
        if (isTyping) return;

        const input = document.getElementById('chat-input');
        const text  = input.value.trim();
        if (!text) return;

        appendMsg(text, 'user');
        chatHistory.push({ role: 'user', text });

        input.value = '';
        input.style.height = 'auto';
        document.getElementById('chat-send').disabled = true;
        isTyping = true;

        showTyping();

        try {
            const res  = await fetch(ROUTE_CHAT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text, history: chatHistory.slice(-10) }),
            });

            const data = await res.json();
            removeTyping();

            const reply = data.reply ?? 'Sin respuesta.';
            appendMsg(reply, 'bot');
            chatHistory.push({ role: 'model', text: reply });

            // Keep history manageable
            if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);

        } catch (err) {
            removeTyping();
            appendMsg('Error de conexión. Intenta de nuevo.', 'bot');
        } finally {
            document.getElementById('chat-send').disabled = false;
            isTyping = false;
        }
    };

    window.clearChat = function() {
        const msgs = document.getElementById('chat-messages');
        msgs.innerHTML = '<div class="chat-msg bot">Conversación reiniciada. ¿En qué te ayudo?</div>';
        chatHistory = [];
        document.getElementById('chat-suggestions').style.display = 'flex';
    };

    window.useSuggestion = function(btn) {
        const text = btn.textContent.trim();
        document.getElementById('chat-suggestions').style.display = 'none';
        document.getElementById('chat-input').value = text;
        sendChat();
    };

    // Ocultar sugerencias cuando el usuario empieza a escribir
    document.getElementById('chat-input').addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            document.getElementById('chat-suggestions').style.display = 'none';
        }
    });
})();
</script>

{{-- Polling alertas admin cada 60 segundos --}}
<script>
(function() {
    const CONTEO_URL = "{{ route('admin.alertas.conteo') }}";
    let lastCount    = {{ $alertasTopbar ?? 0 }};

    async function pollAlertas() {
        try {
            const res  = await fetch(CONTEO_URL, { headers: { 'Accept': 'application/json' } });
            if (! res.ok) return;
            const { total } = await res.json();
            const badge = document.getElementById('adminBellBadge');
            const bell  = document.getElementById('adminBell');
            if (! badge) return;

            if (total > 0) {
                badge.textContent = total > 9 ? '9+' : total;
                badge.style.display = 'flex';
                if (total > lastCount) {
                    bell.style.color = '#ef4444';
                    setTimeout(() => { bell.style.color = ''; }, 3000);
                }
            } else {
                badge.style.display = 'none';
            }
            lastCount = total;
        } catch (_) {}
    }

    setInterval(pollAlertas, 60000);
})();
</script>
</body>
</html>
