@extends('layouts.admin')
@section('page-title',
    isset($asignacion)
        ? 'Registro · ' . $asignacion->asignatura->nombre . ' · ' . $grupo->grado->nombre . ' ' . $grupo->seccion->nombre
        : 'Registro de Calificaciones'
)

@push('styles')
<style>
/* ════════════════════════════════════════════════════════════
   VARIABLES
═══════════════════════════════════════════════════════════ */
:root {
    --c1-bg:#fee2e2; --c1-txt:#991b1b; --c1-border:#fca5a5;
    --c2-bg:#fef9c3; --c2-txt:#854d0e; --c2-border:#fde047;
    --c3-bg:#dbeafe; --c3-txt:#1e40af; --c3-border:#93c5fd;
    --c4-bg:#dcfce7; --c4-txt:#15803d; --c4-border:#86efac;
    --header-bg:#1e3a6e;
    --ce-bg:#2d5aa0;
    --il-bg:#e8edf8;
    --prom-bg:#f0fdf4;
    --cell-h:36px;
}

/* ════ PANEL DE SELECCIÓN ════ */
.sel-panel { background:#fff; border:1px solid #e5e7eb; border-radius:16px;
    padding:2rem; box-shadow:0 2px 12px rgba(0,0,0,.06); }
.sel-panel .sel-label { font-size:.72rem; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.06em; margin-bottom:.4rem; }
.sel-panel select, .sel-panel .form-select {
    border-radius:10px; border:1.5px solid #d1d5db; font-size:.875rem;
    padding:.5rem .875rem; transition:border .15s; }
.sel-panel select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.btn-cargar { background:var(--primary); color:#fff; border:none; border-radius:10px;
    padding:.55rem 1.5rem; font-weight:700; font-size:.9rem; cursor:pointer; transition:.15s; }
.btn-cargar:hover { filter:brightness(1.1); }

/* ════ TOOLBAR ════ */
.reg-toolbar { background:#fff; border:1px solid #e5e7eb; border-radius:14px;
    padding:.875rem 1.25rem; display:flex; align-items:center;
    gap:.75rem; flex-wrap:wrap; margin-bottom:1rem; }
.periodo-tab { border:1.5px solid #d1d5db; border-radius:8px; padding:.3rem .85rem;
    font-size:.78rem; font-weight:700; cursor:pointer; background:#fff;
    transition:.15s; color:#374151; }
.periodo-tab:hover { border-color:var(--primary); color:var(--primary); }
.periodo-tab.active { background:var(--primary); color:#fff; border-color:var(--primary); }
.periodo-tab.cerrado { opacity:.55; cursor:not-allowed; }

.search-box { position:relative; }
.search-box input { border-radius:8px; border:1.5px solid #d1d5db; font-size:.82rem;
    padding:.35rem .75rem .35rem 2rem; width:200px; }
.search-box .bi { position:absolute; left:.6rem; top:50%; transform:translateY(-50%);
    color:#9ca3af; font-size:.9rem; pointer-events:none; }

/* ════ TABLA REGISTRO ════ */
.reg-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch;
    border-radius:14px; border:1.5px solid #d1d5db; background:#fff;
    box-shadow:0 2px 16px rgba(0,0,0,.06); }
.reg-tbl { border-collapse:collapse; font-size:.72rem; min-width:100%; }
.reg-tbl th, .reg-tbl td { border:1px solid #e2e8f0; white-space:nowrap; }

/* Cabecera */
.th-info   { background:var(--header-bg); color:#fff; font-weight:800;
    text-align:center; padding:.45rem .6rem; font-size:.78rem; }
.th-ce     { background:var(--ce-bg); color:#fff; font-size:.7rem; font-weight:700;
    text-align:center; padding:.35rem .5rem; }
.th-il     { background:var(--il-bg); color:#1e3a6e; font-size:.68rem; font-weight:700;
    text-align:center; padding:.28rem .5rem; position:relative; }
.th-il .il-tip { position:absolute; bottom:100%; left:50%; transform:translateX(-50%);
    background:#111; color:#fff; font-size:.65rem; border-radius:6px; padding:.2rem .5rem;
    white-space:normal; width:200px; display:none; z-index:20; pointer-events:none; }
.th-il:hover .il-tip { display:block; }
.th-prom  { background:var(--prom-bg); color:#15803d; font-size:.68rem; font-weight:700;
    text-align:center; padding:.28rem .5rem; }
.th-total { background:#111827; color:#fff; font-size:.72rem; font-weight:800;
    text-align:center; padding:.35rem .5rem; }

/* Celdas datos */
.td-num    { background:#f8fafc; color:#374151; font-weight:700; text-align:center;
    padding:.25rem .4rem; min-width:36px; position:sticky; left:0; z-index:2; }
.td-nombre { background:#fff; font-weight:600; color:#111827; padding:.3rem .75rem;
    min-width:200px; position:sticky; left:36px; z-index:2;
    border-right:2px solid #d1d5db !important; }
.td-nombre .n-mat { font-size:.62rem; color:#2563eb; font-weight:700; font-family:monospace; margin-top:.1rem; }
[data-theme="dark"] .td-nombre .n-mat { color:#93c5fd !important; }

.td-cell   { text-align:center; padding:0; width:52px; min-width:52px; cursor:pointer;
    transition:filter .1s; vertical-align:middle; }
.td-cell:hover { filter:brightness(.94); }
.td-cell.cerrado { cursor:not-allowed; opacity:.7; }
.td-cell.editando { outline:3px solid #f59e0b; outline-offset:-2px; z-index:5; }
.td-cell.dirty::after { content:''; position:absolute; top:2px; right:2px;
    width:5px; height:5px; background:#f59e0b; border-radius:50%; }
.td-cell { position:relative; }

.badge-val { display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; border-radius:7px; font-weight:800; font-size:.82rem;
    border:1.5px solid transparent; line-height:1; }
.badge-val.v1 { background:var(--c1-bg); color:var(--c1-txt); border-color:var(--c1-border); }
.badge-val.v2 { background:var(--c2-bg); color:var(--c2-txt); border-color:var(--c2-border); }
.badge-val.v3 { background:var(--c3-bg); color:var(--c3-txt); border-color:var(--c3-border); }
.badge-val.v4 { background:var(--c4-bg); color:var(--c4-txt); border-color:var(--c4-border); }
.badge-val.empty { background:#f1f5f9; color:#cbd5e1; border-color:#e2e8f0; }

.td-prom { text-align:center; font-weight:700; font-size:.75rem;
    padding:.25rem .4rem; background:#f0fdf4; }
.td-total { text-align:center; font-weight:800; font-size:.78rem;
    padding:.25rem .4rem; min-width:58px; background:#f0fdf4;
    border-left:2px solid #86efac !important; }

/* Fila oculta (search) */
.reg-row.hidden { display:none; }

/* Sticky headers */
.reg-tbl thead th.th-info:first-child  { position:sticky; left:0; z-index:12; }
.reg-tbl thead th.th-info:nth-child(2) { position:sticky; left:36px; z-index:12; }

/* Fila grupo */
.tr-grupo { background:#f1f5f9; border-top:2px solid #475569; }
.tr-grupo td { font-weight:800; font-size:.75rem; color:#374151; padding:.35rem .6rem; }

/* ════ ESCALA VISUAL ════ */
.leyenda-bar { display:flex; gap:.6rem; flex-wrap:wrap; align-items:center;
    padding:.6rem 1rem; background:#f8fafc; border-radius:10px;
    border:1px solid #e5e7eb; margin-bottom:1rem; font-size:.75rem; }
.leg-item { display:inline-flex; align-items:center; gap:.35rem; font-weight:600; }

/* ════ EDITOR FLOTANTE ════ */
.cell-editor-wrap { position:fixed; z-index:1000; display:none; }
.cell-editor-wrap .choices-row { display:flex; gap:.3rem; }
.choice-btn { width:40px; height:40px; border-radius:10px; border:2px solid transparent;
    font-weight:800; font-size:1rem; cursor:pointer; transition:.12s;
    display:flex; align-items:center; justify-content:center; }
.choice-btn:hover, .choice-btn.selected { transform:scale(1.12); border-color:#111; }
.choice-btn.c1 { background:var(--c1-bg); color:var(--c1-txt); }
.choice-btn.c2 { background:var(--c2-bg); color:var(--c2-txt); }
.choice-btn.c3 { background:var(--c3-bg); color:var(--c3-txt); }
.choice-btn.c4 { background:var(--c4-bg); color:var(--c4-txt); }
.choice-btn.del { background:#f1f5f9; color:#94a3b8; font-size:.8rem; }
.choice-popup { background:#fff; border:1.5px solid #e5e7eb; border-radius:12px;
    padding:.6rem .75rem; box-shadow:0 8px 32px rgba(0,0,0,.15); }
.choice-label { font-size:.65rem; font-weight:700; color:#6b7280; margin-bottom:.4rem; text-align:center; }

/* ════ TOAST ════ */
.reg-toast { position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
    background:#111827; color:#fff; border-radius:10px; padding:.65rem 1.25rem;
    font-size:.82rem; font-weight:600; opacity:0; transform:translateY(8px);
    transition:all .22s; pointer-events:none; display:flex; align-items:center; gap:.5rem; }
.reg-toast.show { opacity:1; transform:translateY(0); }
.reg-toast.ok   { background:#059669; }
.reg-toast.err  { background:#dc2626; }
.reg-toast.info { background:#2563eb; }

/* ════ BOTONES ACCIÓN ════ */
.btn-accion { display:inline-flex; align-items:center; gap:.4rem;
    border-radius:9px; padding:.4rem .9rem; font-size:.8rem; font-weight:700;
    cursor:pointer; transition:.15s; border:1.5px solid transparent; }
.btn-guardar-todo { background:#059669; color:#fff; border-color:#059669; }
.btn-guardar-todo:hover { background:#047857; }
.btn-exportar { background:#fff; color:#dc2626; border-color:#fca5a5; }
.btn-exportar:hover { background:#fef2f2; }
.btn-limpiar  { background:#fff; color:#6b7280; border-color:#d1d5db; }
.btn-limpiar:hover  { background:#f9fafb; color:#111827; }
.btn-dirty-badge { background:#f59e0b; color:#fff; border-radius:20px;
    font-size:.65rem; padding:.05rem .4rem; margin-left:.2rem; }

/* ════ INDICADOR GUARDANDO ════ */
.saving-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.25);
    z-index:8000; align-items:center; justify-content:center; }
.saving-overlay.show { display:flex; }
.saving-box { background:#fff; border-radius:16px; padding:1.5rem 2.5rem;
    font-weight:700; font-size:1rem; display:flex; align-items:center; gap:.75rem; }

/* ════ INFO HEADER ════ */
.reg-info-header { display:flex; flex-wrap:wrap; gap:1.5rem; align-items:center;
    background:linear-gradient(135deg, #1e3a6e 0%, #2d5aa0 100%);
    color:#fff; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1rem; }
.reg-info-header .ri-label { font-size:.65rem; opacity:.7; font-weight:600;
    text-transform:uppercase; letter-spacing:.06em; margin-bottom:.1rem; }
.reg-info-header .ri-val { font-weight:800; font-size:.95rem; }
.reg-info-header .ri-sep { width:1px; background:rgba(255,255,255,.25); height:40px; }
</style>
@endpush

@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────────────────── --}}
<nav aria-label="breadcrumb" style="font-size:.8rem;" class="mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.registro.index') }}">Registro</a></li>
        <li class="breadcrumb-item">
            <a href="{{ route('admin.registro.show', $grupo) }}">{{ $grupo->grado->nombre }} {{ $grupo->seccion->nombre }}</a>
        </li>
        <li class="breadcrumb-item active">
            {{ isset($asignacion) ? $asignacion->asignatura->nombre : 'Seleccionar materia' }}
        </li>
    </ol>
</nav>

{{-- ══════════════════════════════════════════════════════════════════════
     PANEL DE SELECCIÓN (siempre visible)
════════════════════════════════════════════════════════════════════════ --}}
<div class="sel-panel mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-journal-plus" style="color:var(--primary);font-size:1.3rem;"></i>
        <div>
            <div style="font-weight:800;font-size:1.05rem;color:#111827;">
                Registro de Calificaciones MINERD
            </div>
            <div class="text-muted" style="font-size:.78rem;">
                {{ $schoolYear->nombre }} &nbsp;·&nbsp;
                <span class="badge text-bg-primary" style="font-size:.68rem;">Primer Ciclo</span>
            </div>
        </div>
    </div>

    @php
        // Mapa: grupo_id → [{id, label}] para refrescar materias vía JS al cambiar grupo
        $grupoAsignaciones = ($grupos ?? collect())->mapWithKeys(
            fn($g) => [$g->id => ($g->asignaciones ?? collect())->map(fn($a) => [
                'id'    => $a->id,
                'label' => $a->asignatura->nombre . ($a->docente ? ' · ' . $a->docente->apellidos : ''),
            ])]
        )->toJson();
    @endphp

    <form method="GET" action="{{ route('admin.registro.calificaciones', $grupo) }}"
          id="formSelector" class="row g-3 align-items-end">

        {{-- Grupo --}}
        <div class="col-lg-3 col-md-6">
            <div class="sel-label">Grupo / Sección</div>
            <select class="form-select" id="selGrupo" required>
                <option value="">— Seleccionar grupo —</option>
                @foreach($grupos ?? [] as $g)
                    <option value="{{ $g->id }}"
                            data-url="{{ route('admin.registro.calificaciones', $g) }}"
                            {{ $g->id === $grupo->id ? 'selected' : '' }}>
                        {{ $g->grado->nombre }} — Sección {{ $g->seccion->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Materia --}}
        <div class="col-lg-4 col-md-6">
            <div class="sel-label">Materia / Asignatura</div>
            <select name="asignacion_id" class="form-select" required id="selAsignacion">
                <option value="">— Seleccionar materia —</option>
                @foreach($asignaciones as $a)
                    <option value="{{ $a->id }}"
                        {{ isset($asignacion) && $asignacion->id === $a->id ? 'selected' : '' }}>
                        {{ $a->asignatura->nombre }}
                        @if($a->docente) · {{ $a->docente->apellidos }} @endif
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Período --}}
        <div class="col-lg-2 col-md-4">
            <div class="sel-label">Período</div>
            <select name="periodo_id" class="form-select" required id="selPeriodo">
                <option value="">— Seleccionar período —</option>
                @foreach($periodos as $p)
                    <option value="{{ $p->id }}"
                        {{ isset($periodo) && $periodo->id === $p->id ? 'selected' : '' }}>
                        {{ $p->nombre }}
                        @if($p->cerrado) 🔒 @endif
                        @if($p->activo) ← Activo @endif
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Botón --}}
        <div class="col-auto">
            <button type="submit" class="btn-cargar">
                <i class="bi bi-table me-1"></i>Cargar Registro
            </button>
        </div>
    </form>
</div>

@if(!isset($asignacion))
{{-- ── Sin selección: instrucciones ──────────────────────────────── --}}
<div class="text-center py-5" style="color:#94a3b8;">
    <i class="bi bi-arrow-up-circle d-block mb-3" style="font-size:2.5rem;"></i>
    <div style="font-size:.95rem;font-weight:600;color:#64748b;">
        Selecciona una materia y un período para ver el registro
    </div>
    <div style="font-size:.8rem;margin-top:.5rem;">
        También puedes ver el
        <a href="{{ route('admin.registro.show', $grupo) }}">registro completo del grupo →</a>
    </div>
</div>

@else
{{-- ══════════════════════════════════════════════════════════════════════
     REGISTRO CARGADO
════════════════════════════════════════════════════════════════════════ --}}
@php
    $ces     = $asignacion->asignatura->competenciasActivas ?? collect();
    $periodoActual = $periodo;

    // Calcular promedio de un estudiante para esta asignación
    $calcPromedioEst = function(int $mId) use ($ces, $periodos, $evalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    $key = "il_{$il->id}";
                    $vp = $evalMap[$mId][$key] ?? [];
                    foreach ($vp as $v) { if ($v !== null) $vals[] = (float)$v; }
                }
            } else {
                $key = "ce_{$ce->id}";
                $vp = $evalMap[$mId][$key] ?? [];
                foreach ($vp as $v) { if ($v !== null) $vals[] = (float)$v; }
            }
        }
        return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
    };

    $calcColorClass = function(?float $v): string {
        if ($v === null) return '';
        if ($v >= 3.5) return 'td-total-v4';
        if ($v >= 2.5) return 'td-total-v3';
        if ($v >= 1.5) return 'td-total-v2';
        return 'td-total-v1';
    };
@endphp

{{-- ── Info header ─────────────────────────────────────────────────── --}}
<div class="reg-info-header">
    <div>
        <div class="ri-label">Grupo</div>
        <div class="ri-val">{{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }}</div>
    </div>
    <div class="ri-sep"></div>
    <div>
        <div class="ri-label">Materia</div>
        <div class="ri-val">{{ $asignacion->asignatura->nombre }}</div>
    </div>
    <div class="ri-sep"></div>
    <div>
        <div class="ri-label">Período activo</div>
        <div class="ri-val">{{ $periodo->nombre }}</div>
    </div>
    <div class="ri-sep"></div>
    <div>
        <div class="ri-label">Docente</div>
        <div class="ri-val">{{ $asignacion->docente?->nombre_completo ?? '—' }}</div>
    </div>
    <div class="ri-sep"></div>
    <div>
        <div class="ri-label">Estudiantes</div>
        <div class="ri-val">{{ $matriculas->count() }}</div>
    </div>
    @if($periodo->cerrado)
    <div class="ms-auto">
        <span class="badge" style="background:rgba(255,255,255,.2);font-size:.78rem;">
            <i class="bi bi-lock-fill me-1"></i>Período cerrado — solo lectura
        </span>
    </div>
    @endif
</div>

{{-- ── Leyenda escala ──────────────────────────────────────────────── --}}
<div class="leyenda-bar">
    <span style="font-size:.72rem;font-weight:700;color:#374151;">Escala MINERD:</span>
    <span class="leg-item"><span class="badge-val v1">1</span> Inicial</span>
    <span class="leg-item"><span class="badge-val v2">2</span> En proceso</span>
    <span class="leg-item"><span class="badge-val v3">3</span> Logrado</span>
    <span class="leg-item"><span class="badge-val v4">4</span> Avanzado</span>
    <span class="ms-auto text-muted" style="font-size:.7rem;">
        <i class="bi bi-mouse2 me-1"></i>Clic en celda para editar &nbsp;·&nbsp;
        <kbd style="font-size:.65rem;">Tab</kbd> siguiente &nbsp;·&nbsp;
        <kbd style="font-size:.65rem;">Esc</kbd> cancelar
    </span>
</div>

{{-- ── Toolbar ─────────────────────────────────────────────────────── --}}
<div class="reg-toolbar">
    {{-- Buscar --}}
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="buscador" placeholder="Buscar estudiante…"
               oninput="filtrarEstudiantes(this.value)">
    </div>

    {{-- Tabs períodos --}}
    @foreach($periodos as $p)
        <a href="{{ route('admin.registro.calificaciones', [$grupo, 'asignacion_id'=>$asignacion->id, 'periodo_id'=>$p->id]) }}"
           class="periodo-tab {{ $p->id === $periodo->id ? 'active' : '' }} {{ $p->cerrado ? 'cerrado' : '' }}">
            P{{ $p->numero }}
            @if($p->cerrado)<i class="bi bi-lock-fill ms-1" style="font-size:.6rem;"></i>@endif
        </a>
    @endforeach

    {{-- Acciones --}}
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <button class="btn-accion btn-limpiar" onclick="limpiarPeriodo()" id="btnLimpiar"
                {{ $periodo->cerrado ? 'disabled' : '' }} title="Borrar todas las notas de este período">
            <i class="bi bi-eraser"></i>Limpiar
        </button>

        <a href="{{ route('admin.registro.calificaciones.pdf', [$grupo, 'asignacion_id'=>$asignacion->id, 'periodo_id'=>$periodo->id]) }}"
           class="btn-accion btn-exportar" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>

        <button class="btn-accion btn-guardar-todo" onclick="guardarTodo()" id="btnGuardarTodo"
                {{ $periodo->cerrado ? 'disabled' : '' }}>
            <i class="bi bi-cloud-check"></i>Guardar todo
            <span class="btn-dirty-badge d-none" id="contadorDirty">0</span>
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TABLA PRINCIPAL
════════════════════════════════════════════════════════════════════════ --}}
<div class="reg-wrap">
<table class="reg-tbl" id="tablaReg">

{{-- ── Cabecera ── --}}
<thead>

{{-- Fila 1: Materias / CE --}}
<tr>
    <th class="th-info" rowspan="3" style="min-width:36px;">#</th>
    <th class="th-info" rowspan="3"
        style="min-width:200px;position:sticky;left:36px;z-index:13;text-align:left;padding-left:.875rem;">
        Apellidos, Nombres
    </th>

    @foreach($ces as $ce)
        @php
            $ils = $ce->indicadoresActivos ?? collect();
            $ceColspan = $ils->isNotEmpty() ? $ils->count() + 1 : 2; // ILs + prom CE
        @endphp
        <th class="th-ce" colspan="{{ $ceColspan }}">
            {{ $ce->codigo }}: {{ $ce->nombre }}
        </th>
    @endforeach

    <th class="th-total" rowspan="3" style="min-width:64px;">
        PROM<br>GRAL
    </th>
</tr>

{{-- Fila 2: IL headers --}}
<tr>
    @foreach($ces as $ce)
        @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
        @if($ils->isNotEmpty())
            @foreach($ils as $il)
                <th class="th-il" style="width:54px;">
                    {{ $il->codigo }}
                    <span class="il-tip">{{ $il->descripcion }}</span>
                </th>
            @endforeach
        @else
            <th class="th-il" style="width:54px;">{{ $ce->codigo }}</th>
        @endif
        <th class="th-prom" style="width:54px;">PROM<br>CE</th>
    @endforeach
</tr>

{{-- Fila 3: Período actual para cada columna --}}
<tr>
    @foreach($ces as $ce)
        @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
        @php $cols = $ils->isNotEmpty() ? $ils->count() : 1; @endphp
        @for($i = 0; $i < $cols; $i++)
            <th style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:700;
                       text-align:center;padding:.2rem;border:1px solid #e5e7eb;">
                P{{ $periodo->numero }}
            </th>
        @endfor
        <th style="background:#f0fdf4;color:#166534;font-size:.63rem;font-weight:600;
                   text-align:center;padding:.2rem;border:1px solid #e5e7eb;">
            Todos
        </th>
    @endforeach
</tr>
</thead>

{{-- ── Cuerpo ── --}}
<tbody id="tbodyReg">
@foreach($matriculas as $idx => $m)
    @php
        $promEstudiante = $calcPromedioEst($m->id);
        $promColor = $promEstudiante !== null
            ? ($promEstudiante >= 3.5 ? 'var(--c4-bg)' : ($promEstudiante >= 2.5 ? 'var(--c3-bg)' : ($promEstudiante >= 1.5 ? 'var(--c2-bg)' : 'var(--c1-bg)')))
            : '#f8fafc';
        $promTxt = $promEstudiante !== null
            ? ($promEstudiante >= 3.5 ? 'var(--c4-txt)' : ($promEstudiante >= 2.5 ? 'var(--c3-txt)' : ($promEstudiante >= 1.5 ? 'var(--c2-txt)' : 'var(--c1-txt)')))
            : '#94a3b8';
    @endphp
    <tr class="reg-row" data-nombre="{{ strtolower($m->estudiante?->apellidos . ' ' . $m->estudiante?->nombres) }}">
        <td class="td-num">{{ $idx + 1 }}</td>
        <td class="td-nombre">
            <div>{{ $m->estudiante?->apellidos ?? '—' }}, {{ $m->estudiante?->nombres ?? '' }}</div>
            <div class="n-mat">{{ $m->estudiante?->numero_matricula }}</div>
        </td>

        {{-- ── Columnas por CE y sus ILs ── --}}
        @foreach($ces as $ceIdx => $ce)
            @php
                $ils         = $ce->indicadoresActivos ?? collect();
                $valsParaCe  = []; // para calcular prom CE de este estudiante
            @endphp

            @if($ils->isNotEmpty())
                {{-- Evaluación por IL --}}
                @foreach($ils as $il)
                    @php
                        $refKey = "il_{$il->id}";
                        $val    = $evalMap[$m->id][$refKey][$periodo->id] ?? null;
                        // Calcular prom IL (todos los períodos)
                        $allIlVals = array_filter($evalMap[$m->id][$refKey] ?? [], fn($v) => $v !== null);
                        $promIl    = count($allIlVals) ? round(array_sum($allIlVals)/count($allIlVals), 2) : null;
                        if ($promIl !== null) $valsParaCe[] = $promIl;
                        $bgVal  = $val !== null ? "var(--c{$val}-bg)" : '#fff';
                        $locked = $periodo->cerrado;
                    @endphp
                    <td class="td-cell {{ $locked ? 'cerrado' : '' }}"
                        style="background:{{ $bgVal }};"
                        data-matricula="{{ $m->id }}"
                        data-asignacion="{{ $asignacion->id }}"
                        data-periodo="{{ $periodo->id }}"
                        data-schoolyear="{{ $schoolYear->id }}"
                        data-tipo="indicador"
                        data-ref="{{ $il->id }}"
                        data-refkey="{{ $refKey }}"
                        data-val="{{ $val ?? '' }}"
                        onclick="{{ $locked ? 'return' : 'abrirEditor(this)' }}"
                        tabindex="{{ $locked ? '' : '0' }}"
                        onkeydown="handleCellKey(event, this)">
                        <span class="badge-val {{ $val ? 'v'.$val : 'empty' }}">
                            {{ $val ?? '—' }}
                        </span>
                    </td>
                @endforeach
            @else
                {{-- Evaluación directa por CE --}}
                @php
                    $refKey  = "ce_{$ce->id}";
                    $val     = $evalMap[$m->id][$refKey][$periodo->id] ?? null;
                    $allCeV  = array_filter($evalMap[$m->id][$refKey] ?? [], fn($v) => $v !== null);
                    $promCeD = count($allCeV) ? round(array_sum($allCeV)/count($allCeV), 2) : null;
                    if ($promCeD !== null) $valsParaCe[] = $promCeD;
                    $bgVal   = $val !== null ? "var(--c{$val}-bg)" : '#fff';
                    $locked  = $periodo->cerrado;
                @endphp
                <td class="td-cell {{ $locked ? 'cerrado' : '' }}"
                    style="background:{{ $bgVal }};"
                    data-matricula="{{ $m->id }}"
                    data-asignacion="{{ $asignacion->id }}"
                    data-periodo="{{ $periodo->id }}"
                    data-schoolyear="{{ $schoolYear->id }}"
                    data-tipo="competencia"
                    data-ref="{{ $ce->id }}"
                    data-refkey="{{ $refKey }}"
                    data-val="{{ $val ?? '' }}"
                    onclick="{{ $locked ? 'return' : 'abrirEditor(this)' }}"
                    tabindex="{{ $locked ? '' : '0' }}"
                    onkeydown="handleCellKey(event, this)">
                    <span class="badge-val {{ $val ? 'v'.$val : 'empty' }}">
                        {{ $val ?? '—' }}
                    </span>
                </td>
            @endif

            {{-- Promedio CE (todos los períodos) --}}
            @php
                $promCe = count($valsParaCe) ? round(array_sum($valsParaCe)/count($valsParaCe), 2) : null;
                $bgCe   = $promCe !== null ? ($promCe>=3.5?'var(--c4-bg)':($promCe>=2.5?'var(--c3-bg)':($promCe>=1.5?'var(--c2-bg)':'var(--c1-bg)'))) : '#f0fdf4';
                $txCe   = $promCe !== null ? ($promCe>=3.5?'var(--c4-txt)':($promCe>=2.5?'var(--c3-txt)':($promCe>=1.5?'var(--c2-txt)':'var(--c1-txt)'))) : '#94a3b8';
            @endphp
            <td class="td-prom"
                style="background:{{ $bgCe }};color:{{ $txCe }};"
                data-prom-ce="{{ $ce->id }}-{{ $m->id }}">
                {{ $promCe !== null ? number_format($promCe, 1) : '—' }}
            </td>
        @endforeach

        {{-- Promedio general del estudiante --}}
        <td class="td-total"
            style="background:{{ $promColor }};color:{{ $promTxt }};"
            data-prom-gen="{{ $m->id }}">
            @if($promEstudiante !== null)
                <strong>{{ number_format($promEstudiante, 1) }}</strong>
            @else
                <span style="color:#94a3b8;">—</span>
            @endif
        </td>
    </tr>
@endforeach

{{-- Fila promedio del grupo --}}
<tr class="tr-grupo">
    <td colspan="2" class="td-nombre" style="background:#f1f5f9;">
        <strong>PROMEDIO DEL GRUPO</strong>
    </td>
    @foreach($ces as $ce)
        @php
            $ils = $ce->indicadoresActivos ?? collect();
            $cols = $ils->isNotEmpty() ? $ils->count() : 1;
        @endphp
        @for($i = 0; $i < $cols; $i++)
            <td style="background:#f1f5f9;"></td>
        @endfor
        {{-- Prom CE del grupo --}}
        @php
            $promsGrupoCe = $matriculas->map(function($m) use ($ce, $periodos, $evalMap) {
                $ils = $ce->indicadoresActivos ?? collect();
                $vals = [];
                if ($ils->isNotEmpty()) {
                    foreach ($ils as $il) {
                        $key = "il_{$il->id}";
                        foreach ($evalMap[$m->id][$key] ?? [] as $v) {
                            if ($v !== null) $vals[] = (float)$v;
                        }
                    }
                } else {
                    $key = "ce_{$ce->id}";
                    foreach ($evalMap[$m->id][$key] ?? [] as $v) {
                        if ($v !== null) $vals[] = (float)$v;
                    }
                }
                return count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
            })->filter();
        @endphp
        <td class="td-prom" style="font-weight:800;background:#dcfce7;color:#15803d;">
            {{ $promsGrupoCe->count() ? number_format($promsGrupoCe->avg(), 1) : '—' }}
        </td>
    @endforeach
    {{-- Prom general grupo --}}
    @php
        $promsGenGrupo = $matriculas->map(fn($m) => $calcPromedioEst($m->id))->filter();
    @endphp
    <td class="td-total" style="font-weight:800;background:#86efac;color:#14532d;">
        {{ $promsGenGrupo->count() ? number_format($promsGenGrupo->avg(), 1) : '—' }}
    </td>
</tr>
</tbody>
</table>
</div>

{{-- Sin resultados de búsqueda --}}
<div id="sinResultados" class="text-center py-4 text-muted d-none" style="font-size:.85rem;">
    <i class="bi bi-search me-1"></i>Sin resultados para la búsqueda
</div>

@endif {{-- fin isset($asignacion) --}}

{{-- ══════════════════════════════════════════════════════════════════════
     EDITOR FLOTANTE (popup de selección 1-4)
════════════════════════════════════════════════════════════════════════ --}}
<div class="cell-editor-wrap" id="cellEditorWrap">
    <div class="choice-popup">
        <div class="choice-label" id="editorLabel">Indicador</div>
        <div class="choices-row">
            <button class="choice-btn c1" onclick="elegirValor(1)" title="1 — Inicial">1</button>
            <button class="choice-btn c2" onclick="elegirValor(2)" title="2 — En proceso">2</button>
            <button class="choice-btn c3" onclick="elegirValor(3)" title="3 — Logrado">3</button>
            <button class="choice-btn c4" onclick="elegirValor(4)" title="4 — Avanzado">4</button>
            <button class="choice-btn del" onclick="elegirValor(null)" title="Borrar valor">
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

{{-- Overlay guardando --}}
<div class="saving-overlay" id="savingOverlay">
    <div class="saving-box">
        <div class="spinner-border text-primary" style="width:1.5rem;height:1.5rem;"></div>
        Guardando todas las notas…
    </div>
</div>

@push('scripts')
<script>
// ════════════════════════════════════════════════════════════════════════
// CONFIGURACIÓN
// ════════════════════════════════════════════════════════════════════════
const CSRF       = '{{ csrf_token() }}';
const URL_SAVE   = '{{ route('admin.registro.guardar') }}';
const URL_BATCH  = '{{ route('admin.registro.guardar-lote') }}';
const CERRADO    = {{ $periodo->cerrado ? 'true' : 'false' }};
const SY_ID      = {{ $schoolYear->id }};
const ASIG_ID    = {{ isset($asignacion) ? $asignacion->id : 0 }};
const PER_ID     = {{ isset($periodo) ? $periodo->id : 0 }};

// Mapa de celdas con cambios pendientes: key → {td, valor}
const pendientes = new Map();
let celdaActiva  = null;
let toastTimer   = null;

// ════════════════════════════════════════════════════════════════════════
// ABRIR / CERRAR EDITOR
// ════════════════════════════════════════════════════════════════════════
function abrirEditor(td) {
    if (CERRADO) return;
    cerrarEditor();
    celdaActiva = td;

    const wrap = document.getElementById('cellEditorWrap');
    const rect = td.getBoundingClientRect();

    // Marcar botón activo según valor actual
    const valActual = td.dataset.val ? parseInt(td.dataset.val) : null;
    document.querySelectorAll('.choice-btn').forEach(b => b.classList.remove('selected'));
    if (valActual) {
        document.querySelector(`.choice-btn.c${valActual}`)?.classList.add('selected');
    }

    // Etiqueta
    document.getElementById('editorLabel').textContent =
        td.dataset.tipo === 'indicador' ? 'IL — escala 1 a 4' : 'CE — escala 1 a 4';

    // Posicionamiento inteligente
    const winH = window.innerHeight;
    const popH = 95;
    let top  = rect.bottom + window.scrollY + 4;
    let left = rect.left  + window.scrollX - 10;

    if (rect.bottom + popH > winH) {
        top = rect.top + window.scrollY - popH - 4;
    }
    if (left + 230 > window.innerWidth) {
        left = window.innerWidth - 240;
    }

    wrap.style.top    = top  + 'px';
    wrap.style.left   = left + 'px';
    wrap.style.display = 'block';

    td.classList.add('editando');
}

function cerrarEditor() {
    document.getElementById('cellEditorWrap').style.display = 'none';
    celdaActiva?.classList.remove('editando');
    celdaActiva = null;
}

// ════════════════════════════════════════════════════════════════════════
// ELEGIR VALOR (1-4 o null)
// ════════════════════════════════════════════════════════════════════════
async function elegirValor(val) {
    if (!celdaActiva || CERRADO) return;
    const td = celdaActiva;
    cerrarEditor();

    const valAnterior = td.dataset.val ? parseInt(td.dataset.val) : null;
    if (val === valAnterior) return; // sin cambio

    // Actualizar UI inmediatamente (optimistic)
    actualizarCeldaUI(td, val);

    // Guardar via AJAX
    try {
        const body = {
            matricula_id  : parseInt(td.dataset.matricula),
            asignacion_id : parseInt(td.dataset.asignacion),
            periodo_id    : parseInt(td.dataset.periodo),
            school_year_id: SY_ID,
            tipo          : td.dataset.tipo,
            referencia_id : parseInt(td.dataset.ref),
            valor         : val,
            _token        : CSRF,
        };

        const res  = await fetch(URL_SAVE, {
            method : 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body   : JSON.stringify(body),
        });
        const json = await res.json();

        if (!json.ok) throw new Error(json.message ?? 'Error del servidor');

        mostrarToast('✓ Guardado', 'ok');
        recalcularPromediosUI(td.dataset.matricula);

    } catch(e) {
        // Revertir UI
        actualizarCeldaUI(td, valAnterior);
        mostrarToast('Error: ' + e.message, 'err');
    }
}

// ════════════════════════════════════════════════════════════════════════
// ACTUALIZAR UI DE CELDA
// ════════════════════════════════════════════════════════════════════════
function actualizarCeldaUI(td, val) {
    const span = td.querySelector('.badge-val');
    if (!span) return;

    if (val === null || val === '') {
        td.style.background = '#fff';
        span.className = 'badge-val empty';
        span.textContent = '—';
        td.dataset.val  = '';
    } else {
        const v = parseInt(val);
        const colores = { 1:'var(--c1-bg)', 2:'var(--c2-bg)', 3:'var(--c3-bg)', 4:'var(--c4-bg)' };
        td.style.background = colores[v] || '#fff';
        span.className = `badge-val v${v}`;
        span.textContent = v;
        td.dataset.val  = v;
    }
}

// ════════════════════════════════════════════════════════════════════════
// RECALCULAR PROMEDIOS EN UI (sin recargar)
// ════════════════════════════════════════════════════════════════════════
function recalcularPromediosUI(matriculaId) {
    // Recargar la fila vía fetch para actualizar promedios CE y general
    // Solución simple: recargar la página silenciosamente solo si el usuario lo pide
    // Por ahora actualizamos visualmente leyendo las celdas del DOM
    const fila = document.querySelector(`tr[data-nombre]`);
    // Buscar todas las celdas de esta matrícula
    const celdas = document.querySelectorAll(`td[data-matricula="${matriculaId}"]`);
    let allVals = [];

    celdas.forEach(td => {
        const v = td.dataset.val ? parseFloat(td.dataset.val) : null;
        if (v !== null && v > 0) allVals.push(v);
    });

    const prom = allVals.length
        ? parseFloat((allVals.reduce((a,b)=>a+b,0)/allVals.length).toFixed(2))
        : null;

    const tdProm = document.querySelector(`td[data-prom-gen="${matriculaId}"]`);
    if (tdProm) {
        if (prom !== null) {
            const colores = {bg: prom>=3.5?'var(--c4-bg)':prom>=2.5?'var(--c3-bg)':prom>=1.5?'var(--c2-bg)':'var(--c1-bg)',
                             tx: prom>=3.5?'var(--c4-txt)':prom>=2.5?'var(--c3-txt)':prom>=1.5?'var(--c2-txt)':'var(--c1-txt)'};
            tdProm.style.background = colores.bg;
            tdProm.style.color      = colores.tx;
            tdProm.innerHTML = `<strong>${prom.toFixed(1)}</strong>`;
        } else {
            tdProm.innerHTML = `<span style="color:#94a3b8;">—</span>`;
        }
    }
}

// ════════════════════════════════════════════════════════════════════════
// GUARDAR TODO (batch)
// ════════════════════════════════════════════════════════════════════════
async function guardarTodo() {
    if (CERRADO) return;

    // Recoger TODOS los valores del período actual
    const celdas = document.querySelectorAll('td[data-matricula]');
    const lote   = [];

    celdas.forEach(td => {
        const val = td.dataset.val;
        if (val !== '' && val !== undefined && val !== null) {
            lote.push({
                matricula_id  : parseInt(td.dataset.matricula),
                asignacion_id : parseInt(td.dataset.asignacion),
                periodo_id    : parseInt(td.dataset.periodo),
                school_year_id: SY_ID,
                tipo          : td.dataset.tipo,
                referencia_id : parseInt(td.dataset.ref),
                valor         : parseFloat(val),
            });
        }
    });

    if (lote.length === 0) {
        mostrarToast('No hay notas para guardar', 'info');
        return;
    }

    document.getElementById('savingOverlay').classList.add('show');

    try {
        const res  = await fetch(URL_BATCH, {
            method : 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body   : JSON.stringify({ evaluaciones: lote }),
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.message ?? 'Error');
        mostrarToast(`✓ ${json.guardadas} notas guardadas`, 'ok');
    } catch(e) {
        mostrarToast('Error al guardar: ' + e.message, 'err');
    } finally {
        document.getElementById('savingOverlay').classList.remove('show');
    }
}

// ════════════════════════════════════════════════════════════════════════
// LIMPIAR PERÍODO
// ════════════════════════════════════════════════════════════════════════
async function limpiarPeriodo() {
    if (CERRADO) return;

    const celdas = document.querySelectorAll('td[data-matricula]');
    const tieneDatos = [...celdas].some(td => td.dataset.val !== '');

    if (!tieneDatos) {
        mostrarToast('No hay notas para limpiar en este período', 'info');
        return;
    }

    if (!confirm('¿Borrar todas las notas del período actual para esta materia?\nEsta acción no se puede deshacer.')) return;

    const lote = [];
    celdas.forEach(td => {
        if (td.dataset.val !== '') {
            lote.push({
                matricula_id  : parseInt(td.dataset.matricula),
                asignacion_id : parseInt(td.dataset.asignacion),
                periodo_id    : parseInt(td.dataset.periodo),
                school_year_id: SY_ID,
                tipo          : td.dataset.tipo,
                referencia_id : parseInt(td.dataset.ref),
                valor         : null,
            });
            actualizarCeldaUI(td, null);
        }
    });

    if (lote.length === 0) return;

    // Guardar los nulls
    for (const item of lote) {
        await fetch(URL_SAVE, {
            method : 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body   : JSON.stringify({ ...item, _token: CSRF }),
        });
    }

    mostrarToast(`${lote.length} notas eliminadas`, 'info');
}

// ════════════════════════════════════════════════════════════════════════
// BÚSQUEDA
// ════════════════════════════════════════════════════════════════════════
function filtrarEstudiantes(texto) {
    const q    = texto.toLowerCase().trim();
    const rows = document.querySelectorAll('.reg-row');
    let vis = 0;

    rows.forEach(tr => {
        const nombre = tr.dataset.nombre || '';
        const ok = q === '' || nombre.includes(q);
        tr.classList.toggle('hidden', !ok);
        if (ok) vis++;
    });

    document.getElementById('sinResultados')?.classList.toggle('d-none', vis > 0 || q === '');
}

// ════════════════════════════════════════════════════════════════════════
// NAVEGACIÓN CON TECLADO
// ════════════════════════════════════════════════════════════════════════
function handleCellKey(e, td) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrirEditor(td); }
    if (e.key === 'Escape') cerrarEditor();
    if (e.key === 'Tab') {
        e.preventDefault();
        cerrarEditor();
        // Mover al siguiente campo editable
        const celdas = [...document.querySelectorAll('td[tabindex="0"]')];
        const idx    = celdas.indexOf(td);
        const next   = celdas[e.shiftKey ? idx - 1 : idx + 1];
        next?.focus();
    }
    // Atajo: escribir 1-4 directamente
    if (['1','2','3','4'].includes(e.key) && !CERRADO) {
        e.preventDefault();
        celdaActiva = td;
        elegirValor(parseInt(e.key));
    }
    if (e.key === 'Delete' || e.key === 'Backspace') {
        if (!CERRADO) { celdaActiva = td; elegirValor(null); }
    }
}

// ════════════════════════════════════════════════════════════════════════
// TOAST
// ════════════════════════════════════════════════════════════════════════
function mostrarToast(msg, tipo = 'ok') {
    const t    = document.getElementById('regToast');
    const icon = document.getElementById('toastIcon');
    const span = document.getElementById('toastMsg');

    icon.className = 'bi ' + (tipo==='ok'?'bi-check-circle-fill':tipo==='err'?'bi-x-circle-fill':'bi-info-circle-fill');
    span.textContent = msg;

    t.className = `reg-toast show ${tipo}`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
}

// ════════════════════════════════════════════════════════════════════════
// CERRAR EDITOR AL CLIC AFUERA
// ════════════════════════════════════════════════════════════════════════
document.addEventListener('click', e => {
    const wrap = document.getElementById('cellEditorWrap');
    if (!wrap.contains(e.target) && !e.target.closest('td[data-matricula]')) {
        cerrarEditor();
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarEditor();
});

// ════════════════════════════════════════════════════════════════════════
// SELECTOR DE GRUPO — redirige al cambiar y refresca materias
// ════════════════════════════════════════════════════════════════════════
const GRUPO_ASIGNACIONES = {!! $grupoAsignaciones ?? '{}' !!};

document.addEventListener('DOMContentLoaded', () => {
    const selGrupo    = document.getElementById('selGrupo');
    const selAsig     = document.getElementById('selAsignacion');
    const formSel     = document.getElementById('formSelector');

    if (selGrupo && selAsig && formSel) {
        selGrupo.addEventListener('change', () => {
            const opt = selGrupo.options[selGrupo.selectedIndex];
            if (!opt || !opt.value) return;

            const url = opt.dataset.url;
            const grupoId = parseInt(opt.value);

            // Actualizar action del form
            formSel.action = url;

            // Refrescar select de materias
            const ags = GRUPO_ASIGNACIONES[grupoId] ?? [];
            selAsig.innerHTML = '<option value="">— Seleccionar materia —</option>';
            ags.forEach(a => {
                const o = document.createElement('option');
                o.value = a.id;
                o.textContent = a.label;
                selAsig.appendChild(o);
            });
        });
    }

    // Enfocar buscador con Ctrl+F
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            const b = document.getElementById('buscador');
            if (b) { e.preventDefault(); b.focus(); b.select(); }
        }
    });
});
</script>
@endpush
