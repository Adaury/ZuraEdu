<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $__env->yieldContent('page-title') ?: $__env->yieldContent('title', 'Portal') }} — SGE</title>

    <link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Aplicar tema antes de renderizar para evitar flash --}}
    <script>
    (function(){
        var t = localStorage.getItem('sge-theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);
    })();
    </script>

    <style>
    /* ══════════════════════════════════════════════════════
       PORTAL LAYOUT — mobile-first, educativo
    ══════════════════════════════════════════════════════ */
    :root {
        --prt-bg:      #f0f4f8;
        --prt-primary: #2563eb;
        --prt-dark:    #1e3a5f;
        --prt-card:    #ffffff;
        --prt-border:  #e2e8f0;
        --prt-text:    #1e293b;
        --prt-muted:   #64748b;
        --role-grad1:  #1e1b4b;
        --role-grad2:  #2563eb;
        --role-glow:   rgba(37,99,235,.4);
    }
    /* ── Docente → violeta ── */
    body.role-docente {
        --prt-primary: #7c3aed;
        --prt-dark:    #4c1d95;
        --role-grad1:  #2e1065;
        --role-grad2:  #7c3aed;
        --role-glow:   rgba(124,58,237,.4);
    }
    /* ── Padre → teal ── */
    body.role-padre {
        --prt-primary: #0f766e;
        --prt-dark:    #134e4a;
        --role-grad1:  #042f2e;
        --role-grad2:  #0f766e;
        --role-glow:   rgba(15,118,110,.4);
    }
    /* ── Estudiante → azul océano ── */
    body.role-estudiante {
        --prt-primary: #2563eb;
        --prt-dark:    #1e3a6e;
        --role-grad1:  #0f172a;
        --role-grad2:  #2563eb;
        --role-glow:   rgba(37,99,235,.4);
    }

    /* ── Dark mode variables ── */
    [data-theme="dark"] {
        --prt-bg:     #0f172a;
        --prt-card:   #1e293b;
        --prt-border: #334155;
        --prt-text:   #e2e8f0;
        --prt-muted:  #94a3b8;
    }
    [data-theme="dark"] body { background: var(--prt-bg); color: var(--prt-text); }
    [data-theme="dark"] .prt-card  { background: var(--prt-card) !important; border-color: var(--prt-border) !important; }
    [data-theme="dark"] .prt-card-header { border-color: var(--prt-border) !important; }
    [data-theme="dark"] .prt-sidebar { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .prt-sidebar-section { color: #64748b; }
    [data-theme="dark"] .prt-sidebar-link { color: #cbd5e1; }
    [data-theme="dark"] .prt-sidebar-link:hover { background: #334155; color: #f1f5f9; }
    [data-theme="dark"] .prt-sidebar-link.active { background: #1e40af; color: #fff; }
    [data-theme="dark"] .prt-bottom-nav { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .prt-nav-item { color: #94a3b8; }
    [data-theme="dark"] .prt-nav-item.active { color: var(--prt-primary); }
    [data-theme="dark"] .prt-stat { background: #1e293b; }
    [data-theme="dark"] .prt-stat-val { color: #f1f5f9; }
    [data-theme="dark"] .prt-stat-lbl { color: #94a3b8; }
    [data-theme="dark"] h1,[data-theme="dark"] h2,[data-theme="dark"] h3,
    [data-theme="dark"] h4,[data-theme="dark"] h5,[data-theme="dark"] h6 { color: #e2e8f0; }
    [data-theme="dark"] p   { color: #cbd5e1; }
    [data-theme="dark"] small, [data-theme="dark"] .small { color: #94a3b8; }
    [data-theme="dark"] table { color: #e2e8f0; }
    [data-theme="dark"] tr { border-color: #334155; }
    [data-theme="dark"] td, [data-theme="dark"] th { border-color: #334155 !important; }
    [data-theme="dark"] thead tr { background: #1e3a8a !important; }
    [data-theme="dark"] tbody tr:hover td { background: #1e293b !important; color: #f1f5f9 !important; }
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select,
    [data-theme="dark"] input[type="date"],
    [data-theme="dark"] input[type="text"],
    [data-theme="dark"] textarea {
        background: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important;
    }
    [data-theme="dark"] .form-control:focus { background: #1e293b !important; }
    [data-theme="dark"] label, [data-theme="dark"] .form-label { color: #cbd5e1 !important; }
    [data-theme="dark"] .btn-outline-primary { color: #93c5fd; border-color: #3b82f6; }
    [data-theme="dark"] .btn-outline-primary:hover { background: #1e40af; color: #fff; }
    [data-theme="dark"] .btn-outline-secondary { color: #94a3b8; border-color: #475569; }
    [data-theme="dark"] .btn-outline-secondary:hover { background: #334155; color: #e2e8f0; }
    [data-theme="dark"] .alert { border-color: #334155; }
    [data-theme="dark"] .prt-alert-success { background: #052e16 !important; border-color: #166534 !important; color: #4ade80 !important; }
    [data-theme="dark"] .prt-alert-warning { background: #1c1000 !important; border-color: #92400e !important; color: #fbbf24 !important; }
    [data-theme="dark"] .prt-alert-info    { background: #0c1a3a !important; border-color: #1e40af !important; color: #93c5fd !important; }
    [data-theme="dark"] .prt-alert-danger  { background: #1c0000 !important; border-color: #7f1d1d !important; color: #f87171 !important; }
    [data-theme="dark"] .notif-item { border-color: #334155 !important; }
    [data-theme="dark"] .notif-titulo { color: #f1f5f9 !important; }
    [data-theme="dark"] .notif-msg   { color: #94a3b8 !important; }
    [data-theme="dark"] .notif-time  { color: #64748b !important; }
    [data-theme="dark"] .sch-table th { background: #1e293b !important; color: #94a3b8 !important; }
    [data-theme="dark"] .sch-recreo td { background: #1c1a00 !important; color: #fbbf24 !important; }
    [data-theme="dark"] hr { border-color: #334155; }
    [data-theme="dark"] .badge.bg-light { background: #334155 !important; color: #e2e8f0 !important; }

    /* ── Dropdown menú usuario ── */
    [data-theme="dark"] .prt-dropdown { background: #1e293b; border-color: #334155; box-shadow: 0 8px 30px rgba(0,0,0,.4); }
    [data-theme="dark"] .prt-dropdown a,
    [data-theme="dark"] .prt-dropdown button { color: #cbd5e1; }
    [data-theme="dark"] .prt-dropdown a:hover,
    [data-theme="dark"] .prt-dropdown button:hover { background: #334155; color: #f1f5f9; }
    [data-theme="dark"] .prt-dropdown-divider { border-top-color: #334155; }

    /* ── Card header ── */
    [data-theme="dark"] .prt-card-header { background: #162032 !important; }

    /* ── Notificaciones sin leer ── */
    [data-theme="dark"] .notif-item.unread { background: #0d1f30 !important; }

    /* ── Horario — celdas y columna franja ── */
    [data-theme="dark"] .sch-table td { border-color: #334155 !important; }
    [data-theme="dark"] .sch-table td.franja-col { background: #1e293b !important; color: #94a3b8 !important; border-right-color: #475569 !important; }

    /* ── Toolbars grises en vistas (botones rápidos, footer forms) ── */
    [data-theme="dark"] .dm-toolbar { background: #162032 !important; border-color: #334155 !important; color: #94a3b8 !important; }

    /* ── Botón Volver ── */
    [data-theme="dark"] .btn-back { background: #1e293b !important; color: #cbd5e1 !important; }
    [data-theme="dark"] .btn-back:hover { background: #334155 !important; color: #f1f5f9 !important; }

    /* ── Filas de listas en portales ── */
    [data-theme="dark"] .dm-list-item { border-bottom-color: #334155 !important; }
    [data-theme="dark"] .dm-text-primary { color: #e2e8f0 !important; }
    [data-theme="dark"] .dm-text-muted { color: #64748b !important; }

    /* ── Stats grid de vistas (calificaciones) ── */
    [data-theme="dark"] .dm-stat-card { filter: brightness(.7) saturate(.8); }

    /* ── Alertas inline en vistas (success/error con inline style) ── */
    [data-theme="dark"] .prt-inline-success { background: #052e16 !important; border-color: #166534 !important; color: #4ade80 !important; }
    [data-theme="dark"] .prt-inline-error   { background: #1c0000 !important; border-color: #7f1d1d !important; color: #f87171 !important; }

    /* ── Thead de tablas en vistas ── */
    [data-theme="dark"] .dm-thead { background: #1e3a5f !important; border-bottom-color: #334155 !important; }
    [data-theme="dark"] .dm-thead th { color: #93c5fd !important; }

    /* ── Avatar circular en listas ── */
    [data-theme="dark"] .dm-avatar { background: #1e3a5f !important; color: #93c5fd !important; }

    /* ── Date input en asistencia ── */
    [data-theme="dark"] input[type="date"] { background: #0f172a !important; border-color: #334155 !important; color: #e2e8f0 !important; }

    /* ── Botones de estado en asistencia (sin seleccionar) ── */
    [data-theme="dark"] .est-btn-default { background: #1e293b !important; color: #64748b !important; }

    /* ── Filas de notas/asignaturas (background:#f8fafc) ── */
    [data-theme="dark"] .dm-note-row { background: #1a2640 !important; }

    /* ── Badge de promedio en card hijo ── */
    [data-theme="dark"] .dm-promedio-badge { background: #1e293b !important; border-color: #334155 !important; }

    /* ── Progress bar de asistencia ── */
    [data-theme="dark"] .dm-progress-bg { background: #334155 !important; }

    /* ── Badges de asistencia (presentes/ausentes/tardanzas) ── */
    [data-theme="dark"] .dm-att-present { background: #052e16 !important; }
    [data-theme="dark"] .dm-att-absent  { background: #1c0000 !important; }
    [data-theme="dark"] .dm-att-late    { background: #1c1000 !important; }

    /* ── Observaciones (compartido entre docente/padre) ── */
    [data-theme="dark"] .obs-grupo-header { background: #162032 !important; border-bottom-color: #334155 !important; color: #94a3b8 !important; }
    [data-theme="dark"] .obs-item-row { border-bottom-color: #334155 !important; }
    [data-theme="dark"] .obs-item-text { color: #cbd5e1 !important; }
    [data-theme="dark"] .obs-count-badge { background: #1e293b !important; color: #94a3b8 !important; }

    /* ── Cobertura de inline styles hardcodeados ── */
    [data-theme="dark"] [style*="color:#1e293b"],
    [data-theme="dark"] [style*="color: #1e293b"] { color: #e2e8f0 !important; }
    [data-theme="dark"] [style*="color:#374151"],
    [data-theme="dark"] [style*="color: #374151"] { color: #cbd5e1 !important; }
    [data-theme="dark"] [style*="color:#64748b"],
    [data-theme="dark"] [style*="color: #64748b"] { color: #64748b !important; }
    [data-theme="dark"] [style*="color:#9ca3af"],
    [data-theme="dark"] [style*="color: #9ca3af"] { color: #475569 !important; }
    [data-theme="dark"] [style*="background:#f8fafc"],
    [data-theme="dark"] [style*="background:#f8faff"],
    [data-theme="dark"] [style*="background:#f1f5f9"],
    [data-theme="dark"] [style*="background:#fafbff"] { background: #162032 !important; }
    [data-theme="dark"] [style*="background:#fff;"],
    [data-theme="dark"] [style*="background: #fff;"],
    [data-theme="dark"] [style*="background:#ffffff"] { background: #1e293b !important; }
    [data-theme="dark"] [style*="border-bottom:1px solid #f1f5f9"],
    [data-theme="dark"] [style*="border-bottom: 1px solid #f1f5f9"] { border-bottom-color: #334155 !important; }
    [data-theme="dark"] [style*="border:1px solid #e2e8f0"],
    [data-theme="dark"] [style*="border: 1px solid #e2e8f0"] { border-color: #334155 !important; }
    [data-theme="dark"] [style*="border-color:#e2e8f0"] { border-color: #334155 !important; }

    /* ── Dark mode toggle button ── */
    .prt-dark-toggle {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.2);
        color: #fff;
        border-radius: 8px;
        padding: .3rem .45rem;
        cursor: pointer;
        font-size: .95rem;
        line-height: 1;
        transition: background .18s;
        flex-shrink: 0;
    }
    .prt-dark-toggle:hover { background: rgba(255,255,255,.22); }

    /* ── NProgress barra de carga ── */
    #nprogress .bar { background: #60a5fa; height: 3px; }
    #nprogress .peg { box-shadow: 0 0 10px #60a5fa, 0 0 5px #60a5fa; }
    #nprogress .spinner-icon {
        border-top-color: #60a5fa;
        border-left-color: #60a5fa;
    }

    /* ── Transición suave entre páginas ── */
    .prt-main { animation: prtFadeIn .22s ease; }
    @keyframes prtFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Quick access cards (dashboard docente) ── */
    .prt-quick-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .75rem;
        margin-bottom: 1rem;
    }
    @media (min-width: 480px) { .prt-quick-grid { grid-template-columns: repeat(4, 1fr); } }
    .prt-quick-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: 1.1rem .75rem;
        background: var(--prt-card);
        border: 1.5px solid var(--prt-border);
        border-radius: 14px;
        text-decoration: none;
        transition: all .2s cubic-bezier(.34,1.56,.64,1);
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }
    .prt-quick-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        border-color: var(--c, #2563eb);
        text-decoration: none;
    }
    .prt-quick-card i {
        font-size: 1.6rem;
        color: var(--c, #2563eb);
        width: 44px; height: 44px;
        background: rgba(99,102,241,.12); /* fallback sin color-mix() */
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
    }
    .prt-quick-card span {
        font-size: .78rem;
        font-weight: 700;
        color: var(--prt-text);
        text-align: center;
    }
    [data-theme="dark"] .prt-quick-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .prt-quick-card:hover { background: #263248; }
    [data-theme="dark"] .prt-quick-card i { background: rgba(255,255,255,.08); }
    [data-theme="dark"] .prt-quick-card span { color: #e2e8f0; }
    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', 'Segoe UI', sans-serif;
        background: var(--prt-bg);
        color: var(--prt-text);
        min-height: 100vh;
        margin: 0;
        overflow-x: hidden; /* evita scroll horizontal en móvil */
    }

    /* ── Topbar ── */
    .prt-topbar {
        background: linear-gradient(135deg, var(--role-grad1) 0%, var(--role-grad2) 100%);
        padding: .75rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 12px rgba(0,0,0,.18);
    }
    .prt-logo {
        width: 36px; height: 36px;
        background: rgba(255,255,255,.18);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 900; color: #fff; font-size: .85rem;
        flex-shrink: 0;
        text-decoration: none;
    }
    .prt-brand { color: #fff; font-weight: 700; font-size: .95rem; line-height: 1.1; }
    .prt-brand-sub { color: rgba(255,255,255,.65); font-size: .7rem; }
    .prt-topbar-right { margin-left: auto; display: flex; align-items: center; gap: .65rem; }

    /* Campana de notificaciones */
    .prt-bell {
        position: relative;
        width: 36px; height: 36px;
        background: rgba(255,255,255,.12);
        border-radius: 50%;
        border: none;
        color: #fff;
        font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: background .18s;
        text-decoration: none;
    }
    .prt-bell:hover { background: rgba(255,255,255,.22); }
    .prt-badge {
        position: absolute;
        top: -3px; right: -3px;
        background: #ef4444;
        color: #fff;
        border-radius: 10px;
        font-size: .58rem;
        font-weight: 700;
        padding: .1rem .3rem;
        min-width: 17px;
        text-align: center;
        line-height: 1.4;
    }

    /* Menú usuario */
    .prt-user-btn {
        display: flex;
        align-items: center;
        gap: .5rem;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 20px;
        padding: .3rem .75rem .3rem .4rem;
        cursor: pointer;
        color: #fff;
        font-size: .78rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .18s;
        position: relative;
    }
    .prt-user-btn:hover { background: rgba(255,255,255,.2); color: #fff; }
    .prt-user-avatar {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: rgba(255,255,255,.25);
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 800;
        flex-shrink: 0;
    }
    .prt-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: #fff;
        border: 1px solid var(--prt-border);
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,.12);
        min-width: 180px;
        z-index: 200;
        padding: .4rem 0;
        display: none;
    }
    .prt-dropdown.open { display: block; }
    .prt-dropdown a, .prt-dropdown button {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .55rem 1rem;
        font-size: .82rem;
        color: var(--prt-text);
        text-decoration: none;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        transition: background .15s;
    }
    .prt-dropdown a:hover, .prt-dropdown button:hover { background: #f1f5f9; }
    .prt-dropdown-divider { border-top: 1px solid var(--prt-border); margin: .3rem 0; }

    /* ── Bottom nav (móvil) ── */
    .prt-bottom-nav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: #fff;
        border-top: 1px solid var(--prt-border);
        display: flex;
        z-index: 100;
        box-shadow: 0 -2px 12px rgba(0,0,0,.06);
    }
    .prt-nav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: .6rem .25rem .5rem;
        font-size: .62rem;
        color: var(--prt-muted);
        text-decoration: none;
        transition: color .15s;
        gap: .15rem;
    }
    .prt-nav-item i { font-size: 1.2rem; }
    .prt-nav-item.active { color: var(--prt-primary); }
    .prt-nav-item.active i { color: var(--prt-primary); }

    /* ── Desktop sidebar ── */
    .prt-sidebar {
        width: 230px;
        background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
        border-right: 1px solid rgba(255,255,255,.06);
        min-height: calc(100vh - 56px);
        padding: 1rem .6rem;
        flex-shrink: 0;
        display: none;
        box-shadow: 4px 0 20px rgba(0,0,0,.2);
    }
    .prt-sidebar-link {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .5rem .8rem;
        border-radius: 10px;
        font-size: .82rem;
        color: #94a3b8;
        text-decoration: none;
        transition: all .15s;
        margin-bottom: 2px;
        font-weight: 500;
    }
    .prt-sidebar-link:hover { background: rgba(255,255,255,.07); color: #e2e8f0; }
    .prt-sidebar-link.active {
        background: var(--prt-primary);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 4px 12px var(--role-glow);
    }
    .prt-sidebar-link i { font-size: 1rem; flex-shrink: 0; opacity: .8; }
    .prt-sidebar-link.active i { opacity: 1; }
    .prt-sidebar-section {
        font-size: .6rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: .12em;
        padding: .75rem .8rem .2rem;
    }

    /* ── Contenido principal ── */
    .prt-main {
        flex: 1;
        min-width: 0;          /* evita overflow horizontal en flexbox */
        padding: 1.25rem 1rem 5rem; /* bottom: espacio para bottom nav */
        max-width: 900px;
        width: 100%;
    }

    /* ── Layout wrapper ── */
    .prt-body {
        display: flex;
        width: 100%;
        min-height: calc(100vh - 56px);
    }

    /* ── Cards ── */
    .prt-card {
        background: var(--prt-card);
        border: 1px solid var(--prt-border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 1rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }
    .prt-card-header {
        padding: .85rem 1.1rem;
        border-bottom: 1px solid var(--prt-border);
        display: flex;
        align-items: center;
        gap: .6rem;
        background: #fafbff;
    }
    .prt-card-header h3 {
        font-size: .9rem;
        font-weight: 700;
        color: var(--prt-text);
        margin: 0;
    }
    .prt-card-body { padding: 1rem 1.1rem; }

    /* ── Stats grid ── */
    .prt-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .75rem;
        margin-bottom: 1rem;
    }
    .prt-stat {
        background: var(--prt-card);
        border: 1px solid var(--prt-border);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .prt-stat-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .prt-stat-val { font-size: 1.4rem; font-weight: 900; color: var(--prt-text); line-height: 1; }
    .prt-stat-lbl { font-size: .68rem; color: var(--prt-muted); text-transform: uppercase; letter-spacing: .04em; }

    /* ── Badge de materia/nota ── */
    .nota-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 28px;
        border-radius: 8px;
        font-size: .8rem;
        font-weight: 800;
        padding: 0 .5rem;
    }
    .nota-a { background: #dcfce7; color: #15803d; }
    .nota-b { background: #dbeafe; color: #1d4ed8; }
    .nota-c { background: #fef9c3; color: #854d0e; }
    .nota-d { background: #ffedd5; color: #c2410c; }
    .nota-f { background: #fee2e2; color: #991b1b; }

    /* ── Alerta portal ── */
    .prt-alert {
        border-radius: 10px;
        padding: .7rem 1rem;
        font-size: .82rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: .6rem;
    }
    .prt-alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .prt-alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    .prt-alert-info    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .prt-alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

    /* ── Notificaciones panel ── */
    .notif-list { list-style: none; padding: 0; margin: 0; }
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: .7rem;
        padding: .75rem 1rem;
        border-bottom: 1px solid var(--prt-border);
        transition: background .15s;
    }
    .notif-item:last-child { border-bottom: none; }
    .notif-item.unread { background: #f0f9ff; }
    .notif-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 5px;
    }
    .notif-icon {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .notif-titulo { font-size: .82rem; font-weight: 600; color: var(--prt-text); margin-bottom: .1rem; }
    .notif-msg    { font-size: .76rem; color: var(--prt-muted); line-height: 1.4; }
    .notif-time   { font-size: .67rem; color: #94a3b8; margin-top: .2rem; }

    /* ── Horario mini ── */
    .sch-table { width: 100%; border-collapse: collapse; font-size: .76rem; }
    .sch-table th {
        background: var(--prt-dark);
        color: #fff;
        padding: .4rem .3rem;
        text-align: center;
        font-size: .68rem;
        font-weight: 700;
    }
    .sch-table td {
        border: 1px solid #f1f5f9;
        padding: 0;
        min-height: 54px;
        vertical-align: top;
    }
    .sch-table td.franja-col {
        background: #f8fafc;
        text-align: center;
        padding: .35rem .2rem;
        border-right: 2px solid var(--prt-border);
        font-size: .64rem;
        font-weight: 700;
        color: #374151;
        width: 52px;
    }
    .sch-cell {
        padding: .3rem .35rem;
        height: 100%;
        border-radius: 5px;
        margin: 2px;
        font-size: .7rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
    }
    .sch-recreo td { background: #fef9ec; text-align: center; padding: .3rem; font-size: .7rem; font-weight: 600; color: #92400e; }

    /* ── Responsive helpers ── */
    /* Ocultar texto de nombre usuario en topbar muy estrecho */
    @media (max-width: 400px) {
        .prt-brand-sub { display: none; }
        .prt-topbar { gap: .6rem; }
    }

    /* Asistencia: fila de estudiante + botones */
    .est-row-inner {
        display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
    }
    .est-row-buttons {
        display: flex; gap: .4rem; flex-shrink: 0;
    }
    @media (max-width: 480px) {
        .est-row-inner { gap: .5rem; }
        .est-row-buttons { width: 100%; justify-content: flex-end; }
        .est-row-avatar { display: none; }
    }

    /* Stats grid responsivo (override para views que usan 4 cols) */
    .cal-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .6rem;
        margin-bottom: 1rem;
    }
    @media (min-width: 480px) {
        .cal-stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    @media (min-width: 768px) {
        .prt-sidebar { display: block; }
        .prt-bottom-nav { display: none; }
        .prt-main { padding: 1.5rem 1.75rem; max-width: none; }
        .prt-stats { grid-template-columns: repeat(4, 1fr); }
        .cal-stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    @media (min-width: 992px) {
        .prt-main { padding: 1.75rem 2rem; }
    }
    </style>
    @stack('styles')

    {{-- PWA --}}
    <link rel="manifest" href="/pwa/manifest.json">
    @php
        $__pwaColor = app()->bound('tenant') ? (app('tenant')->color_primario ?? '#1d4ed8') : '#1d4ed8';
        $__pwaTid   = tenant_id() ?? 0;
        $__pwaName  = app()->bound('tenant') ? (app('tenant')->nombre_institucion ?? config('app.name')) : config('app.name');
    @endphp
    <meta name="theme-color" content="{{ $__pwaColor }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $__pwaName }}">
    <link rel="apple-touch-icon" href="/pwa/icon/192?tid={{ $__pwaTid }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(() => {});
            });
        }
    </script>
</head>
<body class="{{ auth()->check() ? (auth()->user()->hasRole('Docente') ? 'role-docente' : (auth()->user()->hasRole('Representante') ? 'role-padre' : (auth()->user()->hasRole('Estudiante') ? 'role-estudiante' : ''))) : '' }}">

{{-- ── Banner Modo Demo ──────────────────────────────────────────────── --}}
@if(session('demo_mode'))
<div style="background:linear-gradient(90deg,#92400e,#b45309);color:#fff;text-align:center;padding:.5rem 1rem;font-size:.77rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:.65rem;flex-wrap:wrap;z-index:999;position:relative;box-shadow:0 2px 8px rgba(0,0,0,.2);">
    <span style="display:flex;align-items:center;gap:.35rem;">
        <i class="bi bi-shield-exclamation"></i>
        <strong>MODO DEMO</strong> — Datos de ejemplo · Cambios críticos bloqueados
    </span>
    @if($errors->has('demo_mode'))
    <span style="background:rgba(0,0,0,.2);border-radius:5px;padding:.15rem .5rem;font-size:.72rem;">🔒 {{ $errors->first('demo_mode') }}</span>
    @endif
    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
        @csrf
        <button type="submit" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:5px;padding:.2rem .65rem;font-size:.7rem;font-weight:700;cursor:pointer;">
            <i class="bi bi-box-arrow-right me-1"></i>Salir
        </button>
    </form>
</div>
@endif

{{-- ── Topbar ────────────────────────────────────────────────────────── --}}
@php
$sysAbbr = \Illuminate\Support\Facades\Cache::remember('system_abbr',600,fn()=>\Illuminate\Support\Facades\DB::table('system_settings')->where('key','system_abbr')->value('value')) ?? 'SGE';
$sysName = \Illuminate\Support\Facades\Cache::remember('system_name',600,fn()=>\Illuminate\Support\Facades\DB::table('system_settings')->where('key','system_name')->value('value')) ?? config('app.name','SGE');
@endphp
<nav class="prt-topbar">
    <a href="{{ route('admin.dashboard') }}" class="prt-logo" style="background:rgba(255,255,255,.18);font-size:.78rem;">{{ $sysAbbr }}</a>
    <div>
        <div class="prt-brand">@yield('portal-name', 'Portal')</div>
        <div class="prt-brand-sub">{{ $sysName }}</div>
    </div>

    <div class="prt-topbar-right">
        {{-- Toggle dark mode --}}
        <button class="prt-dark-toggle" id="prtDarkToggle" title="Alternar modo oscuro/claro" type="button">
            <i class="bi bi-moon-stars-fill" id="prtDarkIcon"></i>
        </button>

        {{-- Campana notificaciones --}}
        @php $totalNoLeidas = $totalNoLeidas ?? 0; @endphp
        <a href="#notificaciones" class="prt-bell" title="Notificaciones" id="btnBell">
            <i class="bi bi-bell-fill"></i>
            @if($totalNoLeidas > 0)
                <span class="prt-badge">{{ $totalNoLeidas > 9 ? '9+' : $totalNoLeidas }}</span>
            @endif
        </a>

        {{-- Usuario --}}
        <div style="position:relative;">
            <button class="prt-user-btn" onclick="toggleDropdown()" id="userBtn" type="button">
                @if(auth()->user()->photo_url)
                    <img src="{{ auth()->user()->photo_url }}" alt="Foto" class="prt-user-avatar" style="object-fit:cover;">
                @else
                    <div class="prt-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                @endif
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                <i class="bi bi-chevron-down" style="font-size:.6rem;"></i>
            </button>
            <div class="prt-dropdown" id="userDropdown">
                <a href="{{ route('perfil.show') }}"><i class="bi bi-person-circle" style="color:#6366f1;"></i>Mi perfil</a>
                <div class="prt-dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</nav>

{{-- ── Body (sidebar + main) ───────────────────────────────────────────── --}}
<div class="prt-body">

    {{-- Sidebar desktop --}}
    <aside class="prt-sidebar">
        @yield('sidebar')
    </aside>

    {{-- Contenido --}}
    <main class="prt-main">

        {{-- Alertas de sesión --}}
        @if(session('success'))
            <div class="prt-alert prt-alert-success mb-3">
                <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="prt-alert prt-alert-warning mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>{{ session('warning') }}
            </div>
        @endif
        @if(session('info'))
            <div class="prt-alert prt-alert-info mb-3">
                <i class="bi bi-info-circle-fill"></i>{{ session('info') }}
            </div>
        @endif
        @if(session('error'))
            <div class="prt-alert prt-alert-danger mb-3">
                <i class="bi bi-exclamation-octagon-fill"></i>{{ session('error') }}
            </div>
        @endif
        @if($errors->any() && !$errors->has('email'))
            <div class="prt-alert prt-alert-danger mb-3">
                <i class="bi bi-exclamation-octagon-fill"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- ── Bottom nav móvil ─────────────────────────────────────────────────── --}}
<nav class="prt-bottom-nav">
    @yield('bottom-nav')
</nav>

{{-- NProgress — barra de carga entre páginas --}}
<script>
/* NProgress minimal inline (sin dependencia externa) */
var NProgress=(function(){var s='#nprogress',n=null,i=0,t=null;function c(e){var o=document.getElementById('nprogress-bar');if(!o){o=document.createElement('div');o.id='nprogress-bar';o.style.cssText='position:fixed;top:0;left:0;height:3px;background:#60a5fa;z-index:99999;transition:width .2s ease,opacity .4s ease;width:0;';document.body.appendChild(o);}return o;}function start(){clearTimeout(t);var b=c();b.style.opacity='1';b.style.width=(i=10)+'%';n=setInterval(function(){if(i<90){i+=i>=80?1:i>=50?2:5;b.style.width=i+'%';}},200);}function done(){clearInterval(n);var b=c();b.style.width='100%';t=setTimeout(function(){b.style.opacity='0';setTimeout(function(){b.style.width='0';},400);},200);}return{start:start,done:done};})();

// Iniciar barra al navegar
document.addEventListener('click', function(e) {
    var a = e.target.closest('a[href]');
    if (a && !a.target && !a.href.startsWith('#') && !a.href.startsWith('javascript') &&
        a.href.indexOf(window.location.origin) === 0) {
        NProgress.start();
    }
});
document.addEventListener('submit', function(e) {
    if (e.target.method !== 'get') NProgress.start();
});
window.addEventListener('pageshow', function() { NProgress.done(); });
</script>

<script>
// ── Dark mode toggle ──────────────────────────────────────────────────
(function() {
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        var icon = document.getElementById('prtDarkIcon');
        if (icon) icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    // Aplicar tema guardado
    applyTheme(localStorage.getItem('sge-theme') || 'light');

    document.getElementById('prtDarkToggle')?.addEventListener('click', function() {
        var current = document.documentElement.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('sge-theme', next);
        applyTheme(next);
    });
})();

// ── Dropdown usuario ──────────────────────────────────────────────────
function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('userBtn')?.contains(e.target)) {
        document.getElementById('userDropdown')?.classList.remove('open');
    }
});

// ── Campana → scroll notificaciones ──────────────────────────────────
document.getElementById('btnBell')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('notificaciones')?.scrollIntoView({ behavior: 'smooth' });
});
</script>
@stack('scripts')

{{-- Polling de notificaciones cada 45 segundos --}}
<script>
(function() {
    const CONTEO_URL = "{{ route('notificaciones.conteo') }}";
    let lastCount    = {{ $totalNoLeidas ?? 0 }};

    function actualizarBadge(count) {
        const badge = document.querySelector('.prt-bell .prt-badge');
        const btn   = document.getElementById('btnBell');
        if (! btn) return;

        if (count > 0) {
            if (badge) {
                badge.textContent = count > 9 ? '9+' : count;
            } else {
                const span = document.createElement('span');
                span.className  = 'prt-badge';
                span.textContent = count > 9 ? '9+' : count;
                btn.appendChild(span);
            }
            // Pulso visual si llegó nueva notificación
            if (count > lastCount) {
                btn.style.animation = 'none';
                btn.offsetHeight;   // reflow
                btn.style.animation = 'bellPulse .5s ease 3';
            }
        } else {
            badge?.remove();
        }
        lastCount = count;
    }

    async function pollNotificaciones() {
        try {
            const res  = await fetch(CONTEO_URL, { headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                const { count } = await res.json();
                actualizarBadge(count);
            }
        } catch (_) {}
    }

    // Polling como fallback — Echo lo pausa cuando conecta a Reverb
    window._notifPollingInterval = setInterval(pollNotificaciones, 45000);

    // Cuando Echo actualiza el badge, sincronizar el contador interno del polling
    window.addEventListener('sge:notification-new', (e) => {
        lastCount = (lastCount || 0) + (e.detail?.delta ?? 1);
    });
})();
</script>

<style>
@keyframes bellPulse {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.18); }
}
</style>

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
@endauth

<div id="sge-toast-container" aria-live="polite" aria-atomic="false"
     style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;max-width:340px;"></div>

@include('partials.pwa-install-prompt')

{{-- ── ZuraAI Widget (Portal Docente y Estudiante) ─────────────────────── --}}
@auth
@php
    $__zuraRole = auth()->user()->hasRole('Docente') ? 'docente'
        : (auth()->user()->hasRole('Estudiante')     ? 'estudiante'
        : (auth()->user()->hasRole('Representante')  ? 'padre' : null));
@endphp
@if($__zuraRole)
<style>
#zura-ai-btn {
    position: fixed;
    bottom: 5.5rem;
    right: 1.25rem;
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #6d28d9, #4f46e5);
    border: none; border-radius: 50%;
    color: #fff; font-size: 1.3rem;
    cursor: pointer; z-index: 4000;
    box-shadow: 0 4px 18px rgba(109,40,217,.45);
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s, box-shadow .2s;
}
#zura-ai-btn:hover { transform: scale(1.08); box-shadow: 0 6px 22px rgba(109,40,217,.55); }
#zura-ai-btn .zura-badge {
    position: absolute; top: -3px; right: -3px;
    background: #10b981; border-radius: 50%; width: 14px; height: 14px;
    border: 2px solid #fff;
}
#zura-ai-panel {
    display: none;
    position: fixed;
    bottom: 5.5rem; right: 1.25rem;
    width: 370px; max-width: calc(100vw - 1.5rem);
    height: 530px; max-height: calc(100vh - 6rem);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
    z-index: 4001;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
#zura-ai-panel.open { display: flex; }
[data-theme="dark"] #zura-ai-panel { background: #1e293b; border-color: #334155; }
.zura-header {
    background: linear-gradient(135deg, #6d28d9, #4f46e5);
    padding: .75rem 1rem;
    display: flex; align-items: center; gap: .6rem;
    flex-shrink: 0;
}
.zura-header-icon {
    width: 32px; height: 32px;
    background: rgba(255,255,255,.2);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem; color: #fff; flex-shrink: 0;
}
.zura-header-title { color: #fff; font-weight: 700; font-size: .9rem; line-height: 1.1; }
.zura-header-sub { color: rgba(255,255,255,.7); font-size: .68rem; }
.zura-header-close {
    margin-left: auto; background: none; border: none;
    color: rgba(255,255,255,.8); font-size: 1.1rem; cursor: pointer; padding: .2rem;
    line-height: 1;
}
.zura-header-close:hover { color: #fff; }
.zura-messages {
    flex: 1; overflow-y: auto; padding: .85rem .9rem;
    display: flex; flex-direction: column; gap: .65rem;
    scroll-behavior: smooth;
}
.zura-msg {
    display: flex; gap: .5rem; align-items: flex-end;
    max-width: 92%;
}
.zura-msg.user { flex-direction: row-reverse; align-self: flex-end; }
.zura-msg.assistant { align-self: flex-start; }
.zura-avatar {
    width: 26px; height: 26px; flex-shrink: 0;
    background: linear-gradient(135deg,#6d28d9,#4f46e5);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .65rem; color: #fff;
}
.zura-bubble {
    padding: .5rem .75rem;
    border-radius: 12px;
    font-size: .82rem; line-height: 1.5;
    word-break: break-word;
}
.zura-msg.user .zura-bubble {
    background: linear-gradient(135deg,#4f46e5,#6d28d9);
    color: #fff; border-bottom-right-radius: 3px;
}
.zura-msg.assistant .zura-bubble {
    background: #f1f5f9; color: #1e293b;
    border-bottom-left-radius: 3px;
}
[data-theme="dark"] .zura-msg.assistant .zura-bubble { background: #273549; color: #e2e8f0; }
.zura-bubble code {
    background: rgba(0,0,0,.1); padding: 0 4px;
    border-radius: 3px; font-size: .88em;
}
.zura-msg.user .zura-bubble code { background: rgba(255,255,255,.2); }
.zura-typing { display: flex; gap: 4px; padding: .35rem .1rem; }
.zura-typing span {
    width: 7px; height: 7px; background: #94a3b8;
    border-radius: 50%; animation: zuraDot 1.2s infinite;
}
.zura-typing span:nth-child(2) { animation-delay: .2s; }
.zura-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes zuraDot {
    0%,60%,100% { transform: translateY(0); opacity:.5; }
    30% { transform: translateY(-5px); opacity:1; }
}
.zura-input-row {
    padding: .65rem .75rem;
    border-top: 1px solid #e2e8f0;
    display: flex; gap: .5rem; align-items: flex-end;
    background: #fff; flex-shrink: 0;
}
[data-theme="dark"] .zura-input-row { background: #1e293b; border-color: #334155; }
.zura-input-row textarea {
    flex: 1; resize: none; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: .5rem .7rem;
    font-size: .82rem; font-family: inherit; line-height: 1.4;
    outline: none; max-height: 100px;
    background: #f8fafc; color: #1e293b;
}
.zura-input-row textarea:focus { border-color: #6d28d9; background: #fff; }
[data-theme="dark"] .zura-input-row textarea {
    background: #273549; border-color: #334155; color: #e2e8f0;
}
.zura-send {
    width: 36px; height: 36px; flex-shrink: 0;
    background: linear-gradient(135deg,#6d28d9,#4f46e5);
    border: none; border-radius: 10px; color: #fff;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: .9rem; transition: opacity .15s;
}
.zura-send:disabled { opacity: .45; cursor: default; }
.zura-suggestions {
    display: flex; flex-wrap: wrap; gap: .35rem;
    padding: 0 .9rem .6rem;
}
.zura-suggestion {
    background: #ede9fe; color: #5b21b6;
    border: none; border-radius: 99px; font-size: .72rem;
    padding: .3rem .75rem; cursor: pointer; font-family: inherit;
    transition: background .15s;
}
.zura-suggestion:hover { background: #ddd6fe; }
[data-theme="dark"] .zura-suggestion { background: #312e81; color: #c4b5fd; }
</style>

{{-- Botón flotante --}}
<button id="zura-ai-btn" title="ZuraAI — Asistente académico" aria-label="Abrir asistente IA">
    <i class="bi bi-stars"></i>
    <span class="zura-badge"></span>
</button>

{{-- Panel de chat --}}
<div id="zura-ai-panel" role="dialog" aria-label="ZuraAI Asistente">
    <div class="zura-header">
        <div class="zura-header-icon"><i class="bi bi-stars"></i></div>
        <div>
            <div class="zura-header-title">ZuraAI</div>
            <div class="zura-header-sub">Asistente Académico · Claude</div>
        </div>
        <div class="d-flex align-items-center gap-1 ms-auto">
            <button id="zura-clear" title="Nueva conversación" aria-label="Nueva conversación"
                style="background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:6px;color:inherit;opacity:.6;transition:opacity .15s,background .15s;"
                onmouseenter="this.style.opacity='1';this.style.background='rgba(255,255,255,.1)'"
                onmouseleave="this.style.opacity='.6';this.style.background='none'">
                <i class="bi bi-arrow-counterclockwise" style="font-size:.9rem;"></i>
            </button>
            <button class="zura-header-close" id="zura-close" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div class="zura-messages" id="zura-messages"></div>

    <div class="zura-suggestions" id="zura-suggestions">
        @if($__zuraRole === 'docente')
        <button class="zura-suggestion">Planificar una clase</button>
        <button class="zura-suggestion">Generar 10 preguntas</button>
        <button class="zura-suggestion">Crear una rúbrica</button>
        <button class="zura-suggestion">Comunicado para padres</button>
        @elseif($__zuraRole === 'estudiante')
        <button class="zura-suggestion">Explícame este tema</button>
        <button class="zura-suggestion">Ayuda con mi tarea</button>
        <button class="zura-suggestion">Cómo estudiar mejor</button>
        <button class="zura-suggestion">Resumir un texto</button>
        @else
        <button class="zura-suggestion">Entender el boletín</button>
        <button class="zura-suggestion">Apoyar en casa</button>
        <button class="zura-suggestion">Hablar con el docente</button>
        <button class="zura-suggestion">Hábitos de estudio</button>
        @endif
    </div>

    <div class="zura-input-row">
        <textarea id="zura-input" rows="1" placeholder="Pregunta algo…" maxlength="4000"></textarea>
        <button class="zura-send" id="zura-send" aria-label="Enviar">
            <i class="bi bi-send-fill"></i>
        </button>
    </div>
</div>

<script>
(function () {
    const btn       = document.getElementById('zura-ai-btn');
    const panel     = document.getElementById('zura-ai-panel');
    const closeBtn  = document.getElementById('zura-close');
    const clearBtn  = document.getElementById('zura-clear');
    const messagesEl= document.getElementById('zura-messages');
    const inputEl   = document.getElementById('zura-input');
    const sendBtn   = document.getElementById('zura-send');
    const suggsEl   = document.getElementById('zura-suggestions');
    const CHAT_URL  = "{{ $__zuraRole === 'docente' ? route('portal.docente.asistente.chat') : ($__zuraRole === 'estudiante' ? route('portal.estudiante.asistente.chat') : route('portal.padre.asistente.chat')) }}";
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let history = [];   // [{role, content}]
    let streaming = false;

    // ── Toggle panel ──────────────────────────────────────────────────────
    btn.addEventListener('click', () => {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
            if (messagesEl.children.length === 0) showWelcome();
            inputEl.focus();
        }
    });
    closeBtn.addEventListener('click', () => panel.classList.remove('open'));

    // ── Clear / nueva conversación ────────────────────────────────────────
    clearBtn.addEventListener('click', () => {
        if (streaming) return;
        history = [];
        messagesEl.innerHTML = '';
        suggsEl.style.display = '';
        showWelcome();
        inputEl.value = '';
        inputEl.style.height = 'auto';
        inputEl.focus();
    });

    // ── Welcome message ───────────────────────────────────────────────────
    const ZURA_ROLE = "{{ $__zuraRole }}";

    function showWelcome() {
        const msg = ZURA_ROLE === 'docente'
            ? '¡Hola! Soy **ZuraAI**, tu asistente académico. Puedo ayudarte a planificar clases, generar evaluaciones, redactar observaciones y mucho más. ¿En qué te ayudo hoy?'
            : ZURA_ROLE === 'estudiante'
            ? '¡Hola! Soy **ZuraAI**, tu tutor académico personal. Puedo explicarte temas, ayudarte con tareas, prepararte para exámenes y mucho más. ¿Con qué empezamos?'
            : '¡Hola! Soy **ZuraAI**, tu asistente de apoyo familiar. Puedo ayudarte a entender el desempeño de tu hijo/a, darte consejos para apoyarlo en casa y orientarte sobre la escuela. ¿En qué te ayudo?';
        appendMessage('assistant', msg);
    }

    // ── Suggestion chips ─────────────────────────────────────────────────
    suggsEl.querySelectorAll('.zura-suggestion').forEach(chip => {
        chip.addEventListener('click', () => {
            if (streaming) return;
            inputEl.value = chip.textContent;
            autoResizeInput();
            sendMessage();
        });
    });

    // ── Input auto-resize ─────────────────────────────────────────────────
    inputEl.addEventListener('input', autoResizeInput);
    function autoResizeInput() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + 'px';
    }

    // ── Send on Enter (Shift+Enter = newline) ────────────────────────────
    inputEl.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!streaming) sendMessage();
        }
    });
    sendBtn.addEventListener('click', () => { if (!streaming) sendMessage(); });

    // ── Send message ──────────────────────────────────────────────────────
    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text || streaming) return;

        inputEl.value = '';
        inputEl.style.height = 'auto';
        suggsEl.style.display = 'none';

        appendMessage('user', text);

        streaming = true;
        sendBtn.disabled = true;

        // Typing indicator
        const typingEl = appendTyping();
        scrollBottom();

        let fullText = '';
        let assistantBubble = null;

        try {
            const res = await fetch(CHAT_URL, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  CSRF,
                    'Accept':        'text/event-stream',
                },
                body: JSON.stringify({ message: text, history: history.slice(-10) }),
            });

            if (!res.ok) {
                typingEl.remove();
                appendMessage('assistant', 'Error al conectar con ZuraAI. Verifica la configuración.');
                return;
            }

            typingEl.remove();
            assistantBubble = appendMessage('assistant', '');

            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf      = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buf += dec.decode(value, { stream: true });

                const lines = buf.split('\n');
                buf = lines.pop();

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const raw = line.slice(6).trim();
                    if (!raw || raw === '[DONE]') continue;
                    try {
                        const evt = JSON.parse(raw);
                        if (evt.type === 'content_block_delta' && evt.delta?.type === 'text_delta') {
                            fullText += evt.delta.text;
                            assistantBubble.innerHTML = renderMd(fullText);
                            scrollBottom();
                        }
                        if (evt.type === 'error') {
                            fullText = evt.error?.message ?? 'Error desconocido.';
                            assistantBubble.innerHTML = renderMd(fullText);
                        }
                    } catch (_) {}
                }
            }

        } catch (err) {
            if (typingEl.parentNode) typingEl.remove();
            const errMsg = 'Error de conexión. Intenta de nuevo.';
            if (assistantBubble) assistantBubble.innerHTML = renderMd(errMsg);
            else appendMessage('assistant', errMsg);
        } finally {
            streaming = false;
            sendBtn.disabled = false;
            if (fullText) {
                history.push({ role: 'user',      content: text     });
                history.push({ role: 'assistant', content: fullText });
                if (history.length > 20) history = history.slice(-20);
                // Mostrar sugerencias rápidas de seguimiento
                showFollowUpSuggs();
            }
            scrollBottom();
        }
    }

    // ── Sugerencias de seguimiento ────────────────────────────────────────
    function showFollowUpSuggs() {
        // Elimina sugerencias anteriores de follow-up
        messagesEl.querySelectorAll('.zura-followup').forEach(el => el.remove());
        const ZURA_ROLE_LOCAL = "{{ $__zuraRole }}";
        const chips = ZURA_ROLE_LOCAL === 'docente'
            ? ['Más detalle','Otro ejemplo','Adaptarlo al grado','Simplificarlo']
            : ZURA_ROLE_LOCAL === 'estudiante'
            ? ['Dame un ejemplo','Explícalo más simple','Ejercicio práctico','Cómo lo recuerdo']
            : ['Más consejos','Qué hacer si…','Dónde lo consulto','Otro tema'];
        const row = document.createElement('div');
        row.className = 'zura-followup';
        row.style.cssText = 'display:flex;flex-wrap:wrap;gap:5px;padding:4px 10px 8px;';
        chips.forEach(txt => {
            const btn = document.createElement('button');
            btn.className = 'zura-suggestion';
            btn.style.cssText = 'font-size:.7rem;padding:3px 9px;opacity:.85;';
            btn.textContent = txt;
            btn.addEventListener('click', () => {
                if (streaming) return;
                inputEl.value = txt;
                autoResizeInput();
                row.remove();
                sendMessage();
            });
            row.appendChild(btn);
        });
        messagesEl.appendChild(row);
        scrollBottom();
    }

    // ── DOM helpers ───────────────────────────────────────────────────────
    function appendMessage(role, text) {
        const wrap   = document.createElement('div');
        wrap.className = 'zura-msg ' + role;

        if (role === 'assistant') {
            const av = document.createElement('div');
            av.className = 'zura-avatar';
            av.innerHTML = '<i class="bi bi-stars" style="font-size:.65rem;"></i>';
            wrap.appendChild(av);
        }

        const bubble = document.createElement('div');
        bubble.className = 'zura-bubble';
        bubble.innerHTML = renderMd(text);
        wrap.appendChild(bubble);

        messagesEl.appendChild(wrap);
        scrollBottom();
        return bubble;
    }

    function appendTyping() {
        const wrap = document.createElement('div');
        wrap.className = 'zura-msg assistant';
        const av = document.createElement('div');
        av.className = 'zura-avatar';
        av.innerHTML = '<i class="bi bi-stars" style="font-size:.65rem;"></i>';
        wrap.appendChild(av);
        const bubble = document.createElement('div');
        bubble.className = 'zura-bubble';
        bubble.innerHTML = '<div class="zura-typing"><span></span><span></span><span></span></div>';
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        scrollBottom();
        return wrap;
    }

    function scrollBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // ── Minimal markdown renderer ─────────────────────────────────────────
    function renderMd(text) {
        if (!text) return '';
        return text
            // Escape HTML
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            // Code blocks (```...```)
            .replace(/```[\s\S]*?```/g, m => {
                const code = m.slice(3, -3).replace(/^\w*\n/, '');
                return '<pre style="background:rgba(0,0,0,.07);padding:.4rem .6rem;border-radius:6px;overflow-x:auto;font-size:.78rem;margin:.3rem 0;"><code>' + code + '</code></pre>';
            })
            // Bold
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Inline code
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            // Headers ## / ###
            .replace(/^### (.+)$/gm, '<strong style="display:block;font-size:.84rem;margin-top:.4rem;">$1</strong>')
            .replace(/^## (.+)$/gm,  '<strong style="display:block;font-size:.88rem;margin-top:.5rem;">$1</strong>')
            // List items
            .replace(/^[-*] (.+)$/gm, '• $1')
            // Line breaks
            .replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
    }
})();
</script>
@endif
@endauth
</body>
</html>
