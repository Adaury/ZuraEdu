@extends('layouts.portal')
@section('page-title', 'Registro MINERD · ' . $asignacion->asignatura->nombre)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'registro-minerd', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.registro-minerd', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-table"></i>MINERD
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
@endsection

@push('styles')
<style>
:root {
    --c1-bg:#fee2e2; --c1-txt:#991b1b; --c1-border:#fca5a5;
    --c2-bg:#fef9c3; --c2-txt:#854d0e; --c2-border:#fde047;
    --c3-bg:#dbeafe; --c3-txt:#1e40af; --c3-border:#93c5fd;
    --c4-bg:#dcfce7; --c4-txt:#15803d; --c4-border:#86efac;
}

/* ── Info header ── */
.minerd-header { background:linear-gradient(135deg,#1e3a6e,#4f46e5);
    color:#fff; border-radius:14px; padding:1.1rem 1.5rem; margin-bottom:1rem; }
.mh-label { font-size:.62rem; opacity:.65; font-weight:700; text-transform:uppercase; letter-spacing:.07em; }
.mh-val   { font-weight:800; font-size:.95rem; }
.mh-sep   { width:1px; background:rgba(255,255,255,.2); }

/* ── Leyenda ── */
.leyenda-bar { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center;
    padding:.55rem 1rem; background:#f8fafc; border-radius:10px;
    border:1px solid #e5e7eb; margin-bottom:.85rem; font-size:.75rem; }
.leg-item { display:inline-flex; align-items:center; gap:.35rem; font-weight:600; }
.badge-val { display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border-radius:7px; font-weight:800; font-size:.82rem;
    border:1.5px solid transparent; }
.badge-val.v1 { background:var(--c1-bg); color:var(--c1-txt); border-color:var(--c1-border); }
.badge-val.v2 { background:var(--c2-bg); color:var(--c2-txt); border-color:var(--c2-border); }
.badge-val.v3 { background:var(--c3-bg); color:var(--c3-txt); border-color:var(--c3-border); }
.badge-val.v4 { background:var(--c4-bg); color:var(--c4-txt); border-color:var(--c4-border); }
.badge-val.empty { background:#f1f5f9; color:#cbd5e1; border-color:#e2e8f0; }

/* ── Toolbar ── */
.reg-toolbar { background:#fff; border:1px solid #e5e7eb; border-radius:12px;
    padding:.75rem 1rem; display:flex; align-items:center;
    gap:.6rem; flex-wrap:wrap; margin-bottom:.85rem; }
.per-tab { border:1.5px solid #d1d5db; border-radius:8px; padding:.28rem .8rem;
    font-size:.78rem; font-weight:700; cursor:pointer; background:#fff;
    color:#374151; text-decoration:none; transition:.14s; }
.per-tab:hover  { border-color:var(--primary); color:var(--primary); }
.per-tab.active { background:var(--primary); color:#fff; border-color:var(--primary); }
.per-tab.cerrado{ opacity:.55; cursor:not-allowed; }

/* ── Tabla ── */
.reg-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch;
    border-radius:12px; border:1.5px solid #d1d5db; background:#fff;
    box-shadow:0 2px 12px rgba(0,0,0,.06); }
.reg-tbl { border-collapse:collapse; font-size:.72rem; min-width:100%; }
.reg-tbl th, .reg-tbl td { border:1px solid #e2e8f0; white-space:nowrap; }

.th-info  { background:#1e3a6e; color:#fff; font-weight:800; text-align:center; padding:.4rem .55rem; font-size:.76rem; }
.th-ce    { background:#2d5aa0; color:#fff; font-size:.68rem; font-weight:700; text-align:center; padding:.3rem .45rem; }
.th-il    { background:#e8edf8; color:#1e3a6e; font-size:.65rem; font-weight:700; text-align:center; padding:.25rem .4rem; }
.th-prom  { background:#f0fdf4; color:#15803d; font-size:.65rem; font-weight:700; text-align:center; padding:.25rem .4rem; }
.th-total { background:#111827; color:#fff; font-size:.7rem; font-weight:800; text-align:center; padding:.3rem .45rem; }
.th-per   { background:#fef3c7; color:#92400e; font-size:.62rem; font-weight:700; text-align:center; padding:.2rem; }

.td-num  { background:#f8fafc; color:#374151; font-weight:700; text-align:center;
    padding:.25rem .4rem; min-width:34px; position:sticky; left:0; z-index:2; }
.td-nombre { background:#fff; font-weight:600; color:#111827; padding:.3rem .7rem;
    min-width:190px; position:sticky; left:34px; z-index:2;
    border-right:2px solid #d1d5db !important; }
.td-nombre small { font-size:.62rem; color:#2563eb; font-weight:700; font-family:monospace; }
.td-cell { text-align:center; padding:0; width:50px; min-width:50px; cursor:pointer;
    transition:filter .1s; vertical-align:middle; position:relative; }
.td-cell:hover  { filter:brightness(.93); }
.td-cell.cerrado { cursor:not-allowed; opacity:.7; }
.td-cell.editando { outline:3px solid #f59e0b; outline-offset:-2px; z-index:5; }
.td-prom { text-align:center; font-weight:700; font-size:.74rem; padding:.25rem .4rem; background:#f0fdf4; }
.td-total { text-align:center; font-weight:800; font-size:.76rem; padding:.25rem .4rem;
    min-width:56px; background:#f0fdf4; border-left:2px solid #86efac !important; }
.tr-grupo td { background:#f1f5f9; font-weight:800; font-size:.73rem; color:#374151;
    padding:.3rem .55rem; border-top:2px solid #475569; }
.reg-row.hidden { display:none; }

/* ── Sticky cabecera ── */
.reg-tbl thead th.th-info:first-child  { position:sticky; left:0; z-index:12; }
.reg-tbl thead th.th-info:nth-child(2) { position:sticky; left:34px; z-index:12; }

/* ── Editor flotante (escala 1-4) ── */
.cell-popup { position:fixed; z-index:1000; display:none; }
.cell-popup .pop-inner { background:#fff; border:1.5px solid #e5e7eb; border-radius:12px;
    padding:.6rem .75rem; box-shadow:0 8px 32px rgba(0,0,0,.15); }
.pop-label { font-size:.65rem; font-weight:700; color:#6b7280; margin-bottom:.4rem; text-align:center; }
.choices-row { display:flex; gap:.3rem; }
.choice-btn { width:38px; height:38px; border-radius:9px; border:2px solid transparent;
    font-weight:800; font-size:.95rem; cursor:pointer; transition:.12s;
    display:flex; align-items:center; justify-content:center; }
.choice-btn:hover, .choice-btn.selected { transform:scale(1.1); border-color:#111; }
.choice-btn.c1 { background:var(--c1-bg); color:var(--c1-txt); }
.choice-btn.c2 { background:var(--c2-bg); color:var(--c2-txt); }
.choice-btn.c3 { background:var(--c3-bg); color:var(--c3-txt); }
.choice-btn.c4 { background:var(--c4-bg); color:var(--c4-txt); }
.choice-btn.del { background:#f1f5f9; color:#94a3b8; font-size:.75rem; }

/* ── Editor numérico (segundo ciclo) ── */
.num-popup { position:fixed; z-index:1000; display:none; }
.num-popup .pop-inner { background:#fff; border:1.5px solid #e5e7eb; border-radius:12px;
    padding:.75rem; box-shadow:0 8px 32px rgba(0,0,0,.15); min-width:160px; }
.num-input { width:100%; border:1.5px solid #d1d5db; border-radius:8px; padding:.4rem .6rem;
    font-size:1rem; font-weight:700; text-align:center; -moz-appearance:textfield; }
.num-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.num-input::-webkit-inner-spin-button { -webkit-appearance:none; }

/* ── Botones toolbar ── */
.btn-reg { display:inline-flex; align-items:center; gap:.4rem; border-radius:9px;
    padding:.38rem .85rem; font-size:.78rem; font-weight:700; cursor:pointer;
    transition:.14s; border:1.5px solid transparent; text-decoration:none; }
.btn-pdf { background:#fff; color:#dc2626; border-color:#fca5a5; }
.btn-pdf:hover { background:#fef2f2; }
.btn-save { background:#059669; color:#fff; border-color:#059669; }
.btn-save:hover { background:#047857; }

/* ── Toast ── */
.reg-toast { position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
    background:#111827; color:#fff; border-radius:10px; padding:.65rem 1.25rem;
    font-size:.82rem; font-weight:600; opacity:0; transform:translateY(8px);
    transition:all .22s; pointer-events:none; display:flex; align-items:center; gap:.5rem; }
.reg-toast.show { opacity:1; transform:translateY(0); }
.reg-toast.ok  { background:#059669; }
.reg-toast.err { background:#dc2626; }
.reg-toast.info { background:#2563eb; }

/* Vacío de CE */
.empty-ces { background:#fffbeb; border:1.5px dashed #fcd34d; border-radius:12px;
    padding:2rem; text-align:center; color:#92400e; }

[data-theme="dark"] .reg-wrap { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .reg-tbl th, [data-theme="dark"] .reg-tbl td { border-color:#334155; }
[data-theme="dark"] .td-nombre { background:#1e293b; color:#e2e8f0; border-right-color:#334155 !important; }
[data-theme="dark"] .td-num { background:#162032; }
[data-theme="dark"] .reg-toolbar { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .leyenda-bar { background:#1e293b; border-color:#334155; }
[data-theme="dark"] .cell-popup .pop-inner, [data-theme="dark"] .num-popup .pop-inner
    { background:#1e293b; border-color:#334155; }
</style>
@endpush

@section('content')

@php
    $grupo  = $asignacion->grupo;
    $cerrado = $periodoActivo?->cerrado ?? false;

    $calcPromedioEst = function(int $mId) use ($ces, $periodos, $evalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    foreach ($evalMap[$mId]["il_{$il->id}"] ?? [] as $v) {
                        if ($v !== null) $vals[] = (float)$v;
                    }
                }
            } else {
                foreach ($evalMap[$mId]["ce_{$ce->id}"] ?? [] as $v) {
                    if ($v !== null) $vals[] = (float)$v;
                }
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };
@endphp

{{-- ── Info header ──────────────────────────────────────────────────── --}}
<div class="minerd-header d-flex flex-wrap gap-3 align-items-center">
    <div>
        <div class="mh-label">Materia</div>
        <div class="mh-val">{{ $asignacion->asignatura->nombre }}</div>
    </div>
    <div class="mh-sep" style="height:36px;"></div>
    <div>
        <div class="mh-label">Grupo</div>
        <div class="mh-val">{{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }}</div>
    </div>
    <div class="mh-sep" style="height:36px;"></div>
    <div>
        <div class="mh-label">Ciclo</div>
        <div class="mh-val">{{ $ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo' }}</div>
    </div>
    <div class="mh-sep" style="height:36px;"></div>
    <div>
        <div class="mh-label">Estudiantes</div>
        <div class="mh-val">{{ $matriculas->count() }}</div>
    </div>
    @if($cerrado)
    <span class="ms-auto badge" style="background:rgba(255,255,255,.2);font-size:.78rem;">
        <i class="bi bi-lock-fill me-1"></i>Período cerrado
    </span>
    @endif
</div>

@if($ces->isEmpty())
{{-- ── Sin CE configuradas ─────────────────────────────────────────── --}}
<div class="empty-ces">
    <i class="bi bi-exclamation-triangle-fill d-block mb-2" style="font-size:2rem;"></i>
    <strong>No hay Competencias Específicas configuradas</strong> para
    <em>{{ $asignacion->asignatura->nombre }}</em> ({{ $ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo' }}).
    <div class="mt-2" style="font-size:.83rem;">
        Solicita al coordinador que configure las CE/IL en
        <strong>Admin → Competencias / IL</strong>.
    </div>
</div>
@else

{{-- ── Leyenda ─────────────────────────────────────────────────────── --}}
@if($ciclo === 'primer_ciclo')
<div class="leyenda-bar">
    <span style="font-weight:700;color:#374151;font-size:.73rem;">Escala MINERD:</span>
    <span class="leg-item"><span class="badge-val v1">1</span>Inicial</span>
    <span class="leg-item"><span class="badge-val v2">2</span>En proceso</span>
    <span class="leg-item"><span class="badge-val v3">3</span>Logrado</span>
    <span class="leg-item"><span class="badge-val v4">4</span>Avanzado</span>
    <span class="ms-auto text-muted" style="font-size:.7rem;">
        <i class="bi bi-mouse2 me-1"></i>Clic para editar &nbsp;·&nbsp;
        <kbd style="font-size:.65rem;">1-4</kbd> directo &nbsp;·&nbsp;
        <kbd style="font-size:.65rem;">Tab</kbd> siguiente
    </span>
</div>
@else
<div class="leyenda-bar">
    <span style="font-weight:700;color:#374151;font-size:.73rem;">Escala numérica:</span>
    <span style="background:#d1fae5;color:#065f46;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:700;">≥90 Excelente</span>
    <span style="background:#dcfce7;color:#15803d;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:700;">65–89 Bueno</span>
    <span style="background:#fef9c3;color:#854d0e;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:700;">50–64 Regular</span>
    <span style="background:#fee2e2;color:#991b1b;padding:.1rem .45px;border-radius:4px;font-size:.72rem;font-weight:700;">&lt;50 Insuficiente</span>
    <span class="ms-auto text-muted" style="font-size:.7rem;">
        <i class="bi bi-mouse2 me-1"></i>Clic para editar &nbsp;·&nbsp; Rango 0–100
    </span>
</div>
@endif

{{-- ── Toolbar ─────────────────────────────────────────────────────── --}}
<div class="reg-toolbar">
    {{-- Tabs de período --}}
    @foreach($periodos as $p)
        <a href="{{ route('portal.docente.registro-minerd', [$asignacion, 'periodo_id' => $p->id]) }}"
           class="per-tab {{ $periodoActivo && $p->id === $periodoActivo->id ? 'active' : '' }} {{ $p->cerrado ? 'cerrado' : '' }}">
            P{{ $p->numero }}
            @if($p->cerrado)<i class="bi bi-lock-fill" style="font-size:.55rem;margin-left:.2rem;"></i>@endif
        </a>
    @endforeach

    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="{{ route('portal.docente.registro-minerd.pdf', [$asignacion, 'periodo_id' => $periodoActivo?->id]) }}"
           class="btn-reg btn-pdf" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        @if(!$cerrado)
        <button class="btn-reg btn-save" onclick="guardarTodo()">
            <i class="bi bi-cloud-check"></i>Guardar todo
        </button>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     TABLA PRINCIPAL
═══════════════════════════════════════════════════════════════ --}}
<div class="reg-wrap">
<table class="reg-tbl" id="tablaReg">
<thead>

{{-- Fila 1: # | Nombre | CE... (colspan ILs + 1) | PROM GRAL --}}
<tr>
    <th class="th-info" rowspan="3" style="min-width:34px;">#</th>
    <th class="th-info" rowspan="3"
        style="min-width:190px;position:sticky;left:34px;z-index:13;text-align:left;padding-left:.85rem;">
        Apellidos, Nombres
    </th>
    @foreach($ces as $ce)
        @php $ils = $ce->indicadoresActivos ?? collect();
             $ceColspan = $ils->isNotEmpty() ? $ils->count() + 1 : 2; @endphp
        <th class="th-ce" colspan="{{ $ceColspan }}">
            {{ $ce->codigo ? $ce->codigo.': ' : '' }}{{ \Illuminate\Support\Str::limit($ce->nombre, 40) }}
        </th>
    @endforeach
    <th class="th-total" rowspan="3" style="min-width:60px;">PROM<br>GRAL</th>
</tr>

{{-- Fila 2: IL labels --}}
<tr>
    @foreach($ces as $ce)
        @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
        @if($ils->isNotEmpty())
            @foreach($ils as $il)
                <th class="th-il" style="width:50px;" title="{{ $il->descripcion }}">
                    {{ $il->codigo ?: 'IL'.$loop->iteration }}
                </th>
            @endforeach
        @else
            <th class="th-il" style="width:50px;">{{ $ce->codigo ?: 'CE' }}</th>
        @endif
        <th class="th-prom" style="width:50px;">PROM<br>CE</th>
    @endforeach
</tr>

{{-- Fila 3: Período activo --}}
<tr>
    @foreach($ces as $ce)
        @php $ils = $ce->indicadoresActivos ?? collect(); $cols = $ils->isNotEmpty() ? $ils->count() : 1; @endphp
        @for($i = 0; $i < $cols; $i++)
            <th class="th-per">P{{ $periodoActivo?->numero ?? '?' }}</th>
        @endfor
        <th style="background:#f0fdf4;color:#166534;font-size:.62rem;font-weight:600;
                   text-align:center;padding:.2rem;border:1px solid #e5e7eb;">Todos</th>
    @endforeach
</tr>
</thead>

<tbody id="tbodyReg">
@foreach($matriculas as $idx => $m)
    @php
        $promEst   = $calcPromedioEst($m->id);
        $promColor = $promEst !== null
            ? ($ciclo === 'primer_ciclo'
                ? ($promEst>=3.5?'var(--c4-bg)':($promEst>=2.5?'var(--c3-bg)':($promEst>=1.5?'var(--c2-bg)':'var(--c1-bg)')))
                : ($promEst>=90?'#d1fae5':($promEst>=65?'#dcfce7':($promEst>=50?'#fef9c3':'#fee2e2'))))
            : '#f8fafc';
        $promTxt = $promEst !== null
            ? ($ciclo === 'primer_ciclo'
                ? ($promEst>=3.5?'var(--c4-txt)':($promEst>=2.5?'var(--c3-txt)':($promEst>=1.5?'var(--c2-txt)':'var(--c1-txt)')))
                : ($promEst>=90?'#065f46':($promEst>=65?'#15803d':($promEst>=50?'#854d0e':'#991b1b'))))
            : '#94a3b8';
    @endphp
    <tr class="reg-row" data-nombre="{{ strtolower(($m->estudiante?->apellidos ?? '').' '.($m->estudiante?->nombres ?? '')) }}">
        <td class="td-num">{{ $idx + 1 }}</td>
        <td class="td-nombre">
            <div>{{ $m->estudiante?->apellidos ?? '—' }}, {{ $m->estudiante?->nombres ?? '' }}</div>
            <small>{{ $m->estudiante?->numero_matricula }}</small>
        </td>

        @foreach($ces as $ceIdx => $ce)
            @php
                $ils        = $ce->indicadoresActivos ?? collect();
                $valsParaCe = [];
            @endphp

            @if($ils->isNotEmpty())
                @foreach($ils as $il)
                    @php
                        $refKey  = "il_{$il->id}";
                        $val     = $evalMap[$m->id][$refKey][$periodoActivo?->id] ?? null;
                        $allVals = array_filter($evalMap[$m->id][$refKey] ?? [], fn($v) => $v !== null);
                        $promIl  = count($allVals) ? round(array_sum($allVals)/count($allVals), 2) : null;
                        if ($promIl !== null) $valsParaCe[] = $promIl;

                        // Color de celda según ciclo
                        if ($ciclo === 'primer_ciclo') {
                            $bgVal = $val !== null ? "var(--c{$val}-bg)" : '#fff';
                        } else {
                            $bgVal = $val !== null
                                ? ($val >= 90 ? '#d1fae5' : ($val >= 65 ? '#dcfce7' : ($val >= 50 ? '#fef9c3' : '#fee2e2')))
                                : '#fff';
                        }
                    @endphp
                    <td class="td-cell {{ $cerrado ? 'cerrado' : '' }}"
                        style="background:{{ $bgVal }};"
                        data-matricula="{{ $m->id }}"
                        data-periodo="{{ $periodoActivo?->id }}"
                        data-schoolyear="{{ $schoolYear->id }}"
                        data-tipo="indicador"
                        data-ref="{{ $il->id }}"
                        data-refkey="{{ $refKey }}"
                        data-val="{{ $val ?? '' }}"
                        @if(!$cerrado) onclick="abrirEditor(this)" tabindex="0" onkeydown="handleKey(event,this)" @endif>
                        @if($ciclo === 'primer_ciclo')
                            <span class="badge-val {{ $val ? 'v'.$val : 'empty' }}">{{ $val ?? '—' }}</span>
                        @else
                            <span class="badge-val" style="{{ $val !== null ? 'background:'.$bgVal.';color:'.($val >= 65 ? '#065f46' : '#991b1b').';border-color:transparent;' : 'background:#f1f5f9;color:#cbd5e1;' }}">
                                {{ $val !== null ? $val : '—' }}
                            </span>
                        @endif
                    </td>
                @endforeach
            @else
                @php
                    $refKey  = "ce_{$ce->id}";
                    $val     = $evalMap[$m->id][$refKey][$periodoActivo?->id] ?? null;
                    $allVals = array_filter($evalMap[$m->id][$refKey] ?? [], fn($v) => $v !== null);
                    $promCeD = count($allVals) ? round(array_sum($allVals)/count($allVals), 2) : null;
                    if ($promCeD !== null) $valsParaCe[] = $promCeD;
                    if ($ciclo === 'primer_ciclo') {
                        $bgVal = $val !== null ? "var(--c{$val}-bg)" : '#fff';
                    } else {
                        $bgVal = $val !== null
                            ? ($val >= 90 ? '#d1fae5' : ($val >= 65 ? '#dcfce7' : ($val >= 50 ? '#fef9c3' : '#fee2e2')))
                            : '#fff';
                    }
                @endphp
                <td class="td-cell {{ $cerrado ? 'cerrado' : '' }}"
                    style="background:{{ $bgVal }};"
                    data-matricula="{{ $m->id }}"
                    data-periodo="{{ $periodoActivo?->id }}"
                    data-schoolyear="{{ $schoolYear->id }}"
                    data-tipo="competencia"
                    data-ref="{{ $ce->id }}"
                    data-refkey="{{ $refKey }}"
                    data-val="{{ $val ?? '' }}"
                    @if(!$cerrado) onclick="abrirEditor(this)" tabindex="0" onkeydown="handleKey(event,this)" @endif>
                    @if($ciclo === 'primer_ciclo')
                        <span class="badge-val {{ $val ? 'v'.$val : 'empty' }}">{{ $val ?? '—' }}</span>
                    @else
                        <span class="badge-val" style="{{ $val !== null ? 'background:'.$bgVal.';color:'.($val >= 65 ? '#065f46' : '#991b1b').';border-color:transparent;' : 'background:#f1f5f9;color:#cbd5e1;' }}">
                            {{ $val !== null ? $val : '—' }}
                        </span>
                    @endif
                </td>
            @endif

            {{-- Promedio CE ─────────────────────────────── --}}
            @php
                $promCe = count($valsParaCe) ? round(array_sum($valsParaCe)/count($valsParaCe), 2) : null;
                if ($ciclo === 'primer_ciclo') {
                    $bgCe = $promCe !== null ? ($promCe>=3.5?'var(--c4-bg)':($promCe>=2.5?'var(--c3-bg)':($promCe>=1.5?'var(--c2-bg)':'var(--c1-bg)'))) : '#f0fdf4';
                    $txCe = $promCe !== null ? ($promCe>=3.5?'var(--c4-txt)':($promCe>=2.5?'var(--c3-txt)':($promCe>=1.5?'var(--c2-txt)':'var(--c1-txt)'))) : '#94a3b8';
                } else {
                    $bgCe = $promCe !== null ? ($promCe>=90?'#d1fae5':($promCe>=65?'#dcfce7':($promCe>=50?'#fef9c3':'#fee2e2'))) : '#f0fdf4';
                    $txCe = $promCe !== null ? ($promCe>=90?'#065f46':($promCe>=65?'#15803d':($promCe>=50?'#854d0e':'#991b1b'))) : '#94a3b8';
                }
            @endphp
            <td class="td-prom" style="background:{{ $bgCe }};color:{{ $txCe }};"
                data-prom-ce="{{ $ce->id }}-{{ $m->id }}">
                {{ $promCe !== null ? number_format($promCe, 1) : '—' }}
            </td>
        @endforeach

        {{-- Promedio general ─────────────────────────────── --}}
        <td class="td-total" style="background:{{ $promColor }};color:{{ $promTxt }};"
            data-prom-gen="{{ $m->id }}">
            {{ $promEst !== null ? number_format($promEst, 1) : '—' }}
        </td>
    </tr>
@endforeach

{{-- Fila promedio del grupo --}}
<tr class="tr-grupo">
    <td colspan="2">PROMEDIO DEL GRUPO</td>
    @foreach($ces as $ce)
        @php
            $ils  = $ce->indicadoresActivos ?? collect();
            $cols = $ils->isNotEmpty() ? $ils->count() : 1;
        @endphp
        @for($i = 0; $i < $cols; $i++) <td></td> @endfor
        @php
            $gpVals = $matriculas->map(function($m) use ($ce, $evalMap) {
                $ils = $ce->indicadoresActivos ?? collect();
                $v   = [];
                if ($ils->isNotEmpty()) {
                    foreach ($ils as $il) {
                        foreach ($evalMap[$m->id]["il_{$il->id}"] ?? [] as $vv) {
                            if ($vv !== null) $v[] = (float)$vv;
                        }
                    }
                } else {
                    foreach ($evalMap[$m->id]["ce_{$ce->id}"] ?? [] as $vv) {
                        if ($vv !== null) $v[] = (float)$vv;
                    }
                }
                return count($v) ? round(array_sum($v)/count($v), 2) : null;
            })->filter();
        @endphp
        <td style="background:#dcfce7;color:#15803d;font-weight:800;text-align:center;">
            {{ $gpVals->count() ? number_format($gpVals->avg(), 1) : '—' }}
        </td>
    @endforeach
    @php $gpGral = $matriculas->map(fn($m) => $calcPromedioEst($m->id))->filter(); @endphp
    <td class="td-total" style="background:#86efac;color:#14532d;font-weight:800;text-align:center;">
        {{ $gpGral->count() ? number_format($gpGral->avg(), 1) : '—' }}
    </td>
</tr>
</tbody>
</table>
</div>

@endif {{-- fin $ces->isEmpty() --}}

{{-- ═══════════════ EDITOR ESCALA 1-4 (primer ciclo) ═══════════════ --}}
<div class="cell-popup" id="cellPopup">
    <div class="pop-inner">
        <div class="pop-label" id="popLabel">Indicador — escala 1 a 4</div>
        <div class="choices-row">
            <button class="choice-btn c1" onclick="elegirValor(1)" title="1 — Inicial">1</button>
            <button class="choice-btn c2" onclick="elegirValor(2)" title="2 — En proceso">2</button>
            <button class="choice-btn c3" onclick="elegirValor(3)" title="3 — Logrado">3</button>
            <button class="choice-btn c4" onclick="elegirValor(4)" title="4 — Avanzado">4</button>
            <button class="choice-btn del" onclick="elegirValor(null)"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
</div>

{{-- ═══════════════ EDITOR NUMÉRICO (segundo ciclo) ═══════════════ --}}
<div class="num-popup" id="numPopup">
    <div class="pop-inner">
        <div class="pop-label">Nota (0–100)</div>
        <input type="number" id="numInput" class="num-input"
               min="0" max="100" step="0.5"
               placeholder="0 – 100"
               onkeydown="numInputKey(event)"
               oninput="this.value=Math.min(100,Math.max(0,this.value))">
        <div class="d-flex gap-2 mt-2">
            <button class="btn-reg btn-save w-100" onclick="confirmarNumerico()">
                <i class="bi bi-check-lg"></i>OK
            </button>
            <button class="btn-reg btn-pdf" onclick="elegirValor(null)" style="min-width:36px;padding:.38rem .5rem;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="reg-toast" id="regToast">
    <i class="bi" id="toastIcon"></i>
    <span id="toastMsg"></span>
</div>

@push('scripts')
<script>
const CSRF       = '{{ csrf_token() }}';
const URL_SAVE   = '{{ route('portal.docente.registro-minerd.guardar', $asignacion) }}';
const SY_ID      = {{ $schoolYear->id }};
const CICLO      = '{{ $ciclo }}';
const CERRADO    = {{ $cerrado ? 'true' : 'false' }};

let celdaActiva = null;
let toastTimer  = null;

// ══════════════════════════════
// ABRIR EDITOR
// ══════════════════════════════
function abrirEditor(td) {
    if (CERRADO) return;
    cerrarEditor();
    celdaActiva = td;
    td.classList.add('editando');

    if (CICLO === 'primer_ciclo') {
        const popup   = document.getElementById('cellPopup');
        const valAct  = td.dataset.val ? parseInt(td.dataset.val) : null;
        document.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('selected'));
        if (valAct) document.querySelector(`.choice-btn.c${valAct}`)?.classList.add('selected');
        document.getElementById('popLabel').textContent =
            td.dataset.tipo === 'indicador' ? 'IL — escala 1 a 4' : 'CE — escala 1 a 4';
        posicionarPopup(popup, td);
    } else {
        const popup = document.getElementById('numPopup');
        const inp   = document.getElementById('numInput');
        inp.value   = td.dataset.val !== '' ? td.dataset.val : '';
        posicionarPopup(popup, td);
        setTimeout(() => { inp.focus(); inp.select(); }, 60);
    }
}

function cerrarEditor() {
    document.getElementById('cellPopup').style.display = 'none';
    document.getElementById('numPopup').style.display  = 'none';
    celdaActiva?.classList.remove('editando');
    celdaActiva = null;
}

function posicionarPopup(popup, td) {
    const rect = td.getBoundingClientRect();
    const winH = window.innerHeight;
    const popH = 100;
    let top  = rect.bottom + window.scrollY + 4;
    let left = rect.left   + window.scrollX - 10;
    if (rect.bottom + popH > winH) top = rect.top + window.scrollY - popH - 4;
    if (left + 220 > window.innerWidth) left = window.innerWidth - 230;
    popup.style.top    = top  + 'px';
    popup.style.left   = left + 'px';
    popup.style.display = 'block';
}

// ══════════════════════════════
// ELEGIR / CONFIRMAR VALOR
// ══════════════════════════════
async function elegirValor(val) {
    if (!celdaActiva || CERRADO) return;
    const td  = celdaActiva;
    cerrarEditor();
    await guardarCelda(td, val);
}

function confirmarNumerico() {
    if (!celdaActiva) return;
    const raw = document.getElementById('numInput').value.trim();
    const val = raw !== '' ? parseFloat(raw) : null;
    if (val !== null && (isNaN(val) || val < 0 || val > 100)) {
        mostrarToast('Nota fuera del rango 0–100', 'err');
        return;
    }
    elegirValor(val);
}

function numInputKey(e) {
    if (e.key === 'Enter') confirmarNumerico();
    if (e.key === 'Escape') cerrarEditor();
}

// ══════════════════════════════
// GUARDAR VÍA AJAX
// ══════════════════════════════
async function guardarCelda(td, val) {
    const valAnterior = td.dataset.val !== '' ? td.dataset.val : null;
    const valNuevo    = val !== null && val !== '' ? val : null;

    if (String(valNuevo) === String(valAnterior)) return;

    actualizarCeldaUI(td, valNuevo);

    try {
        const res = await fetch(URL_SAVE, {
            method : 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body   : JSON.stringify({
                matricula_id  : parseInt(td.dataset.matricula),
                periodo_id    : parseInt(td.dataset.periodo),
                school_year_id: SY_ID,
                tipo          : td.dataset.tipo,
                referencia_id : parseInt(td.dataset.ref),
                valor         : valNuevo,
            }),
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.message ?? 'Error del servidor');
        mostrarToast('✓ Guardado', 'ok');
        recalcularPromedioEst(td.dataset.matricula);
    } catch(e) {
        actualizarCeldaUI(td, valAnterior);
        mostrarToast('Error: ' + e.message, 'err');
    }
}

// ══════════════════════════════
// GUARDAR TODO
// ══════════════════════════════
async function guardarTodo() {
    if (CERRADO) return;
    const celdas = [...document.querySelectorAll('td[data-matricula]')].filter(td => td.dataset.val !== '');
    if (!celdas.length) { mostrarToast('No hay notas para guardar', 'info'); return; }

    let ok = 0, err = 0;
    for (const td of celdas) {
        try {
            const res = await fetch(URL_SAVE, {
                method : 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
                body   : JSON.stringify({
                    matricula_id  : parseInt(td.dataset.matricula),
                    periodo_id    : parseInt(td.dataset.periodo),
                    school_year_id: SY_ID,
                    tipo          : td.dataset.tipo,
                    referencia_id : parseInt(td.dataset.ref),
                    valor         : parseFloat(td.dataset.val),
                }),
            });
            const json = await res.json();
            json.ok ? ok++ : err++;
        } catch { err++; }
    }
    mostrarToast(err ? `${ok} guardadas, ${err} errores` : `✓ ${ok} notas guardadas`, err ? 'err' : 'ok');
}

// ══════════════════════════════
// ACTUALIZAR UI DE CELDA
// ══════════════════════════════
function actualizarCeldaUI(td, val) {
    const span = td.querySelector('.badge-val');
    if (!span) return;

    if (val === null || val === '') {
        td.style.background = '#fff';
        td.dataset.val = '';
        if (CICLO === 'primer_ciclo') {
            span.className   = 'badge-val empty';
            span.textContent = '—';
        } else {
            span.style.cssText = 'background:#f1f5f9;color:#cbd5e1;';
            span.textContent   = '—';
        }
    } else {
        td.dataset.val = val;
        if (CICLO === 'primer_ciclo') {
            const v = parseInt(val);
            const bgs = {1:'var(--c1-bg)',2:'var(--c2-bg)',3:'var(--c3-bg)',4:'var(--c4-bg)'};
            td.style.background = bgs[v] || '#fff';
            span.className   = `badge-val v${v}`;
            span.textContent = v;
        } else {
            const n  = parseFloat(val);
            const bg = n>=90?'#d1fae5':n>=65?'#dcfce7':n>=50?'#fef9c3':'#fee2e2';
            const tx = n>=65?'#065f46':'#991b1b';
            td.style.background    = bg;
            span.style.cssText     = `background:${bg};color:${tx};border-color:transparent;`;
            span.textContent       = Number.isInteger(n) ? n : n.toFixed(1);
        }
    }
}

// ══════════════════════════════
// RECALCULAR PROMEDIO EN UI
// ══════════════════════════════
function recalcularPromedioEst(mId) {
    const celdas = [...document.querySelectorAll(`td[data-matricula="${mId}"]`)];
    const vals   = celdas.map(td => td.dataset.val !== '' ? parseFloat(td.dataset.val) : null).filter(v => v !== null);
    const prom   = vals.length ? parseFloat((vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2)) : null;

    const tdProm = document.querySelector(`td[data-prom-gen="${mId}"]`);
    if (!tdProm) return;

    if (prom === null) {
        tdProm.style.background = '#f8fafc';
        tdProm.style.color      = '#94a3b8';
        tdProm.textContent      = '—';
        return;
    }

    let bg, tx;
    if (CICLO === 'primer_ciclo') {
        bg = prom>=3.5?'var(--c4-bg)':prom>=2.5?'var(--c3-bg)':prom>=1.5?'var(--c2-bg)':'var(--c1-bg)';
        tx = prom>=3.5?'var(--c4-txt)':prom>=2.5?'var(--c3-txt)':prom>=1.5?'var(--c2-txt)':'var(--c1-txt)';
    } else {
        bg = prom>=90?'#d1fae5':prom>=65?'#dcfce7':prom>=50?'#fef9c3':'#fee2e2';
        tx = prom>=65?'#065f46':'#991b1b';
    }
    tdProm.style.background = bg;
    tdProm.style.color      = tx;
    tdProm.textContent      = prom.toFixed(1);
}

// ══════════════════════════════
// TECLADO
// ══════════════════════════════
function handleKey(e, td) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrirEditor(td); return; }
    if (e.key === 'Escape') { cerrarEditor(); return; }
    if (e.key === 'Tab') {
        e.preventDefault();
        cerrarEditor();
        const celdas = [...document.querySelectorAll('td[tabindex="0"]')];
        const next   = celdas[celdas.indexOf(td) + (e.shiftKey ? -1 : 1)];
        next?.focus();
        return;
    }
    if (CICLO === 'primer_ciclo' && ['1','2','3','4'].includes(e.key)) {
        e.preventDefault();
        celdaActiva = td;
        elegirValor(parseInt(e.key));
    }
    if ((e.key === 'Delete' || e.key === 'Backspace') && !CERRADO) {
        celdaActiva = td;
        elegirValor(null);
    }
}

// ══════════════════════════════
// TOAST
// ══════════════════════════════
function mostrarToast(msg, tipo = 'ok') {
    const t    = document.getElementById('regToast');
    const icon = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;
    icon.className = 'bi ' + (tipo==='ok'?'bi-check-circle-fill':tipo==='err'?'bi-x-circle-fill':'bi-info-circle-fill');
    t.className    = `reg-toast show ${tipo}`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
}

// Cerrar popup al clic fuera
document.addEventListener('click', e => {
    const p1 = document.getElementById('cellPopup');
    const p2 = document.getElementById('numPopup');
    if (!p1.contains(e.target) && !p2.contains(e.target) && !e.target.closest('td[data-matricula]')) {
        cerrarEditor();
    }
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarEditor(); });
</script>
@endpush

@endsection
