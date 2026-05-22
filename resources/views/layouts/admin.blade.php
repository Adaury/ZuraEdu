<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $__sysName = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name')); @endphp
    <title>@yield('page-title', 'Dashboard') — {{ $__sysName }}</title>

    {{-- Dynamic favicon — tenant-scoped cache --}}
    @php $__tid = tenant_id(); $faviconPath = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_system_favicon", 600, fn () => \Illuminate\Support\Facades\DB::table('system_settings')->where('key','system_favicon')->value('value')); @endphp
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

    <!-- DataTables 2 + Scroller JS — CSS propio (sin CDN CSS para evitar conflictos Bootstrap) -->
    <style>
        /* ══ DataTables wrapper layout ══════════════════════════════════════ */
        div.dataTables_wrapper                    { position: relative; clear: both; }
        div.dataTables_wrapper:after              { visibility: hidden; display: block; content: ""; clear: both; height: 0; }

        /* Cabecera y pie del wrapper */
        div.dataTables_wrapper div.dt-top          { padding: .35rem 0 .5rem; }
        div.dataTables_wrapper div.dt-bottom       { padding: .35rem 0 0; }

        /* ── Búsqueda ── */
        div.dataTables_wrapper div.dataTables_filter        { display: inline-block; }
        div.dataTables_wrapper div.dataTables_filter label  { font-size: .82rem; font-weight: 600; color: #6b7280; display: flex; align-items: center; gap: .4rem; margin: 0; }
        div.dataTables_wrapper div.dataTables_filter input[type="search"] {
            padding: .35rem .7rem; border: 1.5px solid #d1d5db; border-radius: 8px;
            font-size: .83rem; color: #1e293b; background: #fff;
            outline: none; min-width: 180px;
            transition: border-color .2s, box-shadow .2s;
        }
        div.dataTables_wrapper div.dataTables_filter input[type="search"]:focus {
            border-color: var(--primary, #2563eb);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        div.dataTables_wrapper div.dataTables_filter input[type="search"]::placeholder { color: #9ca3af; }

        /* ── Info ── */
        div.dataTables_wrapper div.dataTables_info { font-size: .79rem; color: #9ca3af; padding: 0; }

        /* ── Scroll ── */
        div.dataTables_wrapper .dataTables_scroll         { clear: both; }
        div.dataTables_wrapper .dataTables_scrollHead     { overflow: hidden !important; }
        div.dataTables_wrapper .dataTables_scrollBody     { overflow-y: auto !important; -webkit-overflow-scrolling: touch; }
        div.dataTables_wrapper .dataTables_scrollHeadInner{ box-sizing: border-box !important; }
        div.dataTables_wrapper .dataTables_scrollHeadInner table { margin-bottom: 0 !important; }
        div.dataTables_wrapper .dataTables_scrollFoot     { overflow: hidden; }

        /* ══ Tabla ══════════════════════════════════════════════════════════ */
        table.dataTable { border-collapse: collapse !important; border-spacing: 0 !important; width: 100% !important; }
        table.dataTable.no-footer { border-bottom: 1px solid #dee2e6; }

        /* thead: fondo primario, texto blanco */
        table.dataTable > thead > tr > th,
        table.dataTable > thead > tr > td {
            background: var(--primary, #6366f1) !important;
            color: #fff !important;
            font-size: .8rem !important;
            font-weight: 700 !important;
            border-bottom: 2px solid rgba(255,255,255,.2) !important;
            padding: .55rem .75rem !important;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }

        /* Íconos de ordenamiento */
        table.dataTable > thead > tr > th.sorting          { background-image: none !important; }
        table.dataTable > thead > tr > th.dt-orderable-asc,
        table.dataTable > thead > tr > th.dt-orderable-desc{ position: relative; padding-right: 1.5rem !important; }
        table.dataTable > thead > tr > th.dt-orderable-asc::after,
        table.dataTable > thead > tr > th.dt-orderable-desc::after {
            position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
            font-size: .65rem; opacity: .6; color: #fff;
        }
        table.dataTable > thead > tr > th.dt-ordering-asc::after  { content: '▲'; opacity: 1; }
        table.dataTable > thead > tr > th.dt-ordering-desc::after { content: '▼'; opacity: 1; }
        /* DT2 usa clases distintas para sort */
        table.dataTable thead th.sorting       { background-image: none !important; }
        table.dataTable thead th.sorting::after,
        table.dataTable thead th.sorting_asc::after,
        table.dataTable thead th.sorting_desc::after { color: rgba(255,255,255,.75) !important; }

        /* tbody: texto oscuro, fondo blanco explícito */
        table.dataTable > tbody > tr > td,
        table.dataTable > tbody > tr > th {
            color: #1e293b !important;
            background-color: #fff !important;
            border-color: #f1f5f9 !important;
            padding: .5rem .75rem;
            vertical-align: middle;
        }
        table.dataTable > tbody > tr:nth-child(even) > td,
        table.dataTable > tbody > tr:nth-child(even) > th { background-color: #f8faff !important; }
        table.dataTable > tbody > tr:hover > td,
        table.dataTable > tbody > tr:hover > th {
            background-color: #eff6ff !important;
            color: #1e293b !important;
        }
        table.dataTable > tbody > tr { transition: background .1s; }

        /* Scroller placeholder rows */
        div.dataTables_wrapper div.DTS div.dataTables_scrollBody table { background: transparent; }
        div.dataTables_wrapper div.DTS tbody tr { background: transparent !important; }

        /* ══ Dark mode ══════════════════════════════════════════════════════ */
        [data-theme="dark"] div.dataTables_wrapper div.dataTables_filter input[type="search"] {
            background: #1e293b !important; border-color: #334155 !important; color: #e2e8f0 !important;
        }
        [data-theme="dark"] div.dataTables_wrapper div.dataTables_filter input[type="search"]::placeholder { color: #64748b; }
        [data-theme="dark"] div.dataTables_wrapper div.dataTables_filter label,
        [data-theme="dark"] div.dataTables_wrapper div.dataTables_info    { color: #94a3b8 !important; }
        [data-theme="dark"] div.dataTables_wrapper .dataTables_scrollBody { background: #0f172a; }
        [data-theme="dark"] table.dataTable > tbody > tr > td,
        [data-theme="dark"] table.dataTable > tbody > tr > th             { color: #e2e8f0 !important; background-color: #1e293b !important; border-color: #334155 !important; }
        [data-theme="dark"] table.dataTable > tbody > tr:nth-child(even) > td,
        [data-theme="dark"] table.dataTable > tbody > tr:nth-child(even) > th { background-color: #172032 !important; }
        [data-theme="dark"] table.dataTable > tbody > tr:hover > td,
        [data-theme="dark"] table.dataTable > tbody > tr:hover > th       { background-color: #334155 !important; color: #f1f5f9 !important; }
    </style>

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
        /* ── CSS Variables — estilo ZuraEdu SuperAdmin (índigo) ── */
        :root {
            --primary:         #6366f1;
            --primary-dark:    #4f46e5;
            --primary-light:   #818cf8;
            --secondary:       #10b981;
            --accent:          #10b981;
            --accent-light:    #d1fae5;
            --sidebar-bg:      #0f172a;
            --sidebar-width:   260px;
            --topbar-height:   60px;
            --sidebar-text:    #94a3b8;
            --sidebar-hover:   rgba(255,255,255,.06);
            --sidebar-active:  #6366f1;
            --role-color:      #6366f1;
            --role-glow:       rgba(99,102,241,.35);
            --role-grad1:      #0f172a;
            --role-grad2:      #1e1b4b;
            --role-active-bg:  rgba(99,102,241,.18);
            --role-active-txt: #a5b4fc;
        }
        /* ── Director → rojo carmesí ── */
        body.role-director {
            --primary:      #dc2626;
            --primary-dark: #b91c1c;
            --primary-light:#fca5a5;
            --role-color:   #dc2626;
            --role-glow:    rgba(220,38,38,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #450a0a;
            --role-active-bg: rgba(220,38,38,.15);
            --role-active-txt:#fca5a5;
        }
        /* ── Coordinador → índigo profundo ── */
        body.role-coordinador {
            --primary:      #4f46e5;
            --primary-dark: #4338ca;
            --primary-light:#818cf8;
            --role-color:   #4f46e5;
            --role-glow:    rgba(79,70,229,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #1e1b4b;
            --role-active-bg: rgba(79,70,229,.18);
            --role-active-txt:#a5b4fc;
        }
        /* ── Docente → violeta ── */
        body.role-docente {
            --primary:      #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light:#c4b5fd;
            --role-color:   #7c3aed;
            --role-glow:    rgba(124,58,237,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #2e1065;
            --role-active-bg: rgba(124,58,237,.18);
            --role-active-txt:#c4b5fd;
        }
        /* ── Docente Guía → púrpura ── */
        body.role-docente-guia {
            --primary:      #9333ea;
            --primary-dark: #7e22ce;
            --primary-light:#d8b4fe;
            --role-color:   #9333ea;
            --role-glow:    rgba(147,51,234,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #3b0764;
            --role-active-bg: rgba(147,51,234,.18);
            --role-active-txt:#d8b4fe;
        }
        /* ── Secretaría → rosa ── */
        body.role-secretaria {
            --primary:      #db2777;
            --primary-dark: #be185d;
            --primary-light:#fbcfe8;
            --role-color:   #db2777;
            --role-glow:    rgba(219,39,119,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #500724;
            --role-active-bg: rgba(219,39,119,.15);
            --role-active-txt:#fbcfe8;
        }
        /* ── Cajero / Personal Adm. → esmeralda ── */
        body.role-cajero {
            --primary:      #059669;
            --primary-dark: #047857;
            --primary-light:#6ee7b7;
            --role-color:   #059669;
            --role-glow:    rgba(5,150,105,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #052e16;
            --role-active-bg: rgba(5,150,105,.15);
            --role-active-txt:#6ee7b7;
        }
        /* ── Representante (Padre) → celeste ── */
        body.role-representante {
            --primary:      #0284c7;
            --primary-dark: #0369a1;
            --primary-light:#7dd3fc;
            --role-color:   #0284c7;
            --role-glow:    rgba(2,132,199,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #0c2340;
            --role-active-bg: rgba(2,132,199,.15);
            --role-active-txt:#7dd3fc;
        }
        /* ── Estudiante → cian ── */
        body.role-estudiante {
            --primary:      #0891b2;
            --primary-dark: #0e7490;
            --primary-light:#67e8f9;
            --role-color:   #0891b2;
            --role-glow:    rgba(8,145,178,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #0c2a3a;
            --role-active-bg: rgba(8,145,178,.15);
            --role-active-txt:#67e8f9;
        }
        /* ── Encargado de Área → verde ── */
        body.role-encargado {
            --primary:      #16a34a;
            --primary-dark: #15803d;
            --primary-light:#86efac;
            --role-color:   #16a34a;
            --role-glow:    rgba(22,163,74,.35);
            --role-grad1:   #0f172a;
            --role-grad2:   #052e16;
            --role-active-bg: rgba(22,163,74,.15);
            --role-active-txt:#86efac;
        }

        /* ── Dark mode overrides ───────────────────────── */
        [data-theme="dark"] {
            --primary:       #3b82f6;
            --primary-dark:  #1e40af;
            --primary-light: #60a5fa;
        }
        [data-theme="dark"] body {
            background:
                radial-gradient(ellipse at 80% -5%, rgba(99,102,241,.15) 0%, transparent 52%),
                radial-gradient(ellipse at -10% 90%, rgba(139,92,246,.12) 0%, transparent 50%),
                #070c1a;
            background-attachment: fixed;
            color: #e2e8f0;
        }
        [data-theme="dark"] .topbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            box-shadow: 0 1px 8px rgba(0,0,0,.4);
        }
        [data-theme="dark"] .topbar-title { color: #e2e8f0; }
        [data-theme="dark"] .topbar-hamburger { color: #94a3b8; }
        [data-theme="dark"] .topbar-hamburger:hover { color: #e2e8f0; background: #1e293b; }
        [data-theme="dark"] .topbar-search input { background: #1e293b; border-color: #334155; color: #e2e8f0; }
        [data-theme="dark"] .topbar-search input::placeholder { color: #475569; }
        [data-theme="dark"] .topbar-search input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); background: #1e293b; }
        [data-theme="dark"] .topbar-search .gs-icon { color: #475569; }
        [data-theme="dark"] .schoolyear-badge { background: #1e1b4b; color: #a5b4fc; border-color: #4f46e5; }
        [data-theme="dark"] .topbar-user .dropdown-toggle { background: #1e293b; border-color: #334155; color: #e2e8f0; }
        [data-theme="dark"] .topbar-user .dropdown-toggle:hover { background: #1e1b4b; border-color: #6366f1; }
        [data-theme="dark"] #adminBell { color: #64748b !important; }
        [data-theme="dark"] #adminBell:hover { color: #a5b4fc !important; background: rgba(99,102,241,.15) !important; }
        [data-theme="dark"] .dark-toggle { color: #fcd34d; }
        [data-theme="dark"] .dark-toggle:hover { background: #1e293b; }
        [data-theme="dark"] .main-content { background: #0f172a; }
        [data-theme="dark"] .sidebar { background: linear-gradient(180deg, #060611 0%, #1a1740 100%); box-shadow: 4px 0 32px rgba(0,0,0,.6); border-right-color: rgba(99,102,241,.1); }
        [data-theme="dark"] .card,
        [data-theme="dark"] .card-panel,
        [data-theme="dark"] .import-card,
        [data-theme="dark"] .stat-card { background: #1e293b !important; border-color: #334155 !important; color: #e2e8f0; }
        [data-theme="dark"] .table { color: #e2e8f0; }
        [data-theme="dark"] .table thead th { background: #1e1b4b !important; }
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
        [data-theme="dark"] .schoolyear-badge { background: #1e1b4b; color: #a5b4fc; border-color: #4f46e5; }

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
            border: 1.5px solid #e2e8f0; border-radius: 20px;
            font-size: .82rem; background: #f8faff; color: #1e293b;
            outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .topbar-search input::placeholder { color: #94a3b8; }
        .topbar-search input:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99,102,241,.1);
            background: #fff;
        }
        .topbar-search .gs-icon {
            position: absolute; left: .65rem; top: 50%;
            transform: translateY(-50%); color: #94a3b8; font-size: .85rem; pointer-events: none;
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
        /* topbar-search dark: manejado en bloque dark-mode arriba */
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
            background: #1e1b4b !important;
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
            color: #64748b; font-size: 1.05rem;
            padding: .3rem .4rem; border-radius: 8px;
            cursor: pointer; transition: color .18s, background .18s;
            line-height: 1;
        }
        .dark-toggle:hover { color: #0f172a; background: #f1f5f9; }
        [data-theme="dark"] .dark-toggle { color: #fcd34d; }
        [data-theme="dark"] .dark-toggle:hover { background: rgba(255,255,255,.08); }

        /* ── Bell / alertas en topbar blanco ─────────────── */
        #adminBell { color: #64748b !important; border-radius: 8px; transition: color .15s, background .15s; }
        #adminBell:hover { color: #4f46e5 !important; background: #ede9fe !important; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse at 80% -5%, rgba(99,102,241,.10) 0%, transparent 52%),
                radial-gradient(ellipse at -10% 90%, rgba(139,92,246,.07) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 60%, rgba(59,130,246,.04) 0%, transparent 65%),
                #eef2f7;
            background-attachment: fixed;
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
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            overflow-x: hidden;
            overflow-y: hidden;
            border-right: 1px solid rgba(99,102,241,.12);
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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .92rem;
            color: #fff;
            letter-spacing: .02em;
            flex-shrink: 0;
            box-shadow: 0 4px 18px rgba(99,102,241,.45), 0 0 0 1px rgba(255,255,255,.1);
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
            color: #818cf8;
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
            background: var(--role-active-bg, rgba(99,102,241,.18));
            color: var(--role-active-txt, #a5b4fc);
            font-weight: 600;
            border-right: 3px solid var(--role-color, #6366f1);
            box-shadow: none;
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
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-bottom: 1px solid rgba(255,255,255,.5);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            z-index: 1030;
            box-shadow: 0 1px 0 rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
            gap: 1rem;
            transition: left .3s cubic-bezier(.4,0,.2,1);
        }

        .topbar-hamburger {
            display: none;
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 1.3rem;
            padding: .25rem;
            line-height: 1;
            cursor: pointer;
            border-radius: 6px;
            transition: color .18s, background .18s;
        }
        .topbar-hamburger:hover { color: #0f172a; background: #f1f5f9; }

        .topbar-title {
            font-size: .92rem;
            font-weight: 700;
            color: #0f172a;
            flex: 1;
            letter-spacing: .01em;
        }

        .schoolyear-badge {
            background: #ede9fe;
            color: #4f46e5;
            border: 1px solid #c4b5fd;
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
            background: #f8faff;
            border: 1.5px solid #e0e7ff;
            color: #1e293b;
            font-size: .83rem;
            font-weight: 600;
            padding: .3rem .75rem .3rem .4rem;
            border-radius: 20px;
            transition: background .18s, border-color .18s;
        }
        .topbar-user .dropdown-toggle:hover { background: #ede9fe; border-color: #a5b4fc; color: #1e293b; }
        .topbar-user .dropdown-toggle::after { display: none; }

        .topbar-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
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

        /* ═══════════════════════════════════════════════════════════
           ZURA PREMIUM — SaaS Visual System
           Linear · Vercel · Stripe · Apple · Antigravity
        ═══════════════════════════════════════════════════════════ */

        /* ── GPU hint for animated elements ──────────────────── */
        .card, .btn, .sidebar-nav a, .stat-card,
        .quick-action, .modulo-card, .nav-link {
            will-change: transform;
        }

        /* ── Card glassmorphism ───────────────────────────────── */
        .card {
            background: rgba(255,255,255,.88) !important;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255,255,255,.72) !important;
            border-radius: 16px !important;
            box-shadow:
                0 4px 24px rgba(0,0,0,.06),
                0 1px 0 rgba(255,255,255,.9) inset !important;
            transition:
                box-shadow 240ms cubic-bezier(.4,0,.2,1),
                transform 240ms cubic-bezier(.34,1.56,.64,1),
                border-color 240ms !important;
        }
        .card:hover {
            box-shadow:
                0 10px 40px rgba(0,0,0,.09),
                0 1px 0 rgba(255,255,255,.9) inset !important;
            transform: translateY(-2px);
        }
        .card-header {
            background: rgba(255,255,255,.55) !important;
            border-bottom: 1px solid rgba(0,0,0,.06) !important;
            backdrop-filter: blur(12px);
            border-radius: 16px 16px 0 0 !important;
        }
        [data-theme="dark"] .card {
            background: rgba(15,23,42,.75) !important;
            border: 1px solid rgba(99,102,241,.14) !important;
            box-shadow:
                0 4px 24px rgba(0,0,0,.3),
                0 1px 0 rgba(255,255,255,.04) inset !important;
        }
        [data-theme="dark"] .card:hover {
            border-color: rgba(99,102,241,.28) !important;
            box-shadow: 0 10px 40px rgba(0,0,0,.4), 0 0 0 1px rgba(99,102,241,.2) !important;
        }
        [data-theme="dark"] .card-header {
            background: rgba(7,12,26,.55) !important;
            border-bottom-color: rgba(99,102,241,.1) !important;
        }
        [data-theme="dark"] .main-content { background: transparent !important; }

        /* ── Topbar dark glass ────────────────────────────────── */
        [data-theme="dark"] .topbar {
            background: rgba(7,12,26,.84) !important;
            border-bottom: 1px solid rgba(99,102,241,.14) !important;
            box-shadow: 0 1px 0 rgba(99,102,241,.08), 0 4px 16px rgba(0,0,0,.25) !important;
        }

        /* ── Sidebar premium hover ────────────────────────────── */
        .sidebar-nav a {
            transition: all 200ms cubic-bezier(.4,0,.2,1) !important;
            border-left: 2px solid transparent !important;
        }
        .sidebar-nav a:hover:not(.active) {
            border-left-color: rgba(99,102,241,.6) !important;
            transform: translateX(4px);
            background: rgba(99,102,241,.1) !important;
        }
        .sidebar-nav a.active {
            border-left: 2px solid var(--role-color, #6366f1) !important;
            box-shadow: inset 0 0 0 1px rgba(99,102,241,.15), 4px 0 20px rgba(99,102,241,.18);
        }
        .logo-badge {
            box-shadow: 0 4px 18px rgba(99,102,241,.5), 0 0 0 1px rgba(255,255,255,.15),
                        0 0 32px rgba(99,102,241,.25) !important;
            transition: box-shadow 300ms, transform 300ms !important;
        }
        .sidebar-logo:hover .logo-badge {
            transform: scale(1.06);
            box-shadow: 0 6px 24px rgba(99,102,241,.7), 0 0 0 1px rgba(255,255,255,.2),
                        0 0 48px rgba(99,102,241,.35) !important;
        }

        /* ── Quick actions premium ────────────────────────────── */
        .quick-action {
            background: rgba(255,255,255,.82) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.65) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 16px rgba(0,0,0,.05), 0 1px 0 rgba(255,255,255,.8) inset !important;
            transition: all 250ms cubic-bezier(.34,1.56,.64,1) !important;
        }
        .quick-action:hover {
            transform: translateY(-6px) scale(1.04) !important;
            box-shadow: 0 16px 40px rgba(29,78,216,.18), 0 1px 0 rgba(255,255,255,.8) inset !important;
            border-color: var(--primary) !important;
            background: rgba(239,246,255,.95) !important;
            color: var(--primary) !important;
        }
        [data-theme="dark"] .quick-action {
            background: rgba(15,23,42,.78) !important;
            border-color: rgba(99,102,241,.15) !important;
        }
        [data-theme="dark"] .quick-action:hover {
            background: rgba(30,27,75,.9) !important;
            border-color: rgba(99,102,241,.5) !important;
            box-shadow: 0 16px 40px rgba(99,102,241,.22) !important;
        }

        /* ── Module cards premium ─────────────────────────────── */
        .modulo-card {
            background: rgba(255,255,255,.82) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.65) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 16px rgba(0,0,0,.05) !important;
            transition: all 250ms cubic-bezier(.34,1.56,.64,1) !important;
        }
        .modulo-card:hover {
            transform: translateY(-5px) scale(1.01) !important;
            box-shadow: 0 14px 36px rgba(0,0,0,.10) !important;
            border-color: var(--primary) !important;
            background: rgba(255,255,255,.96) !important;
        }
        [data-theme="dark"] .modulo-card {
            background: rgba(15,23,42,.78) !important;
            border-color: rgba(99,102,241,.12) !important;
        }
        [data-theme="dark"] .modulo-card:hover {
            background: rgba(30,27,75,.9) !important;
            border-color: rgba(99,102,241,.4) !important;
            box-shadow: 0 14px 36px rgba(99,102,241,.18) !important;
        }

        /* ── Buttons glow ─────────────────────────────────────── */
        .btn-primary, [class*="btn-"][style*="background:#1d4ed8"],
        [class*="btn-"][style*="background:#1e3a6e"] {
            transition: all 200ms cubic-bezier(.34,1.56,.64,1) !important;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99,102,241,.45) !important;
        }
        .btn-primary:active { transform: translateY(0); }
        .btn-success:hover  { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(22,163,74,.4) !important; }
        .btn-danger:hover   { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(220,38,38,.4) !important; }
        .btn-warning:hover  { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.35) !important; }

        /* ── Premium focus ring ───────────────────────────────── */
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(99,102,241,.18) !important;
            border-color: var(--primary) !important;
        }

        /* ── Scrollbar thin ───────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,.12); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,.22); }
        [data-theme="dark"] ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); }
        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.2); }

        /* ── Nav tabs premium ─────────────────────────────────── */
        .nav-tabs {
            border-color: rgba(0,0,0,.08) !important;
            gap: 4px;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0 !important;
            transition: all 180ms ease !important;
            font-size: .83rem;
            font-weight: 600;
        }
        .nav-tabs .nav-link:hover:not(.active) {
            background: rgba(99,102,241,.07) !important;
            color: var(--primary) !important;
        }
        .nav-tabs .nav-link.active {
            background: rgba(255,255,255,.95) !important;
            border-color: rgba(0,0,0,.08) rgba(0,0,0,.08) transparent !important;
            color: var(--primary) !important;
            box-shadow: 0 -2px 0 var(--primary) inset !important;
        }
        [data-theme="dark"] .nav-tabs { border-color: rgba(99,102,241,.15) !important; }
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: rgba(15,23,42,.9) !important;
            border-color: rgba(99,102,241,.2) rgba(99,102,241,.2) transparent !important;
            color: #a5b4fc !important;
            box-shadow: 0 -2px 0 #6366f1 inset !important;
        }

        /* ── Page header gradient text ────────────────────────── */
        .page-header h1,
        .page-header .h4,
        h1[style*="color:var(--primary)"],
        .h4[style*="color:var(--primary)"] {
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%) !important;
            -webkit-background-clip: text !important;
            background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            font-weight: 800 !important;
            letter-spacing: -.02em !important;
        }
        .page-header h1 i, .page-header .h4 i,
        h1[style*="color:var(--primary)"] i,
        .h4[style*="color:var(--primary)"] i {
            -webkit-text-fill-color: initial !important;
            background: none !important;
        }
        [data-theme="dark"] .page-header h1,
        [data-theme="dark"] .page-header .h4,
        [data-theme="dark"] h1[style*="color:var(--primary)"],
        [data-theme="dark"] .h4[style*="color:var(--primary)"] {
            background: linear-gradient(135deg, #a5b4fc 0%, #c4b5fd 100%) !important;
            -webkit-background-clip: text !important;
            background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }

        /* ── Filter bar glass ─────────────────────────────────── */
        .filter-bar {
            background: rgba(255,255,255,.80) !important;
            backdrop-filter: blur(14px) saturate(160%) !important;
            -webkit-backdrop-filter: blur(14px) saturate(160%) !important;
            border: 1px solid rgba(255,255,255,.65) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 16px rgba(0,0,0,.05), 0 1px 0 rgba(255,255,255,.9) inset !important;
        }
        [data-theme="dark"] .filter-bar {
            background: rgba(15,23,42,.75) !important;
            border-color: rgba(99,102,241,.14) !important;
            box-shadow: 0 2px 16px rgba(0,0,0,.2) !important;
        }

        /* ── Table card premium ───────────────────────────────── */
        .table-card {
            background: rgba(255,255,255,.88) !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border: 1px solid rgba(255,255,255,.72) !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 24px rgba(0,0,0,.06), 0 1px 0 rgba(255,255,255,.9) inset !important;
            overflow: hidden;
        }
        [data-theme="dark"] .table-card {
            background: rgba(15,23,42,.75) !important;
            border-color: rgba(99,102,241,.14) !important;
        }
        .table-card thead th {
            background: rgba(99,102,241,.06) !important;
            border-bottom: 1px solid rgba(99,102,241,.12) !important;
            font-size: .78rem !important;
            font-weight: 700 !important;
            letter-spacing: .04em !important;
            text-transform: uppercase !important;
            color: rgba(0,0,0,.45) !important;
        }
        [data-theme="dark"] .table-card thead th {
            background: rgba(99,102,241,.08) !important;
            color: rgba(255,255,255,.4) !important;
        }
        .table-card tbody tr {
            transition: background 150ms ease !important;
        }
        .table-card tbody tr:hover {
            background: rgba(99,102,241,.04) !important;
        }
        [data-theme="dark"] .table-card tbody tr:hover {
            background: rgba(99,102,241,.08) !important;
        }

        /* ── Grupo cards glassmorphism ────────────────────────── */
        .grupo-card {
            background: rgba(255,255,255,.85) !important;
            backdrop-filter: blur(14px) saturate(170%) !important;
            -webkit-backdrop-filter: blur(14px) saturate(170%) !important;
            border: 1px solid rgba(255,255,255,.7) !important;
            border-radius: 20px !important;
            box-shadow: 0 4px 24px rgba(0,0,0,.07), 0 1px 0 rgba(255,255,255,.9) inset !important;
            transition: all 280ms cubic-bezier(.34,1.56,.64,1) !important;
        }
        .grupo-card:hover {
            transform: translateY(-7px) scale(1.02) !important;
            box-shadow: 0 20px 48px rgba(99,102,241,.18), 0 1px 0 rgba(255,255,255,.9) inset !important;
            border-color: rgba(99,102,241,.35) !important;
        }
        [data-theme="dark"] .grupo-card {
            background: rgba(15,23,42,.76) !important;
            border-color: rgba(99,102,241,.14) !important;
        }
        [data-theme="dark"] .grupo-card:hover {
            background: rgba(30,27,75,.88) !important;
            border-color: rgba(99,102,241,.45) !important;
            box-shadow: 0 20px 48px rgba(99,102,241,.22) !important;
        }

        /* ── Section titles (grado-section-title) ─────────────── */
        .grado-section-title {
            font-weight: 800 !important;
            letter-spacing: -.01em !important;
            font-size: 1rem !important;
        }

        /* ── Badge pill premium ───────────────────────────────── */
        .badge {
            font-weight: 600 !important;
            letter-spacing: .02em !important;
        }

        /* ── Table premium (global tables inside cards) ───────── */
        .card .table thead th {
            background: rgba(99,102,241,.05) !important;
            font-size: .77rem !important;
            font-weight: 700 !important;
            letter-spacing: .04em !important;
            text-transform: uppercase !important;
            color: rgba(0,0,0,.42) !important;
            border-bottom: 1px solid rgba(99,102,241,.1) !important;
        }
        [data-theme="dark"] .card .table thead th {
            background: rgba(99,102,241,.08) !important;
            color: rgba(255,255,255,.38) !important;
        }
        .card .table tbody tr { transition: background 140ms ease !important; }
        .card .table tbody tr:hover { background: rgba(99,102,241,.03) !important; }
        [data-theme="dark"] .card .table tbody tr:hover { background: rgba(99,102,241,.07) !important; }

        /* ── Card footer glass ────────────────────────────────── */
        .card-footer.bg-white {
            background: rgba(255,255,255,.55) !important;
            backdrop-filter: blur(8px) !important;
            border-top: 1px solid rgba(0,0,0,.06) !important;
        }
        [data-theme="dark"] .card-footer.bg-white {
            background: rgba(15,23,42,.5) !important;
            border-top-color: rgba(99,102,241,.12) !important;
        }

        /* ── Avatar initials ring ─────────────────────────────── */
        .avatar-initials {
            box-shadow: 0 2px 8px rgba(99,102,241,.25), 0 0 0 2px rgba(255,255,255,.8) !important;
            transition: transform 200ms, box-shadow 200ms !important;
        }
        tr:hover .avatar-initials {
            transform: scale(1.08) !important;
            box-shadow: 0 4px 12px rgba(99,102,241,.35), 0 0 0 2px rgba(255,255,255,.9) !important;
        }

        /* ── Entrance animations ──────────────────────────────── */
        @keyframes premiumSlideUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .p-slide-up   { animation: premiumSlideUp 420ms cubic-bezier(.4,0,.2,1) both; }
        .p-delay-1    { animation-delay: 60ms; }
        .p-delay-2    { animation-delay: 120ms; }
        .p-delay-3    { animation-delay: 180ms; }
        .p-delay-4    { animation-delay: 240ms; }
        .p-delay-5    { animation-delay: 300ms; }
        .p-delay-6    { animation-delay: 360ms; }
    </style>

    {{-- PWA --}}
    <link rel="manifest" href="/pwa/manifest.json">
    <meta name="theme-color" content="{{ $currentTenant->color_primario ?? '#1d4ed8' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $currentTenant->nombre_institucion ?? config('app.name') }}">
    <link rel="apple-touch-icon" href="/pwa/icon/192?tid={{ $currentTenant->id ?? 0 }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(() => {});
            });
        }
    </script>
</head>
@php
$bodyRoleClass = '';
if (auth()->check()) {
    $r = auth()->user();
    if      ($r->hasRole('Director'))                                                                          $bodyRoleClass = 'role-director';
    elseif  ($r->hasAnyRole(['Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo'])) $bodyRoleClass = 'role-coordinador';
    elseif  ($r->hasRole('Secretaria Docente'))                                                                $bodyRoleClass = 'role-docente-guia';
    elseif  ($r->hasRole('Docente'))                                                                           $bodyRoleClass = 'role-docente';
    elseif  ($r->hasRole('Secretaría'))                                                                        $bodyRoleClass = 'role-secretaria';
    elseif  ($r->hasAnyRole(['Personal Administrativo','Cajero']))                                             $bodyRoleClass = 'role-cajero';
    elseif  ($r->hasRole('Representante'))                                                                     $bodyRoleClass = 'role-representante';
    elseif  ($r->hasRole('Estudiante'))                                                                        $bodyRoleClass = 'role-estudiante';
    elseif  ($r->hasRole('Encargado de Área'))                                                                 $bodyRoleClass = 'role-encargado';
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
                <div class="logo-badge">{{ $systemSettings['system_abbr'] ?? 'SGE' }}</div>
            @endif
            <div class="logo-text">
                <div class="system-name">{{ $systemSettings['system_name'] ?? 'Zura' }}</div>
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
                $isSuperAdmin   = $u->hasRole('super_admin');
                $canSupervisar  = $isAdmin || $isDir || $isPersonalAdm;
                $canConfig      = $isAdmin || $isSuperAdmin;
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
                    <a href="{{ route('admin.inscripciones.index') }}" class="{{ request()->routeIs('admin.inscripciones*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Inscripciones
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
                    <a href="{{ route('admin.planificacion.dashboard') }}" class="{{ request()->routeIs('admin.planificacion*') ? 'active' : '' }}">
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
                    <a href="{{ route('admin.academico.index') }}" class="{{ request()->routeIs('admin.academico*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3-event"></i>Año Escolar
                    </a>
                </li>
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
                    <a href="{{ route('admin.disciplina.dashboard') }}" class="{{ request()->routeIs('admin.disciplina*') ? 'active' : '' }}">
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
                            <li><a href="{{ route('admin.salud.dashboard') }}" class="{{ request()->routeIs('admin.salud*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-heart-pulse"></i>Salud Escolar</a></li>
                            <li><a href="{{ route('admin.evaluaciones-docentes.index') }}" class="{{ request()->routeIs('admin.evaluaciones-docentes*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-clipboard2-check"></i>Eval. Docentes</a></li>
                            <li><a href="{{ route('admin.reuniones.dashboard') }}" class="{{ request()->routeIs('admin.reuniones*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-journal-text"></i>Actas Reuniones</a></li>
                            <li><a href="{{ route('admin.proyectos.dashboard') }}" class="{{ request()->routeIs('admin.proyectos*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-lightbulb"></i>Proyectos</a></li>
                            <li><a href="{{ route('admin.reconocimientos.dashboard') }}" class="{{ request()->routeIs('admin.reconocimientos*') ? 'active' : '' }}" style="font-size:.81rem;padding:.4rem .75rem;"><i class="bi bi-trophy"></i>Reconocimientos</a></li>
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
                    <a href="{{ route('admin.riesgo.index') }}" class="{{ request()->routeIs('admin.riesgo*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation" style="color:#ef4444;font-size:.75rem;"></i>Risk Score
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
                @if($isAdmin || $isDir || $isCoord)
                <li class="nav-item">
                    <a href="{{ route('admin.ejecutivo.index') }}" class="{{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
                    </a>
                    @if(request()->routeIs('admin.ejecutivo*'))
                    <ul class="list-unstyled ms-3 mt-1 mb-1" style="font-size:.76rem;">
                        <li>
                            <a href="{{ route('admin.ejecutivo.pdf', request()->query()) }}" target="_blank"
                               style="color:#94a3b8;display:flex;align-items:center;gap:.4rem;padding:.25rem .5rem;border-radius:6px;text-decoration:none;"
                               onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">
                                <i class="bi bi-file-earmark-pdf" style="color:#f87171;"></i>Exportar PDF
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.ejecutivo.excel', request()->query()) }}"
                               style="color:#94a3b8;display:flex;align-items:center;gap:.4rem;padding:.25rem .5rem;border-radius:6px;text-decoration:none;"
                               onmouseover="this.style.color='#e2e8f0'" onmouseout="this.style.color='#94a3b8'">
                                <i class="bi bi-file-earmark-excel" style="color:#4ade80;"></i>Exportar Excel
                            </a>
                        </li>
                    </ul>
                    @endif
                </li>
                @endif
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
                    <a href="{{ route('admin.rubricas.index') }}" class="{{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
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
                <li class="nav-item">
                    <a href="{{ route('admin.importaciones.index') }}" class="{{ request()->routeIs('admin.importaciones*') ? 'active' : '' }}">
                        <i class="bi bi-cloud-upload"></i>Importaciones
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
                    <a href="{{ route('admin.comunicaciones.index') }}" class="{{ request()->routeIs('admin.comunicaciones*') ? 'active' : '' }}">
                        <i class="bi bi-envelope-fill"></i>Mensajes Internos
                        @php try { $__uid = auth()->id(); $msgNoLeidos = \Illuminate\Support\Facades\Cache::remember("user_{$__uid}_msg_unread", 60, fn() => \App\Models\MensajeDestinatario::where('destinatario_id',$__uid)->whereNull('leido_at')->where('eliminado',false)->count()); } catch(\Exception $e){ $msgNoLeidos=0; } @endphp
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
                    <a href="{{ route('admin.comunicados.dashboard') }}" class="{{ request()->routeIs('admin.comunicados*') ? 'active' : '' }}">
                        <i class="bi bi-megaphone-fill"></i>Gestionar Comunicados
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.avisos-emergencia.index') }}" class="{{ request()->routeIs('admin.avisos-emergencia*') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-octagon-fill" style="color:#ef4444;"></i>Avisos Emergencia
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.encuestas.dashboard') }}" class="{{ request()->routeIs('admin.encuestas*') ? 'active' : '' }}">
                        <i class="bi bi-patch-question"></i>Encuestas
                    </a>
                </li>
                @endif
            </ul>

            {{-- ══ PAGOS Y COLEGIATURAS ══ --}}
            @php
                $moduloPagos    = \App\Models\ConfigInstitucional::moduloActivo('pagos');
                $countVencidos  = $moduloPagos ? \App\Models\Pago::vencidos()->count() : 0;
            @endphp
            @if($moduloPagos && ($isAdmin || $isDir))
            <div class="nav-section-title">Pagos y Colegiaturas</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.dashboard') }}" class="{{ request()->routeIs('admin.pagos.dashboard') || request()->routeIs('admin.pagos.index') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin"></i>Gestión de Pagos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.deudores') }}" class="{{ request()->routeIs('admin.pagos.deudores') ? 'active' : '' }}" style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-exclamation-circle"></i>Deudores</span>
                        @if($countVencidos > 0)
                        <span style="background:#dc2626;color:#fff;border-radius:99px;font-size:.62rem;font-weight:700;min-width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px;flex-shrink:0;">{{ $countVencidos > 99 ? '99+' : $countVencidos }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.becas.dashboard') }}" class="{{ request()->routeIs('admin.becas*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>Becas y Descuentos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.conceptos') }}" class="{{ request()->routeIs('admin.pagos.conceptos') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>Conceptos de Pago
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
                    <a href="{{ route('admin.inscripciones.index') }}" class="{{ request()->routeIs('admin.inscripciones*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>Inscripciones
                    </a>
                </li>
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

            {{-- ══ SOLICITUDES DEL PERSONAL ══ --}}
            @if($isAdmin || $isDir || $isCoord)
            <div class="nav-section-title">Solicitudes</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.solicitudes.index') }}" class="{{ request()->routeIs('admin.solicitudes.index') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-people-fill"></i>Representantes</span>
                        @php
                        try {
                            $__tid = tenant_id();
                            $solRepPend = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_sol_rep_pend", 60,
                                fn() => \App\Models\SolicitudRepresentante::where('estado','pendiente')->count()
                            );
                        } catch(\Exception $e){ $solRepPend=0; }
                        @endphp
                        @if($solRepPend > 0)
                        <span style="background:#d97706;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;">{{ $solRepPend }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.solicitudes-est.index') }}" class="{{ request()->routeIs('admin.solicitudes-est*') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-mortarboard-fill"></i>Estudiantes</span>
                        @php
                        try {
                            $solEstPend = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_sol_est_pend", 60,
                                fn() => \App\Models\SolicitudEstudiante::where('estado','pendiente')->count()
                            );
                        } catch(\Exception $e){ $solEstPend=0; }
                        @endphp
                        @if($solEstPend > 0)
                        <span style="background:#d97706;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;">{{ $solEstPend }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.solicitudes-docente.index') }}" class="{{ request()->routeIs('admin.solicitudes-docente*') ? 'active' : '' }}"
                       style="display:flex;align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-person-badge-fill"></i>Docentes</span>
                        @php
                        try {
                            $solDocPend = \Illuminate\Support\Facades\Cache::remember("t{$__tid}_sol_doc_pend", 60,
                                fn() => \App\Models\SolicitudDocente::where('estado','pendiente')->count()
                            );
                        } catch(\Exception $e){ $solDocPend=0; }
                        @endphp
                        @if($solDocPend > 0)
                        <span style="background:#d97706;color:#fff;font-size:.65rem;font-weight:800;padding:.1rem .45rem;border-radius:20px;line-height:1.5;">{{ $solDocPend }}</span>
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
                    <a href="{{ route('admin.cafeteria.dashboard') }}" class="{{ request()->routeIs('admin.cafeteria*') ? 'active' : '' }}">
                        <i class="bi bi-shop"></i>Cafetería
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.equipos.dashboard') }}" class="{{ request()->routeIs('admin.equipos.dashboard') || request()->routeIs('admin.equipos.index') || request()->routeIs('admin.equipos.create') || request()->routeIs('admin.equipos.edit') ? 'active' : '' }}">
                        <i class="bi bi-laptop"></i>Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.equipos.prestamos.index') }}" class="{{ request()->routeIs('admin.equipos.prestamos*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>Préstamos de Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.biblioteca.dashboard') }}" class="{{ request()->routeIs('admin.biblioteca.dashboard') || request()->routeIs('admin.biblioteca.index') || request()->routeIs('admin.biblioteca.libros*') ? 'active' : '' }}">
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
                    <a href="{{ route('admin.transporte.dashboard') }}" class="{{ request()->routeIs('admin.transporte*') ? 'active' : '' }}">
                        <i class="bi bi-bus-front"></i>Transporte Escolar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.galeria.dashboard') }}" class="{{ request()->routeIs('admin.galeria*') ? 'active' : '' }}">
                        <i class="bi bi-images"></i>Galería
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('admin.eventos.dashboard') }}" class="{{ request()->routeIs('admin.eventos*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-event-fill"></i>Eventos
                    </a>
                </li>
                @if($isAdmin || $isDir)
                <li class="nav-item">
                    <a href="{{ route('admin.nomina.dashboard') }}" class="{{ request()->routeIs('admin.nomina*') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack"></i>Nómina de Empleados
                    </a>
                </li>
                @endif
            </ul>
            @endif

            {{-- ══ SOPORTE ══ --}}
            @if($isAdmin || $isDir)
            <div class="nav-section-title">Soporte</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('admin.soporte.chat') }}" class="{{ request()->routeIs('admin.soporte.chat*') ? 'active' : '' }}"
                       id="sidebar-soporte-chat">
                        <i class="bi bi-headset"></i>Chat de Soporte
                        <span id="sidebar-support-badge" style="display:none;background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;font-weight:700;min-width:17px;height:17px;padding:0 4px;margin-left:auto;align-items:center;justify-content:center;"></span>
                    </a>
                </li>
            </ul>
            @endif

            {{-- ══ CONFIGURACIÓN ══ --}}
            @if($canConfig || $isDir)
            <div class="nav-section-title">Configuración</div>
            <ul class="list-unstyled mb-0">
                @if($isAdmin || $isDir || $isSuperAdmin)
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
                    <a href="{{ route('admin.familias.index') }}" class="{{ request()->routeIs('admin.familias*') ? 'active' : '' }}">
                        <i class="bi bi-collection"></i>Familias Profesionales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.bachillerato-tecnico.index') }}" class="{{ request()->routeIs('admin.bachillerato-tecnico*') ? 'active' : '' }}">
                        <i class="bi bi-mortarboard-fill"></i>Bachillerato Técnico
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.especialidades.index') }}" class="{{ request()->routeIs('admin.especialidades*') ? 'active' : '' }}">
                        <i class="bi bi-tools"></i>Especialidades Técnicas
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

                        {{-- INTEGRACIONES --}}
            @if($isAdmin)
            <div class="nav-section-title">Integraciones</div>
            <ul class="list-unstyled mb-0">
                <li>
                    <a href="{{ route("admin.integraciones.index") }}" class="{{ request()->routeIs("admin.integraciones*", "admin.sigerd*") ? "active" : "" }}">
                        <i class="bi bi-plug-fill"></i>Integraciones
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.asistente.index') }}" class="{{ request()->routeIs('admin.asistente*') ? 'active' : '' }}">
                        <i class="bi bi-stars"></i>ZuraAI
                    </a>
                </li>
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
                            <span id="admin-pending-badge" class="badge rounded-pill text-bg-warning" style="font-size:.62rem;padding:.2rem .5rem;">{{ $usuariosPendientes }}</span>
                        @else
                            <span id="admin-pending-badge" class="badge rounded-pill text-bg-warning" style="font-size:.62rem;padding:.2rem .5rem;display:none;">0</span>
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
                <li class="nav-item">
                    <a href="{{ route('admin.billing.index') }}" class="{{ request()->routeIs('admin.billing*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card"></i>Facturación
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
                    <a href="{{ route('admin.soporte.dashboard') }}" class="{{ request()->routeIs('admin.soporte*') ? 'active' : '' }}">
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

    {{-- ── Chat interno del tenant ────────────────────────────────────────── --}}
    @auth
    <div id="tenant-chat-widget" style="position:fixed;bottom:1.5rem;right:5rem;z-index:9990;display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;">
        {{-- Panel de chat --}}
        <div id="tenant-chat-panel"
             style="width:340px;max-height:480px;background:#fff;border-radius:18px;box-shadow:0 8px 32px rgba(0,0,0,.16);display:none;flex-direction:column;overflow:hidden;border:1px solid #e2e8f0;">
            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#1e3a6e,#3B82F6);padding:.85rem 1rem;display:flex;align-items:center;gap:.6rem;">
                <div style="width:32px;height:32px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-people-fill" style="color:#fff;font-size:.9rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;color:#fff;font-size:.88rem;line-height:1.2;">Chat del Personal</div>
                    <div id="chat-online-count" style="font-size:.72rem;color:rgba(255,255,255,.7);">conectando...</div>
                </div>
                <button onclick="clearTenantChat()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:7px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.78rem;cursor:pointer;opacity:.8;flex-shrink:0;" title="Limpiar chat">
                    <i class="bi bi-trash3"></i>
                </button>
                <button onclick="toggleTenantChat()" style="background:none;border:none;color:#fff;opacity:.7;cursor:pointer;font-size:1.1rem;padding:.2rem;" title="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            {{-- Mensajes --}}
            <div id="tenant-chat-messages"
                 style="flex:1;overflow-y:auto;padding:.75rem;display:flex;flex-direction:column;gap:.5rem;background:#f8fafc;min-height:200px;max-height:320px;">
            </div>
            {{-- Input --}}
            <div style="padding:.6rem .75rem;border-top:1px solid #e2e8f0;background:#fff;">
                <form id="tenant-chat-form" style="display:flex;gap:.5rem;align-items:center;">
                    @csrf
                    <input id="tenant-chat-input" type="text" placeholder="Escribe un mensaje..."
                           autocomplete="off" maxlength="2000"
                           style="flex:1;border:1px solid #e2e8f0;border-radius:10px;padding:.45rem .75rem;font-size:.83rem;outline:none;">
                    <button type="submit" style="background:#3B82F6;border:none;color:#fff;border-radius:10px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;">
                        <i class="bi bi-send-fill" style="font-size:.8rem;"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Botón flotante --}}
        <button id="tenant-chat-btn" onclick="toggleTenantChat()"
                style="width:52px;height:52px;background:linear-gradient(135deg,#1e3a6e,#3B82F6);border:none;border-radius:50%;color:#fff;box-shadow:0 4px 16px rgba(59,130,246,.5);cursor:pointer;display:flex;align-items:center;justify-content:center;position:relative;transition:transform .2s;"
                onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
                title="Chat del Personal">
            <i class="bi bi-chat-dots-fill" style="font-size:1.2rem;"></i>
            <span id="tenant-chat-badge" data-count="0"
                  style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:.6rem;font-weight:700;min-width:18px;height:18px;border-radius:99px;align-items:center;justify-content:center;border:2px solid #fff;"></span>
        </button>
    </div>

    <script>
    const _CHAT_ME_ID   = {{ auth()->id() }};
    const _CHAT_ME_NAME = '{{ Str::limit(auth()->user()->name ?? '', 20) }}';
    const _CHAT_URL     = '{{ route('admin.tenant-chat.index') }}';
    const _CHAT_CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let   _chatLoaded   = false;

    function toggleTenantChat() {
        const panel = document.getElementById('tenant-chat-panel');
        const isOpen = panel.style.display !== 'none';

        if (!isOpen) {
            // Abrir siempre limpio
            const box = document.getElementById('tenant-chat-messages');
            if (box) box.innerHTML = '';
            _chatLoaded = true;
            panel.style.display = 'flex';
            panel.style.flexDirection = 'column';
            const badge = document.getElementById('tenant-chat-badge');
            if (badge) { badge.style.display = 'none'; badge.dataset.count = '0'; }
            setTimeout(() => document.getElementById('tenant-chat-input')?.focus(), 100);
        } else {
            panel.style.display = 'none';
        }
    }

    function clearTenantChat() {
        if (!confirm('¿Eliminar todos los mensajes del chat?')) return;
        fetch('{{ route('admin.tenant-chat.clear') }}', {
            method:  'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _CHAT_CSRF },
        })
        .then(r => r.json())
        .then(() => {
            const box = document.getElementById('tenant-chat-messages');
            if (box) box.innerHTML = '<div class="text-center text-muted small py-3">Chat limpiado.</div>';
            _chatLoaded = true;
        })
        .catch(() => {});
    }

    function loadChatHistory() {
        fetch(_CHAT_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(msgs => {
                _chatLoaded = true;
                const box = document.getElementById('tenant-chat-messages');
                const loader = document.getElementById('chat-loading-msg');
                if (loader) loader.remove();
                msgs.forEach(m => appendChatBubble(m, false));
                scrollChatBottom();
            })
            .catch(() => { _chatLoaded = true; });
    }

    function appendChatBubble(data, scroll = true) {
        const box   = document.getElementById('tenant-chat-messages');
        if (!box) return;
        const isMio = data.user_id === _CHAT_ME_ID;
        const div   = document.createElement('div');
        div.style.cssText = `display:flex;flex-direction:column;align-items:${isMio ? 'flex-end' : 'flex-start'};gap:2px;`;
        div.innerHTML = `
            ${!isMio ? `<span style="font-size:.68rem;color:#64748b;font-weight:600;">${escHtml(data.user_name)}</span>` : ''}
            <div style="max-width:78%;background:${isMio ? '#3B82F6' : '#fff'};color:${isMio ? '#fff' : '#1e293b'};
                        border-radius:${isMio ? '14px 14px 4px 14px' : '14px 14px 14px 4px'};
                        padding:.45rem .75rem;font-size:.83rem;line-height:1.4;
                        box-shadow:0 1px 3px rgba(0,0,0,.08);">
                ${escHtml(data.mensaje)}
            </div>
            <span style="font-size:.63rem;color:#94a3b8;">${data.hora || data.tiempo}</span>`;
        box.appendChild(div);
        if (scroll) scrollChatBottom();
    }

    function scrollChatBottom() {
        const box = document.getElementById('tenant-chat-messages');
        if (box) box.scrollTop = box.scrollHeight;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str ?? ''));
        return d.innerHTML;
    }

    // Enviar mensaje
    document.getElementById('tenant-chat-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('tenant-chat-input');
        const msg   = input.value.trim();
        if (!msg) return;
        input.value = '';

        fetch(_CHAT_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _CHAT_CSRF },
            body:    JSON.stringify({ mensaje: msg }),
        })
        .then(r => r.json())
        .then(data => appendChatBubble(data, true))
        .catch(() => {});
    });

    // Escuchar mensajes entrantes vía Echo
    window.addEventListener('tenant:chat-message', function(e) {
        const panel = document.getElementById('tenant-chat-panel');
        if (panel && panel.style.display !== 'none' && _chatLoaded) {
            if (e.detail.user_id !== _CHAT_ME_ID) {
                appendChatBubble(e.detail, true);
            }
        }
    });
    </script>
    @endauth

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
            <div class="chat-title">Zura — {{ $systemSettings['system_name'] ?? config('app.name') }}</div>
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

    <div id="chat-messages"></div>

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

    const WELCOME_MSG = '¡Hola! Soy <strong>Zura</strong>, el asistente de <strong>{{ $systemSettings['system_name'] ?? config('app.name') }}</strong>. Puedo ayudarte con el sistema: asistencia, calificaciones, matrículas, boletines y más. ¿En qué te ayudo?';

    function resetChat() {
        chatHistory = [];
        isTyping    = false;
        document.getElementById('chat-messages').innerHTML = '<div class="chat-msg bot">' + WELCOME_MSG + '</div>';
        document.getElementById('chat-suggestions').style.display = 'flex';
        document.getElementById('chat-send').disabled = false;
        const inp = document.getElementById('chat-input');
        if (inp) { inp.value = ''; inp.style.height = 'auto'; }
    }

    window.toggleChat = function() {
        chatOpen = !chatOpen;
        document.getElementById('chat-window').classList.toggle('open', chatOpen);
        if (chatOpen) {
            resetChat();
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
        // Snapshot del historial ANTES de agregar el mensaje actual
        // para no enviarlo duplicado al backend (history + message)
        const historySend = chatHistory.slice(-10);

        input.value = '';
        input.style.height = 'auto';
        document.getElementById('chat-send').disabled = true;
        isTyping = true;

        showTyping();

        try {
            const res = await fetch(ROUTE_CHAT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text, history: historySend }),
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            removeTyping();

            const reply = data.reply ?? 'Sin respuesta.';
            appendMsg(reply, 'bot');

            // Agregar al historial DESPUÉS de recibir respuesta
            chatHistory.push({ role: 'user', text });
            chatHistory.push({ role: 'model', text: reply });

            if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);

        } catch (err) {
            removeTyping();
            appendMsg('No se pudo obtener respuesta. Por favor intenta de nuevo.', 'bot');
        } finally {
            document.getElementById('chat-send').disabled = false;
            isTyping = false;
        }
    };

    window.clearChat = function() {
        resetChat();
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

<!-- DataTables 2 + Scroller JS (sin jQuery) -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.4.3/js/dataTables.scroller.min.js"></script>
<script>
(function () {
    'use strict';

    const LANG_ES = {
        decimal: ',', thousands: '.',
        emptyTable:       'No hay datos disponibles',
        info:             'Mostrando _START_–_END_ de _TOTAL_ registros',
        infoEmpty:        'Sin registros',
        infoFiltered:     '(filtrado de _MAX_ total)',
        loadingRecords:   'Cargando…',
        processing:       'Procesando…',
        search:           '',
        searchPlaceholder:'Buscar en tabla…',
        zeroRecords:      'Sin resultados',
        paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    };

    // dom: f = filter, t = table, i = info (sin paginación visual — el scroll es la navegación)
    const DT_DOM = '<"dt-top d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2"f>t<"dt-bottom d-flex align-items-center gap-2 mt-1"i>';

    function dtInit(el) {
        if (typeof DataTable === 'undefined') return;
        if (DataTable.isDataTable(el)) return;

        const tbody = el.querySelector('tbody');
        if (!tbody) return;
        const rowCount = tbody.querySelectorAll('tr').length;
        if (rowCount < 5) return;

        // Altura dinámica según número de filas
        const scrollH = rowCount > 40 ? '62vh' : rowCount > 20 ? '48vh' : '340px';

        new DataTable(el, {
            language:      LANG_ES,
            dom:           DT_DOM,
            deferRender:   true,   // renderiza filas solo al hacerse visibles (lazy DOM)
            scrollY:       scrollH,
            scrollX:       true,
            scrollCollapse:true,
            scroller:      true,   // virtual scroll — carga filas a demanda al desplazarse
            pageLength:    50,
            orderCellsTop: true,
            initComplete: function () {
                const wrap = el.closest('.table-responsive');
                if (wrap) wrap.style.overflow = 'hidden';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.table:not([data-no-dt])').forEach(dtInit);
    });

    // API pública para inicialización manual desde vistas
    window.SGE = window.SGE || {};
    window.SGE.dtInit = dtInit;
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

{{-- ── ZuraEdu Realtime — Echo + Reverb ──────────────────────────────────── --}}
@auth
<script>
window._REVERB_KEY    = '{{ config("broadcasting.connections.reverb.key") }}';
window._REVERB_HOST   = '{{ config("broadcasting.connections.reverb.options.host") }}';
window._REVERB_PORT   = {{ config("broadcasting.connections.reverb.options.port", 8080) }};
window._REVERB_SCHEME = '{{ config("broadcasting.connections.reverb.options.scheme", "http") }}';
window._SGE_USER_ID   = {{ auth()->id() }};
window._SGE_ROL       = '{{ auth()->user()->roles->first()?->name ?? "" }}';
window._SGE_TENANT_ID = {{ tenant_id() ?? 'null' }};
window._SGE_GRUPO_IDS = [];
window._SGE_CLASE_IDS = [];
window._SGE_DEBUG     = {{ config('app.debug') ? 'true' : 'false' }};
</script>
@stack('realtime-data')
@vite('resources/js/echo.js')
<script>
// Actualizar UI global cuando llega DashboardActualizado
window.addEventListener('sge:dashboard-updated', function(e) {
    const data = e.detail;

    if (data.tipo === 'usuario_aprobado') {
        const badge = document.getElementById('admin-pending-badge');
        if (badge) {
            const n = data.datos?.usuarios_pendientes ?? 0;
            badge.textContent   = n;
            badge.style.display = n > 0 ? '' : 'none';
        }
    }

    if (data.tipo === 'nueva_matricula') {
        // Pulsa el botón Actualizar en el dashboard si estamos en esa página
        const btn = document.getElementById('btnRefreshStats');
        if (btn && !btn.disabled) btn.click();
    }
});
</script>
@endauth

@include('partials.pwa-install-prompt')

@auth
@php
    $__zuraAdmin = auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo','SuperAdmin']);
@endphp
@if(false) {{-- widget removido; ZuraAI disponible en /admin/asistente --}}
<style>
#zura-fab{position:fixed;bottom:28px;right:28px;z-index:9999;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;color:#fff;font-size:24px;box-shadow:0 4px 20px rgba(79,70,229,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s}
#zura-fab:hover{transform:scale(1.1)}
#zura-panel{position:fixed;bottom:96px;right:28px;z-index:9998;width:400px;max-width:calc(100vw - 40px);background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;border:1px solid #e5e7eb}
#zura-panel.open{display:flex}
#zura-header{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;padding:14px 18px;display:flex;align-items:center;gap:10px}
#zura-header .zura-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:18px}
#zura-header .zura-info{flex:1}
#zura-header .zura-info strong{display:block;font-size:.95rem}
#zura-header .zura-info small{opacity:.85;font-size:.75rem}
#zura-close{background:none;border:none;color:#fff;font-size:20px;cursor:pointer;opacity:.8;line-height:1}
#zura-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;max-height:380px;min-height:200px}
.zmsg{max-width:85%;padding:9px 13px;border-radius:12px;font-size:.875rem;line-height:1.5;word-break:break-word}
.zmsg.bot{background:#f3f4f6;color:#111;border-bottom-left-radius:4px;align-self:flex-start}
.zmsg.usr{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-bottom-right-radius:4px;align-self:flex-end}
.zmsg h1,.zmsg h2,.zmsg h3{font-size:1em;font-weight:700;margin:6px 0 2px}
.zmsg ul,.zmsg ol{padding-left:18px;margin:4px 0}
.zmsg li{margin:2px 0}
.zmsg code{background:rgba(0,0,0,.08);padding:1px 5px;border-radius:4px;font-family:monospace;font-size:.82em}
.zmsg pre code{display:block;padding:8px;white-space:pre-wrap}
.zmsg strong{font-weight:700}
#zura-chips{padding:8px 12px 4px;display:flex;flex-wrap:wrap;gap:6px}
.zchip{background:#ede9fe;color:#5b21b6;border:none;border-radius:20px;padding:4px 12px;font-size:.78rem;cursor:pointer;transition:background .15s}
.zchip:hover{background:#ddd6fe}
#zura-footer{padding:10px 12px;border-top:1px solid #f3f4f6;display:flex;gap:8px;align-items:flex-end}
#zura-input{flex:1;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:.875rem;resize:none;outline:none;max-height:120px;line-height:1.4;font-family:inherit}
#zura-input:focus{border-color:#7c3aed}
#zura-send{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:10px;padding:8px 14px;cursor:pointer;font-size:18px;flex-shrink:0;transition:opacity .15s}
#zura-send:disabled{opacity:.5;cursor:not-allowed}
.ztyping{display:flex;gap:4px;padding:4px 0}
.ztyping span{width:7px;height:7px;background:#9ca3af;border-radius:50%;animation:zdot 1.2s infinite}
.ztyping span:nth-child(2){animation-delay:.2s}
.ztyping span:nth-child(3){animation-delay:.4s}
@keyframes zdot{0%,80%,100%{transform:scale(.7);opacity:.5}40%{transform:scale(1);opacity:1}}
</style>

<button id="zura-fab" title="ZuraAI — Asistente Institucional"><i class="bi bi-stars"></i></button>

<div id="zura-panel">
    <div id="zura-header">
        <div class="zura-avatar"><i class="bi bi-stars"></i></div>
        <div class="zura-info"><strong>ZuraAI</strong><small>Asistente Institucional</small></div>
        <button id="zura-close" title="Cerrar">&times;</button>
    </div>
    <div id="zura-msgs"></div>
    <div id="zura-chips">
        <button class="zchip">Interpretar rendimiento académico</button>
        <button class="zchip">Redactar circular oficial</button>
        <button class="zchip">Analizar estadísticas del período</button>
        <button class="zchip">Preparar informe SIGERD/MINERD</button>
    </div>
    <div id="zura-footer">
        <textarea id="zura-input" rows="1" placeholder="Escribe tu consulta institucional…"></textarea>
        <button id="zura-send"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
(function(){
    const fab    = document.getElementById('zura-fab');
    const panel  = document.getElementById('zura-panel');
    const close  = document.getElementById('zura-close');
    const msgs   = document.getElementById('zura-msgs');
    const input  = document.getElementById('zura-input');
    const send   = document.getElementById('zura-send');
    const chips  = document.querySelectorAll('.zchip');
    const CHAT_URL = '{{ route("admin.asistente.chat") }}';
    const CSRF     = '{{ csrf_token() }}';
    let history    = [];
    let streaming  = false;

    const welcome = 'Hola, soy <strong>ZuraAI</strong>. Estoy aquí para asistirte en la gestión institucional: reportes, estadísticas, documentos oficiales y más. ¿En qué te ayudo hoy?';
    addMsg('bot', welcome);

    fab.addEventListener('click', () => { panel.classList.toggle('open'); if(panel.classList.contains('open')) input.focus(); });
    close.addEventListener('click', () => panel.classList.remove('open'));

    chips.forEach(c => c.addEventListener('click', () => { if(streaming) return; input.value = c.textContent.trim(); input.focus(); sendMsg(); }));

    input.addEventListener('keydown', e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); sendMsg(); }});
    input.addEventListener('input', () => { input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,120)+'px'; });
    send.addEventListener('click', sendMsg);

    function addMsg(role, html){
        const d = document.createElement('div');
        d.className = 'zmsg ' + (role==='bot' ? 'bot' : 'usr');
        d.innerHTML = html;
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    }

    function renderMd(t){
        return t
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/```[\w]*\n?([\s\S]*?)```/g,'<pre><code>$1</code></pre>')
            .replace(/`([^`]+)`/g,'<code>$1</code>')
            .replace(/^### (.+)$/gm,'<h3>$1</h3>')
            .replace(/^## (.+)$/gm,'<h2>$1</h2>')
            .replace(/^# (.+)$/gm,'<h1>$1</h1>')
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
            .replace(/^\* (.+)$/gm,'<li>$1</li>')
            .replace(/^- (.+)$/gm,'<li>$1</li>')
            .replace(/^\d+\. (.+)$/gm,'<li>$1</li>')
            .replace(/(<li>[\s\S]*?<\/li>)/g,'<ul>$1</ul>')
            .replace(/\n/g,'<br>');
    }

    async function sendMsg(){
        const text = input.value.trim();
        if(!text || streaming) return;
        input.value = ''; input.style.height = 'auto';
        addMsg('usr', text.replace(/</g,'&lt;'));
        streaming = true; send.disabled = true;

        const typing = addMsg('bot','<div class="ztyping"><span></span><span></span><span></span></div>');

        try {
            const res = await fetch(CHAT_URL, {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'text/event-stream'},
                body: JSON.stringify({message: text, history})
            });
            if(!res.ok){ typing.innerHTML='<em>Error al conectar con ZuraAI.</em>'; return; }

            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf = '', out = '';
            typing.innerHTML = '';

            while(true){
                const {done, value} = await reader.read();
                if(done) break;
                buf += dec.decode(value, {stream:true});
                const lines = buf.split('\n');
                buf = lines.pop();
                for(const line of lines){
                    if(!line.startsWith('data:')) continue;
                    try {
                        const ev = JSON.parse(line.slice(5).trim());
                        if(ev.type==='content_block_delta' && ev.delta?.type==='text_delta'){
                            out += ev.delta.text;
                            typing.innerHTML = renderMd(out);
                            msgs.scrollTop = msgs.scrollHeight;
                        }
                        if(ev.type==='message_stop') break;
                    } catch(e){}
                }
            }
            if(out) history.push({role:'user',content:text},{role:'assistant',content:out});
            if(history.length > 20) history = history.slice(-20);
        } catch(e){
            typing.innerHTML = '<em>Error de conexión. Intenta de nuevo.</em>';
        } finally {
            streaming = false; send.disabled = false; input.focus();
        }
    }
})();
</script>
@endif
@endauth
</body>
</html>
