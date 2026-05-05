@extends('layouts.admin')

@section('page-title', 'Manual de Ayuda')

@push('styles')
<style>
/* ════════════════════════════════════════════════════
   MANUAL DE AYUDA — PSAC SGE
════════════════════════════════════════════════════ */
:root {
    --c-pc:#1d4ed8; --c-acad:#047857; --c-tec:#7c3aed;
    --c-asist:#0f766e; --c-rep:#b45309; --c-cfg:#374151; --c-hor:#312e81;
    /* Light mode tokens */
    --h-bg:#fff; --h-border:#e5e7eb; --h-text:#111827;
    --h-desc:#4b5563; --h-step:#374151; --h-step-sep:#f3f4f6;
    --h-stb-border:#e5e7eb; --h-rtb-bg:#f3f4f6; --h-rtb-c:#6b7280;
    --h-tab-bg:#fff; --h-tab-c:#6b7280; --h-tab-border:#e5e7eb;
    --h-nav-bg:#f3f4f6; --h-nav-c:#6b7280; --h-nav-border:#d1d5db;
    --h-mock-bg:#f8fafc; --h-mock-border:#e2e8f0; --h-mock-label:#94a3b8;
    --h-mi-bg:#fff; --h-mi-border:#d1d5db; --h-mi-c:#374151;
    --h-td-bg:#fff; --h-td-border:#d1d5db; --h-td-c:#374151;
    --h-row-bg:#fff; --h-row-border:#e5e7eb;
    --h-tip-bg:#eff6ff; --h-tip-c:#1e40af;
    --h-warn-bg:#fffbeb; --h-warn-c:#78350f;
    --h-ok-bg:#f0fdf4; --h-ok-c:#14532d;
    --h-search-bg:#fff; --h-search-border:#e5e7eb; --h-search-c:#111827;
    --h-snr-c:#9ca3af;
    --h-att-p-bg:#dcfce7; --h-att-p-c:#14532d;
    --h-att-a-bg:#fee2e2; --h-att-a-c:#7f1d1d;
    --h-att-t-bg:#fef9c3; --h-att-t-c:#78350f;
    --h-mi-ok-bg:#dcfce7; --h-mi-ok-border:#86efac; --h-mi-ok-c:#14532d;
    --h-mi-war-bg:#fef9c3; --h-mi-war-border:#fde047; --h-mi-war-c:#78350f;
    --h-mi-bad-bg:#fee2e2; --h-mi-bad-border:#fca5a5; --h-mi-bad-c:#7f1d1d;
    --h-nav-done-bg:#dcfce7; --h-nav-done-c:#14532d;
    --h-title-c:#1e3a8a;
    --h-head-c:#1e3a8a;
}
[data-theme="dark"] {
    --h-bg:#1e293b; --h-border:#334155; --h-text:#e2e8f0;
    --h-desc:#94a3b8; --h-step:#cbd5e1; --h-step-sep:#293548;
    --h-stb-border:#334155; --h-rtb-bg:#0f172a; --h-rtb-c:#94a3b8;
    --h-tab-bg:#1e293b; --h-tab-c:#94a3b8; --h-tab-border:#334155;
    --h-nav-bg:#1e293b; --h-nav-c:#94a3b8; --h-nav-border:#334155;
    --h-mock-bg:#0f172a; --h-mock-border:#1e3a5f; --h-mock-label:#475569;
    --h-mi-bg:#1e293b; --h-mi-border:#475569; --h-mi-c:#cbd5e1;
    --h-td-bg:#1e293b; --h-td-border:#334155; --h-td-c:#cbd5e1;
    --h-row-bg:#1e293b; --h-row-border:#334155;
    --h-tip-bg:#162032; --h-tip-c:#93c5fd;
    --h-warn-bg:#2a1f06; --h-warn-c:#fcd34d;
    --h-ok-bg:#052e16; --h-ok-c:#4ade80;
    --h-search-bg:#1e293b; --h-search-border:#334155; --h-search-c:#e2e8f0;
    --h-snr-c:#475569;
    --h-att-p-bg:#052e16; --h-att-p-c:#4ade80;
    --h-att-a-bg:#3f0b0b; --h-att-a-c:#fca5a5;
    --h-att-t-bg:#2a1f06; --h-att-t-c:#fcd34d;
    --h-mi-ok-bg:#052e16; --h-mi-ok-border:#166534; --h-mi-ok-c:#4ade80;
    --h-mi-war-bg:#2a1f06; --h-mi-war-border:#854d0e; --h-mi-war-c:#fcd34d;
    --h-mi-bad-bg:#3f0b0b; --h-mi-bad-border:#991b1b; --h-mi-bad-c:#fca5a5;
    --h-nav-done-bg:#052e16; --h-nav-done-c:#4ade80;
    --h-title-c:#93c5fd;
    --h-head-c:#93c5fd;
}

.htb.hor-btn.active { background:var(--c-hor); }
.hor-n { background:var(--c-hor); color:#fff; width:28px; height:28px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:800; flex-shrink:0; }

/* ── Page header (always dark gradient — intentional) ── */
.help-page-header {
    background: linear-gradient(135deg, #0f1f3d 0%, #1e3a6e 100%);
    border-radius: 16px; padding: 1.75rem 2rem; margin-bottom: 1.75rem;
    display: flex; align-items: flex-start; gap: 1.25rem;
    position: relative; overflow: hidden;
}
.help-page-header::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:180px; height:180px; background:rgba(255,255,255,.04); border-radius:50%;
}
.help-page-header .header-icon {
    width:56px; height:56px; background:rgba(255,255,255,.15);
    border-radius:14px; display:flex; align-items:center; justify-content:center;
    font-size:1.6rem; color:#fff; flex-shrink:0;
}
.help-page-header h1 { font-size:1.4rem; font-weight:800; color:#fff; margin-bottom:.25rem; }
.help-page-header p  { font-size:.875rem; color:rgba(255,255,255,.78); margin:0; }

/* ── Search ── */
.help-search-wrap { position:relative; margin-bottom:1.5rem; }
.help-search-wrap input {
    width:100%; padding:.7rem 1rem .7rem 2.75rem;
    border:2px solid var(--h-search-border); border-radius:12px; font-size:.9rem;
    outline:none; transition:border-color .2s,box-shadow .2s;
    background:var(--h-search-bg); color:var(--h-search-c);
}
.help-search-wrap input::placeholder { color:var(--h-rtb-c); }
.help-search-wrap input:focus { border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,.15); }
.help-search-wrap .si { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--h-rtb-c); font-size:1rem; pointer-events:none; }
#snr { display:none; text-align:center; padding:2.5rem; color:var(--h-snr-c); font-size:.875rem; }

/* ── Tabs ── */
.help-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.htb {
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.5rem 1.1rem; border-radius:10px; font-size:.84rem; font-weight:600;
    border:2px solid var(--h-tab-border); cursor:pointer;
    transition:background .18s,border-color .18s,color .18s;
    background:var(--h-tab-bg); color:var(--h-tab-c);
}
.htb:hover { background:#f0f4ff; border-color:#1d4ed8; color:#1d4ed8; }
[data-theme="dark"] .htb:hover { background:#162032; border-color:#3b82f6; color:#93c5fd; }
.htb.active { color:#fff; border-color:transparent; }
.htb.cfg-btn.active   { background:var(--c-cfg); }
.htb.pc-btn.active    { background:var(--c-pc); }
.htb.acad-btn.active  { background:var(--c-acad); }
.htb.tec-btn.active   { background:var(--c-tec); }
.htb.asist-btn.active { background:var(--c-asist); }
.htb.rep-btn.active   { background:var(--c-rep); }

/* ── Sections ── */
.help-section { display:none; }
.help-section.active { display:block; }

/* ── Section title bar ── */
.stb {
    display:flex; align-items:center; gap:.75rem;
    margin-bottom:1rem; padding-bottom:.75rem;
    border-bottom:2px solid var(--h-stb-border);
}
.stb .iw {
    width:40px; height:40px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; color:#fff; flex-shrink:0;
}
.stb h2 { font-size:1.1rem; font-weight:800; color:var(--h-head-c); margin:0; }
.stb p  { font-size:.8rem; color:var(--h-desc); margin:.1rem 0 0; }
.rtb {
    display:inline-flex; align-items:center; gap:.35rem;
    font-size:.72rem; font-weight:600; color:var(--h-rtb-c);
    background:var(--h-rtb-bg); border-radius:20px; padding:.2rem .65rem; margin-left:auto;
}

/* ── Step cards ── */
.sc {
    background:var(--h-bg); border-radius:14px; padding:1.4rem;
    margin-bottom:1.15rem; border:1px solid var(--h-border);
    box-shadow:0 1px 4px rgba(0,0,0,.06);
    display:flex; gap:1.15rem;
    scroll-margin-top:80px; transition:box-shadow .2s,border-color .2s;
}
.sc:hover { box-shadow:0 4px 16px rgba(0,0,0,.1); border-color:rgba(29,78,216,.25); }
[data-theme="dark"] .sc:hover { border-color:#3b5577; }
.sc.search-hidden { display:none !important; }
.sn {
    width:44px; height:44px; flex-shrink:0; color:#fff; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; font-weight:800;
}
.cfg-n  { background:linear-gradient(135deg,#374151,#1f2937); }
.pc-n   { background:linear-gradient(135deg,#1d4ed8,#1e3a8a); }
.acad-n { background:linear-gradient(135deg,#047857,#065f46); }
.tec-n  { background:linear-gradient(135deg,#7c3aed,#4c1d95); }
.asist-n{ background:linear-gradient(135deg,#0f766e,#134e4a); }
.rep-n  { background:linear-gradient(135deg,#b45309,#78350f); }

.sc-body { flex:1; min-width:0; }
.sc-title { font-size:.98rem; font-weight:700; color:var(--h-title-c); margin-bottom:.2rem; }
.sc-desc  { font-size:.845rem; color:var(--h-desc); margin-bottom:.7rem; }
.sc-steps { list-style:none; padding:0; margin:.5rem 0 0; }
.sc-steps li {
    font-size:.835rem; color:var(--h-step);
    padding:.28rem 0; border-bottom:1px solid var(--h-step-sep);
    display:flex; align-items:flex-start; gap:.5rem;
}
.sc-steps li:last-child { border-bottom:none; }
.sc-steps li i { flex-shrink:0; margin-top:.12rem; }
.sc-steps strong { color:var(--h-text); }

/* ── Tip / Warn / Ok boxes ── */
.tip  { background:var(--h-tip-bg); border-left:3px solid #3b82f6; border-radius:0 8px 8px 0; padding:.55rem .85rem; margin:.65rem 0; font-size:.81rem; color:var(--h-tip-c); line-height:1.55; }
.warn { background:var(--h-warn-bg); border-left:3px solid #f59e0b; border-radius:0 8px 8px 0; padding:.55rem .85rem; margin:.65rem 0; font-size:.81rem; color:var(--h-warn-c); line-height:1.55; }
.ok   { background:var(--h-ok-bg);  border-left:3px solid #22c55e; border-radius:0 8px 8px 0; padding:.55rem .85rem; margin:.65rem 0; font-size:.81rem; color:var(--h-ok-c);  line-height:1.55; }

/* ── Flow diagram ── */
.flow { display:flex; align-items:center; flex-wrap:wrap; gap:.3rem; margin:.75rem 0; font-size:.78rem; }
.flow-step {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.35rem .75rem; border-radius:8px; font-weight:600; color:#fff;
}
.flow-arrow { color:var(--h-rtb-c); font-size:1rem; }
.f-cfg  { background:#374151; }
.f-pc   { background:#1d4ed8; }
.f-acad { background:#047857; }
.f-tec  { background:#7c3aed; }
.f-asist{ background:#0f766e; }
.f-rep  { background:#b45309; }
.f-blt  { background:#0891b2; }
.f-ind  { background:#0284c7; }

/* ── Mock UI elements ── */
.mock {
    background:var(--h-mock-bg); border:2px solid var(--h-mock-border);
    border-radius:12px; padding:1rem; margin:.7rem 0;
    font-size:.76rem; position:relative; overflow:hidden;
}
.mock::after {
    content:'Vista del Sistema'; position:absolute; top:6px; right:8px;
    font-size:.6rem; color:var(--h-mock-label); font-weight:600; text-transform:uppercase; letter-spacing:.06em;
}
.mbar {
    height:30px; background:linear-gradient(135deg,#1e3a6e,#0f1f3d);
    border-radius:8px 8px 0 0; margin:-1rem -1rem .7rem;
    display:flex; align-items:center; padding:0 .7rem; gap:.35rem;
}
.mbar .d { width:7px; height:7px; border-radius:50%; }
.mbar .lbl { color:rgba(255,255,255,.7); font-size:.62rem; margin-left:.4rem; }

.mi  { background:var(--h-mi-bg); border:1px solid var(--h-mi-border); border-radius:4px; padding:.1rem .3rem; width:46px; text-align:center; font-size:.72rem; display:inline-block; color:var(--h-mi-c); }
.mi.ok  { background:var(--h-mi-ok-bg);  border-color:var(--h-mi-ok-border);  color:var(--h-mi-ok-c); }
.mi.war { background:var(--h-mi-war-bg); border-color:var(--h-mi-war-border); color:var(--h-mi-war-c); }
.mi.bad { background:var(--h-mi-bad-bg); border-color:var(--h-mi-bad-border); color:var(--h-mi-bad-c); }

.mb  { display:inline-flex; align-items:center; gap:.3rem; padding:.28rem .7rem; border-radius:6px; font-size:.7rem; font-weight:600; cursor:default; }
.mb-p{ background:#1e3a6e; color:#fff; }
.mb-s{ background:#16a34a; color:#fff; }
.mb-w{ background:#d97706; color:#fff; }
.mb-d{ background:#dc2626; color:#fff; }
.mb-o{ background:#6b7280; color:#fff; }
.mb-i{ background:#0891b2; color:#fff; }

.badge-pill { display:inline-block; padding:.15em .5em; border-radius:99px; font-size:.65rem; font-weight:700; }
.row-item { display:flex; align-items:center; gap:.6rem; background:var(--h-row-bg); border:1px solid var(--h-row-border); border-radius:8px; padding:.45rem .7rem; margin-bottom:.3rem; color:var(--h-step); }

/* ── Attendance badges ── */
.att-p { background:var(--h-att-p-bg); color:var(--h-att-p-c); }
.att-a { background:var(--h-att-a-bg); color:var(--h-att-a-c); }
.att-t { background:var(--h-att-t-bg); color:var(--h-att-t-c); }

/* ── Tables ── */
.mt { width:100%; border-collapse:collapse; font-size:.7rem; }
.mt th, .mt td { border:1px solid var(--h-td-border); padding:.22rem .35rem; text-align:center; }
.mt td { background:var(--h-td-bg); color:var(--h-td-c); }
.mt th { font-weight:700; }
.th-n { background:#374151; color:#fff; }
.th-c1{ background:#1d4ed8; color:#fff; }
.th-c2{ background:#15803d; color:#fff; }
.th-c3{ background:#7e22ce; color:#fff; }
.th-c4{ background:#b91c1c; color:#fff; }
.th-ra{ background:#6d28d9; color:#fff; }
.th-f { background:#0f766e; color:#fff; }
.th-cc{ background:#4f46e5; color:#fff; }
.th-as{ background:#0e7490; color:#fff; }
.th-ind{ background:#1e40af; color:#fff; }

/* ── Semáforo ── */
.sem { display:inline-block; width:16px; height:16px; border-radius:50%; vertical-align:middle; }
.sem-g { background:#16a34a; }
.sem-y { background:#d97706; }
.sem-r { background:#dc2626; }

/* ── Nav flow steps ── */
.nav-steps { display:flex; gap:0; margin:.75rem 0; }
.nav-step {
    flex:1; text-align:center; padding:.5rem .3rem;
    background:var(--h-nav-bg); font-size:.7rem; font-weight:600; color:var(--h-nav-c);
    border-right:1px solid var(--h-nav-border); position:relative;
}
.nav-step:first-child { border-radius:8px 0 0 8px; }
.nav-step:last-child  { border-radius:0 8px 8px 0; border-right:none; }
.nav-step.active { background:#1d4ed8; color:#fff; }
.nav-step.done   { background:var(--h-nav-done-bg); color:var(--h-nav-done-c); }

/* ── Dark mode: override inline color styles on table cells & highlight divs ── */
[data-theme="dark"] .mt td { color: var(--h-td-c) !important; }
[data-theme="dark"] .badge-pill[style*="background:#dbeafe"] { background:#1e3a5f !important; color:#93c5fd !important; }
[data-theme="dark"] .badge-pill[style*="background:#dcfce7"] { background:#052e16 !important; color:#4ade80 !important; }
[data-theme="dark"] .badge-pill[style*="background:#fee2e2"] { background:#3f0b0b !important; color:#fca5a5 !important; }
[data-theme="dark"] [style*="color:#1e293b"] { color:#e2e8f0 !important; }
[data-theme="dark"] [style*="color:#374151"] { color:#cbd5e1 !important; }
[data-theme="dark"] [style*="color:#1d4ed8"]:not(.htb):not(.flow-step):not(.htb *) { color:#93c5fd !important; }
[data-theme="dark"] [style*="color:#15803d"] { color:#4ade80 !important; }
[data-theme="dark"] [style*="color:#991b1b"] { color:#fca5a5 !important; }
[data-theme="dark"] [style*="color:#6d28d9"] { color:#c4b5fd !important; }
[data-theme="dark"] [style*="background:#dcfce7"] { background:#052e16 !important; color:#4ade80 !important; }
[data-theme="dark"] [style*="background:#fee2e2"] { background:#3f0b0b !important; color:#fca5a5 !important; }
[data-theme="dark"] [style*="color:#2563eb"] { color:#93c5fd !important; }
[data-theme="dark"] [style*="color:#dc2626"] { color:#fca5a5 !important; }
[data-theme="dark"] [style*="color:#d97706"] { color:#fcd34d !important; }

/* ── Responsive ── */
@media (max-width:767px) {
    .sc { flex-direction:column; gap:.7rem; }
    .help-tabs { gap:.3rem; }
    .htb { font-size:.76rem; padding:.4rem .8rem; }
    .flow { gap:.2rem; }
    .flow-step { padding:.28rem .5rem; font-size:.72rem; }
    .nav-steps { flex-direction:column; }
    .nav-step { border-right:none; border-bottom:1px solid var(--h-nav-border); border-radius:0; }
    .nav-step:first-child { border-radius:8px 8px 0 0; }
    .nav-step:last-child  { border-radius:0 0 8px 8px; border-bottom:none; }
    .stb { flex-wrap:wrap; }
    .rtb { margin-left:0; }
}
</style>
@endpush

@section('content')

{{-- ── PAGE HEADER ─────────────────────────────────────────────── --}}
<div class="help-page-header">
    <div class="header-icon"><i class="bi bi-book-half"></i></div>
    <div style="position:relative;z-index:1;">
        <h1>Manual de Ayuda — SGE PSAC</h1>
        <p>Politécnico Salesiano Arquides Calderón · Sistema de Gestión Escolar · Guía completa de uso en el orden correcto</p>
    </div>
</div>

{{-- Flujo general --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-body p-3">
        <div class="fw-bold mb-2" style="font-size:.82rem;color:#374151;"><i class="bi bi-diagram-3 me-2"></i>Flujo correcto de trabajo (de inicio a fin)</div>
        <div class="flow">
            <span class="flow-step f-cfg"><i class="bi bi-gear-fill me-1"></i>1. Configurar año</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-cfg"><i class="bi bi-person-badge me-1"></i>2. Docentes y grupos</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-pc"><i class="bi bi-people-fill me-1"></i>3. Matricular estudiantes</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-asist"><i class="bi bi-calendar-check me-1"></i>4. Asistencia diaria</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-acad"><i class="bi bi-journal-check me-1"></i>5. Registrar notas</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-ind"><i class="bi bi-check2-all me-1"></i>6. Indicadores logro</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-blt"><i class="bi bi-file-earmark-text me-1"></i>7. Boletines</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-rep"><i class="bi bi-bar-chart-fill me-1"></i>8. Reportes</span>
        </div>
    </div>
</div>

{{-- ── SEARCH ──────────────────────────────────────────────────── --}}
<div class="help-search-wrap">
    <i class="bi bi-search si"></i>
    <input type="text" id="helpSearch" placeholder="Buscar en el manual… (ej: notas, matrícula, publicar, boletín, semáforo…)" autocomplete="off">
</div>
<div id="snr">
    <i class="bi bi-search" style="font-size:2rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
    No se encontraron pasos que coincidan con tu búsqueda.
</div>

{{-- ── TABS ─────────────────────────────────────────────────────── --}}
<div class="help-tabs" id="helpTabs">
    <button class="htb cfg-btn active"  onclick="sw('sCfg',this)"><i class="bi bi-gear-fill"></i> Configuración Inicial</button>
    <button class="htb pc-btn"          onclick="sw('sPC',this)"><i class="bi bi-1-circle-fill"></i> Primer Ciclo (1ro–3ro)</button>
    <button class="htb acad-btn"        onclick="sw('sAcad',this)"><i class="bi bi-book"></i> 2do Ciclo · Académica</button>
    <button class="htb tec-btn"         onclick="sw('sTec',this)"><i class="bi bi-tools"></i> 2do Ciclo · Técnica (RA)</button>
    <button class="htb asist-btn"       onclick="sw('sAsist',this)"><i class="bi bi-calendar-check"></i> Asistencia</button>
    <button class="htb rep-btn"         onclick="sw('sRep',this)"><i class="bi bi-bar-chart-fill"></i> Indicadores y Reportes</button>
    <button class="htb hor-btn"         onclick="sw('sHor',this)"><i class="bi bi-calendar-week-fill"></i> Horarios</button>
    <button class="htb" style="--c:#7c3aed;" onclick="sw('sPlanif',this)"><i class="bi bi-journal-text"></i> Planificación Docente</button>
    <button class="htb" style="--c:#0891b2;" onclick="sw('sPortales',this)"><i class="bi bi-person-circle"></i> Portales</button>
    <button class="htb" style="--c:#0f766e;" onclick="sw('sPagos',this)"><i class="bi bi-cash-coin"></i> Pagos</button>
    <button class="htb" style="--c:#6366f1;" onclick="sw('sPerfil',this)"><i class="bi bi-person-badge"></i> Perfil</button>
</div>


{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 1 — CONFIGURACIÓN INICIAL
══════════════════════════════════════════════════════════════ --}}
<div class="help-section active" id="sCfg">

<div class="stb">
    <div class="iw" style="background:var(--c-cfg);"><i class="bi bi-gear-fill"></i></div>
    <div>
        <h2>Configuración Inicial del Sistema</h2>
        <p>Realiza estos pasos <strong>una sola vez</strong> al inicio de cada año escolar, en este orden exacto</p>
    </div>
    <span class="rtb"><i class="bi bi-clock"></i> ~10 min</span>
</div>

<div class="sc" id="c1" data-s="año escolar school year configurar activo periodos">
    <div class="sn cfg-n">1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-calendar2-range me-2" style="color:var(--c-cfg);"></i>Crear / activar el Año Escolar</h4>
        <p class="sc-desc">Todo el sistema (grupos, matrículas, notas, asistencia) está ligado al año escolar activo. Debe existir uno activo antes de hacer cualquier otra cosa.</p>
        <div class="nav-steps">
            <div class="nav-step done"><i class="bi bi-gear"></i> Configuración</div>
            <div class="nav-step active"><i class="bi bi-calendar2-range"></i> Año Escolar</div>
            <div class="nav-step"><i class="bi bi-plus-circle"></i> Nuevo / Activar</div>
        </div>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Menú lateral → Configuración → Año Escolar</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Configuración → Año Escolar</strong> en el menú lateral.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Haz clic en <strong>"Nuevo Año Escolar"</strong> e ingresa nombre, fecha inicio y fecha fin.</li>
            <li><i class="bi bi-toggle-on text-success"></i>Marca el año como <strong>Activo</strong> — solo puede haber uno activo a la vez.</li>
            <li><i class="bi bi-exclamation-triangle text-warning"></i>Si ya existe el año del ciclo anterior activo, desactívalo primero o edítalo.</li>
        </ul>
    </div>
</div>

<div class="sc" id="c2" data-s="periodos trimestres cuatrimestres configurar numero fechas">
    <div class="sn cfg-n">2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-calendar3 me-2" style="color:var(--c-cfg);"></i>Configurar Períodos (Trimestres)</h4>
        <p class="sc-desc">Los períodos son los trimestres o cuatrimestres del año. Son necesarios para la grilla de notas técnica y para los indicadores de logro.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Configuración → Períodos</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-calendar-plus text-primary"></i>Ve a <strong>Configuración → Períodos</strong>.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Crea los períodos del año (ej: 1er Trimestre, 2do Trimestre…) con su número de orden.</li>
            <li><i class="bi bi-check2 text-success"></i>Normalmente son <strong>4 períodos</strong> (cuatrimestres) o <strong>3 períodos</strong> (trimestres).</li>
            <li><i class="bi bi-link text-primary"></i>Cada período debe estar vinculado al <strong>año escolar activo</strong>.</li>
        </ul>
    </div>
</div>

<div class="sc" id="c3" data-s="grados secciones grupos crear aula asignar tutor">
    <div class="sn cfg-n">3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-people me-2" style="color:var(--c-cfg);"></i>Crear Grupos (Grado + Sección)</h4>
        <p class="sc-desc">Un grupo es la combinación de Grado + Sección (ej: 3ro Bachillerato "A"). Los estudiantes se matriculan en un grupo y las asignaciones de docentes también van por grupo.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión → Grupos/Cursos</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Gestión → Grupos/Cursos</strong>.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Haz clic en <strong>"Nuevo Grupo"</strong>.</li>
            <li><i class="bi bi-mortarboard text-primary"></i>Selecciona el <strong>Grado</strong> (1ro–6to) y la <strong>Sección</strong> (A, B, C…).</li>
            <li><i class="bi bi-person-badge text-primary"></i>Asigna un <strong>Docente Tutor</strong> si aplica, y especifica el aula.</li>
            <li><i class="bi bi-repeat text-secondary"></i>Repite para cada grupo que tenga el plantel este año escolar.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i><strong>Primer Ciclo:</strong> Grados 1ro, 2do, 3ro · <strong>Segundo Ciclo:</strong> 4to, 5to, 6to Bachillerato</div>
    </div>
</div>

<div class="sc" id="c4" data-s="docentes crear usuario asignar area especialidad tecnica academica">
    <div class="sn cfg-n">4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-badge me-2" style="color:var(--c-cfg);"></i>Registrar Docentes</h4>
        <p class="sc-desc">Cada docente debe tener su perfil y un usuario asignado. Los docentes del área técnica también deben ser vinculados a su especialidad.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión → Docentes</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-person-plus text-primary"></i>Ve a <strong>Gestión → Docentes → Nuevo Docente</strong>.</li>
            <li><i class="bi bi-input-cursor text-primary"></i>Completa nombres, apellidos, cédula, email y área (<strong>Académica</strong> o <strong>Técnica</strong>).</li>
            <li><i class="bi bi-person-circle text-success"></i>Vincula un <strong>Usuario del sistema</strong> para que el docente pueda iniciar sesión.</li>
            <li><i class="bi bi-tools" style="color:var(--c-tec);"></i>Si es técnico, ve a <strong>2do Ciclo · Técnica → Especialidades</strong> y asígnalo a su especialidad.</li>
            <li><i class="bi bi-upload text-secondary"></i>Opción rápida: usa <strong>Importar CSV</strong> si tienes varios docentes en un archivo Excel.</li>
        </ul>
    </div>
</div>

<div class="sc" id="c5" data-s="asignaturas materias crear codigo area academica tecnica ra">
    <div class="sn cfg-n">5</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-journal-bookmark me-2" style="color:var(--c-cfg);"></i>Configurar Asignaturas</h4>
        <p class="sc-desc">Las asignaturas son las materias que se imparten. Define si son de evaluación estándar (académica) o por Resultados de Aprendizaje (técnica/RA).</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Configuración → Asignaturas</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-book text-primary"></i>Ve a <strong>Configuración → Asignaturas → Nueva Asignatura</strong>.</li>
            <li><i class="bi bi-input-cursor text-primary"></i>Ingresa nombre, código y área (<strong>Académica</strong> o <strong>Técnica</strong>).</li>
            <li><i class="bi bi-bar-chart-steps" style="color:var(--c-tec);"></i>Para asignaturas técnicas, activa <strong>"Evaluación por RA"</strong> e indica la cantidad de Resultados de Aprendizaje.</li>
            <li><i class="bi bi-check2 text-success"></i>Las asignaturas académicas usarán la planilla de <strong>4 Competencias × 4 Períodos</strong>.</li>
        </ul>
        <div class="warn"><i class="bi bi-exclamation-triangle me-1"></i>El tipo de evaluación (estándar vs RA) no se puede cambiar fácilmente después de ingresar notas.</div>
    </div>
</div>

<div class="sc" id="c6" data-s="asignaciones docente grupo asignatura vincular activo">
    <div class="sn cfg-n">6</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-link-45deg me-2" style="color:var(--c-cfg);"></i>Crear Asignaciones (Docente → Grupo → Asignatura)</h4>
        <p class="sc-desc">Una asignación vincula a un docente con un grupo y una asignatura para el año escolar. Sin asignación, el docente no puede registrar notas ni asistencia.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Configuración → Asignaciones</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-plus-circle text-primary"></i>Ve a <strong>Configuración → Asignaciones → Nueva Asignación</strong>.</li>
            <li><i class="bi bi-person-badge text-primary"></i>Selecciona el <strong>Docente</strong>, el <strong>Grupo</strong> y la <strong>Asignatura</strong>.</li>
            <li><i class="bi bi-tag text-primary"></i>Indica el <strong>Área</strong> (Académica / Técnica) — debe coincidir con el área del docente.</li>
            <li><i class="bi bi-toggle-on text-success"></i>Marca como <strong>Activa</strong> para que aparezca en los módulos de notas y asistencia.</li>
            <li><i class="bi bi-repeat text-secondary"></i>Repite para cada combinación Docente–Grupo–Asignatura del año.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Un mismo docente puede tener múltiples asignaciones (varios grupos o varias asignaturas).</div>
    </div>
</div>

<div class="sc" id="c7" data-s="config calificacion pesos componentes tareas practicas examen porcentajes">
    <div class="sn cfg-n">7</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-sliders me-2" style="color:var(--c-cfg);"></i>Configurar pesos de calificación (Área Técnica)</h4>
        <p class="sc-desc">Para las asignaturas técnicas sin RA, define el peso (%) de cada componente: tareas, prácticas, participación, proyecto y examen. La suma debe ser 100%.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Configuración → Config. Calificación</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-percent text-primary"></i>Ve a <strong>Configuración → Config. Calificación</strong>.</li>
            <li><i class="bi bi-123 text-primary"></i>Ajusta el % de cada componente (Tareas, Prácticas, Participación, Proyecto, Examen).</li>
            <li><i class="bi bi-check2-circle text-success"></i>Verifica que la suma sea exactamente <strong>100%</strong> antes de guardar.</li>
            <li><i class="bi bi-tools" style="color:var(--c-tec);"></i>Para asignaturas con RA ve a <strong>Configuración → Config. RA</strong> y ajusta el peso de cada RA.</li>
        </ul>
    </div>
</div>

<div class="sc" id="c8" data-s="config boletin configurar encabezado pie firma director logo año escolar boletin">
    <div class="sn cfg-n">8</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-file-earmark-text me-2" style="color:var(--c-cfg);"></i>Configurar el Boletín de Calificaciones</h4>
        <p class="sc-desc">Antes de generar boletines, personaliza el encabezado, pie de página, firma del director y el umbral de aprobación. Esta configuración aplica a todo el año escolar.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Configuración → Config. Boletín</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-gear text-primary"></i>Ve a <strong>Configuración → Config. Boletín</strong>.</li>
            <li><i class="bi bi-building text-primary"></i>Configura el nombre del centro, dirección y logo (si aplica).</li>
            <li><i class="bi bi-person-badge text-primary"></i>Ingresa el nombre y cargo del director para la firma del boletín.</li>
            <li><i class="bi bi-123 text-success"></i>Define la <strong>nota mínima de aprobación</strong> (usualmente 70).</li>
            <li><i class="bi bi-eye text-secondary"></i>Usa <strong>"Vista previa"</strong> para ver cómo quedará el encabezado del boletín antes de guardar.</li>
        </ul>
        <div class="warn"><i class="bi bi-exclamation-triangle me-1"></i>Sin esta configuración el boletín puede generarse con datos incompletos o incorrectos.</div>
    </div>
</div>

</div>{{-- /sCfg --}}


{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 2 — PRIMER CICLO
══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sPC">

<div class="stb">
    <div class="iw" style="background:var(--c-pc);"><i class="bi bi-1-circle-fill"></i></div>
    <div>
        <h2>Primer Ciclo — 1ro, 2do y 3ro</h2>
        <p>Gestión completa del ciclo inicial: estudiantes, asistencia, notas académicas e indicadores</p>
    </div>
    <span class="rtb"><i class="bi bi-clock"></i> ~7 min</span>
</div>

<div class="sc" id="p1" data-s="primer ciclo estudiantes registrar matricular nuevo csv importar">
    <div class="sn pc-n">P1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-plus me-2" style="color:var(--c-pc);"></i>Registrar / matricular estudiantes del Primer Ciclo</h4>
        <p class="sc-desc">Los estudiantes se registran en el sistema y luego se matriculan en un grupo. Puedes registrarlos uno a uno o importarlos desde un archivo CSV.</p>
        <div class="nav-steps">
            <div class="nav-step done">Menú lateral</div>
            <div class="nav-step active">Primer Ciclo → Estudiantes</div>
            <div class="nav-step">Nuevo Estudiante</div>
            <div class="nav-step">Matricular en grupo</div>
        </div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-pc);"></i>En el menú lateral, ve a <strong>Primer Ciclo (1ro–3ro) → Estudiantes</strong>.</li>
            <li><i class="bi bi-person-plus text-success"></i>Haz clic en <strong>"Nuevo Estudiante"</strong> y llena el formulario (nombres, apellidos, cédula, fecha nacimiento, sexo).</li>
            <li><i class="bi bi-upload text-primary"></i>Alternativa: usa <strong>"Importar CSV"</strong> para cargar muchos estudiantes a la vez desde Excel (guarda como CSV).</li>
            <li><i class="bi bi-mortarboard text-primary"></i>Después de crear el estudiante, ve a <strong>Gestión → Matrículas → Nueva Matrícula</strong> para inscribirlo en un grupo.</li>
            <li><i class="bi bi-check2-circle text-success"></i>Verifica que el grupo seleccionado sea de <strong>1ro, 2do o 3ro</strong> para que aparezca en Primer Ciclo.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>El sistema genera automáticamente el número de matrícula si lo dejas en blanco.</div>
    </div>
</div>

<div class="sc" id="p2" data-s="primer ciclo asistencia diaria tomar registrar presente ausente tardanza">
    <div class="sn pc-n">P2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-calendar-check me-2" style="color:var(--c-pc);"></i>Registrar asistencia diaria (Primer Ciclo)</h4>
        <p class="sc-desc">La asistencia se toma por asignación (materia + grupo). Cada docente del Primer Ciclo registra la asistencia de su clase.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-pc);"></i>Ve a <strong>Primer Ciclo → Asistencia</strong>.</li>
            <li><i class="bi bi-list-ul text-primary"></i>Se muestran todas las asignaciones activas del docente en ese ciclo.</li>
            <li><i class="bi bi-pencil-square text-success"></i>Haz clic en <strong>"Registrar"</strong> junto a la asignación para tomar la asistencia del día.</li>
            <li><i class="bi bi-check-all text-success"></i>Usa <strong>"Marcar todos Presente"</strong> y luego ajusta los que estuvieron ausentes o llegaron tarde.</li>
            <li><i class="bi bi-floppy text-primary"></i>Haz clic en <strong>"Guardar Asistencia"</strong> al terminar.</li>
        </ul>
        <div class="tip"><i class="bi bi-clock me-1"></i>Registra la asistencia antes de empezar la clase. Si olvidas un día, puedes registrarla con fecha manual.</div>
    </div>
</div>

<div class="sc" id="p3" data-s="primer ciclo calificaciones notas competencias c1 c2 c3 c4 planilla academica periodos">
    <div class="sn pc-n">P3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-journal-check me-2" style="color:var(--c-pc);"></i>Registrar notas — Planilla Académica (Primer Ciclo)</h4>
        <p class="sc-desc">Las asignaturas del Primer Ciclo usan la <strong>Planilla Académica</strong>: 4 Competencias × 4 Períodos. Toda la planilla anual se llena en una sola vista.</p>

        <div class="mock">
            <div class="mbar">
                <div class="d" style="background:#ef4444;"></div>
                <div class="d" style="background:#f59e0b;"></div>
                <div class="d" style="background:#22c55e;"></div>
                <span class="lbl">Planilla Académica — Lengua Española | 1ro Bach. A</span>
            </div>
            <div class="row g-1">
                <div class="col-12" style="font-size:.68rem;">
                    <table class="mt" style="white-space:nowrap;">
                        <thead>
                            <tr>
                                <th class="th-n" rowspan="2" style="text-align:left;padding-left:.4rem;">Estudiante</th>
                                <th class="th-c1" colspan="5">Competencia 1</th>
                                <th class="th-c2" colspan="5">Competencia 2</th>
                                <th class="th-f">FINAL</th>
                            </tr>
                            <tr>
                                <th class="th-c1">P1</th><th class="th-c1">P2</th><th class="th-c1">P3</th><th class="th-c1">P4</th><th class="th-c1">Prom.</th>
                                <th class="th-c2">P1</th><th class="th-c2">P2</th><th class="th-c2">P3</th><th class="th-c2">P4</th><th class="th-c2">Prom.</th>
                                <th class="th-f"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align:left;">Ávila, C.</td>
                                <td><span class="mi ok">88</span></td><td><span class="mi ok">90</span></td><td><span class="mi ok">85</span></td><td><span class="mi">—</span></td>
                                <td style="font-weight:700;color:#1d4ed8;">87.7</td>
                                <td><span class="mi war">72</span></td><td><span class="mi war">68</span></td><td><span class="mi war">75</span></td><td><span class="mi">—</span></td>
                                <td style="font-weight:700;color:#15803d;">71.7</td>
                                <td style="font-weight:700;color:#15803d;">79.7</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-pc);"></i>Ve a <strong>Primer Ciclo → Registro de Notas</strong>.</li>
            <li><i class="bi bi-people text-primary"></i>Selecciona el grupo y la asignatura — el sistema carga la planilla anual automáticamente.</li>
            <li><i class="bi bi-input-cursor text-primary"></i>Ingresa las notas de cada período (P1, P2, P3, P4) para cada competencia (C1, C2, C3, C4).</li>
            <li><i class="bi bi-calculator text-success"></i>Los promedios por competencia y la nota final se calculan <strong>automáticamente</strong>.</li>
            <li><i class="bi bi-cloud-check text-success"></i>El sistema guarda automáticamente al salir de cada celda.</li>
            <li><i class="bi bi-keyboard text-secondary"></i>Atajos: <kbd>Enter</kbd> = bajar fila · <kbd>Tab</kbd> = siguiente columna · <kbd>↑↓</kbd> = subir/bajar.</li>
            <li><i class="bi bi-send text-primary"></i>Al terminar, haz clic en <strong>"Publicar"</strong> para que las notas sean visibles para coordinadores.</li>
        </ul>
    </div>
</div>

<div class="sc" id="p4" data-s="primer ciclo indicadores logro periodo competencia nivel excelente bueno en proceso insuficiente">
    <div class="sn pc-n">P4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-check2-all me-2" style="color:var(--c-pc);"></i>Registrar Indicadores de Logro (Primer Ciclo)</h4>
        <p class="sc-desc">Los indicadores de logro son descriptores específicos del MINERD por período. Para cada indicador, marca el nivel alcanzado por cada estudiante.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-table text-primary"></i>Desde la Planilla de Notas, baja al final de la página — verás la sección <strong>"Indicadores de Logro"</strong>.</li>
            <li><i class="bi bi-calendar3 text-primary"></i>Haz clic en el botón del período que deseas evaluar (Período 1, 2, 3 o 4).</li>
            <li><i class="bi bi-grid text-primary"></i>Verás la tabla de indicadores: estudiantes en filas, indicadores en columnas.</li>
            <li><i class="bi bi-toggle-on text-success"></i>Haz clic en <span style="background:#16a34a;color:#fff;padding:.1em .4em;border-radius:3px;font-size:.75rem;">E</span> Excelente · <span style="background:#2563eb;color:#fff;padding:.1em .4em;border-radius:3px;font-size:.75rem;">B</span> Bueno · <span style="background:#d97706;color:#fff;padding:.1em .4em;border-radius:3px;font-size:.75rem;">EP</span> En proceso · <span style="background:#dc2626;color:#fff;padding:.1em .4em;border-radius:3px;font-size:.75rem;">I</span> Insuficiente.</li>
            <li><i class="bi bi-cloud-check text-success"></i>El registro es inmediato — no necesitas guardar manualmente.</li>
        </ul>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Si no aparecen indicadores, ve a <strong>Gestión → Indicadores de Logro</strong> para configurarlos primero.</div>
    </div>
</div>

<div class="sc" id="p5" data-s="primer ciclo dashboard rendimiento semaforo resumen grupo">
    <div class="sn pc-n">P5</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-speedometer2 me-2" style="color:var(--c-pc);"></i>Dashboard de Rendimiento (Primer Ciclo)</h4>
        <p class="sc-desc">El dashboard muestra un resumen del rendimiento académico de todos los grupos del Primer Ciclo: promedios, semáforo de riesgo y estadísticas.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-pc);"></i>Ve a <strong>Primer Ciclo → Dashboard</strong>.</li>
            <li><i class="bi bi-bar-chart text-primary"></i>Verás el rendimiento por grupo con promedio general y distribución de notas.</li>
            <li><i class="bi bi-circle-fill text-success"></i><span class="sem sem-g"></span> Verde = promedio ≥ 80 · <span class="sem sem-y"></span> Amarillo = ≥ 70 · <span class="sem sem-r"></span> Rojo = &lt; 70 (en riesgo).</li>
            <li><i class="bi bi-funnel text-primary"></i>Puedes filtrar por grupo o ver el resumen consolidado de todo el ciclo.</li>
        </ul>
    </div>
</div>

</div>{{-- /sPC --}}


{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 3 — SEGUNDO CICLO ACADÉMICA
══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sAcad">

<div class="stb">
    <div class="iw" style="background:var(--c-acad);"><i class="bi bi-book"></i></div>
    <div>
        <h2>Segundo Ciclo — Área Académica (4to–6to)</h2>
        <p>Materias de formación general: Matemáticas, Español, Ciencias, Historia… Planilla de 4 Competencias</p>
    </div>
    <span class="rtb"><i class="bi bi-clock"></i> ~6 min</span>
</div>

<div class="sc" id="a1" data-s="segundo ciclo academica estudiantes matricular grupo 4to 5to 6to importar csv subir archivo">
    <div class="sn acad-n">A1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-plus me-2" style="color:var(--c-acad);"></i>Estudiantes del Área Académica</h4>
        <p class="sc-desc">Los estudiantes de 4to, 5to y 6to que cursan materias académicas se gestionan desde la sección del Área Académica. Puedes agregarlos uno a uno o importar desde Excel/CSV.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-acad);"></i>Ve a <strong>2do Ciclo · Académica → Estudiantes</strong>.</li>
            <li><i class="bi bi-people text-primary"></i>Verás la lista filtrada solo con estudiantes de grupos 4to–6to.</li>
            <li><i class="bi bi-person-plus text-success"></i>Para agregar uno: haz clic en <strong>"Nuevo Estudiante"</strong> → llena el formulario → matrícula en grupo de 4to–6to.</li>
            <li><i class="bi bi-upload text-primary"></i>Para importar varios: haz clic en <strong>"Importar"</strong> → descarga la plantilla CSV/Excel → llena los datos → sube el archivo. El sistema solo mostrará grupos de 4to–6to para la matrícula automática.</li>
            <li><i class="bi bi-eye text-primary"></i>Haz clic en un estudiante para ver su expediente con historial de notas y asistencia.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Al importar, activa "Matricular automáticamente" y selecciona el grupo de destino para inscribir a todos los estudiantes del archivo en un solo paso.</div>
    </div>
</div>

<div class="sc" id="a2" data-s="segundo ciclo academica notas competencias planilla anual 4to 5to 6to">
    <div class="sn acad-n">A2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-journal-check me-2" style="color:var(--c-acad);"></i>Registrar notas académicas (Segundo Ciclo)</h4>
        <p class="sc-desc">El proceso es idéntico al Primer Ciclo: planilla de 4 Competencias × 4 Períodos. La diferencia está en los grupos (4to–6to) y en que estas notas alimentan el Área Académica.</p>

        <div class="flow">
            <span class="flow-step f-acad"><i class="bi bi-layout-sidebar me-1"></i>2do Ciclo · Académica</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-acad"><i class="bi bi-journal-check me-1"></i>Registro de Notas</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-acad"><i class="bi bi-people me-1"></i>Seleccionar grupo</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-acad"><i class="bi bi-table me-1"></i>Planilla Anual</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-blt"><i class="bi bi-send me-1"></i>Publicar</span>
        </div>

        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-acad);"></i>Ve a <strong>2do Ciclo · Académica → Registro de Notas</strong>.</li>
            <li><i class="bi bi-people text-primary"></i>Selecciona el grupo (4to, 5to o 6to) y la asignatura académica.</li>
            <li><i class="bi bi-table text-primary"></i>Se abre la <strong>Planilla Anual</strong>: ingresa notas de cada competencia (C1–C4) por período (P1–P4).</li>
            <li><i class="bi bi-calculator text-success"></i>Promedios por competencia y nota final se calculan automáticamente.</li>
            <li><i class="bi bi-send text-primary"></i>Al terminar, pulsa <strong>"Publicar"</strong> para hacerlas visibles.</li>
        </ul>

        <div class="tip"><i class="bi bi-info-circle me-1"></i>Si una asignatura tiene el badge <span style="background:#ede9fe;color:#6d28d9;padding:.1em .4em;border-radius:3px;font-size:.75rem;">RA</span>, usa el flujo técnico (Tab "2do Ciclo · Técnica").</div>
    </div>
</div>

<div class="sc" id="a3" data-s="malla curricular academica asignatura grado secuencia">
    <div class="sn acad-n">A3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-diagram-3 me-2" style="color:var(--c-acad);"></i>Malla Curricular Académica</h4>
        <p class="sc-desc">La malla muestra la distribución oficial de asignaturas por grado para el Área Académica, siguiendo la estructura del MINERD.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-acad);"></i>Ve a <strong>2do Ciclo · Académica → Malla Curricular</strong>.</li>
            <li><i class="bi bi-grid text-primary"></i>Verás la tabla de asignaturas organizadas por grado y área de conocimiento.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Administradores pueden agregar/editar materias de la malla desde esta vista.</li>
            <li><i class="bi bi-file-earmark-pdf text-danger"></i>Puedes exportar la malla en PDF para tenerla de referencia impresa.</li>
        </ul>
    </div>
</div>

<div class="sc" id="a4" data-s="docentes academica area segundo ciclo listado">
    <div class="sn acad-n">A4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-people me-2" style="color:var(--c-acad);"></i>Docentes del Área Académica</h4>
        <p class="sc-desc">Desde esta sección puedes ver qué docentes imparten clases en el Área Académica del Segundo Ciclo y sus asignaciones activas.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-acad);"></i>Ve a <strong>2do Ciclo · Académica → Docentes del Área</strong>.</li>
            <li><i class="bi bi-list-ul text-primary"></i>Se muestra la lista de docentes con sus grupos y asignaturas del área académica.</li>
            <li><i class="bi bi-person-check text-success"></i>Verifica que cada docente tenga al menos una asignación activa para este año.</li>
        </ul>
    </div>
</div>

</div>{{-- /sAcad --}}


{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 4 — SEGUNDO CICLO TÉCNICA (RA)
══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sTec">

<div class="stb">
    <div class="iw" style="background:var(--c-tec);"><i class="bi bi-tools"></i></div>
    <div>
        <h2>Segundo Ciclo — Área Técnica (4to–6to)</h2>
        <p>Especialidades: Turismo, Informática, Mercadeo, Acondicionamiento Físico, Logística · Evaluación por RA</p>
    </div>
    <span class="rtb"><i class="bi bi-clock"></i> ~8 min</span>
</div>

<div class="sc" id="t0" data-s="segundo ciclo tecnica estudiantes matricular importar csv subir 4to 5to 6to">
    <div class="sn tec-n">T0</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-plus me-2" style="color:var(--c-tec);"></i>Estudiantes del Área Técnica</h4>
        <p class="sc-desc">Los estudiantes de 4to, 5to y 6to del área técnica se gestionan igual que en el área académica, pero desde la sección correspondiente. El listado muestra solo grupos técnicos.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-tec);"></i>Ve a <strong>2do Ciclo · Técnica → Estudiantes</strong>.</li>
            <li><i class="bi bi-people text-primary"></i>Verás la lista filtrada a estudiantes de grupos 4to–6to con especialidad técnica.</li>
            <li><i class="bi bi-person-plus text-success"></i>Para agregar uno: <strong>"Nuevo Estudiante"</strong> → llena el formulario → matrícula en grupo técnico.</li>
            <li><i class="bi bi-upload text-primary"></i>Para importar varios: haz clic en <strong>"Importar Estudiantes"</strong> → sube el archivo CSV/Excel. Solo aparecerán grupos de 4to–6to para la matrícula automática.</li>
            <li><i class="bi bi-award text-primary"></i>Recuerda luego asignar la <strong>especialidad técnica</strong> desde <strong>Especialidades → Asignar Docente</strong> si aplica al coordinador.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Un estudiante puede estar en el Área Académica y Técnica a la vez — son matrículas distintas por grupo.</div>
    </div>
</div>

<div class="sc" id="t1" data-s="especialidades tecnicas crear turismo informatica mercadeo acondicionamiento logistica">
    <div class="sn tec-n">T1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-award me-2" style="color:var(--c-tec);"></i>Gestionar Especialidades Técnicas</h4>
        <p class="sc-desc">Las especialidades son las carreras técnicas del plantel. Cada especialidad tiene sus docentes y su propio conjunto de asignaturas técnicas.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>El PSAC tiene: Turismo · Informática · Mercadeo · Acondicionamiento Físico · Logística y Transporte</div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-tec);"></i>Ve a <strong>2do Ciclo · Técnica → Especialidades</strong>.</li>
            <li><i class="bi bi-list-ul text-primary"></i>Verás las especialidades configuradas con sus docentes asignados.</li>
            <li><i class="bi bi-person-plus text-success"></i>Para agregar un docente a una especialidad, haz clic en la especialidad → <strong>"Asignar Docente"</strong>.</li>
            <li><i class="bi bi-plus-circle text-primary"></i>Puedes crear nuevas especialidades si el plantel las agrega en el futuro.</li>
        </ul>
    </div>
</div>

<div class="sc" id="t2" data-s="area tecnica grilla ra notas resultados aprendizaje periodo trimestre ingresar">
    <div class="sn tec-n">T2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-bar-chart-steps me-2" style="color:var(--c-tec);"></i>Registrar notas técnicas — Grilla de RA</h4>
        <p class="sc-desc">Las asignaturas técnicas con evaluación por RA usan una grilla diferente: se selecciona el período y se ingresa la nota de cada Resultado de Aprendizaje.</p>

        <div class="mock">
            <div class="mbar">
                <div class="d" style="background:#ef4444;"></div>
                <div class="d" style="background:#f59e0b;"></div>
                <div class="d" style="background:#22c55e;"></div>
                <span class="lbl">Grilla RA — Programación Avanzada | 4to Bach. A | 1er Trimestre</span>
            </div>
            <table class="mt" style="white-space:nowrap;">
                <thead>
                    <tr>
                        <th class="th-n" style="text-align:left;padding-left:.3rem;">Estudiante</th>
                        <th class="th-ra">RA1 <small style="opacity:.7;">30%</small></th>
                        <th class="th-ra">RA2 <small style="opacity:.7;">40%</small></th>
                        <th class="th-ra">RA3 <small style="opacity:.7;">30%</small></th>
                        <th class="th-f">FINAL</th>
                        <th class="th-cc">C.C</th>
                        <th class="th-cc">COMP.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:left;">García, M.</td>
                        <td><span class="mi ok">88</span></td>
                        <td><span class="mi ok">92</span></td>
                        <td><span class="mi war">72</span></td>
                        <td style="font-weight:700;color:#15803d;">84.8</td>
                        <td><span class="mi">—</span></td>
                        <td style="color:#9ca3af;">—</td>
                    </tr>
                    <tr>
                        <td style="text-align:left;">Herrera, J.</td>
                        <td><span class="mi war">65</span></td>
                        <td><span class="mi bad">48</span></td>
                        <td><span class="mi war">70</span></td>
                        <td style="font-weight:700;color:#991b1b;">60.7</td>
                        <td><span class="mi war">72</span></td>
                        <td style="font-weight:700;color:#6d28d9;">66.4</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flow">
            <span class="flow-step f-tec"><i class="bi bi-layout-sidebar me-1"></i>2do Ciclo · Técnica</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-tec">Registro de Notas</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-tec">Grupo → Asignatura RA</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-tec">Período</span>
            <span class="flow-arrow">→</span>
            <span class="flow-step f-tec">Ir a Grilla</span>
        </div>

        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-tec);"></i>Ve a <strong>2do Ciclo · Técnica → Registro de Notas</strong>.</li>
            <li><i class="bi bi-people text-primary"></i>Selecciona el grupo y la asignatura técnica — identificada con el badge <span style="background:#ede9fe;color:#6d28d9;padding:.1em .4em;border-radius:3px;font-size:.75rem;">RA</span>.</li>
            <li><i class="bi bi-calendar3 text-primary"></i>Selecciona el <strong>Período</strong> (trimestre) a calificar.</li>
            <li><i class="bi bi-grid text-success"></i>Haz clic en <strong>"Ir a Grilla de Notas"</strong>.</li>
            <li><i class="bi bi-input-cursor text-primary"></i>Ingresa la nota de cada RA por estudiante (0–100).</li>
            <li><i class="bi bi-calculator text-success"></i>El <strong>Promedio Final ponderado</strong> se calcula según el % de cada RA.</li>
            <li><i class="bi bi-send text-primary"></i>Publica las notas al terminar el período.</li>
        </ul>
    </div>
</div>

<div class="sc" id="t3" data-s="completivo extraordinario cc ce formula calculo notas especiales">
    <div class="sn tec-n">T3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-calculator me-2" style="color:var(--c-tec);"></i>Completivo y Extraordinario (Área Técnica)</h4>
        <p class="sc-desc">Si un estudiante no aprueba con el Promedio Final, puede presentar prueba completiva o extraordinaria. Las fórmulas se aplican automáticamente.</p>
        <div class="mock">
            <div class="mbar"><div class="d" style="background:#ef4444;"></div><div class="d" style="background:#f59e0b;"></div><div class="d" style="background:#22c55e;"></div></div>
            <div class="row g-2" style="font-size:.7rem;">
                <div class="col-6" style="background:#ede9fe;border-radius:8px;padding:.5rem;">
                    <div class="fw-bold" style="color:#6d28d9;">COMPLETIVO</div>
                    <div style="color:#374151;">= 50% × Final + 50% × C.C</div>
                    <div style="color:#6b7280;font-size:.65rem;margin-top:.2rem;">Ej: Final=58, C.C=72 → Comp.=65.0</div>
                </div>
                <div class="col-6" style="background:#fef3c7;border-radius:8px;padding:.5rem;">
                    <div class="fw-bold" style="color:#92400e;">EXTRAORDINARIO</div>
                    <div style="color:#374151;">= 30% × Final + 70% × C.E</div>
                    <div style="color:#6b7280;font-size:.65rem;margin-top:.2rem;">Ej: Final=45, C.E=75 → Extra.=66.0</div>
                </div>
            </div>
        </div>
        <ul class="sc-steps">
            <li><i class="bi bi-pencil" style="color:var(--c-tec);"></i>En la grilla, columna <strong>C.C</strong>: ingresa la nota de la prueba completiva manualmente.</li>
            <li><i class="bi bi-calculator text-success"></i>La columna <strong>COMPLETIVO</strong> se calcula sola: 50% Final + 50% C.C.</li>
            <li><i class="bi bi-pencil" style="color:#92400e;"></i>Columna <strong>C.E</strong>: ingresa la nota extraordinaria si aplica.</li>
            <li><i class="bi bi-calculator" style="color:#92400e;"></i>La columna <strong>EXTRAORDINARIO</strong> se calcula: 30% Final + 70% C.E.</li>
            <li><i class="bi bi-exclamation-triangle text-warning"></i>Solo rellena completivo/extraordinario para estudiantes que lo presentaron — deja en blanco los demás.</li>
        </ul>
    </div>
</div>

<div class="sc" id="t4" data-s="configurar ra pesos equivalente distribucion equitativa suma 100">
    <div class="sn tec-n">T4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-sliders me-2" style="color:var(--c-tec);"></i>Configurar % equivalente de cada RA</h4>
        <p class="sc-desc">Define cuánto vale cada Resultado de Aprendizaje en el promedio final. La suma de todos debe ser 100%.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Configuración → Config. RA</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-gear text-primary"></i>Ve a <strong>Configuración → Config. RA</strong>.</li>
            <li><i class="bi bi-list-ul text-primary"></i>Selecciona la asignatura técnica en el panel izquierdo.</li>
            <li><i class="bi bi-percent text-success"></i>Ajusta el % de cada RA — deben sumar exactamente <strong>100%</strong>.</li>
            <li><i class="bi bi-distribute-vertical text-primary"></i>Usa <strong>"Distribuir Equitativamente"</strong> para que el sistema calcule 100% ÷ cantidad de RAs.</li>
            <li><i class="bi bi-exclamation-triangle text-warning"></i>Cambiar los pesos <strong>recalcula automáticamente</strong> el promedio de todos los estudiantes.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Ejemplo: 3 RAs con distribución 30% / 40% / 30% → suma = 100% ✓</div>
    </div>
</div>

<div class="sc" id="t5" data-s="publicar tecnica grilla periodo publicado visible coordinador director boletin">
    <div class="sn tec-n">T5</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-send-check me-2" style="color:var(--c-tec);"></i>Publicar calificaciones técnicas</h4>
        <p class="sc-desc">Las notas son privadas hasta que el docente las publique. Sin publicar, no aparecen en boletines ni reportes.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-eye-slash text-warning"></i>Las notas ingresadas son <strong>privadas por defecto</strong>.</li>
            <li><i class="bi bi-send text-primary"></i>En la barra superior de la grilla, haz clic en <strong>"Publicar"</strong>.</li>
            <li><i class="bi bi-check-circle text-success"></i>El botón cambia a <span class="mb mb-s"><i class="bi bi-check-circle me-1"></i>Publicado ✓</span> cuando está activo.</li>
            <li><i class="bi bi-arrow-counterclockwise text-secondary"></i>Un administrador puede despublicar para hacer correcciones si es necesario.</li>
            <li><i class="bi bi-exclamation-triangle text-warning"></i>Revisa todas las notas antes de publicar — especialmente el completivo/extraordinario.</li>
        </ul>
    </div>
</div>

</div>{{-- /sTec --}}


{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 5 — ASISTENCIA
══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sAsist">

<div class="stb">
    <div class="iw" style="background:var(--c-asist);"><i class="bi bi-calendar-check"></i></div>
    <div>
        <h2>Registro de Asistencia</h2>
        <p>Control diario de asistencia para todos los ciclos · Historial · Reportes por estudiante</p>
    </div>
    <span class="rtb"><i class="bi bi-clock"></i> ~5 min</span>
</div>

<div class="sc" id="as1" data-s="asistencia diaria tomar registrar lista asignaciones pendiente tomada hoy">
    <div class="sn asist-n">AS1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-check me-2" style="color:var(--c-asist);"></i>Tomar asistencia del día</h4>
        <p class="sc-desc">Cada docente toma la asistencia desde el módulo de Asistencia de su ciclo/área. El sistema muestra solo las asignaciones activas de ese docente.</p>

        <div class="mock">
            <div class="mbar">
                <div class="d" style="background:#ef4444;"></div>
                <div class="d" style="background:#f59e0b;"></div>
                <div class="d" style="background:#22c55e;"></div>
                <span class="lbl">Asistencia — Martes 17 marzo 2026</span>
            </div>
            @foreach([
                ['Matemáticas','1ro Bach. A','done'],
                ['Programación Avanzada','4to Bach. A','pending'],
                ['Redes y Comunicaciones','4to Bach. A','pending'],
            ] as $r)
            <div class="row-item">
                <div style="flex:1;">
                    <div style="font-size:.78rem;font-weight:700;color:#1e293b;">{{ $r[0] }}</div>
                    <div style="font-size:.65rem;color:#6b7280;">{{ $r[1] }}</div>
                </div>
                @if($r[2]==='done')
                    <span class="badge-pill att-p"><i class="bi bi-check-circle-fill me-1"></i>Tomada hoy</span>
                @else
                    <span class="badge-pill" style="background:#fef9c3;color:#854d0e;"><i class="bi bi-clock me-1"></i>Pendiente</span>
                    <span class="mb mb-p" style="font-size:.68rem;"><i class="bi bi-pencil me-1"></i>Registrar</span>
                @endif
            </div>
            @endforeach
        </div>

        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-asist);"></i>Ve a <strong>[Tu Ciclo] → Asistencia</strong> en el menú lateral.</li>
            <li><i class="bi bi-list-ul text-primary"></i>Se muestran tus asignaciones activas con estado "Tomada hoy" o "Pendiente".</li>
            <li><i class="bi bi-pencil-square text-success"></i>Haz clic en <strong>"Registrar"</strong> junto a la asignación.</li>
            <li><i class="bi bi-check-all text-success"></i>Usa <strong>"Marcar todos Presente"</strong> y luego ajusta los ausentes/tardanzas.</li>
            <li><i class="bi bi-floppy text-primary"></i>Haz clic en <strong>"Guardar Asistencia"</strong>.</li>
        </ul>
        <div class="warn"><i class="bi bi-exclamation-triangle me-1"></i>Si olvidas un día, puedes cambiar la fecha manualmente en el formulario de registro.</div>
    </div>
</div>

<div class="sc" id="as2" data-s="estados asistencia presente ausente tardanza excusa retiro significado">
    <div class="sn asist-n">AS2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-tags me-2" style="color:var(--c-asist);"></i>Estados de asistencia disponibles</h4>
        <p class="sc-desc">El sistema maneja 5 estados posibles para cada estudiante por clase.</p>
        <div class="mock">
            <div class="mbar"><div class="d" style="background:#ef4444;"></div><div class="d" style="background:#f59e0b;"></div><div class="d" style="background:#22c55e;"></div></div>
            <div class="d-flex flex-wrap gap-2" style="font-size:.75rem;">
                <span class="badge-pill att-p px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Presente — asistió normalmente</span>
                <span class="badge-pill att-a px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Ausente — no asistió sin justificación</span>
                <span class="badge-pill att-t px-2 py-1"><i class="bi bi-clock-fill me-1"></i>Tardanza — llegó después del inicio</span>
                <span class="badge-pill" style="background:#dbeafe;color:#1d4ed8;" class="px-2 py-1"><i class="bi bi-shield-check me-1"></i>Excusa — ausencia justificada</span>
                <span class="badge-pill" style="background:#f3e8ff;color:#7c3aed;" class="px-2 py-1"><i class="bi bi-box-arrow-right me-1"></i>Retiro — salida anticipada</span>
            </div>
        </div>
        <ul class="sc-steps">
            <li><i class="bi bi-check-circle text-success"></i><strong>Presente:</strong> el estudiante asistió a la clase completa.</li>
            <li><i class="bi bi-x-circle text-danger"></i><strong>Ausente:</strong> no asistió y no tiene justificación.</li>
            <li><i class="bi bi-clock text-warning"></i><strong>Tardanza:</strong> llegó después de empezada la clase.</li>
            <li><i class="bi bi-shield-check text-primary"></i><strong>Excusa:</strong> ausencia con justificación válida (médica, familiar, etc.).</li>
            <li><i class="bi bi-box-arrow-right" style="color:#7c3aed;"></i><strong>Retiro:</strong> llegó pero tuvo que salir antes de terminar la clase.</li>
        </ul>
    </div>
</div>

<div class="sc" id="as3" data-s="historial asistencia grilla mensual calendar ver fechas dias">
    <div class="sn asist-n">AS3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-calendar3 me-2" style="color:var(--c-asist);"></i>Consultar historial de asistencia</h4>
        <p class="sc-desc">Puedes ver el historial completo de asistencia en formato de grilla mensual o en tabla por fechas.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-clock-history text-primary"></i>En el módulo de Asistencia, haz clic en <strong>"Historial"</strong> junto a la asignación.</li>
            <li><i class="bi bi-calendar3 text-primary"></i>Se muestra una grilla mensual: <span class="badge-pill att-p">P</span> Verde = Presente · <span class="badge-pill att-a">A</span> Rojo = Ausente · <span class="badge-pill att-t">T</span> Amarillo = Tardanza.</li>
            <li><i class="bi bi-arrow-left-right text-secondary"></i>Navega entre meses con las flechas de navegación.</li>
            <li><i class="bi bi-table text-primary"></i>También puedes usar la <strong>vista de grilla</strong> para ver varios estudiantes en columnas por día del mes.</li>
        </ul>
    </div>
</div>

<div class="sc" id="as4" data-s="reporte individual estudiante asistencia porcentaje total clases presentes ausentes">
    <div class="sn asist-n">AS4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-lines-fill me-2" style="color:var(--c-asist);"></i>Reporte individual de asistencia por estudiante</h4>
        <p class="sc-desc">Consulta el resumen estadístico de asistencia de cada estudiante a través de todas sus asignaturas.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-people text-primary"></i>Ve a <strong>Gestión → Matrículas</strong> → busca el estudiante → <strong>"Reporte Asistencia"</strong>.</li>
            <li><i class="bi bi-pie-chart text-success"></i>Verás el % de asistencia por asignatura con totales de presentes, ausentes y tardanzas.</li>
            <li><i class="bi bi-exclamation-triangle text-warning"></i>Si el % es menor al <strong>75%</strong>, aparece en alerta roja — riesgo de reprobar por inasistencia.</li>
        </ul>
    </div>
</div>

</div>{{-- /sAsist --}}


{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 6 — INDICADORES Y REPORTES
══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sRep">

<div class="stb">
    <div class="iw" style="background:var(--c-rep);"><i class="bi bi-bar-chart-fill"></i></div>
    <div>
        <h2>Indicadores de Logro, Boletines y Reportes</h2>
        <p>Evaluación por competencias MINERD · Boletines · Dashboard Institucional · Semáforo académico</p>
    </div>
    <span class="rtb"><i class="bi bi-clock"></i> ~8 min</span>
</div>

<div class="sc" id="r1" data-s="indicadores aprendizaje logro crear gestionar asignatura grado periodo descripcion">
    <div class="sn rep-n">R1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-plus-circle me-2" style="color:var(--c-rep);"></i>Crear y gestionar Indicadores de Logro</h4>
        <p class="sc-desc">Antes de evaluar indicadores, deben estar configurados por asignatura, grado y período. Un administrador o coordinador los configura al inicio del año.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión → Indicadores de Logro</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Gestión → Indicadores de Logro</strong>.</li>
            <li><i class="bi bi-funnel text-primary"></i>Filtra por asignatura, grado y período para ver los indicadores existentes.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Para agregar: rellena asignatura, grado, período, descripción del indicador y número de orden.</li>
            <li><i class="bi bi-pencil text-primary"></i>Puedes editar o desactivar indicadores existentes sin eliminarlos.</li>
            <li><i class="bi bi-repeat text-secondary"></i>Configura indicadores para <strong>cada período</strong> (P1, P2, P3, P4) de cada asignatura y grado.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Los indicadores siguen la malla curricular del MINERD — consúltalos en los documentos oficiales de cada asignatura.</div>
    </div>
</div>

<div class="sc" id="r2" data-s="evaluar indicadores estudiante nivel excelente bueno proceso insuficiente registrar">
    <div class="sn rep-n">R2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-check2-all me-2" style="color:var(--c-rep);"></i>Registrar evaluación de indicadores por período</h4>
        <p class="sc-desc">El docente evalúa cada indicador de logro marcando el nivel de cada estudiante. Se accede desde la Planilla de Notas o directamente desde el menú.</p>

        <div class="mock">
            <div class="mbar"><div class="d" style="background:#ef4444;"></div><div class="d" style="background:#f59e0b;"></div><div class="d" style="background:#22c55e;"></div><span class="lbl">Indicadores de Logro — Matemáticas | 1er Período</span></div>
            <table class="mt" style="white-space:nowrap;">
                <thead>
                    <tr>
                        <th class="th-n" style="text-align:left;padding-left:.3rem;">Estudiante</th>
                        <th class="th-ind">Ind. 1: Resuelve<br>ecuaciones</th>
                        <th class="th-ind">Ind. 2: Analiza<br>gráficas</th>
                        <th class="th-ind">Ind. 3: Aplica<br>fórmulas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:left;">Ávila, C.</td>
                        <td><span class="mb mb-s" style="font-size:.62rem;">E</span></td>
                        <td><span class="mb mb-i" style="font-size:.62rem;background:#2563eb;">B</span></td>
                        <td><span class="mb mb-w" style="font-size:.62rem;">EP</span></td>
                    </tr>
                    <tr>
                        <td style="text-align:left;">Beltrán, S.</td>
                        <td><span class="mb mb-w" style="font-size:.62rem;">EP</span></td>
                        <td><span class="mb mb-d" style="font-size:.62rem;">I</span></td>
                        <td><span class="mb mb-d" style="font-size:.62rem;">I</span></td>
                    </tr>
                </tbody>
            </table>
            <div class="d-flex gap-2 mt-2" style="font-size:.65rem;">
                <span style="background:#16a34a;color:#fff;padding:.1em .4em;border-radius:3px;">E = Excelente</span>
                <span style="background:#2563eb;color:#fff;padding:.1em .4em;border-radius:3px;">B = Bueno</span>
                <span style="background:#d97706;color:#fff;padding:.1em .4em;border-radius:3px;">EP = En proceso</span>
                <span style="background:#dc2626;color:#fff;padding:.1em .4em;border-radius:3px;">I = Insuficiente</span>
            </div>
        </div>

        <ul class="sc-steps">
            <li><i class="bi bi-table text-primary"></i>Desde la <strong>Planilla de Notas</strong>, baja al final y haz clic en <strong>"Registrar evaluaciones"</strong> del período.</li>
            <li><i class="bi bi-grid text-primary"></i>Verás la tabla: estudiantes en filas, indicadores en columnas.</li>
            <li><i class="bi bi-toggle-on text-success"></i>Haz clic en <span style="background:#16a34a;color:#fff;padding:.1em .3em;border-radius:3px;font-size:.72rem;">E</span> <span style="background:#2563eb;color:#fff;padding:.1em .3em;border-radius:3px;font-size:.72rem;">B</span> <span style="background:#d97706;color:#fff;padding:.1em .3em;border-radius:3px;font-size:.72rem;">EP</span> <span style="background:#dc2626;color:#fff;padding:.1em .3em;border-radius:3px;font-size:.72rem;">I</span> para marcar el nivel de logro.</li>
            <li><i class="bi bi-cloud-check text-success"></i>El registro es <strong>instantáneo</strong> — no hay botón de guardar.</li>
            <li><i class="bi bi-arrow-left-right text-secondary"></i>Usa el selector de período en la parte superior para cambiar de trimestre.</li>
        </ul>
    </div>
</div>

<div class="sc" id="r3" data-s="boletin generar imprimir pdf estudiante grupo notas asistencia periodo">
    <div class="sn rep-n">R3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-file-earmark-text me-2" style="color:var(--c-rep);"></i>Generar Boletines de Calificaciones</h4>
        <p class="sc-desc">Los boletines consolidan notas y asistencia de un estudiante en un documento imprimible. Solo se generan con notas <strong>publicadas</strong>.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión → Boletines</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Gestión → Boletines</strong>.</li>
            <li><i class="bi bi-people text-primary"></i>Selecciona el <strong>Grupo</strong> y el <strong>Período</strong>.</li>
            <li><i class="bi bi-eye text-success"></i>Haz clic en <strong>"Ver Boletín"</strong> del estudiante para previsualizar en pantalla.</li>
            <li><i class="bi bi-file-earmark-pdf text-danger"></i>Usa <strong>"Descargar PDF"</strong> para obtener el boletín individual imprimible.</li>
            <li><i class="bi bi-file-zip text-warning"></i>Para descargar todos los boletines del grupo de una vez, usa <strong>"Exportar ZIP"</strong> — genera un archivo .zip con un PDF por estudiante (máx. 60 estudiantes por grupo).</li>
            <li><i class="bi bi-printer text-secondary"></i>Alternativamente, <strong>"Imprimir Todos"</strong> abre todos en el navegador para imprimir directamente.</li>
        </ul>
        <div class="warn"><i class="bi bi-exclamation-triangle me-1"></i>Si las notas no han sido publicadas por el docente, el boletín aparecerá incompleto.</div>
    </div>
</div>

<div class="sc" id="r4" data-s="dashboard rendimiento institucional promedio general grupos semaforo riesgo alertas">
    <div class="sn rep-n">R4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-speedometer2 me-2" style="color:var(--c-rep);"></i>Dashboard de Rendimiento Institucional</h4>
        <p class="sc-desc">Vista ejecutiva del rendimiento académico de todo el plantel: promedios por grupo, semáforo de riesgo, comparativa por área.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Rendimiento Institucional → Dashboard General</strong></div>

        <div class="mock">
            <div class="mbar"><div class="d" style="background:#ef4444;"></div><div class="d" style="background:#f59e0b;"></div><div class="d" style="background:#22c55e;"></div><span class="lbl">Dashboard General — Año 2025-2026</span></div>
            <div class="row g-2">
                <div class="col-4 text-center" style="background:#dcfce7;border-radius:8px;padding:.5rem;">
                    <div style="font-size:1.3rem;font-weight:800;color:#15803d;">156</div>
                    <div style="font-size:.65rem;color:#065f46;">Matrículas activas</div>
                </div>
                <div class="col-4 text-center" style="background:#dbeafe;border-radius:8px;padding:.5rem;">
                    <div style="font-size:1.3rem;font-weight:800;color:#1d4ed8;">78.4</div>
                    <div style="font-size:.65rem;color:#1e40af;">Promedio general</div>
                </div>
                <div class="col-4 text-center" style="background:#fee2e2;border-radius:8px;padding:.5rem;">
                    <div style="font-size:1.3rem;font-weight:800;color:#dc2626;">12</div>
                    <div style="font-size:.65rem;color:#991b1b;">En riesgo (&lt;70)</div>
                </div>
            </div>
            <div class="mt-2" style="font-size:.68rem;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="sem sem-g"></span> 3ro Bach. A — Prom. 82.1 (18/20 estudiantes ≥70)
                </div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="sem sem-y"></span> 4to Bach. B — Prom. 73.4 (15/22 estudiantes ≥70)
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="sem sem-r"></span> 5to Bach. A — Prom. 65.2 (8/20 estudiantes &lt;70)
                </div>
            </div>
        </div>

        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-rep);"></i>Ve a <strong>Rendimiento Institucional → Dashboard General</strong>.</li>
            <li><i class="bi bi-circle-fill text-success"></i><span class="sem sem-g"></span> Verde = promedio ≥ 80 · <span class="sem sem-y"></span> Amarillo = 70–79 · <span class="sem sem-r"></span> Rojo = &lt; 70.</li>
            <li><i class="bi bi-bar-chart text-primary"></i>Ve la distribución por grupo, por ciclo y por área.</li>
            <li><i class="bi bi-people text-danger"></i>La sección <strong>"En riesgo"</strong> lista a los estudiantes con promedio menor a 70 para intervención.</li>
        </ul>
    </div>
</div>

<div class="sc" id="r5" data-s="semaforo academico estudiantes riesgo promedio bajo alerta color rojo amarillo">
    <div class="sn rep-n">R5</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-traffic-light me-2" style="color:var(--c-rep);"></i>Semáforo Académico</h4>
        <p class="sc-desc">El semáforo permite identificar rápidamente qué estudiantes están en riesgo académico y requieren atención o seguimiento especial.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Rendimiento Institucional → Semáforo</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-rep);"></i>Ve a <strong>Rendimiento Institucional → Semáforo</strong>.</li>
            <li><i class="bi bi-circle-fill text-success"></i>Cada estudiante tiene un indicador de color según su promedio general.</li>
            <li><i class="bi bi-funnel text-primary"></i>Filtra por grupo, ciclo o área para identificar casos específicos.</li>
            <li><i class="bi bi-person-exclamation text-danger"></i>Los estudiantes en <strong>rojo (&lt;70)</strong> deben recibir seguimiento y posiblemente activar un plan de intervención.</li>
        </ul>
    </div>
</div>

<div class="sc" id="r6" data-s="reporte consolidado grupo calificaciones situacion final aprobados reprobados">
    <div class="sn rep-n">R6</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-table me-2" style="color:var(--c-rep);"></i>Reportes Consolidados y Situación Final</h4>
        <p class="sc-desc">Los reportes consolidados muestran todas las calificaciones de un grupo en una sola vista. La situación final indica si el estudiante aprobó o reprobó.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Reportes Institucionales</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar" style="color:var(--c-rep);"></i>Ve a <strong>Reportes Institucionales</strong> en el menú lateral.</li>
            <li><i class="bi bi-table text-primary"></i><strong>Consolidado por Grupo:</strong> selecciona un grupo para ver todas las notas de todos los estudiantes en una tabla.</li>
            <li><i class="bi bi-check2-circle text-success"></i><strong>Situación Final:</strong> muestra <span style="background:#dcfce7;color:#15803d;padding:.1em .4em;border-radius:3px;font-size:.75rem;">A = Aprobado</span> o <span style="background:#fee2e2;color:#991b1b;padding:.1em .4em;border-radius:3px;font-size:.75rem;">R = Reprobado</span> por asignatura.</li>
            <li><i class="bi bi-file-earmark-pdf text-danger"></i>Exporta el consolidado en <strong>PDF o Excel</strong> para entregar a secretaría o directivos.</li>
        </ul>
    </div>
</div>

<div class="sc" id="r7" data-s="resumen notas ranking estudiante mejor promedio posicion desempeño">
    <div class="sn rep-n">R7</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-trophy me-2" style="color:var(--c-rep);"></i>Resumen de Notas y Ranking</h4>
        <p class="sc-desc">El resumen muestra el desempeño de cada estudiante por asignatura. El ranking ordena a los estudiantes por promedio general descendente.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-bar-chart text-primary"></i>Ve a <strong>Gestión → Resumen de Notas</strong> para ver un cuadro completo por asignatura.</li>
            <li><i class="bi bi-trophy text-warning"></i>Ve a <strong>Gestión → Ranking</strong> para ver la clasificación por promedio general.</li>
            <li><i class="bi bi-funnel text-primary"></i>Filtra por grupo o por período para comparaciones específicas.</li>
        </ul>
    </div>
</div>

<div class="sc" id="r8" data-s="alertas sistema notificaciones entrega notas riesgo academico campana">
    <div class="sn rep-n">R8</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-bell me-2" style="color:var(--c-rep);"></i>Alertas del Sistema</h4>
        <p class="sc-desc">El sistema genera alertas automáticas para recordar entregas de notas, estudiantes en riesgo académico y eventos importantes.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-bell text-warning"></i>Las alertas aparecen en el ícono de campana en la barra superior.</li>
            <li><i class="bi bi-exclamation-triangle text-danger"></i><strong>Riesgo académico:</strong> se genera automáticamente cuando un estudiante tiene nota final &lt; 70.</li>
            <li><i class="bi bi-clock text-warning"></i><strong>Entrega de notas:</strong> el sistema notifica a los docentes 3 días antes del cierre del período.</li>
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Alertas</strong> en el menú lateral para ver todas las alertas con su estado.</li>
            <li><i class="bi bi-check2 text-success"></i>Marca las alertas como leídas después de atenderlas.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Los coordinadores y directores reciben las mismas alertas de riesgo académico de sus estudiantes.</div>
    </div>
</div>

</div>{{-- /sRep --}}

{{-- ══════════════════════════════════════════════════════════════
     SECCIÓN 7 — HORARIOS
══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sHor">

<div class="stb">
    <div class="iw" style="background:var(--c-hor);"><i class="bi bi-calendar-week-fill"></i></div>
    <div>
        <h2>Módulo de Horarios</h2>
        <p>Generación automática · Edición manual · Vista del docente · Horario del estudiante</p>
    </div>
</div>

{{-- ─── Arquitectura del módulo ─────────────────────────── --}}
<div class="sc" id="h0" data-s="horario arquitectura roles administrador docente estudiante representante publicar visualizar">
    <div class="sn hor-n">H0</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-diagram-3 me-2" style="color:var(--c-hor);"></i>¿Cómo funciona el módulo de horarios?</h4>
        <p class="sc-desc">El módulo sigue una separación clara de roles: el administrador <strong>gestiona</strong> los horarios, y el docente y el estudiante solo <strong>visualizan</strong> el resultado.</p>
        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:1rem;">
                    <p style="font-size:.72rem;font-weight:800;color:#1d4ed8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem;">
                        <i class="bi bi-shield-lock-fill me-1"></i>Administrador
                    </p>
                    <ul style="font-size:.8rem;color:#374151;margin:0;padding-left:1.1rem;">
                        <li>Genera el horario automáticamente</li>
                        <li>Edita celdas de forma manual</li>
                        <li>Configura franjas, aulas, disponibilidad</li>
                        <li>Publica o despublica el horario</li>
                        <li>Regenera si el resultado tiene conflictos</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1rem;">
                    <p style="font-size:.72rem;font-weight:800;color:#047857;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem;">
                        <i class="bi bi-person-badge me-1"></i>Docente
                    </p>
                    <ul style="font-size:.8rem;color:#374151;margin:0;padding-left:1.1rem;">
                        <li>Ve <strong>solo sus clases</strong> — no todo el horario</li>
                        <li>Ve aula, grupo y horario por día</li>
                        <li>Accede desde el menú: <strong>Gestión → Mi Horario</strong></li>
                        <li>También disponible como miniatura en el Dashboard</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;padding:1rem;">
                    <p style="font-size:.72rem;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem;">
                        <i class="bi bi-person-heart me-1"></i>Estudiante / Representante
                    </p>
                    <ul style="font-size:.8rem;color:#374151;margin:0;padding-left:1.1rem;">
                        <li>Ve el horario completo de su grupo</li>
                        <li>Accede desde el <strong>Portal del Representante</strong></li>
                        <li>El enlace del portal se genera desde el perfil del estudiante</li>
                        <li>No requiere contraseña — URL firmada con validez de 30 días</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>El horario solo es visible una vez que el administrador lo <strong>Publica</strong>. En borrador, nadie más lo ve.</div>
    </div>
</div>

{{-- ─── Configuración previa ────────────────────────────── --}}
<div class="sc" id="h1" data-s="horario configurar franjas aulas disponibilidad docente prerequisitos preparar">
    <div class="sn hor-n">H1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-sliders me-2" style="color:var(--c-hor);"></i>Configuración previa (requisitos)</h4>
        <p class="sc-desc">Antes de generar el horario, el sistema verifica automáticamente que todo esté en orden. Estos son los datos que deben existir:</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión de Horarios →</strong> cada subsección</div>
        <ul class="sc-steps">
            <li><i class="bi bi-clock text-primary"></i><strong>Franjas horarias:</strong> ve a <strong>Horarios → Franjas Horarias</strong> y crea los bloques de tiempo (ej: 07:00–07:45). Marca las de recreo como "Es recreo".</li>
            <li><i class="bi bi-door-open text-primary"></i><strong>Aulas:</strong> ve a <strong>Horarios → Aulas</strong> y registra las aulas físicas con su capacidad. Activa la casilla "Disponible" para que el algoritmo las use.</li>
            <li><i class="bi bi-person-check text-success"></i><strong>Disponibilidad docentes:</strong> ve a <strong>Horarios → Disponibilidad</strong> y marca los bloques donde cada docente NO está disponible. Si no marcas nada, se asume disponible todo el tiempo.</li>
            <li><i class="bi bi-diagram-3 text-warning"></i><strong>Asignaciones con horas:</strong> en <strong>Configuración → Asignaciones</strong>, asegúrate de que cada asignación tenga el campo <em>horas_semana</em> &gt; 0. El algoritmo genera exactamente ese número de horas por materia.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Si falta algún requisito, el sistema <strong>detiene la generación</strong> y muestra un mensaje exacto de qué falta y dónde corregirlo.</div>
    </div>
</div>

{{-- ─── Generación automática ───────────────────────────── --}}
<div class="sc" id="h2" data-s="horario generar automatico algoritmo backtracking heuristicas puntaje score conflictos reintentar">
    <div class="sn hor-n">H2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-cpu me-2" style="color:var(--c-hor);"></i>Generar horario automáticamente</h4>
        <p class="sc-desc">El algoritmo Backtracking + Heurísticas (MRV) genera el mejor horario posible cumpliendo todas las reglas. Realiza hasta 3 intentos automáticos y guarda el que tenga mayor puntaje.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión de Horarios → Horarios → Generar Horario</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-input-cursor text-primary"></i><strong>Nombre del horario</strong> (opcional): ej. "Horario Semestre I 2026".</li>
            <li><i class="bi bi-people text-primary"></i><strong>Grupos</strong> (opcional): si dejas vacío, genera para todos los grupos. Si seleccionas uno o varios con Ctrl+clic, genera solo para esos.</li>
            <li><i class="bi bi-magic text-success"></i>Haz clic en <strong>Generar Horario</strong>. El sistema valida los datos, ejecuta el algoritmo y muestra el resultado sin recargar la página.</li>
        </ul>
        <p style="font-size:.82rem;font-weight:700;color:var(--c-hor);margin-top:.75rem;margin-bottom:.4rem;">Resultado del panel:</p>
        <ul class="sc-steps">
            <li><i class="bi bi-percent text-primary"></i><strong>Puntaje 0–100%:</strong> qué porcentaje de clases quedaron asignadas. 100% = perfecto.</li>
            <li><i class="bi bi-exclamation-triangle text-warning"></i><strong>Conflictos:</strong> clases que el algoritmo no pudo ubicar por restricciones imposibles de resolver.</li>
            <li><i class="bi bi-shield-check text-success"></i><strong>Integridad N/4:</strong> 4 verificaciones automáticas post-generación (docente, aula, grupo, horas).</li>
            <li><i class="bi bi-arrow-repeat text-danger"></i><strong>Reintentar:</strong> si hay conflictos, el botón "Reintentar" aparece automáticamente.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Si el puntaje es menor a 100%, suele significar que hay más horas requeridas que slots disponibles. Agrega franjas o reduce horas de alguna materia.</div>
    </div>
</div>

{{-- ─── Reglas del algoritmo ───────────────────────────── --}}
<div class="sc" id="h3" data-s="horario reglas algoritmo docente aula grupo horas distribucion materia mismo dia">
    <div class="sn hor-n">H3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-list-check me-2" style="color:var(--c-hor);"></i>Reglas que respeta el algoritmo</h4>
        <p class="sc-desc">El generador garantiza el cumplimiento de 7 reglas simultáneamente. Si una restricción es imposible de cumplir, el slot queda como "conflicto".</p>
        <div class="row g-2">
            @php
            $reglas = [
                ['R1','bi-person-x','text-danger','Un docente no puede dar dos clases al mismo tiempo'],
                ['R2','bi-door-closed','text-warning','Un aula no puede tener dos grupos al mismo tiempo'],
                ['R3','bi-people-fill','text-primary','Un grupo no puede tener dos materias a la misma hora'],
                ['R4','bi-clock-history','text-success','Cada materia tiene exactamente las horas_semana configuradas'],
                ['R5','bi-calendar-x','text-secondary','Respeta la disponibilidad declarada de cada docente'],
                ['R6','bi-arrow-repeat','text-info','No repite la misma materia más de 1 vez por día por grupo'],
                ['R7','bi-distribute-vertical','text-success','Distribuye las clases de forma equilibrada a lo largo de la semana'],
            ];
            @endphp
            @foreach($reglas as $r)
            <div class="col-md-6">
                <div style="display:flex;align-items:flex-start;gap:.6rem;background:#f8fafc;border-radius:8px;padding:.6rem .8rem;">
                    <span style="background:var(--c-hor);color:#fff;border-radius:5px;padding:.15rem .45rem;font-size:.68rem;font-weight:800;flex-shrink:0;">{{ $r[0] }}</span>
                    <span style="font-size:.8rem;color:#374151;"><i class="bi {{ $r[1] }} {{ $r[2] }} me-1"></i>{{ $r[3] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ─── Edición manual de celdas ───────────────────────── --}}
<div class="sc" id="h4" data-s="horario edicion manual celda agregar eliminar editar clase arrastrar mover">
    <div class="sn hor-n">H4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-pencil-square me-2" style="color:var(--c-hor);"></i>Edición manual de celdas</h4>
        <p class="sc-desc">Después de generar, puedes ajustar el horario manualmente: agregar, editar o eliminar clases en cualquier celda.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión de Horarios → Horarios → Ver horario → clic en celda</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-plus-circle text-success"></i><strong>Agregar clase:</strong> haz clic en cualquier celda vacía ("+"). Se abre un modal para seleccionar grupo, materia, docente y aula.</li>
            <li><i class="bi bi-pencil text-primary"></i><strong>Editar clase:</strong> pasa el cursor sobre una celda ocupada y haz clic en el ícono de lápiz que aparece.</li>
            <li><i class="bi bi-trash text-danger"></i><strong>Eliminar clase:</strong> pasa el cursor sobre la celda y haz clic en el ícono de papelera.</li>
            <li><i class="bi bi-arrows-move text-secondary"></i><strong>Mover clase:</strong> arrastra la celda a otra posición. El sistema verifica conflictos antes de guardar.</li>
        </ul>
        <div class="warn"><i class="bi bi-exclamation-triangle me-1"></i>El sistema valida que no haya conflictos de docente, aula o grupo al agregar o editar una celda. Si existe un conflicto, muestra el error sin guardar.</div>
    </div>
</div>

{{-- ─── Publicar horario ───────────────────────────────── --}}
<div class="sc" id="h5" data-s="horario publicar despublicar borrador comunicado notificacion docente estudiante representante ver">
    <div class="sn hor-n">H5</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-send-check me-2" style="color:var(--c-hor);"></i>Publicar el horario</h4>
        <p class="sc-desc">Un horario en estado "Borrador" es invisible para docentes y representantes. Al publicarlo, queda accesible para toda la comunidad educativa.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-list-ul text-primary"></i>Ve a <strong>Gestión de Horarios → Horarios</strong>. Verás la lista de horarios generados.</li>
            <li><i class="bi bi-send-check text-success"></i>Haz clic en <strong>"Publicar"</strong> en el horario que quieres activar.</li>
            <li><i class="bi bi-megaphone text-primary"></i>El sistema crea automáticamente un <strong>Comunicado</strong> notificando la publicación a toda la institución.</li>
            <li><i class="bi bi-eye-slash text-warning"></i>Para volver atrás: haz clic en <strong>"Despublicar"</strong>. El horario queda en borrador.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Solo puede haber un horario publicado activo a la vez. Al publicar uno nuevo, el anterior pasa a borrador automáticamente.</div>
    </div>
</div>

{{-- ─── Regenerar horario ──────────────────────────────── --}}
<div class="sc" id="h6" data-s="horario regenerar reintentar mejorar puntaje score conflictos nuevo intento">
    <div class="sn hor-n">H6</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-arrow-repeat me-2" style="color:var(--c-hor);"></i>Regenerar un horario existente</h4>
        <p class="sc-desc">Si el resultado tiene conflictos o quieres un mejor puntaje, puedes re-ejecutar el algoritmo sobre el mismo horario sin crear uno nuevo.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Ver horario → botón "Regenerar"</strong> (parte superior derecha)</div>
        <ul class="sc-steps">
            <li><i class="bi bi-eye text-primary"></i>Abre el horario que quieres mejorar.</li>
            <li><i class="bi bi-arrow-repeat text-primary"></i>Haz clic en <strong>"Regenerar"</strong>. El sistema pregunta confirmación.</li>
            <li><i class="bi bi-trash text-warning"></i>El algoritmo <strong>elimina todas las celdas</strong> actuales del horario y genera de nuevo.</li>
            <li><i class="bi bi-check-circle text-success"></i>El puntaje y los conflictos se actualizan. El nombre y estado del horario se mantienen.</li>
        </ul>
        <div class="warn"><i class="bi bi-exclamation-triangle me-1"></i>Regenerar borra las ediciones manuales que hayas hecho. Hazlo antes de ajustar manualmente.</div>
    </div>
</div>

{{-- ─── Vista del docente ──────────────────────────────── --}}
<div class="sc" id="h7" data-s="docente mi horario ver semana clases grupos aulas personal">
    <div class="sn hor-n">H7</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-calendar-week-fill me-2" style="color:var(--c-hor);"></i>Vista del docente — Mi Horario</h4>
        <p class="sc-desc">Cada docente tiene una página personal que muestra <strong>únicamente sus clases</strong> del horario publicado, con grupo, aula y horario de cada una.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Menú lateral → Gestión → Mi Horario</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>El docente inicia sesión y accede a <strong>Gestión → Mi Horario</strong> en el menú lateral.</li>
            <li><i class="bi bi-grid-3x3-gap text-success"></i>Ve una cuadrícula semanal (Lun–Vie) con sus clases en color por materia.</li>
            <li><i class="bi bi-people text-primary"></i>Cada celda muestra: materia, grupo y aula asignada.</li>
            <li><i class="bi bi-bar-chart text-secondary"></i>Un resumen en la parte superior indica cuántas clases, grupos y materias tiene por semana.</li>
            <li><i class="bi bi-speedometer2 text-secondary"></i>También aparece una miniatura del horario en el Dashboard del docente.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>Si el horario aún no está publicado, el docente verá un mensaje indicando que el horario está pendiente. No puede ver borradores.</div>
    </div>
</div>

{{-- ─── Vista del estudiante ──────────────────────────── --}}
<div class="sc" id="h8" data-s="estudiante representante portal horario semana grupo ver acceso enlace link">
    <div class="sn hor-n">H8</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-heart me-2" style="color:var(--c-hor);"></i>Vista del estudiante — Portal del Representante</h4>
        <p class="sc-desc">Los estudiantes y sus representantes pueden ver el horario del grupo a través del <strong>Portal del Representante</strong>, sin necesidad de usuario ni contraseña.</p>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Ruta: <strong>Gestión → Estudiantes → Ver perfil → botón "Portal del Representante"</strong></div>
        <ul class="sc-steps">
            <li><i class="bi bi-person-badge text-primary"></i>Ve al perfil del estudiante desde <strong>Estudiantes → Ver</strong>.</li>
            <li><i class="bi bi-link-45deg text-success"></i>Haz clic en <strong>"Portal del Representante"</strong> para generar el enlace de acceso.</li>
            <li><i class="bi bi-send text-primary"></i>Copia el enlace y envíalo al representante por WhatsApp, email, etc. El enlace es válido por <strong>30 días</strong>.</li>
            <li><i class="bi bi-calendar-week text-success"></i>En el portal, el representante ve la pestaña <strong>"Horario"</strong> con la cuadrícula semanal del grupo del estudiante.</li>
            <li><i class="bi bi-shield-lock text-secondary"></i>El enlace usa firma digital — no puede ser manipulado ni funciona si se altera.</li>
        </ul>
        <div class="ok"><i class="bi bi-lightbulb me-1"></i>El portal también muestra: notas, asistencia y comunicados del estudiante — todo en una sola vista para el representante.</div>
    </div>
</div>

{{-- ─── Debug y errores ────────────────────────────────── --}}
<div class="sc" id="h9" data-s="horario debug error validacion log problema fallo algoritmo conflicto no genera">
    <div class="sn hor-n">H9</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-bug-fill me-2" style="color:var(--c-hor);"></i>Solución de problemas comunes</h4>
        <p class="sc-desc">Si el generador falla o produce muchos conflictos, aquí están las causas más frecuentes y cómo resolverlas.</p>
        <div class="table-responsive">
            <table class="table table-sm" style="font-size:.8rem;">
                <thead style="background:#f1f5f9;">
                    <tr>
                        <th style="width:35%;">Problema</th>
                        <th>Solución</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="bi bi-x-circle text-danger me-1"></i>Error: "No hay franjas horarias activas"</td>
                        <td>Ve a <strong>Horarios → Franjas Horarias</strong> y activa al menos una franja.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-x-circle text-danger me-1"></i>Error: "No hay aulas disponibles"</td>
                        <td>Ve a <strong>Horarios → Aulas</strong> y marca al menos un aula como disponible.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-x-circle text-danger me-1"></i>Error: "La materia X no tiene docente"</td>
                        <td>Ve a <strong>Asignaciones</strong> y asigna un docente a esa materia-grupo.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-exclamation-triangle text-warning me-1"></i>Puntaje &lt; 100%, muchos conflictos</td>
                        <td>Las horas requeridas superan los slots disponibles. Agrega franjas, aulas o reduce horas.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-exclamation-triangle text-warning me-1"></i>Docente no aparece en su horario</td>
                        <td>Verifica que el horario esté <strong>publicado</strong> (no en borrador) y que el docente tenga asignaciones activas.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-exclamation-triangle text-warning me-1"></i>Horario no aparece en portal del estudiante</td>
                        <td>El horario debe estar en estado <strong>Publicado</strong>. El portal solo muestra horarios publicados.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-info-circle text-primary me-1"></i>Quiero ver el detalle de lo que hizo el algoritmo</td>
                        <td>Activa <code>HORARIO_DEBUG=true</code> en el archivo <code>.env</code>. El panel de resultado mostrará el registro de depuración completo.</td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-info-circle text-primary me-1"></i>¿Dónde están los logs del sistema?</td>
                        <td>En <code>storage/logs/horario.log</code> — se guarda cada generación, intento, puntaje y error crítico.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>{{-- /sHor --}}

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN: Planificación Docente --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sPlanif">

<div class="sc" id="p1" data-s="planificacion docente tecnica ra resultados aprendizaje modulo formativo crear nueva por ra actividad">
    <div class="sc-header"><div class="sc-num">P1</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-journal-text me-2" style="color:#7c3aed;"></i>Planificaciones Área Técnica — ¿Qué son?</h4>
            <p class="sc-desc">Las planificaciones del área técnica permiten a los docentes registrar su planificación por <strong>Resultados de Aprendizaje (RA)</strong> o por <strong>Actividad de Aprendizaje</strong>, siguiendo el modelo INFOTEP/MINERD para el área técnica.</p>
        </div>
    </div>
    <div class="sc-body">
        <p>Hay dos tipos de planificación:</p>
        <ul class="sc-steps">
            <li><i class="bi bi-bookmark-check text-primary"></i><strong>Por RA (Resultados de Aprendizaje):</strong> incluye múltiples RA con elementos de capacidad, fechas, actividades de E-A, instrumentos y contenidos.</li>
            <li><i class="bi bi-activity text-success"></i><strong>Por Actividad:</strong> una sola actividad con inicio, desarrollo, cierre, estrategias, recursos e instrumentos de evaluación.</li>
        </ul>
        <div class="ok"><i class="bi bi-info-circle me-1"></i>Las planificaciones solo están disponibles para asignaciones con <strong>área = Técnica</strong>.</div>
    </div>
</div>

<div class="sc" id="p2" data-s="planificacion crear nueva portal docente asignacion tecnica">
    <div class="sc-header"><div class="sc-num">P2</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-plus-circle me-2" style="color:#7c3aed;"></i>Crear una Planificación (Portal Docente)</h4>
        </div>
    </div>
    <div class="sc-body">
        <ol class="sc-steps">
            <li><i class="bi bi-house text-primary"></i>Accede al <strong>Portal Docente</strong> → desde el dashboard, haz clic en el botón <strong>Planif.</strong> de una de tus materias técnicas.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Haz clic en <strong>Nueva por RA</strong> o <strong>Nueva por Actividad</strong>.</li>
            <li><i class="bi bi-pencil text-warning"></i>Completa los campos: Familia Profesional, Denominación, Módulo, Código MF, Sesión, Nivel, Horas, Fechas, UC.</li>
            <li><i class="bi bi-bookmark-check text-primary"></i>Para planificaciones por RA: agrega los RA con sus descripciones, elementos de capacidad, fechas y actividades.</li>
            <li><i class="bi bi-save text-success"></i>Haz clic en <strong>Guardar Planificación</strong>. Se guarda como borrador.</li>
            <li><i class="bi bi-eye text-primary"></i>Para hacer visible a estudiantes/representantes: entra a la planificación y cambia el estado a <strong>Publicado</strong>.</li>
        </ol>
    </div>
</div>

<div class="sc" id="p3" data-s="planificacion admin panel control gestionar ver todas filtrar docente">
    <div class="sc-header"><div class="sc-num">P3</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-list-ul me-2" style="color:#7c3aed;"></i>Gestionar Planificaciones (Panel Admin)</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>Desde el panel admin, en la sección <strong>Área Técnica → Planificaciones</strong>, puedes ver todas las planificaciones del sistema. Los docentes solo ven las suyas.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-funnel text-primary"></i>Filtra por tipo (RA / Actividad) o por asignación.</li>
            <li><i class="bi bi-eye text-success"></i>Haz clic en <strong>Ver</strong> para ver el detalle con formato de matriz oficial.</li>
            <li><i class="bi bi-pencil text-warning"></i>Haz clic en <strong>Editar</strong> para modificar la planificación.</li>
            <li><i class="bi bi-printer text-primary"></i>En la vista de detalle, usa <strong>Imprimir</strong> para obtener la versión imprimible.</li>
        </ul>
    </div>
</div>

<div class="sc" id="p4" data-s="planes clase academica portal docente crear plan semanal diario quincenal mensual">
    <div class="sc-header"><div class="sc-num">P4</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-journal-bookmark me-2" style="color:#0891b2;"></i>Planes de Clase — Área Académica</h4>
            <p class="sc-desc">Los planes de clase del área académica tienen estructura de momentos pedagógicos: Inicio, Desarrollo y Cierre.</p>
        </div>
    </div>
    <div class="sc-body">
        <ol class="sc-steps">
            <li><i class="bi bi-house text-primary"></i>Portal Docente → haz clic en <strong>Planes</strong> desde el botón de una materia académica.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Clic en <strong>Nuevo Plan</strong>. Elige tipo: diaria, semanal, quincenal o mensual.</li>
            <li><i class="bi bi-layers text-warning"></i>Completa los <strong>Momentos Pedagógicos</strong> (Inicio, Desarrollo, Cierre) con competencias, contenidos, actividades e indicadores.</li>
            <li><i class="bi bi-lightbulb text-primary"></i>Selecciona las estrategias didácticas usadas.</li>
            <li><i class="bi bi-paperclip text-success"></i>Adjunta un archivo si lo deseas (PDF, Word, PowerPoint).</li>
            <li><i class="bi bi-save text-primary"></i>Guarda. El plan queda publicado automáticamente.</li>
        </ol>
    </div>
</div>

<div class="sc" id="p5" data-s="instrumentos evaluacion lista cotejo rubrica escala estimacion criterios">
    <div class="sc-header"><div class="sc-num">P5</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-clipboard-check me-2" style="color:#7c3aed;"></i>Instrumentos de Evaluación</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>Los instrumentos de evaluación permiten registrar criterios de evaluación formales. Tipos disponibles:</p>
        <ul class="sc-steps">
            <li><i class="bi bi-check2-square text-success"></i><strong>Lista de cotejo</strong> — ítems verificables (sí/no)</li>
            <li><i class="bi bi-table text-primary"></i><strong>Rúbrica</strong> — criterios con niveles de desempeño</li>
            <li><i class="bi bi-sliders text-warning"></i><strong>Escala de estimación</strong> — valores numéricos por criterio</li>
        </ul>
        <p>Acceso: Portal Docente → botón <strong>Instrum.</strong> de cualquier materia.</p>
    </div>
</div>

<div class="sc" id="p6" data-s="recursos materia materiales docente archivos links videos pdf compartir estudiantes">
    <div class="sc-header"><div class="sc-num">P6</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-folder-fill me-2" style="color:#2563eb;"></i>Recursos de Materia</h4>
        </div>
    </div>
    <div class="sc-body">
        <ol class="sc-steps">
            <li><i class="bi bi-folder text-primary"></i>Portal Docente → botón <strong>Recursos</strong> de cualquier materia.</li>
            <li><i class="bi bi-plus-circle text-success"></i>Clic en <strong>Agregar Recurso</strong>. Tipos: Enlace externo, PDF, Video, Documento, Imagen, Otro.</li>
            <li><i class="bi bi-eye text-primary"></i>Activa el toggle de visibilidad para que los estudiantes puedan verlo.</li>
        </ol>
        <div class="ok"><i class="bi bi-info-circle me-1"></i>Los estudiantes y representantes pueden ver los recursos publicados desde sus respectivos portales en la sección <strong>Mis Materias</strong>.</div>
    </div>
</div>

<div class="sc" id="p7" data-s="observaciones docente estudiante comportamiento conductual academica positiva portal">
    <div class="sc-header"><div class="sc-num">P7</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-chat-square-text me-2" style="color:#f59e0b;"></i>Observaciones de Docentes</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>Los docentes pueden registrar observaciones sobre sus estudiantes (académicas, conductuales, positivas o generales).</p>
        <ul class="sc-steps">
            <li><i class="bi bi-pencil text-primary"></i>Desde el Portal Docente → sección <strong>Observaciones</strong> de cualquier materia.</li>
            <li><i class="bi bi-eye text-success"></i>Las observaciones <strong>públicas</strong> son visibles para el representante en su portal y en el portal del representante por enlace.</li>
            <li><i class="bi bi-eye-slash text-warning"></i>Las observaciones <strong>privadas</strong> solo las ve el docente y el administrador/director.</li>
        </ul>
        <p>El administrador o director puede ver <strong>todas</strong> las observaciones en <strong>Panel Admin → Gestión → Observaciones</strong>.</p>
    </div>
</div>

</div>{{-- /sPlanif --}}

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN: Portales --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="help-section" id="sPortales">

<div class="sc" id="por1" data-s="portal docente acceso login dashboard clases asignadas calificaciones asistencia">
    <div class="sc-header"><div class="sc-num">PO1</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-person-badge me-2" style="color:#0891b2;"></i>Portal Docente — Funciones disponibles</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>El docente accede a <strong>/portal/docente</strong> con su usuario y contraseña. El dashboard muestra sus materias asignadas con acceso rápido a:</p>
        <ul class="sc-steps">
            <li><i class="bi bi-calendar-check text-success"></i><strong>Asistencia</strong> — tomar la asistencia del día, importar desde CSV/Excel.</li>
            <li><i class="bi bi-journal-check text-primary"></i><strong>Calificaciones</strong> — ingresar notas (área académica: 4 competencias P1–P4 / área técnica: criterios RA).</li>
            <li><i class="bi bi-people text-warning"></i><strong>Estudiantes</strong> — ver el listado con promedio y asistencia de cada estudiante.</li>
            <li><i class="bi bi-chat-square-text text-danger"></i><strong>Observaciones</strong> — registrar observaciones sobre estudiantes.</li>
            <li><i class="bi bi-file-earmark-text text-primary"></i><strong>Boletines</strong> — ver el boletín de cada estudiante de su materia.</li>
            <li><i class="bi bi-folder text-primary"></i><strong>Recursos</strong> — compartir materiales con los estudiantes.</li>
            <li><i class="bi bi-journal-text text-success"></i><strong>Planes de Clase</strong> — crear y gestionar planes (área académica).</li>
            <li><i class="bi bi-clipboard-check" style="color:#7c3aed;"></i><strong>Instrumentos</strong> — crear listas de cotejo, rúbricas y escalas.</li>
            <li><i class="bi bi-journal-text" style="color:#7c3aed;"></i><strong>Planificaciones</strong> — crear planificaciones formativas RA/Actividad (<em>solo área técnica</em>).</li>
        </ul>
    </div>
</div>

<div class="sc" id="por2" data-s="portal estudiante boletin notas asistencia horario planificaciones recursos">
    <div class="sc-header"><div class="sc-num">PO2</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-mortarboard me-2" style="color:#0891b2;"></i>Portal Estudiante — Funciones disponibles</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>El estudiante accede a <strong>/portal/estudiante</strong>. El dashboard incluye:</p>
        <ul class="sc-steps">
            <li><i class="bi bi-journals text-primary"></i><strong>Mis Materias</strong> — tarjetas por asignatura con acceso a Recursos y Boletín/Planificaciones.</li>
            <li><i class="bi bi-journal-check text-success"></i><strong>Mis Notas</strong> — calificaciones publicadas del año actual.</li>
            <li><i class="bi bi-file-earmark-text text-warning"></i><strong>Mi Boletín</strong> — boletín completo con P1–P4 y competencias MINERD.</li>
            <li><i class="bi bi-journal-text" style="color:#7c3aed;"></i><strong>Planificaciones</strong> — ver planificaciones técnicas publicadas de sus docentes.</li>
            <li><i class="bi bi-calendar-check text-success"></i><strong>Asistencia</strong> — resumen de asistencia por materia.</li>
            <li><i class="bi bi-calendar-week text-primary"></i><strong>Mi Horario</strong> — horario semanal publicado.</li>
        </ul>
    </div>
</div>

<div class="sc" id="por3" data-s="portal padre representante hijo boletin asistencia planificaciones recursos observaciones">
    <div class="sc-header"><div class="sc-num">PO3</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-people-fill me-2" style="color:#0891b2;"></i>Portal Padre/Representante — Funciones disponibles</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>El representante accede a <strong>/portal/padre</strong>. Puede ver la información de todos sus hijos registrados:</p>
        <ul class="sc-steps">
            <li><i class="bi bi-journals text-primary"></i><strong>Materias del Hijo</strong> — tarjetas por asignatura con acceso a Recursos y Boletín.</li>
            <li><i class="bi bi-journal-check text-success"></i><strong>Calificaciones</strong> — notas por período del hijo.</li>
            <li><i class="bi bi-file-earmark-text text-warning"></i><strong>Boletín</strong> — boletín completo del hijo, imprimible.</li>
            <li><i class="bi bi-folder text-primary"></i><strong>Recursos del hijo</strong> — acceso a los materiales compartidos por asignatura.</li>
            <li><i class="bi bi-calendar-check text-success"></i><strong>Asistencia</strong> — resumen general y por materia.</li>
            <li><i class="bi bi-calendar-week text-primary"></i><strong>Horario</strong> — horario semanal del grupo.</li>
            <li><i class="bi bi-chat-square-text text-warning"></i><strong>Observaciones</strong> — observaciones públicas del docente.</li>
            <li><i class="bi bi-journal-text" style="color:#7c3aed;"></i><strong>Planificaciones técnicas</strong> — si el hijo tiene materias técnicas.</li>
        </ul>
    </div>
</div>

<div class="sc" id="por4" data-s="portal representante publico enlace firmado sin login url qr calificaciones asistencia horario">
    <div class="sc-header"><div class="sc-num">PO4</div>
        <div>
            <h4 class="sc-title"><i class="bi bi-link-45deg me-2" style="color:#0891b2;"></i>Portal Representante Público (Sin Login)</h4>
        </div>
    </div>
    <div class="sc-body">
        <p>El sistema genera un <strong>enlace firmado</strong> para que el representante pueda ver la información del estudiante sin necesidad de crear una cuenta.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-person-lines-fill text-primary"></i>Ve a <strong>Admin → Estudiantes → Ficha del estudiante</strong>.</li>
            <li><i class="bi bi-link text-success"></i>En la ficha, encuentra el botón <strong>"Generar/Ver Enlace del Representante"</strong>.</li>
            <li><i class="bi bi-share text-warning"></i>Copia el enlace y compártelo con el representante. El enlace es válido por 30 días.</li>
        </ul>
        <div class="ok"><i class="bi bi-info-circle me-1"></i>El portal público muestra: Calificaciones, Asistencia, Planificaciones técnicas (si hay publicadas), Observaciones públicas y Horario.</div>
        <div class="warn"><i class="bi bi-shield-exclamation me-1"></i>El enlace es único para cada estudiante. No compartas el enlace de un estudiante con otra persona.</div>
    </div>
</div>

</div>{{-- /sPortales --}}


<script>
(function () {
    'use strict';

    window.sw = function(id, btn) {
        document.querySelectorAll('.help-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.htb').forEach(b => b.classList.remove('active'));
        var t = document.getElementById(id);
        if (t) t.classList.add('active');
        if (btn) btn.classList.add('active');
        var s = document.getElementById('helpSearch');
        if (s) { s.value = ''; runSearch(''); }
    };

    function runSearch(q) {
        q = q.trim().toLowerCase();
        var nr = document.getElementById('snr');
        if (!q) {
            document.querySelectorAll('.sc').forEach(c => c.classList.remove('search-hidden'));
            document.querySelectorAll('.help-section').forEach(s => s.classList.remove('active'));
            if (nr) nr.style.display = 'none';
            var ab = document.querySelector('.htb.active');
            if (!ab) ab = document.querySelector('.htb');
            if (ab) { var s2 = document.getElementById(ab.dataset.section || 'sCfg'); if (s2) s2.classList.add('active'); }
            var fs = document.querySelector('.help-section');
            if (!document.querySelector('.help-section.active') && fs) fs.classList.add('active');
            return;
        }
        document.querySelectorAll('.help-section').forEach(s => s.classList.add('active'));
        var any = false;
        document.querySelectorAll('.sc').forEach(function(card) {
            var txt = (card.dataset.s || '') + ' ' + (card.innerText || '');
            var match = txt.toLowerCase().includes(q);
            card.classList.toggle('search-hidden', !match);
            if (match) any = true;
        });
        if (nr) nr.style.display = any ? 'none' : 'block';
    }

    var si = document.getElementById('helpSearch');
    if (si) si.addEventListener('input', function() { runSearch(this.value); });

    // Set correct data-section for tabs to restore on search clear
    document.querySelectorAll('.htb').forEach(function(b) {
        var fn = b.getAttribute('onclick') || '';
        var m = fn.match(/'(s\w+)'/);
        if (m) b.dataset.section = m[1];
    });
})();
</script>

{{-- ══ SECCIÓN PAGOS ══════════════════════════════════════════════════════ --}}
<div class="help-section" id="sPagos">
<div class="stb">
    <div class="iw" style="background:#0f766e;"><i class="bi bi-cash-coin"></i></div>
    <div>
        <h2>Pagos y Colegiaturas</h2>
        <p>Módulo disponible solo para <strong>centros privados</strong>. Permite registrar, generar y controlar el cobro de cuotas escolares.</p>
    </div>
    <span class="rtb"><i class="bi bi-shield-check"></i> Solo Admin / Director</span>
</div>

<div class="sc" id="pag1" data-s="pagos colegiaturas activar módulo privado configuracion">
    <div class="sn" style="background:#0f766e;">P1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-toggle-on me-2" style="color:#0f766e;"></i>Activar el módulo de pagos</h4>
        <p class="sc-desc">El módulo es opcional y solo aplica a centros privados. Debe activarse manualmente.</p>
        <div class="nav-steps">
            <div class="nav-step done"><i class="bi bi-cash-coin"></i> Pagos</div>
            <div class="nav-step active"><i class="bi bi-gear"></i> Configuración</div>
        </div>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Pagos y Colegiaturas → Configuración</strong> en el menú lateral.</li>
            <li><i class="bi bi-toggle-on text-success"></i>Activa el switch <strong>"Activar módulo de pagos"</strong>.</li>
            <li><i class="bi bi-currency-dollar text-primary"></i>Configura la moneda (DOP por defecto) y el concepto estándar (ej. "Cuota escolar mensual").</li>
            <li><i class="bi bi-credit-card text-secondary"></i>Si usarás pagos en línea, selecciona <strong>Stripe</strong> e ingresa las claves de API.</li>
        </ul>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>Si el módulo no está activo, la sección "Pagos y Colegiaturas" no aparece en el menú.</div>
    </div>
</div>

<div class="sc" id="pag2" data-s="pagos cuotas generar masivo estudiantes todos">
    <div class="sn" style="background:#0f766e;">P2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-lightning-fill me-2" style="color:#0f766e;"></i>Generar cuotas masivas</h4>
        <p class="sc-desc">Con un solo clic puedes crear una cuota pendiente para todos los estudiantes activos del año.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-cash-coin text-primary"></i>Ve a <strong>Pagos → Gestión de Pagos</strong>.</li>
            <li><i class="bi bi-lightning-fill text-warning"></i>Haz clic en <strong>"Generar Cuotas"</strong> (botón superior derecho).</li>
            <li><i class="bi bi-input-cursor text-primary"></i>Escribe el concepto (ej. "Cuota Enero 2026"), el monto y la fecha límite de pago.</li>
            <li><i class="bi bi-people-fill text-success"></i>Selecciona un grupo específico o deja en blanco para <strong>todos los grupos</strong>.</li>
            <li><i class="bi bi-check-circle text-success"></i>El sistema evita duplicar si ya existe el mismo concepto y fecha para un estudiante.</li>
        </ul>
    </div>
</div>

<div class="sc" id="pag3" data-s="pagos registrar pagar cobrar manual efectivo transferencia">
    <div class="sn" style="background:#0f766e;">P3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-check-circle me-2" style="color:#0f766e;"></i>Registrar un pago</h4>
        <p class="sc-desc">Dos formas rápidas de registrar que un estudiante pagó.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-table text-primary"></i><strong>Desde el índice:</strong> busca al estudiante, haz clic en el botón verde ✓ junto a la cuota pendiente.</li>
            <li><i class="bi bi-person text-primary"></i><strong>Desde el estado de cuenta:</strong> ve a <em>Gestión de Pagos → clic en el nombre del estudiante</em> → botón "Pagar" por cuota.</li>
            <li><i class="bi bi-credit-card text-secondary"></i>Selecciona el método (efectivo, transferencia, tarjeta) y escribe el número de recibo si aplica.</li>
        </ul>
    </div>
</div>

<div class="sc" id="pag4" data-s="deudores mora vencidos reporte excel pdf exportar">
    <div class="sn" style="background:#0f766e;">P4</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-exclamation-circle me-2" style="color:#0f766e;"></i>Reporte de deudores</h4>
        <p class="sc-desc">Lista completa de estudiantes con pagos vencidos, ordenados por deuda.</p>
        <ul class="sc-steps">
            <li><i class="bi bi-layout-sidebar text-primary"></i>Ve a <strong>Pagos → Deudores</strong>.</li>
            <li><i class="bi bi-funnel text-secondary"></i>Filtra por grupo si necesitas ver solo un sección.</li>
            <li><i class="bi bi-file-earmark-pdf text-danger"></i>Exporta a <strong>PDF</strong> o <strong>Excel</strong> para compartir con la dirección.</li>
        </ul>
        <div class="tip"><i class="bi bi-info-circle me-1"></i>El sistema actualiza automáticamente los estados "vencido" al abrir la pantalla.</div>
    </div>
</div>
</div>

{{-- ══ SECCIÓN PERFIL ══════════════════════════════════════════════════════ --}}
<div class="help-section" id="sPerfil">
<div class="stb">
    <div class="iw" style="background:#6366f1;"><i class="bi bi-person-badge"></i></div>
    <div>
        <h2>Perfil de Usuario</h2>
        <p>Cada usuario puede editar su propia información, foto y contraseña desde cualquier portal.</p>
    </div>
</div>

<div class="sc" id="per1" data-s="perfil foto usuario editar información personal nombre correo teléfono">
    <div class="sn" style="background:#6366f1;">U1</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-person-circle me-2" style="color:#6366f1;"></i>Editar información personal</h4>
        <p class="sc-desc">Cualquier usuario (admin, docente, estudiante, representante) puede actualizar sus datos desde el menú superior.</p>
        <div class="nav-steps">
            <div class="nav-step active"><i class="bi bi-person-circle"></i> Menú usuario</div>
            <div class="nav-step"><i class="bi bi-person-badge"></i> Mi Perfil</div>
        </div>
        <ul class="sc-steps">
            <li><i class="bi bi-chevron-down text-primary"></i>Haz clic en tu nombre en la esquina superior derecha.</li>
            <li><i class="bi bi-person-circle text-primary"></i>Selecciona <strong>"Mi Perfil"</strong>.</li>
            <li><i class="bi bi-pencil text-warning"></i>Edita nombre, apellidos, correo electrónico o teléfono.</li>
            <li><i class="bi bi-check-circle text-success"></i>Haz clic en <strong>"Guardar cambios"</strong>.</li>
        </ul>
    </div>
</div>

<div class="sc" id="per2" data-s="foto perfil imagen cambiar subir cargar avatar">
    <div class="sn" style="background:#6366f1;">U2</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-camera me-2" style="color:#6366f1;"></i>Foto de perfil</h4>
        <ul class="sc-steps">
            <li><i class="bi bi-person-badge text-primary"></i>En la página de perfil, haz clic en <strong>"Cambiar foto"</strong>.</li>
            <li><i class="bi bi-file-image text-secondary"></i>Selecciona una imagen JPG, PNG o WEBP de máximo 2 MB.</li>
            <li><i class="bi bi-check-circle text-success"></i>La foto se actualiza automáticamente en el topbar de todos los portales.</li>
            <li><i class="bi bi-trash text-danger"></i>Para eliminarla, usa el botón <strong>"Quitar foto"</strong>.</li>
        </ul>
    </div>
</div>

<div class="sc" id="per3" data-s="contraseña cambiar password seguridad actualizar">
    <div class="sn" style="background:#6366f1;">U3</div>
    <div class="sc-body">
        <h4 class="sc-title"><i class="bi bi-shield-lock me-2" style="color:#6366f1;"></i>Cambiar contraseña</h4>
        <ul class="sc-steps">
            <li><i class="bi bi-person-badge text-primary"></i>En la página de perfil, baja a la sección <strong>"Cambiar Contraseña"</strong>.</li>
            <li><i class="bi bi-key text-warning"></i>Escribe tu contraseña actual (requerida para verificar identidad).</li>
            <li><i class="bi bi-shield-check text-success"></i>Ingresa la nueva contraseña (mínimo 8 caracteres) y confírmala.</li>
            <li><i class="bi bi-check-circle text-success"></i>Haz clic en <strong>"Actualizar contraseña"</strong>.</li>
        </ul>
        <div class="tip"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Si olvidaste tu contraseña, usa la opción <strong>"¿Olvidé mi contraseña?"</strong> en la pantalla de inicio de sesión.</div>
    </div>
</div>
</div>

@endsection
