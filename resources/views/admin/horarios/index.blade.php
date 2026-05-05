@extends('layouts.admin')

@section('page-title', 'Horarios')

@push('styles')
<style>
    /* ── Page layout ──────────────────────────────────────── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.75rem;
    }
    .page-header-title {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .55rem;
    }
    .year-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: var(--accent-light);
        color: #92400e;
        border: 1px solid #fde68a;
        border-radius: 20px;
        padding: .22rem .75rem;
        font-size: .74rem;
        font-weight: 700;
    }

    /* ── Generate card ────────────────────────────────────── */
    .generate-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 1px 6px rgba(30,58,110,.06);
        margin-bottom: 1.75rem;
        overflow: hidden;
    }
    .generate-card-header {
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        padding: .9rem 1.4rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        color: #fff;
    }
    .generate-card-header h6 {
        margin: 0;
        font-size: .85rem;
        font-weight: 700;
        letter-spacing: .04em;
    }
    .generate-card-body {
        padding: 1.1rem 1.4rem;
    }

    /* ── Table card ───────────────────────────────────────── */
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 1px 6px rgba(30,58,110,.06);
        overflow: hidden;
    }
    .table-card-header {
        padding: .8rem 1.2rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .table-card-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .71rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #6b7280;
        padding: .75rem 1rem;
        white-space: nowrap;
    }
    .table tbody td {
        padding: .72rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
        color: #374151;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }

    /* ── Badges ───────────────────────────────────────────── */
    .badge-publicado {
        background: #d1fae5;
        color: #065f46;
        border-radius: 20px;
        padding: .25rem .65rem;
        font-size: .72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .badge-borrador {
        background: #fef3c7;
        color: #92400e;
        border-radius: 20px;
        padding: .25rem .65rem;
        font-size: .72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .badge-score {
        background: #eff6ff;
        color: var(--primary);
        border: 1px solid #dbeafe;
        border-radius: 20px;
        padding: .2rem .6rem;
        font-size: .73rem;
        font-weight: 700;
    }

    /* ── Action buttons ───────────────────────────────────── */
    .btn-action {
        padding: .24rem .55rem;
        font-size: .76rem;
        border-radius: 7px;
        line-height: 1.4;
        font-weight: 600;
    }

    /* ── Empty state ──────────────────────────────────────── */
    .empty-state {
        text-align: center;
        padding: 3.5rem 2rem;
        color: #9ca3af;
    }
    .empty-state i {
        font-size: 2.8rem;
        display: block;
        margin-bottom: .85rem;
        color: #d1d5db;
    }

    /* ── Spinner on submit ────────────────────────────────── */
    .btn-generate .spinner-border {
        width: 1rem;
        height: 1rem;
        border-width: .15em;
    }

    /* ── Full-page generation overlay ────────────────────── */
    #generatingOverlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gen-box {
        background: #fff;
        border-radius: 18px;
        padding: 2.25rem 2.5rem;
        text-align: center;
        box-shadow: 0 24px 70px rgba(0,0,0,.3);
        max-width: 440px;
        width: 94%;
    }
    .gen-spinner {
        width: 3rem;
        height: 3rem;
        border-width: .3em;
        color: var(--primary);
        margin-bottom: .75rem;
    }
    .gen-step {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: .3rem;
    }
    .gen-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0 0 .35rem;
    }
    .gen-sub {
        font-size: .81rem;
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
    }

    /* ── Result panel (post-generation, replaces generate card) ─ */
    .result-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 1px 6px rgba(30,58,110,.06);
        margin-bottom: 1.75rem;
        overflow: hidden;
    }
    .result-header-ok  { background: linear-gradient(90deg,#065f46,#059669); padding:.85rem 1.4rem; color:#fff; }
    .result-header-warn{ background: linear-gradient(90deg,#92400e,#d97706); padding:.85rem 1.4rem; color:#fff; }
    .result-header-err { background: linear-gradient(90deg,#991b1b,#dc2626); padding:.85rem 1.4rem; color:#fff; }
    .result-header-ok h6, .result-header-warn h6, .result-header-err h6 {
        margin:0; font-size:.88rem; font-weight:800;
    }
    .result-body { padding: 1.1rem 1.4rem; }

    /* ── Validation warnings ──────────────────────────────── */
    .warn-list { list-style: none; padding: 0; margin: 0; }
    .warn-list li { font-size:.82rem; color:#92400e; margin-bottom:.3rem; }
    .warn-list li::before { content:'⚠ '; }

    /* ── Debug panel ──────────────────────────────────────── */
    .debug-panel {
        background: #0f172a;
        color: #7dd3fc;
        border-radius: 10px;
        padding: 1rem 1.2rem;
        font-family: 'Courier New', monospace;
        font-size: .73rem;
        max-height: 260px;
        overflow-y: auto;
        margin-top: .75rem;
    }
    .debug-panel .dline { border-bottom: 1px solid #1e293b; padding: .15rem 0; }
    .debug-panel .dts   { color: #475569; margin-right: .5rem; }

    [data-theme="dark"] .generate-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .year-badge { background: #1c1000; color: #fcd34d; border-color: #78350f; }
    [data-theme="dark"] .result-body { color: #cbd5e1; }
    [data-theme="dark"] .warn-list li { color: #fcd34d; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios',  'url' => ''],
]" />

{{-- ── Page header ─────────────────────────────────────────────────────── --}}
<div class="page-header">
    <div>
        <h1 class="page-header-title">
            <i class="bi bi-calendar3-week"></i>Horarios
        </h1>
        <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
            @if($schoolYear)
                <span class="year-badge">
                    <i class="bi bi-mortarboard-fill"></i>{{ $schoolYear->nombre }}
                </span>
            @else
                <span class="year-badge" style="background:#f3f4f6;color:#6b7280;border-color:#e5e7eb;">
                    <i class="bi bi-exclamation-circle"></i>Sin año escolar activo
                </span>
            @endif
            <span style="font-size:.8rem;color:#9ca3af;">{{ $horarios->count() }} horario{{ $horarios->count() !== 1 ? 's' : '' }} generado{{ $horarios->count() !== 1 ? 's' : '' }}</span>
        </div>
    </div>
</div>

{{-- ── Session alerts ──────────────────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"
         role="alert" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-0"
         role="alert" style="border-radius:10px 10px 0 0;font-size:.85rem;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3"
         role="alert" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Conflictos no resueltos ──────────────────────────────────────────── --}}
@if(session('conflictos'))
@php $conflictos = session('conflictos'); @endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:0 0 10px 10px;border-top:3px solid #f59e0b!important;">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span style="font-size:.78rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em;">
                <i class="bi bi-exclamation-circle-fill me-1"></i>
                {{ count($conflictos) }} conflicto(s) sin resolver
            </span>
            <button class="btn btn-sm" style="font-size:.75rem;background:#fef3c7;color:#92400e;border-radius:7px;"
                    data-bs-toggle="collapse" data-bs-target="#detalleConflictos">
                <i class="bi bi-chevron-down me-1"></i>Ver detalle
            </button>
        </div>
        <div class="collapse" id="detalleConflictos">
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.78rem;">
                    <thead style="background:#fef3c7;">
                        <tr>
                            <th style="padding:.4rem .6rem;color:#92400e;">Grupo</th>
                            <th style="padding:.4rem .6rem;color:#92400e;">Materia</th>
                            <th style="padding:.4rem .6rem;color:#92400e;">Docente</th>
                            <th style="padding:.4rem .6rem;color:#92400e;">Razón</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conflictos as $c)
                        <tr>
                            <td style="padding:.35rem .6rem;">{{ $c['grupo'] }}</td>
                            <td style="padding:.35rem .6rem;">{{ $c['materia'] }}</td>
                            <td style="padding:.35rem .6rem;">{{ $c['docente'] }}</td>
                            <td style="padding:.35rem .6rem;color:#9ca3af;">{{ $c['razon'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted mb-0 mt-2" style="font-size:.75rem;">
                <i class="bi bi-lightbulb me-1"></i>
                Sugerencia: verifica disponibilidad de docentes o aumenta las franjas horarias disponibles y <strong>reintenta</strong>.
            </p>
        </div>
    </div>
</div>
@endif

{{-- ── Generation overlay ───────────────────────────────────────────────── --}}
<div id="generatingOverlay" style="display:none;">
    <div class="gen-box">
        <div class="spinner-border gen-spinner" role="status"></div>
        <p class="gen-step" id="genStepLabel">Paso 1 de 3</p>
        <p class="gen-title" id="genTitle">Validando datos…</p>
        <p class="gen-sub" id="genSub">Comprobando cursos, franjas, aulas y asignaciones.</p>
    </div>
</div>

{{-- ── Result panel (rendered by JS after generation) ────────────────────── --}}
<div id="resultPanel" style="display:none;"></div>

{{-- ── Generate form ────────────────────────────────────────────────────── --}}
<div class="generate-card">
    <div class="generate-card-header">
        <i class="bi bi-cpu-fill" style="font-size:1.1rem;opacity:.9;"></i>
        <h6>Generar nuevo horario</h6>
        <span style="font-size:.72rem;opacity:.75;font-weight:400;margin-left:.5rem;">
            Algoritmo: Backtracking + Heurísticas (MRV, Forward Checking)
        </span>
    </div>
    <div class="generate-card-body">
        <form id="formGenerar">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label for="nombre" class="form-label fw-semibold mb-1" style="font-size:.8rem;">
                        Nombre del horario
                        <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="form-control form-control-sm"
                        placeholder="Ej. Horario Semestre I — 2026"
                        maxlength="120"
                        style="border-radius:8px;"
                    >
                </div>
                @if($grupos->isNotEmpty())
                <div class="col-md-4 col-lg-4">
                    <label for="grupo_ids" class="form-label fw-semibold mb-1" style="font-size:.8rem;">
                        Grupos
                        <span class="text-muted fw-normal">(vacío = todos)</span>
                    </label>
                    <select id="grupo_ids" name="grupo_ids[]" class="form-select form-select-sm"
                            multiple style="border-radius:8px;min-height:36px;">
                        @foreach($grupos as $g)
                            <option value="{{ $g->id }}">
                                {{ $g->nombre_completo ?? ($g->grado?->nombre . ' ' . $g->seccion?->nombre) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:.72rem;">Ctrl+clic para seleccionar varios</div>
                </div>
                @endif
                <div class="col d-flex align-items-end gap-2 flex-wrap">
                    <button type="submit" id="btnGenerar" class="btn fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1.2rem;font-size:.88rem;">
                        <i class="bi bi-magic me-1"></i>Generar Horario
                    </button>
                    <p class="text-muted mb-0" style="font-size:.75rem;line-height:1.4;">
                        <i class="bi bi-info-circle me-1"></i>
                        Hasta 3 intentos automáticos. Se guarda el mejor score.
                    </p>
                </div>
            </div>
            <div id="genError" class="alert alert-danger mt-3 mb-0 py-2 d-none" style="border-radius:8px;font-size:.84rem;"></div>
        </form>
    </div>
</div>

{{-- ── Horarios table ───────────────────────────────────────────────────── --}}
@if($horarios->isEmpty())
    <div class="table-card">
        <div class="empty-state">
            <i class="bi bi-calendar3-week"></i>
            <h6 class="fw-semibold mb-1" style="color:#6b7280;">No hay horarios generados</h6>
            <p class="mb-0" style="font-size:.84rem;">
                Usa el formulario de arriba para generar el primer horario del año escolar.
            </p>
        </div>
    </div>
@else
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">
                <i class="bi bi-list-ul"></i>Horarios existentes
            </span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Puntaje</th>
                        <th>Creado por</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($horarios as $horario)
                    <tr>
                        {{-- Nombre --}}
                        <td>
                            <a href="{{ route('admin.horarios.show', $horario) }}"
                               class="fw-semibold text-decoration-none"
                               style="color:#111827;">
                                {{ $horario->nombre ?? 'Horario #' . $horario->id }}
                            </a>
                        </td>

                        {{-- Estado --}}
                        <td class="text-center">
                            @if($horario->estado === 'publicado')
                                <span class="badge-publicado">
                                    <i class="bi bi-check-circle-fill"></i>Publicado
                                </span>
                            @else
                                <span class="badge-borrador">
                                    <i class="bi bi-pencil-square"></i>Borrador
                                </span>
                            @endif
                        </td>

                        {{-- Score --}}
                        <td class="text-center">
                            @if($horario->score !== null)
                                <span class="badge-score">
                                    {{ number_format($horario->score, 1) }}&thinsp;%
                                </span>
                            @else
                                <span class="text-muted" style="font-size:.78rem;">—</span>
                            @endif
                        </td>

                        {{-- Creado por --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="
                                    width:28px; height:28px; border-radius:50%;
                                    background:linear-gradient(135deg,var(--primary),var(--primary-light));
                                    color:#fff; font-size:.65rem; font-weight:800;
                                    display:inline-flex; align-items:center; justify-content:center;
                                    flex-shrink:0;
                                ">
                                    {{ strtoupper(substr($horario->creador->name ?? 'S', 0, 1)) }}
                                </div>
                                <span style="font-size:.83rem;color:#374151;">
                                    {{ $horario->creador->name ?? '—' }}
                                </span>
                            </div>
                        </td>

                        {{-- Fecha --}}
                        <td>
                            <span style="font-size:.8rem;color:#6b7280;" title="{{ $horario->created_at->format('d/m/Y H:i') }}">
                                {{ $horario->created_at->diffForHumans() }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                {{-- Ver --}}
                                <a href="{{ route('admin.horarios.show', $horario) }}"
                                   class="btn btn-action"
                                   style="background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;"
                                   title="Ver horario">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </a>

                                {{-- Publicar / Despublicar --}}
                                <form action="{{ route('admin.horarios.publicar', $horario) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if($horario->estado === 'publicado')
                                        <button type="submit" class="btn btn-action"
                                                style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;"
                                                title="Despublicar horario"
                                                onclick="return confirm('¿Despublicar este horario? Dejará de estar visible.')">
                                            <i class="bi bi-eye-slash me-1"></i>Despublicar
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-action"
                                                style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;"
                                                title="Publicar horario"
                                                onclick="return confirm('¿Publicar este horario? Será visible para docentes y estudiantes.')">
                                            <i class="bi bi-send-check me-1"></i>Publicar
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ── Config quick links ───────────────────────────────────────────────── --}}
<div class="d-flex gap-2 flex-wrap mt-4">
    <span style="font-size:.78rem;color:#9ca3af;align-self:center;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">
        Configuración:
    </span>
    <a href="{{ route('admin.horarios.aulas') }}"
       class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center gap-1"
       style="border-radius:8px;font-size:.8rem;">
        <i class="bi bi-door-open" style="color:#6366f1;"></i>Aulas
    </a>
    <a href="{{ route('admin.horarios.franjas') }}"
       class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center gap-1"
       style="border-radius:8px;font-size:.8rem;">
        <i class="bi bi-clock-history" style="color:#0891b2;"></i>Franjas horarias
    </a>
    <a href="{{ route('admin.horarios.disponibilidad') }}"
       class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center gap-1"
       style="border-radius:8px;font-size:.8rem;">
        <i class="bi bi-person-check" style="color:#059669;"></i>Disponibilidad docentes
    </a>
    <a href="{{ route('admin.horarios.suplencias') }}"
       class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center gap-1"
       style="border-radius:8px;font-size:.8rem;">
        <i class="bi bi-person-fill-exclamation" style="color:#dc2626;"></i>Suplencias
    </a>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const form        = document.getElementById('formGenerar');
    const btnGenerar  = document.getElementById('btnGenerar');
    const overlay     = document.getElementById('generatingOverlay');
    const resultPanel = document.getElementById('resultPanel');
    const genStep     = document.getElementById('genStepLabel');
    const genTitle    = document.getElementById('genTitle');
    const genSub      = document.getElementById('genSub');
    const genErrBox   = document.getElementById('genError');

    // ── Helpers ────────────────────────────────────────────────────────────
    function setOverlayStep(step, title, sub) {
        genStep.textContent  = step;
        genTitle.textContent = title;
        genSub.textContent   = sub;
    }

    function showOverlay()  { overlay.style.display = 'flex'; }
    function hideOverlay()  { overlay.style.display = 'none';  }

    function resetBtn() {
        btnGenerar.disabled = false;
        btnGenerar.innerHTML = '<i class="bi bi-magic me-1"></i>Generar Horario';
    }

    function showInlineError(msg) {
        genErrBox.textContent = msg;
        genErrBox.classList.remove('d-none');
    }

    // ── Build result panel HTML ─────────────────────────────────────────────
    function renderResultPanel(json) {
        const pendientes = json.pendientes ?? 0;
        const score      = json.score ?? 0;
        const integridad = json.integridad ?? {};
        const warnings   = json.advertencias ?? [];
        const debug      = json.debug ?? [];

        // Header color
        let headerClass, icon, titulo;
        if (pendientes === 0 && integridad.valido !== false) {
            headerClass = 'result-header-ok';
            icon        = 'bi-check-circle-fill';
            titulo      = '¡Horario generado correctamente!';
        } else if (pendientes > 0 || integridad.violaciones?.length > 0) {
            headerClass = 'result-header-warn';
            icon        = 'bi-exclamation-triangle-fill';
            titulo      = `Horario generado con ${pendientes} conflicto(s) sin resolver`;
        } else {
            headerClass = 'result-header-ok';
            icon        = 'bi-check-circle-fill';
            titulo      = 'Horario generado';
        }

        // Score badge
        const scoreColor = score >= 90 ? '#16a34a' : score >= 70 ? '#d97706' : '#dc2626';

        // Integrity violations
        let integrityHtml = '';
        if ((integridad.violaciones ?? []).length > 0) {
            const vItems = integridad.violaciones.map(v =>
                `<li class="mb-1" style="font-size:.8rem;">`
                + `<span class="badge me-1" style="background:${v.severidad==='critico'?'#fee2e2':'#fef3c7'};color:${v.severidad==='critico'?'#991b1b':'#92400e'};border-radius:6px;font-size:.7rem;">${v.severidad}</span>`
                + escapeHtml(v.mensaje)
                + `</li>`
            ).join('');
            integrityHtml = `
                <div class="mt-3 pt-3" style="border-top:1px solid #f3f4f6;">
                    <p style="font-size:.75rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;">
                        <i class="bi bi-bug-fill me-1"></i>Problemas de integridad detectados (${integridad.violaciones.length})
                    </p>
                    <ul class="ps-0" style="list-style:none;">${vItems}</ul>
                </div>`;
        }

        // Warnings
        let warningsHtml = '';
        if (warnings.length > 0) {
            const wItems = warnings.map(w => `<li>${escapeHtml(w)}</li>`).join('');
            warningsHtml = `
                <div class="mt-2">
                    <ul class="warn-list">${wItems}</ul>
                </div>`;
        }

        // Conflicts table
        let conflictsHtml = '';
        const conflictos = json.conflictos ?? [];
        if (conflictos.length > 0) {
            const rows = conflictos.map(c =>
                `<tr>
                    <td style="padding:.3rem .6rem;font-size:.78rem;">${escapeHtml(c.grupo??'')}</td>
                    <td style="padding:.3rem .6rem;font-size:.78rem;">${escapeHtml(c.materia??'')}</td>
                    <td style="padding:.3rem .6rem;font-size:.78rem;">${escapeHtml(c.docente??'')}</td>
                    <td style="padding:.3rem .6rem;font-size:.78rem;color:#9ca3af;">${escapeHtml(c.razon??'')}</td>
                </tr>`
            ).join('');
            conflictsHtml = `
                <div class="mt-3 pt-3" style="border-top:1px solid #f3f4f6;">
                    <button class="btn btn-sm mb-2" style="font-size:.75rem;background:#fef3c7;color:#92400e;border-radius:7px;"
                            data-bs-toggle="collapse" data-bs-target="#conflictosDetalle">
                        <i class="bi bi-chevron-down me-1"></i>Ver ${conflictos.length} conflicto(s)
                    </button>
                    <div class="collapse" id="conflictosDetalle">
                        <table class="table table-sm mb-0">
                            <thead style="background:#fef3c7;">
                                <tr>
                                    <th style="padding:.35rem .6rem;color:#92400e;font-size:.72rem;">Grupo</th>
                                    <th style="padding:.35rem .6rem;color:#92400e;font-size:.72rem;">Materia</th>
                                    <th style="padding:.35rem .6rem;color:#92400e;font-size:.72rem;">Docente</th>
                                    <th style="padding:.35rem .6rem;color:#92400e;font-size:.72rem;">Razón</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>`;
        }

        // Debug panel
        let debugHtml = '';
        if (debug.length > 0) {
            const lines = debug.map(d =>
                `<div class="dline"><span class="dts">${escapeHtml(d.ts??'')}</span>${escapeHtml(d.msg??'')}${d.ctx?(' — '+escapeHtml(JSON.stringify(d.ctx))):''}
                </div>`
            ).join('');
            debugHtml = `
                <div class="mt-3 pt-3" style="border-top:1px solid #f3f4f6;">
                    <p style="font-size:.73rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;">
                        <i class="bi bi-terminal-fill me-1"></i>Registro de depuración (${debug.length} entradas)
                    </p>
                    <div class="debug-panel">${lines}</div>
                </div>`;
        }

        // Integrity checks summary
        const checksOk = integridad.checks_pasados ?? 0;
        const chksTotal = integridad.checks_total ?? 4;
        const checksColor = checksOk === chksTotal ? '#16a34a' : '#d97706';

        return `
        <div class="result-panel">
            <div class="${headerClass} d-flex align-items-center gap-2">
                <i class="bi ${icon}" style="font-size:1.1rem;"></i>
                <h6>${titulo}</h6>
            </div>
            <div class="result-body">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div style="text-align:center;">
                        <div style="font-size:1.9rem;font-weight:900;color:${scoreColor};line-height:1;">${score}%</div>
                        <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Puntaje</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:1.4rem;font-weight:800;color:#111827;line-height:1;">${json.asignados??0}</div>
                        <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Clases</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:1.4rem;font-weight:800;color:${pendientes>0?'#dc2626':'#16a34a'};line-height:1;">${pendientes}</div>
                        <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Conflictos</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:1.1rem;font-weight:700;color:${checksColor};line-height:1;">${checksOk}/${chksTotal}</div>
                        <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">Integridad</div>
                    </div>
                    <div class="d-flex gap-2 ms-auto flex-wrap align-items-center">
                        <a href="${json.redirect??'#'}" class="btn btn-sm fw-semibold"
                           style="background:var(--primary);color:#fff;border-radius:8px;">
                            <i class="bi bi-eye me-1"></i>Ver horario
                        </a>
                        ${pendientes > 0 || (integridad.violaciones??[]).length > 0 ? `
                        <button onclick="reintentar()" class="btn btn-sm fw-semibold"
                                style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:8px;">
                            <i class="bi bi-arrow-repeat me-1"></i>Reintentar
                        </button>` : ''}
                        <button onclick="mostrarFormGenerar()" class="btn btn-sm"
                                style="background:#f3f4f6;color:#374151;border-radius:8px;">
                            <i class="bi bi-plus me-1"></i>Generar otro
                        </button>
                    </div>
                </div>
                ${warningsHtml}
                ${integrityHtml}
                ${conflictsHtml}
                ${debugHtml}
            </div>
        </div>`;
    }

    // ── Build error panel ───────────────────────────────────────────────────
    function renderErrorPanel(json) {
        const errores    = json.errores ?? [json.error ?? 'Error desconocido'];
        const sugerencias = json.sugerencias ?? [];
        const stats      = json.stats ?? {};

        const eItems = errores.map(e =>
            `<li class="mb-2" style="font-size:.84rem;color:#991b1b;">
                <i class="bi bi-x-circle-fill me-1" style="color:#dc2626;"></i>${escapeHtml(e)}
            </li>`
        ).join('');

        const sItems = sugerencias.map(s =>
            `<li style="font-size:.8rem;color:#374151;">
                <i class="bi bi-lightbulb-fill me-1" style="color:#d97706;"></i>${escapeHtml(s)}
            </li>`
        ).join('');

        let statsHtml = '';
        if (Object.keys(stats).length > 0) {
            const sRows = Object.entries(stats).map(([k, v]) =>
                `<span class="me-3" style="font-size:.75rem;color:#6b7280;"><strong>${escapeHtml(String(k))}:</strong> ${escapeHtml(String(v))}</span>`
            ).join('');
            statsHtml = `<div class="mt-2 pt-2" style="border-top:1px solid #fecaca;">${sRows}</div>`;
        }

        return `
        <div class="result-panel">
            <div class="result-header-err d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill" style="font-size:1.1rem;"></i>
                <h6>No se pudo generar el horario</h6>
            </div>
            <div class="result-body">
                <ul class="ps-0 mb-3" style="list-style:none;">${eItems}</ul>
                ${sugerencias.length > 0 ? `
                <p style="font-size:.73rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;">
                    <i class="bi bi-lightbulb me-1"></i>Sugerencias
                </p>
                <ul class="ps-0 mb-0" style="list-style:none;">${sItems}</ul>` : ''}
                ${statsHtml}
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button onclick="mostrarFormGenerar()" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-arrow-left me-1"></i>Volver al formulario
                    </button>
                </div>
            </div>
        </div>`;
    }

    // ── Form state management ───────────────────────────────────────────────
    const generateCard = document.querySelector('.generate-card');

    function mostrarFormGenerar() {
        resultPanel.style.display = 'none';
        resultPanel.innerHTML = '';
        generateCard.style.display = '';
        resetBtn();
    }

    // Allow "Reintentar" to re-submit (window-scoped for onclick)
    window.reintentar = function () {
        resultPanel.style.display = 'none';
        resultPanel.innerHTML = '';
        generateCard.style.display = '';
        resetBtn();
        // Trigger submit automatically
        setTimeout(() => form.dispatchEvent(new Event('submit')), 100);
    };

    window.mostrarFormGenerar = mostrarFormGenerar;

    // ── Escape HTML ─────────────────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Main form submit ────────────────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        genErrBox.classList.add('d-none');
        btnGenerar.disabled = true;
        btnGenerar.innerHTML = '<span class="spinner-border" style="width:.85rem;height:.85rem;border-width:.15em;" role="status"></span> Procesando…';

        generateCard.style.display = 'none';
        resultPanel.style.display  = 'none';
        resultPanel.innerHTML      = '';

        // Step 1: validating
        setOverlayStep('Paso 1 de 3', 'Validando datos…', 'Comprobando cursos, franjas horarias, aulas y asignaciones.');
        showOverlay();

        // Slight delay so the UI renders the overlay before the heavy request
        await new Promise(r => setTimeout(r, 80));

        // Step 2: generating
        setOverlayStep('Paso 2 de 3', 'Ejecutando algoritmo…', 'Backtracking + heurísticas (MRV). Hasta 3 intentos automáticos.');

        const data = new FormData(form);

        try {
            const resp = await fetch('{{ route("admin.horarios.generar") }}', {
                method : 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept'      : 'application/json',
                },
                body: data,
            });

            const json = await resp.json();
            hideOverlay();

            if (!resp.ok || json.error) {
                // ── Error / validation failure ──────────────────────────────
                resultPanel.innerHTML = renderErrorPanel(json);
                resultPanel.style.display = '';
                return;
            }

            // Step 3: done
            // ── Success: redirect to show page so CSRF token is fresh ───────
            if (json.redirect) {
                setOverlayStep('Listo', 'Redirigiendo al horario…', '100% completado');
                setTimeout(() => { window.location.href = json.redirect; }, 800);
                return;
            }
            resultPanel.innerHTML = renderResultPanel(json);
            resultPanel.style.display = '';

        } catch (err) {
            hideOverlay();
            generateCard.style.display = '';
            resetBtn();
            showInlineError('Error de red. Verifica la conexión e intenta de nuevo.');
        }
    });

})();
</script>
@endpush
