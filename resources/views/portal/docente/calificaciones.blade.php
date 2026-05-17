@extends('layouts.portal')
@section('page-title', 'Calificaciones — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'calificaciones'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
@endsection

@push('styles')
<style>
/* ── inputs ── */
.nota-inp {
    width: 58px; text-align: center;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    padding: .3rem .2rem; font-size: .85rem; font-weight: 700;
    background: #fff; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
    -moz-appearance: textfield;
}
.nota-inp.ra-inp { width: 52px; }
.nota-inp::-webkit-inner-spin-button,
.nota-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.nota-inp:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.18); }
.nota-inp.aprobado { border-color: #10b981; }
.nota-inp.reprobado { border-color: #ef4444; }

/* ── final badge ── */
.final-badge {
    display: inline-block; min-width: 44px; text-align: center;
    font-weight: 800; font-size: .9rem; border-radius: 8px;
    padding: .25rem .4rem;
}
.final-aprobado { background: #dcfce7; color: #15803d; }
.final-reprobado { background: #fee2e2; color: #dc2626; }
.final-vacio { background: #f1f5f9; color: #94a3b8; }

/* ── table rows ── */
.est-row { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.est-row:hover { background: #f8faff; }
.col-head {
    padding: .5rem .4rem; text-align: center;
    font-size: .7rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #2563eb; white-space: nowrap;
}

/* ── período tabs (técnica) ── */
.per-tabs { display: flex; gap: .4rem; flex-wrap: wrap; margin-bottom: 1rem; }
.per-tab {
    padding: .38rem .9rem; border-radius: 8px; font-size: .78rem; font-weight: 700;
    border: 1.5px solid #e2e8f0; cursor: pointer; text-decoration: none;
    color: #374151; background: #f8fafc; transition: all .15s;
}
.per-tab:hover { border-color: #7c3aed; color: #7c3aed; background: #f5f3ff; }
.per-tab.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }

/* ── RA column header ── */
.ra-col-head { max-width: 70px; }
.ra-col-head .ra-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 6px;
    background: #7c3aed; color: #fff; font-size: .68rem; font-weight: 800;
    margin-bottom: .15rem;
}
.ra-col-head .ra-desc {
    display: block; font-size: .58rem; color: #6b7280;
    white-space: normal; line-height: 1.3; max-width: 70px; margin-top: .1rem;
}

/* ── tipo badge ── */
.tipo-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .7rem; border-radius: 20px; font-size: .72rem; font-weight: 700;
}
.tipo-academica { background: #dbeafe; color: #1e40af; }
.tipo-tecnica   { background: #ede9fe; color: #5b21b6; }

/* ── RA cell: criterios + recuperación ── */
.ra-cell-wrap { display: flex; flex-direction: column; align-items: center; gap: .18rem; }
.intento-block { display: flex; flex-direction: column; align-items: center; gap: .08rem; }
.intento-label {
    font-size: .55rem; font-weight: 800; color: #6b7280;
    text-transform: uppercase; letter-spacing: .04em; line-height: 1;
}
/* Criterios grid */
.crit-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 2px;
    background: #f5f3ff; border-radius: 6px; padding: 3px; margin-top: 2px;
    border: 1px solid #ede9fe;
}
.crit-block { display: flex; flex-direction: column; align-items: center; }
.crit-lbl { font-size: .48rem; font-weight: 800; color: #7c3aed; line-height: 1; }
.crit-inp {
    width: 38px; text-align: center;
    border: 1px solid #c4b5fd; border-radius: 4px;
    padding: .18rem .1rem; font-size: .72rem; font-weight: 700;
    background: #fff; color: #1e293b; -moz-appearance: textfield;
}
.crit-inp::-webkit-inner-spin-button, .crit-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.crit-inp:focus { outline: none; border-color: #7c3aed; }
/* Recuperación estructurada */
.rec-struct { display: flex; flex-direction: column; align-items: center; gap: 2px; margin-top: 3px; }
.rec-struct-header {
    font-size: .5rem; font-weight: 800; color: #dc2626;
    background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 4px; padding: .1rem .3rem; width: 100%; text-align: center;
}
.rec-struct-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; width: 100%; }
.rec-struct-block { display: flex; flex-direction: column; align-items: center; }
.rec-struct-lbl { font-size: .48rem; font-weight: 700; color: #b91c1c; line-height: 1; }
.rec-struct-inp {
    width: 38px; text-align: center;
    border: 1px solid #fca5a5; border-radius: 4px;
    padding: .18rem .1rem; font-size: .72rem; font-weight: 700;
    background: #fff7f7; color: #991b1b; -moz-appearance: textfield;
}
.rec-struct-inp::-webkit-inner-spin-button, .rec-struct-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.rec-struct-inp:focus { outline: none; border-color: #dc2626; }
.rec-cf-badge {
    font-size: .55rem; font-weight: 800; border-radius: 4px;
    padding: .1rem .35rem; margin-top: 2px;
}
[data-theme="dark"] .crit-grid { background: #2e1b4e; border-color: #6d28d9; }
[data-theme="dark"] .crit-inp { background: #0f172a; border-color: #7c3aed; color: #e2e8f0; }
[data-theme="dark"] .rec-struct-header { background: #3f0b0b; border-color: #991b1b; color: #fca5a5; }
[data-theme="dark"] .rec-struct-inp { background: #1c0000; border-color: #991b1b; color: #fca5a5; }
/* El input I1 usa nota-inp ra-inp; los demás usan rec-inp — mismo look */
.rec-inp {
    width: 48px; text-align: center;
    border: 1.5px solid #e2e8f0; border-radius: 7px;
    padding: .3rem .2rem; font-size: .85rem; font-weight: 700;
    background: #fff; color: #1e293b;
    transition: border-color .15s;
    -moz-appearance: textfield;
}
.rec-inp::-webkit-inner-spin-button,
.rec-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.rec-inp:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.18); }
.rec-inp.rec-usada { border-color: #10b981; background: #f0fdf4; }

/* ── responsive: inputs más pequeños en móvil muy estrecho ── */
@media (max-width: 400px) {
    .nota-inp { width: 46px; font-size: .78rem; }
    .nota-inp.ra-inp { width: 42px; }
    .rec-inp { width: 38px; font-size: .66rem; }
    .ra-col-head { max-width: 56px; }
}

/* ── dark mode ── */
[data-theme="dark"] .nota-inp { background: #0f172a; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .rec-inp { background: #0f172a; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .rec-inp.rec-usada { background: #052e16; border-color: #10b981; }
[data-theme="dark"] .intento-label { color: #64748b; }
[data-theme="dark"] .nota-inp:focus { border-color: #3b82f6; }
[data-theme="dark"] .est-row:hover { background: #1e3a5f; }
[data-theme="dark"] .col-head { color: #60a5fa; }
[data-theme="dark"] .final-vacio { background: #1e293b; color: #475569; }
[data-theme="dark"] .per-tab { background: #1e293b; border-color: #334155; color: #cbd5e1; }
[data-theme="dark"] .per-tab:hover { border-color: #7c3aed; color: #c4b5fd; background: #1e1b3a; }
[data-theme="dark"] .per-tab.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
[data-theme="dark"] .ra-col-head .ra-desc { color: #94a3b8; }
[data-theme="dark"] .tipo-academica { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .tipo-tecnica   { background: #2e1b4e; color: #c4b5fd; }
[data-theme="dark"] .final-aprobado { background: #052e16; color: #4ade80; }
[data-theme="dark"] .final-reprobado { background: #3f0b0b; color: #fca5a5; }
[data-theme="dark"] #ra-pesos-panel { background: #1e1b3a !important; border-color: #3b1f6e !important; }
[data-theme="dark"] .pesos-ra-inp { background: #0f172a !important; border-color: #6d28d9 !important; color: #e2e8f0 !important; }

/* ═══════════════════════════════════════════════════════
   MÓDULO COMPETENCIAS MINERD — Registro institucional
═══════════════════════════════════════════════════════ */

/* ── Tabs de competencias ── */
.comp-tab {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .38rem .85rem; font-size: .76rem; font-weight: 700;
    border: 2px solid var(--tc, #e2e8f0); border-radius: 8px; cursor: pointer;
    color: var(--tc, #374151); background: transparent; transition: all .15s;
}
.comp-tab:hover { background: color-mix(in srgb, var(--tc) 10%, transparent); }
.comp-tab.active { background: var(--tc); color: #fff; box-shadow: 0 2px 8px color-mix(in srgb, var(--tc) 30%, transparent); }
.comp-tab-label { display: none; }
@media (min-width: 480px) { .comp-tab-label { display: inline; } }

/* ── Tabla institucional MINERD ── */
.minerd-tbl { width: 100%; border-collapse: collapse; font-size: .78rem; }
.minerd-tbl th, .minerd-tbl td {
    border: 1px solid #9ca3af; padding: .28rem .18rem;
    text-align: center; vertical-align: middle;
}
/* Cabeceras de período */
.mth-per {
    font-size: .63rem; font-weight: 800; letter-spacing: .05em;
    text-transform: uppercase; color: #fff; padding: .3rem .25rem;
}
/* Sub-cabeceras */
.mth-p    { font-size: .62rem; font-weight: 800; background: #dbeafe; color: #1e40af; }
.mth-rp   { font-size: .62rem; font-weight: 800; background: #fef3c7; color: #92400e; }
.mth-cf   { font-size: .62rem; font-weight: 800; background: #dcfce7; color: #15803d; }
.mth-prom { font-size: .62rem; font-weight: 800; background: #ede9fe; color: #4c1d95; min-width: 52px; }
/* Celdas de datos */
.mcell-p  { background: #fff; padding: .12rem .1rem; }
.mcell-rp { background: #fefce8; padding: .12rem .1rem; }
.mcell-rp.locked { background: #f3f4f6; }
.mcell-cf { background: #f0fdf4; font-weight: 800; font-size: .87rem; padding: .2rem .15rem; }
/* Color del número en CF */
.avg-ok  { color: #15803d; font-weight: 800; }
.avg-mal { color: #dc2626; font-weight: 800; }
.avg-nd  { color: #94a3b8; font-weight: 600; }
/* Filas alternas */
.minerd-row:nth-child(even) .mcell-p  { background: #eff6ff; }
.minerd-row:nth-child(even) .mcell-rp { background: #fefce8; }
.minerd-row:nth-child(even) .mcell-cf { background: #f0fdf4; }
/* Prom comp badge (resumen) */
.prom-comp-badge, .final-comp-badge { font-weight: 800; font-size: .87rem; }
.fn-ok  { color: #15803d; font-weight: 800; }
.fn-mal { color: #dc2626; font-weight: 800; }
.fn-nd  { color: #94a3b8; font-weight: 600; }
/* Situación */
.sit-comp { font-size: .72rem; font-weight: 800; }
.sit-ap { color: #15803d; }
.sit-rp { color: #dc2626; }
.sit-nd { color: #94a3b8; }

/* ── Inputs ── */
.acad-inp {
    width: 48px; text-align: center;
    border: 1px solid #d1d5db; border-radius: 4px;
    padding: .22rem .1rem; font-size: .82rem; font-weight: 700;
    background: #fff; color: #111827;
    transition: border-color .12s;
    -moz-appearance: textfield;
}
.acad-inp::-webkit-inner-spin-button,
.acad-inp::-webkit-outer-spin-button { -webkit-appearance: none; }
.acad-inp:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.2); }
.acad-inp.saving { border-color: #f59e0b !important; background: #fffbeb !important; }
.acad-inp.saved  { border-color: #10b981 !important; background: #f0fdf4 !important; }
.acad-inp.inp-error { border-color: #ef4444 !important; background: #fef2f2 !important; }

/* ── R como recuperación ── */
.r-inp.r-rec  { border-color: #f59e0b !important; background: #fffbeb !important; color: #92400e !important; }
.r-inp:disabled { opacity: .2; cursor: not-allowed; pointer-events: none; }
.r-faltante-lbl { font-size: .5rem; font-weight: 800; color: #d97706; white-space: nowrap; line-height: 1; }

/* ── AJAX status bar ── */
#ajax-status { display: none; border-radius: 8px; padding: .4rem .85rem; font-size: .76rem; font-weight: 600; margin-bottom: .75rem; }
#ajax-status.saving { display: flex !important; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
#ajax-status.saved  { display: flex !important; background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
#ajax-status.err    { display: flex !important; background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }

/* ── Dark mode ── */
[data-theme="dark"] .minerd-tbl th, [data-theme="dark"] .minerd-tbl td { border-color: #374155; }
[data-theme="dark"] .mth-p    { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .mth-rp   { background: #2d1b00; color: #fde68a; }
[data-theme="dark"] .mth-cf   { background: #052e16; color: #4ade80; }
[data-theme="dark"] .mth-prom { background: #2e1b4e; color: #c4b5fd; }
[data-theme="dark"] .mcell-p  { background: #0f172a; }
[data-theme="dark"] .mcell-rp { background: #1c1000; }
[data-theme="dark"] .mcell-cf { background: #052e16; }
[data-theme="dark"] .minerd-row:nth-child(even) .mcell-p { background: #1a2640; }
[data-theme="dark"] .acad-inp { background: #0f172a; border-color: #334155; color: #e2e8f0; }
[data-theme="dark"] .acad-inp.saving { background: #1a1500 !important; }
[data-theme="dark"] .acad-inp.saved  { background: #052e16 !important; }
[data-theme="dark"] .avg-ok, [data-theme="dark"] .fn-ok { color: #4ade80; }
[data-theme="dark"] .avg-mal, [data-theme="dark"] .fn-mal { color: #f87171; }
[data-theme="dark"] .avg-nd,  [data-theme="dark"] .fn-nd  { color: #475569; }
[data-theme="dark"] .r-inp.r-rec { background: #1c1000 !important; border-color: #854d0e !important; color: #fde68a !important; }
[data-theme="dark"] .r-faltante-lbl { color: #fbbf24; }
[data-theme="dark"] .sit-ap { color: #4ade80; }
[data-theme="dark"] .sit-rp { color: #f87171; }
[data-theme="dark"] .sit-nd { color: #475569; }
</style>
@endpush

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-journal-check" style="color:{{ $esTecnica ? '#7c3aed' : '#1d4ed8' }};"></i>
            Calificaciones — {{ $asignacion->asignatura?->nombre }}
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
            <span>{{ $asignacion->grupo?->nombre_completo ?? '—' }}</span>
            <span>·</span>
            <span>{{ $matriculas->count() }} estudiante(s)</span>
            @if($schoolYear)<span>·</span><span>{{ $schoolYear->nombre }}</span>@endif
            <span class="tipo-badge {{ $esTecnica ? 'tipo-tecnica' : 'tipo-academica' }}">
                <i class="bi bi-{{ $esTecnica ? 'tools' : 'book' }}"></i>
                {{ $esTecnica ? 'Técnica — RA' : 'Académica' }}
            </span>
        </div>
    </div>
    <a href="{{ route('portal.docente.consolidado-periodo', $asignacion) }}"
       style="background:#0f766e;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-clipboard-data-fill"></i>Consolidado
    </a>
    <a href="{{ route('portal.docente.acta-calificaciones', $asignacion) }}"
       style="background:#1e3a6e;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-spreadsheet-fill"></i>Acta Oficial
    </a>
    <a href="{{ route('portal.docente.calificaciones.exportar-pdf', $asignacion) }}" target="_blank"
       style="background:#991b1b;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-pdf"></i>PDF
    </a>
    <a href="{{ route('portal.docente.calificaciones.exportar-excel', $asignacion) }}"
       style="background:#166534;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-excel"></i>Excel
    </a>
</div>

{{-- ── Mensajes ─────────────────────────────────────────────────────────── --}}
@if(session('success'))
    <div style="background:#dcfce7;border:1px solid #86efac;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.83rem;color:#15803d;display:flex;align-items:center;gap:.5rem;">
        <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.83rem;color:#dc2626;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
    </div>
@endif

{{-- ── Stats ────────────────────────────────────────────────────────────── --}}
@php
    $conNota   = $calificaciones->filter(fn($c) => $c->nota_final !== null)->count();
    $sinNota   = $matriculas->count() - $conNota;
    $promedio  = $calificaciones->filter(fn($c) => $c->nota_final !== null)->avg('nota_final');
    $umbral    = 70;
    $aprobados = $calificaciones->filter(fn($c) => $c->nota_final !== null && $c->nota_final >= $umbral)->count();
    $acColor   = $esTecnica ? '#7c3aed' : '#1d4ed8';
    $acLight   = $esTecnica ? '#ede9fe' : '#dbeafe';
@endphp
<div class="cal-stats-grid">
    <div style="background:{{ $acLight }};border-radius:10px;padding:.7rem;text-align:center;">
        <div style="font-size:1.2rem;font-weight:800;color:{{ $acColor }};">{{ $matriculas->count() }}</div>
        <div style="font-size:.68rem;color:{{ $acColor }};">Estudiantes</div>
    </div>
    <div style="background:#dcfce7;border-radius:10px;padding:.7rem;text-align:center;">
        <div style="font-size:1.2rem;font-weight:800;color:#15803d;">{{ $aprobados }}</div>
        <div style="font-size:.68rem;color:#16a34a;">Aprobados</div>
    </div>
    <div style="background:{{ $sinNota > 0 ? '#fee2e2' : '#f0fdf4' }};border-radius:10px;padding:.7rem;text-align:center;">
        <div style="font-size:1.2rem;font-weight:800;color:{{ $sinNota > 0 ? '#dc2626' : '#15803d' }};">{{ $sinNota }}</div>
        <div style="font-size:.68rem;color:{{ $sinNota > 0 ? '#dc2626' : '#15803d' }};">Sin nota</div>
    </div>
    <div style="background:#f0fdf4;border-radius:10px;padding:.7rem;text-align:center;">
        <div style="font-size:1.2rem;font-weight:800;color:#15803d;">
            {{ $promedio ? number_format($promedio, 1) : '—' }}
            @if($promedio)<span style="font-size:.65rem;font-weight:600;color:#16a34a;">/100</span>@endif
        </div>
        <div style="font-size:.68rem;color:#16a34a;">Promedio</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     ÁREA TÉCNICA — Resultados de Aprendizaje por Período
══════════════════════════════════════════════════════════════════════════ --}}
@if($esTecnica)

{{-- Sin RAs configurados --}}
@if($numRA === 0)
    <div class="prt-card" style="text-align:center;padding:2rem;">
        <i class="bi bi-exclamation-triangle" style="font-size:2rem;color:#f59e0b;"></i>
        <p style="margin-top:.75rem;color:#92400e;font-size:.85rem;">
            La asignatura <strong>{{ $asignacion->asignatura?->nombre }}</strong> no tiene Resultados de Aprendizaje configurados.
            <br>Ve a <strong>Configuración → Asignaturas → editar</strong> y activa la evaluación por RA.
        </p>
    </div>
@else

{{-- Período tabs --}}
@if($periodos->isNotEmpty())
<div class="per-tabs">
    @foreach($periodos as $per)
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}?periodo_id={{ $per->id }}"
       class="per-tab {{ $per->id == $periodoId ? 'active' : '' }}">
        <i class="bi bi-calendar3 me-1"></i>{{ $per->nombre ?? 'Período ' . $per->numero }}
    </a>
    @endforeach
</div>
@else
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#92400e;">
        <i class="bi bi-exclamation-triangle me-1"></i>No hay períodos configurados para el año escolar activo.
    </div>
@endif

@if($periodoActual)
<form method="POST" action="{{ route('portal.docente.calificaciones.guardar', $asignacion) }}" id="form-calificaciones">
@csrf
<input type="hidden" name="periodo_id" value="{{ $periodoId }}">

<div class="prt-card">
    <div class="prt-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="bi bi-tools" style="color:#7c3aed;font-size:1rem;"></i>
            <h3>RA — {{ $periodoActual->nombre ?? 'Período ' . $periodoActual->numero }}</h3>
        </div>
        <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:.38rem .9rem;font-size:.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-floppy-fill"></i>Guardar
        </button>
    </div>

    {{-- ── Panel editable de puntos RA ──────────────────────────────── --}}
    <div id="ra-pesos-panel" style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;background:#faf5ff;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;flex-wrap:wrap;gap:.4rem;">
            <span style="font-size:.72rem;font-weight:700;color:#5b21b6;text-transform:uppercase;letter-spacing:.06em;">
                <i class="bi bi-sliders me-1"></i>Puntos por RA
                @if($asignacion->pesos_ra)
                    <span style="background:#7c3aed;color:#fff;border-radius:4px;padding:.1rem .4rem;font-size:.62rem;margin-left:.3rem;">Personalizado</span>
                @else
                    <span style="background:#e2e8f0;color:#64748b;border-radius:4px;padding:.1rem .4rem;font-size:.62rem;margin-left:.3rem;">Global</span>
                @endif
            </span>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span id="pesos-total-badge" style="font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:5px;background:#e2e8f0;color:#374151;">Total: 0 pts</span>
                <button type="button" id="btn-distribuir-pesos"
                    style="font-size:.72rem;background:#ede9fe;color:#5b21b6;border:1px solid #c4b5fd;border-radius:6px;padding:.2rem .6rem;cursor:pointer;">
                    <i class="bi bi-distribute-horizontal me-1"></i>Igualar
                </button>
                <button type="button" id="btn-guardar-pesos"
                    style="font-size:.72rem;background:#7c3aed;color:#fff;border:none;border-radius:6px;padding:.2rem .7rem;cursor:pointer;font-weight:700;">
                    <i class="bi bi-floppy me-1"></i>Guardar puntos
                </button>
            </div>
        </div>

        {{-- Barra visual --}}
        <div style="height:6px;background:#e9d5ff;border-radius:4px;overflow:hidden;margin-bottom:.6rem;display:flex;">
            @foreach($pesosRA as $n => $p)
            <div id="pesos-bar-{{ $n }}" style="height:100%;width:{{ $p }}%;background:hsl({{ ($n-1)*36 }},65%,55%);transition:width .3s;"></div>
            @endforeach
        </div>

        {{-- Inputs RA --}}
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;" id="pesos-inputs-wrap">
            @foreach($pesosRA as $n => $p)
            @php $raObj = $ras->firstWhere('numero', $n); @endphp
            <div style="display:flex;flex-direction:column;align-items:center;gap:.2rem;min-width:72px;">
                <span style="background:#7c3aed;color:#fff;border-radius:5px;font-size:.65rem;font-weight:800;padding:.1rem .35rem;">RA{{ $n }}</span>
                @if($raObj?->descripcion)
                <span style="font-size:.58rem;color:#6b7280;text-align:center;max-width:72px;line-height:1.2;" title="{{ $raObj->descripcion }}">
                    {{ Str::limit($raObj->descripcion, 30) }}
                </span>
                @endif
                <div style="display:flex;align-items:center;gap:.15rem;">
                    <input type="number" class="pesos-ra-inp"
                           data-n="{{ $n }}"
                           value="{{ round($p) }}"
                           min="0" max="100" step="1"
                           style="width:52px;text-align:center;border:1.5px solid #c4b5fd;border-radius:6px;padding:.25rem;font-size:.78rem;font-weight:700;background:#fff;color:#1e293b;">
                    <span style="font-size:.7rem;color:#7c3aed;">pts</span>
                </div>
            </div>
            @endforeach
        </div>
        <div id="pesos-msg" style="font-size:.75rem;min-height:1.1rem;margin-top:.35rem;"></div>
    </div>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr class="dm-thead" style="background:#f8faff;border-bottom:1.5px solid #e2e8f0;">
                    <th style="padding:.5rem .85rem;text-align:left;" class="col-head">#</th>
                    <th style="padding:.5rem .85rem;text-align:left;min-width:160px;" class="col-head">Estudiante</th>
                    @for($i = 1; $i <= $numRA; $i++)
                    @php
                        $raObj   = $ras->firstWhere('numero', $i);
                        $ptoMax  = $pesosRA[$i] ?? round(100 / $numRA, 1);
                    @endphp
                    <th class="col-head ra-col-head" style="padding:.4rem .3rem;min-width:66px;">
                        <div class="ra-num">{{ $i }}</div>
                        @if($raObj && $raObj->descripcion)
                        <span class="ra-desc" title="{{ $raObj->descripcion }}">
                            {{ Str::limit($raObj->descripcion, 35) }}
                        </span>
                        @endif
                        <span style="display:block;font-size:.6rem;color:#7c3aed;font-weight:800;margin-top:.15rem;">{{ number_format($ptoMax, 0) }} pts</span>
                        <span style="display:block;font-size:.52rem;color:#a78bfa;">+3 Rec.</span>
                    </th>
                    @endfor
                    <th class="col-head">Final</th>
                </tr>
            </thead>
            <tbody>
            @foreach($matriculas as $idx => $mat)
            @php
                $cal = $calificaciones->get($mat->id);
                $nf  = $cal?->nota_final;
            @endphp
            <tr class="est-row" data-row="{{ $mat->id }}">
                <td style="padding:.55rem .85rem;color:#7c3aed;font-size:.75rem;font-weight:700;">{{ $idx + 1 }}</td>
                <td style="padding:.55rem .85rem;">
                    <div class="dm-text-primary" style="font-weight:700;font-size:.85rem;">
                        {{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}
                    </div>
                    <div style="font-size:.68rem;color:#a78bfa;font-family:monospace;">
                        {{ $mat->estudiante?->numero_matricula }}
                    </div>
                </td>
                @for($i = 1; $i <= $numRA; $i++)
                @php
                    $raVal    = $cal ? $cal->{"ra{$i}"} : null;
                    $recData  = $cal?->recuperaciones_ra[$i] ?? $cal?->recuperaciones_ra[(string)$i] ?? null;
                    $critData = $cal?->criterios_ra[$i]      ?? $cal?->criterios_ra[(string)$i]      ?? null;
                    $ptoMax   = $pesosRA[$i] ?? round(100 / $numRA, 1);
                    $umbralRA = round($ptoMax * 0.7, 1);
                    // Si hay recuperación estructurada, nota efectiva = mejor entre raw y cf_escalada
                    $cfEscalada = is_array($recData) ? ($recData['cf_escalada'] ?? null) : null;
                    $raEfectiva = $raVal !== null ? max($raVal, $cfEscalada ?? 0) : null;
                    $fmtVal = fn($v) => $v !== null ? ($v == (int)$v ? (int)$v : number_format($v, 1, '.', '')) : '';
                    // Criterios guardados
                    $cTp = $critData['tp'] ?? '';
                    $cEx = $critData['ex'] ?? '';
                    $cCc = $critData['cc'] ?? '';
                    $cOh = $critData['oh'] ?? '';
                    $cPd = $critData['pd'] ?? '';
                    $cEc = $critData['ec'] ?? '';
                    // Recuperación guardada
                    $rPractica    = is_array($recData) ? ($recData['practica']      ?? '') : '';
                    $rExposicion  = is_array($recData) ? ($recData['exposicion']    ?? '') : '';
                    $rPracticaEval= is_array($recData) ? ($recData['practica_eval'] ?? '') : '';
                    $rCf          = is_array($recData) ? ($recData['cf']            ?? null) : null;
                    $hayRec       = $rPractica !== '' || $rExposicion !== '' || $rPracticaEval !== '';
                @endphp
                <td style="padding:.3rem .2rem;text-align:center;vertical-align:top;min-width:100px;">
                    <div class="ra-cell-wrap">
                        {{-- Criterios por RA (T.P./EX./C.C./O.H./P.D./E.C.) --}}
                        <div class="crit-grid">
                            @foreach([['tp','T.P.',30],['ex','EX.',15],['cc','C.C.',10],['oh','O.H.',20],['pd','P.D.',15],['ec','E.C.',10]] as [$cn,$cl,$cmax])
                            <div class="crit-block">
                                <span class="crit-lbl">{{ $cl }}<span style="font-weight:400;color:#a78bfa;">/{{ $cmax }}</span></span>
                                <input type="number"
                                       name="criterios[{{ $mat->id }}][ra{{ $i }}][{{ $cn }}]"
                                       value="{{ ${'c'.ucfirst($cn)} ?? '' }}"
                                       min="0" max="{{ $cmax }}" step="1"
                                       class="crit-inp"
                                       data-row="{{ $mat->id }}"
                                       data-ra="{{ $i }}"
                                       data-crit="{{ $cn }}"
                                       data-cmax="{{ $cmax }}"
                                       data-pto-max="{{ $ptoMax }}"
                                       oninput="recalcularCriterios(this)">
                            </div>
                            @endforeach
                        </div>
                        {{-- Nota directa RA (resultado o manual) --}}
                        <div class="intento-block" style="margin-top:3px;">
                            <span class="intento-label" style="color:#7c3aed;">Nota RA</span>
                            <input type="number"
                                   name="notas[{{ $mat->id }}][ra{{ $i }}]"
                                   value="{{ $fmtVal($raVal) }}"
                                   min="0" max="{{ $ptoMax }}" step="any"
                                   class="nota-inp ra-inp {{ $raEfectiva !== null ? ($raEfectiva >= $umbralRA ? 'aprobado' : 'reprobado') : '' }}"
                                   data-row="{{ $mat->id }}"
                                   data-ra="{{ $i }}"
                                   data-pto-max="{{ $ptoMax }}"
                                   oninput="recalcularRA(this)">
                        </div>
                        {{-- Recuperación estructurada (solo si reprueba o ya tiene datos) --}}
                        <div class="rec-struct {{ (!$hayRec && $raEfectiva !== null && $raEfectiva >= $umbralRA) ? 'rec-hidden' : '' }}"
                             id="rec-struct-{{ $mat->id }}-{{ $i }}"
                             style="{{ (!$hayRec && $raEfectiva !== null && $raEfectiva >= $umbralRA) ? 'display:none;' : '' }}">
                            <div class="rec-struct-header">
                                <i class="bi bi-arrow-repeat"></i> Recuperación
                            </div>
                            <div class="rec-struct-grid">
                                <div class="rec-struct-block" style="grid-column:1/-1;">
                                    <span class="rec-struct-lbl">Práctica <span style="opacity:.7;">/25</span></span>
                                    <input type="number"
                                           name="recuperaciones[{{ $mat->id }}][ra{{ $i }}][practica]"
                                           value="{{ $rPractica }}"
                                           min="0" max="25" step="1"
                                           class="rec-struct-inp"
                                           data-row="{{ $mat->id }}"
                                           data-ra="{{ $i }}"
                                           data-reckey="practica"
                                           data-pto-max="{{ $ptoMax }}"
                                           oninput="recalcularRecuperacion(this)">
                                </div>
                                <div class="rec-struct-block">
                                    <span class="rec-struct-lbl">Exp. <span style="opacity:.7;">/25</span></span>
                                    <input type="number"
                                           name="recuperaciones[{{ $mat->id }}][ra{{ $i }}][exposicion]"
                                           value="{{ $rExposicion }}"
                                           min="0" max="25" step="1"
                                           class="rec-struct-inp"
                                           data-row="{{ $mat->id }}"
                                           data-ra="{{ $i }}"
                                           data-reckey="exposicion"
                                           data-pto-max="{{ $ptoMax }}"
                                           oninput="recalcularRecuperacion(this)">
                                </div>
                                <div class="rec-struct-block">
                                    <span class="rec-struct-lbl">P.Eval <span style="opacity:.7;">/50</span></span>
                                    <input type="number"
                                           name="recuperaciones[{{ $mat->id }}][ra{{ $i }}][practica_eval]"
                                           value="{{ $rPracticaEval }}"
                                           min="0" max="50" step="1"
                                           class="rec-struct-inp"
                                           data-row="{{ $mat->id }}"
                                           data-ra="{{ $i }}"
                                           data-reckey="practica_eval"
                                           data-pto-max="{{ $ptoMax }}"
                                           oninput="recalcularRecuperacion(this)">
                                </div>
                            </div>
                            @if($rCf !== null)
                            <span class="rec-cf-badge {{ $rCf >= 70 ? 'per-aprobado' : 'per-reprobado' }}"
                                  id="rec-cf-{{ $mat->id }}-{{ $i }}">
                                CF: {{ number_format($rCf, 0) }}
                            </span>
                            @else
                            <span class="rec-cf-badge final-vacio" id="rec-cf-{{ $mat->id }}-{{ $i }}" style="display:none;"></span>
                            @endif
                        </div>
                    </div>
                </td>
                @endfor
                <td style="padding:.45rem .5rem;text-align:center;" id="final-{{ $mat->id }}">
                    @if($nf !== null)
                        <span class="final-badge {{ $nf >= $umbral ? 'final-aprobado' : 'final-reprobado' }}">
                            {{ number_format($nf, 0) }}<span style="font-size:.6rem;font-weight:500;opacity:.7;">/100</span>
                        </span>
                    @else
                        <span class="final-badge final-vacio">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="dm-toolbar" style="padding:.75rem 1rem;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:.75rem;align-items:center;flex-wrap:wrap;">
        <span class="dm-text-muted" style="font-size:.76rem;color:#64748b;">
            <i class="bi bi-info-circle me-1"></i>Criterios por RA: <strong>T.P.(30) + EX.(15) + C.C.(10) + O.H.(20) + P.D.(15) + E.C.(10) = 100 pts</strong>.
            Nota RA = (Criterios/100) × peso. Recuperación: 50% nota acumulada + 50% (Práctica+Exp.+P.Eval). Aprobado ≥ 70 pts.
        </span>
        <button type="submit" style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:.45rem 1.1rem;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-floppy-fill"></i>Guardar calificaciones
        </button>
    </div>
</div>
</form>
@endif {{-- periodoActual --}}
@endif {{-- numRA --}}

{{-- ══════════════════════════════════════════════════════════════════════════
     ÁREA ACADÉMICA — 4 Competencias MINERD (Guardado AJAX por celda)
══════════════════════════════════════════════════════════════════════════ --}}
@else

@php
/* Ruta AJAX para guardar celda individual */
$celdaRoute = route('portal.docente.calificaciones.acad.celda', $asignacion);
/* Helper: formatea un número sin el .0 innecesario (65.0 → "65", 65.5 → "65.5") */
$fmt = fn($v, $empty = '—') => $v !== null
    ? rtrim(rtrim(number_format((float)$v, 1, '.', ''), '0'), '.')
    : $empty;
@endphp

{{-- ── AJAX status ──────────────────────────────────────────────────────── --}}
<div id="ajax-status" style="display:none;padding:.4rem .85rem;border-radius:8px;font-size:.76rem;font-weight:600;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem;"></div>

{{-- ── Cabecera del panel ───────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:.55rem;padding:.5rem .75rem;background:#f0f4ff;border:1px solid #c7d4f5;border-bottom:none;border-radius:8px 8px 0 0;font-size:.78rem;">
    <i class="bi bi-mortarboard" style="color:#1d4ed8;font-size:1rem;"></i>
    <span style="font-weight:800;color:#1e293b;">Registro Académico — 4 Competencias MINERD</span>
    <span style="margin-left:auto;font-size:.67rem;color:#6b7280;font-weight:600;">
        <i class="bi bi-floppy"></i> Guardado automático por celda
    </span>
</div>

{{-- ── Tabla única: C1–C4 como grupos de columnas ────────────────────────── --}}
<div style="overflow-x:auto;border:1px solid #d1d5db;border-radius:0 0 8px 8px;">
<table class="minerd-tbl" style="min-width:1100px;">
    <thead>
        {{-- Fila 1: grupos competencia + columnas finales --}}
        <tr>
            <th rowspan="2" style="min-width:26px;font-size:.65rem;color:#374151;background:#f8fafc;">#</th>
            <th rowspan="2" style="min-width:160px;text-align:left;padding:.28rem .5rem;font-size:.65rem;color:#374151;background:#f8fafc;position:sticky;left:0;z-index:2;">ESTUDIANTE</th>
            @foreach($competencias as $ci => $comp)
            <th colspan="9" class="mth-per"
                style="background:{{ $comp['color'] }};font-size:.68rem;white-space:normal;line-height:1.3;padding:.28rem .35rem;">
                <i class="bi {{ $comp['icon'] }}" style="opacity:.85;margin-right:.2rem;"></i>C{{ $ci }} — {{ $comp['nombre'] }}
            </th>
            @endforeach
            <th rowspan="2" style="background:#111827;color:#fff;font-size:.62rem;font-weight:800;min-width:46px;padding:.28rem .2rem;">NOTA<br>FINAL</th>
            <th rowspan="2" style="background:#1e40af;color:#fff;font-size:.6rem;font-weight:700;min-width:42px;padding:.28rem .2rem;">CC<br><span style="font-size:.54rem;font-weight:500;opacity:.85;">30%</span></th>
            <th rowspan="2" style="background:#4f46e5;color:#fff;font-size:.6rem;font-weight:700;min-width:42px;padding:.28rem .2rem;">CE<br><span style="font-size:.54rem;font-weight:500;opacity:.85;">50%</span></th>
            <th rowspan="2" style="background:#059669;color:#fff;font-size:.62rem;font-weight:800;min-width:42px;padding:.28rem .2rem;">CCF</th>
        </tr>
        {{-- Fila 2: P1 RP1 … P4 RP4 ★PROM  ×4 --}}
        <tr>
            @foreach($competencias as $ci => $comp)
                @foreach([1,2,3,4] as $per)
                <th class="mth-p">P{{ $per }}</th>
                <th class="mth-rp">RP{{ $per }}</th>
                @endforeach
                <th class="mth-prom" style="background:{{ $comp['color'] }}22;color:{{ $comp['dark'] }};">★<br>PROM</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas as $idx => $mat)
    @php
        $cal             = $calificaciones->get($mat->id);
        $nf              = $cal?->nota_final;
        $nc              = $cal?->nota_cc;
        $ne              = $cal?->nota_ce;
        $notaCompletiva  = $cal?->nota_completiva;
        $notaExtraord    = $cal?->nota_extraordinaria;
        $showCC          = $nf !== null && $nf < 70;
        $showCE          = $notaCompletiva !== null && $notaCompletiva < 70;
        $ccf             = $notaExtraord ?? $notaCompletiva ?? null;
    @endphp
    <tr class="minerd-row" data-mat="{{ $mat->id }}">
        <td style="font-size:.7rem;color:#6b7280;font-weight:600;">{{ $idx + 1 }}</td>
        <td style="text-align:left;padding:.2rem .4rem;position:sticky;left:0;background:#fff;z-index:1;">
            <div style="font-weight:700;font-size:.79rem;white-space:nowrap;">{{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}</div>
            <div style="font-size:.59rem;color:#6b7280;font-family:monospace;">{{ $mat->estudiante?->numero_matricula }}</div>
        </td>

        @foreach($competencias as $ci => $comp)
        @php $promComp = $cal?->{"prom_comp{$ci}"}; @endphp
        @foreach([1,2,3,4] as $per)
        @php
            $pVal     = $cal?->{"comp{$ci}_p{$per}"};
            $rVal     = $cal?->{"comp{$ci}_r{$per}"};
            $faltante = $pVal !== null ? max(0, round(100 - $pVal, 1)) : 100;
            $showR    = $pVal !== null && $pVal < 70;
        @endphp
        <td class="mcell-p">
            <input type="number" class="acad-inp p-inp"
                   min="0" max="100" step="0.5" placeholder="—"
                   value="{{ $fmt($pVal, '') }}"
                   data-mat="{{ $mat->id }}"
                   data-campo="comp{{ $ci }}_p{{ $per }}"
                   data-comp="{{ $ci }}" data-per="{{ $per }}"
                   onblur="guardarCelda(this)" oninput="recalcLocal(this)">
        </td>
        <td class="mcell-rp {{ !$showR ? 'locked' : '' }}" id="rcell-c{{ $ci }}-p{{ $per }}-m{{ $mat->id }}">
            <div style="display:flex;flex-direction:column;align-items:center;gap:.04rem;">
                <span class="r-faltante-lbl"
                      id="rfalt-c{{ $ci }}-p{{ $per }}-m{{ $mat->id }}"
                      style="{{ !$showR ? 'display:none;' : '' }}">
                    máx <span id="rfalt-val-c{{ $ci }}-p{{ $per }}-m{{ $mat->id }}">{{ number_format($faltante, 0) }}</span>
                </span>
                <input type="number" class="acad-inp r-inp {{ $showR ? 'r-rec' : '' }}"
                       min="0" max="{{ $faltante }}" step="0.5" placeholder="—"
                       value="{{ $fmt($rVal, '') }}"
                       data-mat="{{ $mat->id }}"
                       data-campo="comp{{ $ci }}_r{{ $per }}"
                       data-comp="{{ $ci }}" data-per="{{ $per }}"
                       {{ !$showR ? 'disabled' : '' }}
                       onblur="guardarCelda(this)" oninput="recalcLocal(this)">
            </div>
        </td>
        @endforeach
        {{-- PROM COMP --}}
        <td style="background:{{ $comp['color'] }}15;font-weight:800;font-size:.82rem;">
            <span class="prom-comp-badge {{ $promComp !== null ? ($promComp >= 70 ? 'avg-ok' : 'avg-mal') : 'avg-nd' }}"
                  id="prom-c{{ $ci }}-m{{ $mat->id }}">
                {{ $fmt($promComp) }}
            </span>
        </td>
        @endforeach

        {{-- Nota Final --}}
        <td style="background:#11182710;font-weight:800;">
            <span class="final-comp-badge {{ $nf !== null ? ($nf >= 70 ? 'fn-ok' : 'fn-mal') : 'fn-nd' }}"
                  id="res-final-m{{ $mat->id }}">
                {{ $fmt($nf) }}
            </span>
        </td>
        {{-- CC (Completivo) --}}
        <td class="mcell-rp {{ !$showCC ? 'locked' : '' }}" id="cc-cell-m{{ $mat->id }}" style="min-width:48px;">
            <input type="number" class="acad-inp cc-inp {{ $showCC ? 'r-rec' : '' }}"
                   min="0" max="100" step="0.5" placeholder="—"
                   value="{{ $fmt($nc, '') }}"
                   data-mat="{{ $mat->id }}" data-campo="nota_cc"
                   {{ !$showCC ? 'disabled' : '' }}
                   onblur="guardarCelda(this)" oninput="recalcCompletivo(this)">
        </td>
        {{-- CE (Extraordinario) --}}
        <td class="mcell-rp {{ !$showCE ? 'locked' : '' }}" id="ce-cell-m{{ $mat->id }}" style="min-width:48px;">
            <input type="number" class="acad-inp ce-inp {{ $showCE ? 'r-rec' : '' }}"
                   min="0" max="100" step="0.5" placeholder="—"
                   value="{{ $fmt($ne, '') }}"
                   data-mat="{{ $mat->id }}" data-campo="nota_ce"
                   {{ !$showCE ? 'disabled' : '' }}
                   onblur="guardarCelda(this)" oninput="recalcExtraordinario(this)">
        </td>
        {{-- CCF (Calificación Final Completiva — auto) --}}
        <td style="text-align:center;font-weight:800;font-size:.82rem;">
            <span id="ccf-m{{ $mat->id }}"
                  class="{{ $ccf !== null ? ($ccf >= 70 ? 'avg-ok' : 'avg-mal') : 'avg-nd' }}">
                {{ $fmt($ccf) }}
            </span>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>

{{-- Leyenda --}}
<div style="padding:.45rem .75rem;border:1px solid #e5e7eb;border-top:none;font-size:.69rem;color:#6b7280;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;background:#fafafa;border-radius:0 0 6px 6px;margin-bottom:.5rem;">
    <span style="color:#1e40af;font-weight:700;">P</span> = Nota base &nbsp;·&nbsp;
    <span style="color:#92400e;font-weight:700;">RP</span> = Recuperación (si P &lt; 70) &nbsp;·&nbsp;
    <span style="font-weight:700;">★ PROM</span> = Promedio por competencia &nbsp;·&nbsp;
    <span style="color:#1e40af;font-weight:700;">CC</span> = Completivo (activo si NF&lt;70) &nbsp;·&nbsp;
    <span style="color:#4f46e5;font-weight:700;">CE</span> = Extraordinario (activo si CCF&lt;70) &nbsp;·&nbsp;
    <span style="color:#059669;font-weight:700;">CCF</span> = 0.5×NF+0.5×CC → 0.3×NF+0.7×CE
</div>

@endif {{-- @if($esTecnica) --}}

{{-- ── Panel Offline: Descargar plantilla / Importar ──────────────────── --}}
<div class="prt-card" style="margin-top:1rem;">
    <div class="prt-card-header" style="cursor:pointer;user-select:none;" onclick="toggleOffline()">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="bi bi-cloud-slash" style="color:#f59e0b;font-size:1rem;"></i>
            <h3 style="color:#92400e;">Modo sin internet — Plantilla CSV</h3>
        </div>
        <span id="offline-chevron" style="font-size:.8rem;color:#92400e;transition:transform .2s;">▼</span>
    </div>
    <div id="offline-panel" style="display:none;padding:1rem;border-top:1px solid #fde68a;background:#fffbeb;">

        {{-- Instrucciones --}}
        <div style="background:#fff;border:1px solid #fde68a;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#78350f;">
            <strong><i class="bi bi-info-circle me-1"></i>¿Cómo usar la plantilla?</strong>
            <ol style="margin:.4rem 0 0 1.1rem;padding:0;line-height:2;">
                <li>Descarga la plantilla CSV con los estudiantes ya cargados.</li>
                <li>Ábrela en Excel, LibreOffice o Google Sheets y rellena las notas.</li>
                @if($esTecnica)
                <li>Columna <code>periodo</code>: escribe el número del período (1, 2, 3 o 4).</li>
                <li>Columnas <code>ra1, ra2, …</code>: nota de cada Resultado de Aprendizaje (0–100).</li>
                @else
                <li>Columnas <code>p1, p2, p3, p4</code>: nota de cada período (0–100). Deja en blanco si no aplica.</li>
                @endif
                <li>Guarda como CSV y sube el archivo aquí. El sistema calcula la nota final automáticamente.</li>
            </ol>
        </div>

        {{-- Botón descargar --}}
        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;">
            <a href="{{ route('portal.docente.calificaciones.plantilla', $asignacion) }}{{ $esTecnica && $periodoId ? '?periodo_id='.$periodoId : '' }}"
               style="display:inline-flex;align-items:center;gap:.5rem;background:#16a34a;color:#fff;text-decoration:none;border-radius:8px;padding:.45rem 1rem;font-size:.82rem;font-weight:700;">
                <i class="bi bi-file-earmark-arrow-down-fill"></i>Descargar plantilla CSV
            </a>
            <span style="font-size:.75rem;color:#92400e;">
                Incluye los {{ $matriculas->count() }} estudiante(s) del grupo
                @if($esTecnica && $numRA) · {{ $numRA }} RA @else · columnas P1–P4 @endif
            </span>
        </div>

        {{-- Formulario importar con preview --}}
        <form method="POST"
              action="{{ route('portal.docente.calificaciones.importar.preview', $asignacion) }}"
              enctype="multipart/form-data"
              style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            @if($esTecnica && $periodoId)
                <input type="hidden" name="periodo_id" value="{{ $periodoId }}">
            @endif
            <div style="flex:1;min-width:200px;">
                <label style="font-size:.75rem;font-weight:600;color:#92400e;display:block;margin-bottom:.3rem;">
                    Subir plantilla completada (.csv, .xlsx):
                </label>
                <input type="file" name="archivo" accept=".csv,.xlsx,.xls"
                       required
                       style="width:100%;font-size:.8rem;border:1.5px solid #fde68a;border-radius:8px;padding:.35rem .6rem;background:#fff;color:#374151;">
            </div>
            <button type="submit"
                    style="background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:.45rem 1rem;font-size:.82rem;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-eye-fill"></i>Vista previa
            </button>
        </form>

        {{-- Errores de importación --}}
        @if(session('errores_import') && count(session('errores_import')))
        <div style="margin-top:.75rem;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.65rem .9rem;font-size:.77rem;color:#991b1b;">
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Filas con errores (omitidas):</strong>
            <ul style="margin:.3rem 0 0 1rem;padding:0;">
                @foreach(session('errores_import') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
const UMBRAL = 70;

/* ── Formatea número sin .0 innecesario (65.0 → "65", 65.5 → "65.5") ── */
function fmt(v) {
    if (v === null || v === undefined) return '—';
    const n = parseFloat(v);
    return n % 1 === 0 ? String(Math.round(n)) : n.toFixed(1);
}

/* ═══════════════════════════════════════════════════════
   MÓDULO COMPETENCIAS MINERD — Motor AJAX
═══════════════════════════════════════════════════════ */
@if(!$esTecnica)
const CELDA_URL  = @json($celdaRoute);
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ── Indicador de estado ───────────────────────────── */
function setStatus(type, msg) {
    const el = document.getElementById('ajax-status');
    if (!el) return;
    el.className = type;   // 'saving' | 'saved' | 'err'
    el.innerHTML = msg;
    if (type === 'saved') setTimeout(() => { el.className = ''; el.innerHTML = ''; }, 2500);
}

/* ── Recalc local: actualiza faltante + PROM COMP ─── */
function recalcLocal(inp) {
    const mat  = inp.dataset.mat;
    const comp = parseInt(inp.dataset.comp);
    const per  = parseInt(inp.dataset.per);
    const isP  = inp.classList.contains('p-inp');

    const pInp = document.querySelector(`.acad-inp.p-inp[data-mat="${mat}"][data-comp="${comp}"][data-per="${per}"]`);
    const rInp = document.querySelector(`.acad-inp.r-inp[data-mat="${mat}"][data-comp="${comp}"][data-per="${per}"]`);

    const pv = pInp && pInp.value !== '' ? parseFloat(pInp.value) : null;
    const faltante = pv !== null ? Math.max(0, Math.round((100 - pv) * 10) / 10) : 100;

    // Si cambió P → gestionar visibilidad y max de RP
    if (isP && rInp) {
        const rfalt    = document.getElementById(`rfalt-c${comp}-p${per}-m${mat}`);
        const rfaltVal = document.getElementById(`rfalt-val-c${comp}-p${per}-m${mat}`);
        const showR    = pv !== null && pv < UMBRAL;

        rInp.disabled = !showR;
        rInp.classList.toggle('r-rec', showR);
        if (rfalt)    rfalt.style.display = showR ? '' : 'none';
        if (rfaltVal) rfaltVal.textContent = Math.round(faltante);

        if (showR) { rInp.max = faltante; } else { rInp.value = ''; }

        const rcell = document.getElementById(`rcell-c${comp}-p${per}-m${mat}`);
        if (rcell) rcell.classList.toggle('locked', !showR);
    }

    // Recalcular PROM COMP localmente (avg de los 4 CF: P + RP)
    const cfs = [];
    for (let p = 1; p <= 4; p++) {
        const pI = document.querySelector(`.acad-inp.p-inp[data-mat="${mat}"][data-comp="${comp}"][data-per="${p}"]`);
        const rI = document.querySelector(`.acad-inp.r-inp[data-mat="${mat}"][data-comp="${comp}"][data-per="${p}"]`);
        const pVal = pI && pI.value !== '' ? parseFloat(pI.value) : null;
        const rVal = rI && rI.value !== '' ? parseFloat(rI.value) : null;
        if (pVal !== null) {
            const falt = Math.max(0, 100 - pVal);
            let cf = pVal;
            if (rVal !== null && pVal < UMBRAL) cf = Math.min(pVal + Math.min(rVal, falt), 100);
            cfs.push(Math.round(cf * 10) / 10);
        }
    }
    const promComp = cfs.length ? Math.round(cfs.reduce((a,b) => a+b, 0) / cfs.length * 10) / 10 : null;

    const promEl = document.getElementById(`prom-c${comp}-m${mat}`);
    if (promEl) {
        promEl.textContent = fmt(promComp);
        promEl.className   = 'prom-comp-badge ' + (promComp !== null ? (promComp >= UMBRAL ? 'avg-ok' : 'avg-mal') : 'avg-nd');
    }
}

/* ── Completivo local: CCF = 0.5×NF + 0.5×CC ─────── */
function recalcCompletivo(inp) {
    const mat   = inp.dataset.mat;
    const nfEl  = document.getElementById(`res-final-m${mat}`);
    const nf    = nfEl ? parseFloat(nfEl.textContent) : NaN;
    const cc    = inp.value !== '' ? parseFloat(inp.value) : NaN;

    const ccf   = (!isNaN(nf) && !isNaN(cc)) ? Math.round((0.5*nf + 0.5*cc)*10)/10 : null;
    const ccfEl = document.getElementById(`ccf-m${mat}`);
    if (ccfEl) {
        ccfEl.textContent = fmt(ccf);
        ccfEl.className   = ccf !== null ? (ccf >= UMBRAL ? 'avg-ok' : 'avg-mal') : 'avg-nd';
    }

    // Habilitar / bloquear CE según resultado del completivo
    const ceCell = document.getElementById(`ce-cell-m${mat}`);
    const ceInp  = ceCell?.querySelector('.ce-inp');
    const showCE = ccf !== null && ccf < UMBRAL;
    if (ceInp) {
        ceInp.disabled = !showCE;
        ceInp.classList.toggle('r-rec', showCE);
        if (!showCE) ceInp.value = '';
    }
    if (ceCell) ceCell.classList.toggle('locked', !showCE);
}

/* ── Extraordinario local: CEF = 0.3×NF + 0.7×CE ── */
function recalcExtraordinario(inp) {
    const mat   = inp.dataset.mat;
    const nfEl  = document.getElementById(`res-final-m${mat}`);
    const nf    = nfEl ? parseFloat(nfEl.textContent) : NaN;
    const ce    = inp.value !== '' ? parseFloat(inp.value) : NaN;

    const cef   = (!isNaN(nf) && !isNaN(ce)) ? Math.round((0.3*nf + 0.7*ce)*10)/10 : null;
    const ccfEl = document.getElementById(`ccf-m${mat}`);
    if (ccfEl) {
        ccfEl.textContent = fmt(cef);
        ccfEl.className   = cef !== null ? (cef >= UMBRAL ? 'avg-ok' : 'avg-mal') : 'avg-nd';
    }
}

/* ── Guardar celda vía AJAX ────────────────────────── */
async function guardarCelda(inp) {
    const mat   = inp.dataset.mat;
    const campo = inp.dataset.campo;
    const valor = inp.value.trim() !== '' ? parseFloat(inp.value) : null;

    // Validar rango
    if (valor !== null && (valor < 0 || valor > 100)) {
        inp.classList.add('inp-error');
        setTimeout(() => inp.classList.remove('inp-error'), 2500);
        return;
    }

    inp.classList.add('saving');
    setStatus('saving', '<i class="bi bi-arrow-repeat me-1"></i>Guardando…');

    try {
        const res = await fetch(CELDA_URL, {
            method : 'PATCH',
            headers: {
                'Content-Type' : 'application/json',
                'X-CSRF-TOKEN' : CSRF_TOKEN,
                'Accept'       : 'application/json',
            },
            body: JSON.stringify({ matricula_id: mat, campo, valor }),
        });

        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();

        if (json.ok) {
            inp.classList.remove('saving');
            inp.classList.add('saved');
            setTimeout(() => inp.classList.remove('saved'), 2000);
            actualizarDOM(mat, json.data);
            setStatus('saved', '<i class="bi bi-check-circle-fill me-1"></i>Guardado');
        } else {
            throw new Error(json.message ?? 'Error');
        }
    } catch (e) {
        inp.classList.remove('saving');
        inp.classList.add('inp-error');
        setTimeout(() => inp.classList.remove('inp-error'), 3000);
        setStatus('err', '<i class="bi bi-exclamation-triangle-fill me-1"></i>Error al guardar. Reintentando al salir de la celda.');
    }
}

/* ── Actualizar DOM con respuesta del servidor ──────── */
// d.avgs[c][p]  = FINAL = P + R
// d.bases[c][p] = nota base P (para recalcular faltante en la celda R)
function actualizarDOM(mat, d) {
    if (d.bases) {
        for (let c = 1; c <= 4; c++) {
            for (let p = 1; p <= 4; p++) {
                const pBase = d.bases?.[c]?.[p] ?? null;

                // Celda RP: actualizar faltante y estado cuando cambia P
                if (pBase !== null) {
                    const falt     = Math.max(0, Math.round((100 - pBase) * 10) / 10);
                    const rInp     = document.querySelector(`.acad-inp.r-inp[data-mat="${mat}"][data-comp="${c}"][data-per="${p}"]`);
                    const rfalt    = document.getElementById(`rfalt-c${c}-p${p}-m${mat}`);
                    const rfaltVal = document.getElementById(`rfalt-val-c${c}-p${p}-m${mat}`);
                    const showR    = pBase < UMBRAL;

                    if (rInp) {
                        rInp.disabled = !showR;
                        rInp.classList.toggle('r-rec', showR);
                        rInp.max = falt;
                        if (!showR) rInp.value = '';
                    }
                    const rcell = document.getElementById(`rcell-c${c}-p${p}-m${mat}`);
                    if (rcell) rcell.classList.toggle('locked', !showR);
                    if (rfalt)    rfalt.style.display = showR ? '' : 'none';
                    if (rfaltVal) rfaltVal.textContent = Math.round(falt);
                }
            }

            // Promedio competencia
            const pc = d['prom_comp' + c] ?? null;
            const pcEl = document.getElementById(`prom-c${c}-m${mat}`);
            if (pcEl) {
                pcEl.textContent = fmt(pc);
                pcEl.className   = 'prom-comp-badge ' + (pc !== null ? (pc >= UMBRAL ? 'avg-ok' : 'avg-mal') : 'avg-nd');
            }
        }
    }

    // Nota final y situación
    const nf    = d.nota_final ?? null;
    const sit   = d.situacion  ?? null;
    const finEl = document.getElementById(`res-final-m${mat}`);
    if (finEl) {
        finEl.textContent = fmt(nf);
        finEl.className   = 'final-comp-badge ' + (nf !== null ? (nf >= UMBRAL ? 'fn-ok' : 'fn-mal') : 'fn-nd');
    }
    const sitEl = document.getElementById(`res-sit-m${mat}`);
    if (sitEl) {
        sitEl.textContent = sit === 'A' ? 'AP' : (sit === 'R' ? 'REP' : '—');
        sitEl.className   = 'sit-comp ' + (sit === 'A' ? 'sit-ap' : (sit === 'R' ? 'sit-rp' : 'sit-nd'));
    }

    // CC / CE / CCF — habilitar/bloquear y actualizar CCF badge
    const nc  = d.nota_cc             ?? null;
    const ne  = d.nota_ce             ?? null;
    const ncv = d.nota_completiva     ?? null;
    const nev = d.nota_extraordinaria ?? null;

    const showCC = nf !== null && nf < UMBRAL;
    const showCE = ncv !== null && ncv < UMBRAL;
    const ccf    = nev ?? ncv ?? null;

    // Celda CC
    const ccCell = document.getElementById(`cc-cell-m${mat}`);
    const ccInp  = ccCell?.querySelector('.cc-inp');
    if (ccInp) {
        ccInp.disabled = !showCC;
        ccInp.classList.toggle('r-rec', showCC);
        if (!showCC) ccInp.value = '';
        else if (nc !== null && ccInp.value === '') ccInp.value = nc;
    }
    if (ccCell) ccCell.classList.toggle('locked', !showCC);

    // Celda CE
    const ceCell = document.getElementById(`ce-cell-m${mat}`);
    const ceInp  = ceCell?.querySelector('.ce-inp');
    if (ceInp) {
        ceInp.disabled = !showCE;
        ceInp.classList.toggle('r-rec', showCE);
        if (!showCE) ceInp.value = '';
        else if (ne !== null && ceInp.value === '') ceInp.value = ne;
    }
    if (ceCell) ceCell.classList.toggle('locked', !showCE);

    // Badge CCF
    const ccfEl = document.getElementById(`ccf-m${mat}`);
    if (ccfEl) {
        ccfEl.textContent = fmt(ccf);
        ccfEl.className   = ccf !== null ? (ccf >= UMBRAL ? 'avg-ok' : 'avg-mal') : 'avg-nd';
    }

    renderFinal(mat, nf);
}

/* ── Navegación por teclado (Enter = siguiente celda) ── */
document.addEventListener('DOMContentLoaded', () => {
    const allInps = () => Array.from(document.querySelectorAll('.acad-inp:not([disabled])'));
    document.addEventListener('keydown', e => {
        if (!e.target.classList.contains('acad-inp')) return;
        if (e.key === 'Enter') {
            e.preventDefault();
            const inps = allInps();
            const idx  = inps.indexOf(e.target);
            if (idx >= 0 && idx + 1 < inps.length) inps[idx + 1].focus();
        }
    });
});
@endif

/* ─────────────────────────────────────────────────────
   TÉCNICA: recuperación (código original, sin cambios)
───────────────────────────────────────────────────── */
// recalcPeriodo() ya no se usa para académica, se mantiene por compatibilidad
function recalcPeriodo(input) {}   // stub — académica usa AJAX ahora

function __legacyRecalcFinalAcad(row) {
    const pks = ['p1', 'p2', 'p3', 'p4'];
    let sum = 0, cnt = 0;
    pks.forEach(pk => {
        const wrap = document.querySelector(`.per-cell-wrap[data-row="${row}"][data-pk="${pk}"]`);
        if (!wrap) return;
        const gradeInp = wrap.querySelector(`.per-inp[data-pk="${pk}"]`);
        if (!gradeInp || gradeInp.value === '') return;
        let acum = parseFloat(gradeInp.value);
        wrap.querySelectorAll(`.per-rec-block[data-pk="${pk}"]`).forEach(block => {
            if (block.style.display === 'none') return;
            const rv = parseFloat(block.querySelector('.rec-per-inp')?.value ?? '');
            if (!isNaN(rv)) acum = Math.min(acum + rv, 100);
        });
        sum += acum;
        cnt++;
    });

    renderFinal(row, cnt > 0 ? Math.round(sum / cnt) : null);
}

/* ── Técnica: criterios por RA y recuperación estructurada ──────────── */
let PUNTOS_RA = @json($pesosRA);   // { "1": 10, "2": 20, "3": 20, "4": 10, ... } — puntos máx por RA

/* Recalcula nota del RA desde criterios (T.P./EX./C.C./O.H./P.D./E.C.) */
function recalcularCriterios(input) {
    const row  = input.dataset.row;
    const ra   = input.dataset.ra;
    const pMax = parseFloat(input.dataset.ptoMax) || (PUNTOS_RA[ra] ?? 10);

    const crits = document.querySelectorAll(`.crit-inp[data-row="${row}"][data-ra="${ra}"]`);
    let sumaCrit = 0, hayCrit = false;
    crits.forEach(c => {
        const v = parseFloat(c.value);
        if (!isNaN(v) && c.value !== '') {
            const cmax = parseFloat(c.dataset.cmax) || 100;
            sumaCrit += Math.min(v, cmax);
            hayCrit = true;
        }
    });
    if (hayCrit) {
        // Nota RA = (suma_criterios / 100) × pMax
        const notaRA = Math.round((sumaCrit / 100) * pMax * 100) / 100;
        const raInp  = document.querySelector(`.ra-inp[data-row="${row}"][data-ra="${ra}"]`);
        if (raInp) {
            raInp.value = notaRA % 1 === 0 ? notaRA : notaRA.toFixed(1);
            recalcularRA(raInp);
        }
    }
}

/* Recalcula la CF de recuperación: 50% nota_acum + 50% (P+E+PE) */
function recalcularRecuperacion(input) {
    const row  = input.dataset.row;
    const ra   = input.dataset.ra;
    const pMax = parseFloat(input.dataset.ptoMax) || (PUNTOS_RA[ra] ?? 10);

    const practica     = parseFloat(document.querySelector(`.rec-struct-inp[data-row="${row}"][data-ra="${ra}"][data-reckey="practica"]`)?.value)     || 0;
    const exposicion   = parseFloat(document.querySelector(`.rec-struct-inp[data-row="${row}"][data-ra="${ra}"][data-reckey="exposicion"]`)?.value)   || 0;
    const practicaEval = parseFloat(document.querySelector(`.rec-struct-inp[data-row="${row}"][data-ra="${ra}"][data-reckey="practica_eval"]`)?.value) || 0;

    const notaRec  = practica + exposicion + practicaEval; // sobre 100

    // nota acumulada: valor actual del input ra_x, escalado a 0-100
    const raInp   = document.querySelector(`.ra-inp[data-row="${row}"][data-ra="${ra}"]`);
    const raVal   = raInp ? (parseFloat(raInp.value) || 0) : 0;
    const notaAcum = pMax > 0 ? Math.round(raVal / pMax * 100 * 100) / 100 : 0;

    // CF = 50% acumulada + 50% nueva
    const cf = Math.round((0.5 * notaAcum + 0.5 * notaRec) * 100) / 100;
    const cfEscalada = Math.round((cf / 100) * pMax * 100) / 100;

    const badge = document.getElementById(`rec-cf-${row}-${ra}`);
    if (badge) {
        badge.textContent = `CF: ${Math.round(cf)}`;
        badge.style.display = '';
        badge.className = 'rec-cf-badge ' + (cf >= 70 ? 'per-aprobado' : 'per-reprobado');
    }

    // Actualizar nota efectiva del RA si la recuperación mejora
    if (raInp && cfEscalada > raVal) {
        raInp.classList.remove('aprobado','reprobado');
        raInp.classList.add(cfEscalada >= pMax * 0.7 ? 'aprobado' : 'reprobado');
    }
    // Recalcular nota final
    recalcularRA(raInp ?? input);
}

function recalcularRA(input) {
    const row    = input.dataset.row;
    const raInps = document.querySelectorAll(`.ra-inp[data-row="${row}"]`);
    let total = 0, hayNota = false;

    raInps.forEach(mainInp => {
        const raNum  = parseInt(mainInp.dataset.ra);
        const ptoMax = parseFloat(mainInp.dataset.ptoMax) || (PUNTOS_RA[raNum] ?? (100 / raInps.length));
        const umbral = ptoMax * 0.7;

        const mv = parseFloat(mainInp.value);
        mainInp.classList.remove('aprobado','reprobado');

        if (!isNaN(mv) && mainInp.value !== '') {
            // Verificar si hay recuperación que mejore
            const practica     = parseFloat(document.querySelector(`.rec-struct-inp[data-row="${row}"][data-ra="${raNum}"][data-reckey="practica"]`)?.value)     || 0;
            const exposicion   = parseFloat(document.querySelector(`.rec-struct-inp[data-row="${row}"][data-ra="${raNum}"][data-reckey="exposicion"]`)?.value)   || 0;
            const practicaEval = parseFloat(document.querySelector(`.rec-struct-inp[data-row="${row}"][data-ra="${raNum}"][data-reckey="practica_eval"]`)?.value) || 0;
            const hayRec = practica > 0 || exposicion > 0 || practicaEval > 0;

            let efectiva = mv;
            if (hayRec) {
                const notaRec  = practica + exposicion + practicaEval;
                const notaAcum = ptoMax > 0 ? mv / ptoMax * 100 : 0;
                const cf       = 0.5 * notaAcum + 0.5 * notaRec;
                const cfEsc    = cf / 100 * ptoMax;
                if (cfEsc > mv) efectiva = Math.min(cfEsc, ptoMax);

                // Mostrar sección de recuperación si reprueba
                const recStruct = document.getElementById(`rec-struct-${row}-${raNum}`);
                if (recStruct) recStruct.style.display = '';
            }

            mainInp.classList.add(efectiva >= umbral ? 'aprobado' : 'reprobado');

            // Mostrar sección de recuperación si reprueba y no la tiene abierta
            if (efectiva < umbral) {
                const recStruct = document.getElementById(`rec-struct-${row}-${raNum}`);
                if (recStruct) recStruct.style.display = '';
            }

            total   += efectiva;
            hayNota  = true;
        }
    });

    renderFinal(row, hayNota ? Math.round(total * 10) / 10 : null);
}

function toggleOffline() {
    const p = document.getElementById('offline-panel');
    const c = document.getElementById('offline-chevron');
    const open = p.style.display === 'block';
    p.style.display = open ? 'none' : 'block';
    c.style.transform = open ? '' : 'rotate(180deg)';
    if (!open) { sessionStorage.setItem('offline_cal_open','1'); }
    else { sessionStorage.removeItem('offline_cal_open'); }
}
// Auto-open if previous session had it open or if there are import errors
if (sessionStorage.getItem('offline_cal_open') || {{ session('errores_import') ? 'true' : 'false' }}) {
    document.addEventListener('DOMContentLoaded', () => toggleOffline());
}

/* ── Pesos RA editables ──────────────────────────────────────────────── */
@if($esTecnica && $numRA > 0)
(function () {
    const NUM_RA   = {{ $numRA }};
    const ROUTE    = "{{ route('portal.docente.pesos-ra.guardar', $asignacion) }}";
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function getPesosInputs() {
        return Array.from(document.querySelectorAll('.pesos-ra-inp'));
    }
    function calcTotal() {
        return getPesosInputs().reduce((s, i) => s + (parseFloat(i.value) || 0), 0);
    }
    function actualizarBarra() {
        const total = calcTotal();
        getPesosInputs().forEach(inp => {
            const n   = inp.dataset.n;
            const bar = document.getElementById('pesos-bar-' + n);
            if (!bar) return;
            const p = parseFloat(inp.value) || 0;
            bar.style.width = (total > 0 ? (p / total * 100) : 0) + '%';
        });
        const badge = document.getElementById('pesos-total-badge');
        const t = Math.round(calcTotal());
        badge.textContent = 'Total: ' + t + ' pts';
        badge.style.background = t === 100 ? '#dcfce7' : '#fee2e2';
        badge.style.color       = t === 100 ? '#15803d' : '#dc2626';
    }

    document.getElementById('pesos-inputs-wrap')?.addEventListener('input', function(e) {
        if (e.target.classList.contains('pesos-ra-inp')) {
            actualizarBarra();
            // actualizar PESOS_RA en tiempo real para que recalcularRA use los nuevos pesos
            const n = parseInt(e.target.dataset.n);
            PUNTOS_RA[n] = parseFloat(e.target.value) || 0;
            document.querySelectorAll('.ra-inp').forEach(inp => recalcularRA(inp));
        }
    });

    document.getElementById('btn-distribuir-pesos')?.addEventListener('click', function() {
        const base    = Math.floor(100 / NUM_RA);
        const residuo = 100 - base * NUM_RA;
        getPesosInputs().forEach((inp, idx) => {
            inp.value = idx === NUM_RA - 1 ? base + residuo : base;
            PUNTOS_RA[parseInt(inp.dataset.n)] = parseFloat(inp.value);
        });
        actualizarBarra();
        document.querySelectorAll('.ra-inp').forEach(inp => recalcularRA(inp));
    });

    document.getElementById('btn-guardar-pesos')?.addEventListener('click', function() {
        const msg   = document.getElementById('pesos-msg');
        const total = calcTotal();
        if (Math.abs(total - 100) > 0.5) {
            msg.innerHTML = '<span style="color:#dc2626;"><i class="bi bi-exclamation-triangle me-1"></i>Los puntos deben sumar 100 (actual: ' + Math.round(total*100)/100 + ').</span>';
            return;
        }
        const pesos = {};
        getPesosInputs().forEach(inp => { pesos[inp.dataset.n] = parseFloat(inp.value) || 0; });

        msg.innerHTML = '<span style="color:#64748b;"><i class="bi bi-arrow-repeat me-1"></i>Guardando…</span>';
        this.disabled = true;
        fetch(ROUTE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ pesos }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                msg.innerHTML = '<span style="color:#15803d;"><i class="bi bi-check-circle me-1"></i>Pesos guardados correctamente.</span>';
                // marcar como personalizado
                const badge = document.querySelector('#ra-pesos-panel span[style*="background:#e2e8f0"]');
                if (badge) { badge.textContent = 'Personalizado'; badge.style.background = '#7c3aed'; badge.style.color = '#fff'; }
                setTimeout(() => msg.innerHTML = '', 3500);
            } else {
                msg.innerHTML = '<span style="color:#dc2626;"><i class="bi bi-exclamation-triangle me-1"></i>' + (data.error ?? 'Error.') + '</span>';
            }
        })
        .catch(() => {
            msg.innerHTML = '<span style="color:#dc2626;"><i class="bi bi-exclamation-triangle me-1"></i>Error de conexión.</span>';
        })
        .finally(() => { this.disabled = false; });
    });

    actualizarBarra();
})();
@endif

// Init rec-usada class on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.rec-inp').forEach(ri => {
        if (ri.value !== '') ri.classList.add('rec-usada');
    });
});

function renderFinal(row, valor) {
    const cell = document.getElementById(`final-${row}`);
    if (!cell) return;
    if (valor !== null) {
        const cls = valor >= UMBRAL ? 'final-aprobado' : 'final-reprobado';
        cell.innerHTML = `<span class="final-badge ${cls}">${valor}<span style="font-size:.6rem;font-weight:500;opacity:.7;">/100</span></span>`;
    } else {
        cell.innerHTML = `<span class="final-badge final-vacio">—</span>`;
    }
}
</script>
@endpush
