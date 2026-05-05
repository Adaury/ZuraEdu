@extends('layouts.admin')

@section('page-title', ($horario->nombre ?? 'Horario #'.$horario->id) . ' — Horario')

@push('styles')
<style>
    /* ── Page header ──────────────────────────────────────── */
    .horario-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .horario-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .badge-estado-publicado {
        background: #d1fae5;
        color: #065f46;
        border-radius: 20px;
        padding: .22rem .65rem;
        font-size: .72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .badge-estado-borrador {
        background: #fef3c7;
        color: #92400e;
        border-radius: 20px;
        padding: .22rem .65rem;
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
        padding: .22rem .65rem;
        font-size: .72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: .28rem;
    }

    /* ── Filter bar ───────────────────────────────────────── */
    .filter-bar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: .8rem 1.1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 4px rgba(30,58,110,.04);
    }
    .filter-bar label {
        font-size: .75rem;
        font-weight: 700;
        color: #6b7280;
        white-space: nowrap;
        margin: 0;
    }

    /* ── Timetable card ───────────────────────────────────── */
    .timetable-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 1px 6px rgba(30,58,110,.06);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .timetable-card-header {
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        padding: .8rem 1.2rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        color: #fff;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .04em;
    }

    /* ── Grid table ───────────────────────────────────────── */
    .timetable-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .timetable {
        width: 100%;
        border-collapse: collapse;
        min-width: 680px;
    }
    .timetable th {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        padding: .55rem .75rem;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #6b7280;
        text-align: center;
        white-space: nowrap;
    }
    .timetable th.col-hora {
        width: 90px;
        text-align: left;
        color: var(--primary);
    }
    .timetable td {
        border: 1px solid #f0f2f5;
        vertical-align: top;
        padding: .35rem;
        min-width: 130px;
        height: 80px;
        transition: background .15s;
    }
    .timetable td.hora-cell {
        background: #f8fafc;
        vertical-align: middle;
        text-align: right;
        padding: .4rem .65rem;
        white-space: nowrap;
    }
    .hora-label {
        font-size: .7rem;
        font-weight: 700;
        color: var(--primary);
        line-height: 1.3;
    }
    .hora-range {
        font-size: .67rem;
        color: #9ca3af;
        font-weight: 500;
    }

    /* ── Recreo row ───────────────────────────────────────── */
    .recreo-row td {
        background: #fef9ef;
        border-color: #fde68a;
        text-align: center;
        padding: .55rem;
        height: auto;
    }
    .recreo-label {
        font-size: .78rem;
        font-weight: 800;
        color: #92400e;
        letter-spacing: .12em;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
    }

    /* ── Detail card (inside cell) ────────────────────────── */
    .detalle-card {
        border-radius: 8px;
        padding: .38rem .5rem;
        height: 100%;
        min-height: 68px;
        cursor: grab;
        border: 1.5px solid transparent;
        transition: box-shadow .15s, transform .1s, border-color .15s;
        display: flex;
        flex-direction: column;
        gap: .18rem;
        position: relative;
        user-select: none;
    }
    .detalle-card:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,.12);
        transform: translateY(-1px);
    }
    .detalle-card.dragging {
        opacity: .55;
        cursor: grabbing;
    }
    .detalle-card .asignatura-name {
        font-size: .76rem;
        font-weight: 800;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .detalle-card .meta-row {
        font-size: .67rem;
        color: inherit;
        opacity: .8;
        display: flex;
        align-items: center;
        gap: .25rem;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .detalle-card .meta-row i {
        flex-shrink: 0;
        font-size: .7rem;
    }

    /* ── Drop target ──────────────────────────────────────── */
    .timetable td.drop-target {
        background: #eff6ff !important;
        border-color: var(--primary) !important;
        box-shadow: inset 0 0 0 2px var(--primary-light);
    }
    .empty-cell-hint {
        height: 100%;
        min-height: 68px;
        border-radius: 7px;
        border: 1.5px dashed #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d1d5db;
        font-size: .7rem;
    }
    .timetable td.drop-target .empty-cell-hint {
        border-color: var(--primary);
        color: var(--primary);
        background: rgba(37,99,235,.04);
    }

    /* ── Cell action buttons ─────────────────────────────── */
    .cell-actions {
        position: absolute;
        top: 3px; right: 3px;
        display: none;
        gap: 3px;
        z-index: 10;
    }
    .detalle-card:hover .cell-actions { display: flex; }
    .cell-btn {
        width: 22px; height: 22px;
        border-radius: 5px;
        border: none;
        display: flex; align-items: center; justify-content: center;
        font-size: .65rem;
        cursor: pointer;
        transition: background .15s;
    }
    .cell-btn-edit   { background: rgba(255,255,255,.85); color: #1d4ed8; }
    .cell-btn-delete { background: rgba(255,255,255,.85); color: #dc2626; }
    .cell-btn:hover  { background: rgba(255,255,255,1); }
    .cell-add-btn {
        height: 100%;
        min-height: 68px;
        border-radius: 7px;
        border: 1.5px dashed #d1d5db;
        display: flex; align-items: center; justify-content: center;
        color: #d1d5db;
        font-size: .7rem;
        width: 100%;
        background: transparent;
        cursor: pointer;
        transition: border-color .15s, color .15s, background .15s;
        gap: .3rem;
    }
    .cell-add-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: rgba(37,99,235,.04);
    }

    /* ── Config quick links ───────────────────────────────── */
    .link-config {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .38rem .8rem;
        border-radius: 9px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #374151;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .15s, border-color .15s, color .15s;
    }
    .link-config:hover {
        background: #eff6ff;
        border-color: var(--primary);
        color: var(--primary);
    }

    /* ── Toast ────────────────────────────────────────────── */
    #toastContainer {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: .5rem;
        pointer-events: none;
    }
    .toast-item {
        background: #1e293b;
        color: #f8fafc;
        border-radius: 10px;
        padding: .7rem 1.1rem;
        font-size: .83rem;
        font-weight: 600;
        box-shadow: 0 4px 20px rgba(0,0,0,.18);
        display: flex;
        align-items: center;
        gap: .55rem;
        pointer-events: auto;
        animation: toastSlideUp .25s ease;
    }
    .toast-item.toast-success { border-left: 4px solid #10b981; }
    .toast-item.toast-error   { border-left: 4px solid #ef4444; }
    @keyframes toastSlideUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    [data-theme="dark"] .badge-estado-publicado { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-estado-borrador { background: #1c1000; color: #fcd34d; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios',  'url' => route('admin.horarios.index')],
    ['label' => $horario->nombre ?? ('Horario #'.$horario->id), 'url' => ''],
]" />

{{-- ── Page header ─────────────────────────────────────────────────────── --}}
<div class="horario-header">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <a href="{{ route('admin.horarios.index') }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
            <h1 class="horario-title">
                <i class="bi bi-calendar3-week"></i>
                {{ $horario->nombre ?? 'Horario #'.$horario->id }}
            </h1>
            @if($horario->estado === 'publicado')
                <span class="badge-estado-publicado">
                    <i class="bi bi-check-circle-fill"></i>Publicado
                </span>
            @else
                <span class="badge-estado-borrador">
                    <i class="bi bi-pencil-square"></i>Borrador
                </span>
            @endif
            @if($horario->score !== null)
                <span class="badge-score">
                    <i class="bi bi-bar-chart-fill"></i>Puntaje: {{ number_format($horario->score, 1) }}&thinsp;%
                </span>
            @endif
        </div>
        <p class="text-muted mb-0" style="font-size:.8rem;padding-left:.25rem;">
            {{ $detalles->count() }} clase{{ $detalles->count() !== 1 ? 's' : '' }} asignada{{ $detalles->count() !== 1 ? 's' : '' }}
        </p>
    </div>

    @unless(Auth::user()->hasRole('Docente'))
    {{-- Publish / Unpublish --}}
    <form action="{{ route('admin.horarios.publicar', $horario) }}" method="POST">
        @csrf
        @if($horario->estado === 'publicado')
            <button type="submit" class="btn btn-sm fw-semibold"
                    style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:9px;"
                    onclick="return confirm('¿Volver a borrador? El horario dejará de ser visible.')">
                <i class="bi bi-eye-slash me-1"></i>Despublicar
            </button>
        @else
            <button type="submit" class="btn btn-sm fw-semibold"
                    style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:9px;"
                    onclick="return confirm('¿Publicar este horario? Será visible para toda la institución.')">
                <i class="bi bi-send-check me-1"></i>Publicar horario
            </button>
        @endif
    </form>

    {{-- Regenerar --}}
    <button id="btnRegenerar" class="btn btn-sm fw-semibold"
            style="background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;border-radius:9px;"
            onclick="regenerarHorario()"
            title="Volver a ejecutar el algoritmo y reemplazar todas las celdas de este horario">
        <span class="btn-regen-label"><i class="bi bi-arrow-repeat me-1"></i>Regenerar</span>
        <span class="btn-regen-spin d-none">
            <span class="spinner-border" style="width:.85rem;height:.85rem;border-width:.15em;" role="status"></span>
            Regenerando…
        </span>
    </button>

    {{-- Limpiar --}}
    <button id="btnLimpiar" class="btn btn-sm fw-semibold"
            style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:9px;"
            onclick="limpiarHorario()"
            title="Vaciar todas las celdas del horario (no elimina el horario)">
        <span class="btn-limpiar-label"><i class="bi bi-trash3 me-1"></i>Limpiar</span>
        <span class="btn-limpiar-spin d-none">
            <span class="spinner-border" style="width:.85rem;height:.85rem;border-width:.15em;" role="status"></span>
            Limpiando…
        </span>
    </button>
    @endunless
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
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-2"
         role="alert" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
        <a href="{{ route('admin.horarios.index') }}" class="btn btn-sm ms-3"
           style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:7px;font-size:.78rem;">
            <i class="bi bi-arrow-repeat me-1"></i>Reintentar generación
        </a>
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

{{-- ── Conflictos guardados en el modelo ───────────────────────────────── --}}
@if(! empty($horario->conflictos) && count($horario->conflictos) > 0)
<div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #f59e0b;">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between">
            <span style="font-size:.8rem;font-weight:700;color:#92400e;">
                <i class="bi bi-exclamation-circle-fill me-1"></i>
                {{ count($horario->conflictos) }} clase(s) no pudieron ser asignadas
            </span>
            <button class="btn btn-sm" data-bs-toggle="collapse" data-bs-target="#colConflictos"
                    style="font-size:.74rem;background:#fef3c7;color:#92400e;border-radius:7px;">
                <i class="bi bi-list me-1"></i>Ver
            </button>
        </div>
        <div class="collapse mt-2" id="colConflictos">
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.77rem;">
                    <thead style="background:#fef9c3;">
                        <tr>
                            <th style="padding:.35rem .6rem;">Grupo</th>
                            <th style="padding:.35rem .6rem;">Materia</th>
                            <th style="padding:.35rem .6rem;">Docente</th>
                            <th style="padding:.35rem .6rem;">Razón</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($horario->conflictos as $c)
                        <tr>
                            <td style="padding:.3rem .6rem;">{{ $c['grupo'] ?? '?' }}</td>
                            <td style="padding:.3rem .6rem;">{{ $c['materia'] ?? '?' }}</td>
                            <td style="padding:.3rem .6rem;">{{ $c['docente'] ?? '?' }}</td>
                            <td style="padding:.3rem .6rem;color:#9ca3af;font-size:.72rem;">{{ $c['razon'] ?? '?' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2 d-flex gap-2">
                <a href="{{ route('admin.horarios.index') }}"
                   class="btn btn-sm fw-semibold"
                   style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:7px;font-size:.78rem;">
                    <i class="bi bi-arrow-repeat me-1"></i>Reintentar generación
                </a>
                <a href="{{ route('admin.horarios.disponibilidad') }}"
                   class="btn btn-sm btn-outline-secondary"
                   style="font-size:.78rem;border-radius:7px;">
                    <i class="bi bi-person-check me-1"></i>Revisar disponibilidad docentes
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Filter bar ───────────────────────────────────────────────────────── --}}
<div class="filter-bar">
    <i class="bi bi-funnel-fill" style="color:var(--primary);font-size:.9rem;"></i>
    <label for="filtroGrupo">Grupo:</label>
    <select id="filtroGrupo" class="form-select form-select-sm"
            style="width:auto;min-width:160px;border-radius:8px;"
            onchange="applyFilter('grupo_id', this.value)">
        <option value="">Todos los grupos</option>
        @foreach($grupos as $grupo)
            <option value="{{ $grupo->id }}" {{ $grupoId == $grupo->id ? 'selected' : '' }}>
                {{ $grupo->nombre_completo }}
            </option>
        @endforeach
    </select>

    <label for="filtroDocente" class="ms-1">Docente:</label>
    <select id="filtroDocente" class="form-select form-select-sm"
            style="width:auto;min-width:180px;border-radius:8px;"
            onchange="applyFilter('docente_id', this.value)">
        <option value="">Todos los docentes</option>
        @foreach($docentes as $docente)
            <option value="{{ $docente->id }}" {{ $docenteId == $docente->id ? 'selected' : '' }}>
                {{ $docente->nombre_completo }}
            </option>
        @endforeach
    </select>

    @if($grupoId || $docenteId)
        <a href="{{ route('admin.horarios.show', $horario) }}"
           class="btn btn-sm btn-outline-secondary ms-1" style="border-radius:8px;font-size:.78rem;">
            <i class="bi bi-x-lg me-1"></i>Limpiar filtros
        </a>
    @endif
</div>

{{-- ── Timetable ────────────────────────────────────────────────────────── --}}
@php
    $diasGrid = [
        'lunes'     => 'Lunes',
        'martes'    => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves'    => 'Jueves',
        'viernes'   => 'Viernes',
    ];
@endphp

<div class="timetable-card">
    <div class="timetable-card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            Grilla de horario
            @if($grupoId || $docenteId)
                <span style="font-weight:400;opacity:.85;font-size:.78rem;">— Filtrado</span>
            @endif
        </div>
        @unless(Auth::user()->hasRole('Docente'))
        <button type="button"
                class="btn btn-sm"
                style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;font-size:.78rem;font-weight:600;"
                onclick="abrirModalNuevo()">
            <i class="bi bi-plus-lg me-1"></i>Agregar clase
        </button>
        @endunless
    </div>
    <div class="timetable-wrap p-2">
        <table class="timetable">
            <thead>
                <tr>
                    <th class="col-hora"><i class="bi bi-clock me-1"></i>Hora</th>
                    @foreach($diasGrid as $diaNum => $diaNom)
                        <th>{{ $diaNom }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($franjas as $franja)
                    @if($franja->es_recreo)
                        {{-- ── Recreo ── --}}
                        <tr class="recreo-row">
                            <td class="hora-cell">
                                <div class="hora-label" style="color:#92400e;">Recreo</div>
                                <div class="hora-range">
                                    {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                    – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                </div>
                            </td>
                            <td colspan="5">
                                <div class="recreo-label">
                                    <i class="bi bi-sun-fill"></i>
                                    {{ $franja->nombre ?? 'RECREO' }}
                                    <span style="font-weight:400;font-size:.72rem;opacity:.7;letter-spacing:0;">
                                        {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                        – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @else
                        {{-- ── Regular period ── --}}
                        <tr>
                            <td class="hora-cell">
                                <div class="hora-label">{{ $franja->nombre ?? ('P'.$franja->numero) }}</div>
                                <div class="hora-range">
                                    {{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                    – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }}
                                </div>
                            </td>

                            @foreach($diasGrid as $diaNum => $diaNom)
                                @php $detalle = $grid[$franja->id][$diaNum] ?? null; @endphp
                                <td
                                    data-dia="{{ $diaNum }}"
                                    data-franja-id="{{ $franja->id }}"
                                    class="drop-zone"
                                >
                                    @if($detalle)
                                        @php
                                            $asignaturaId = $detalle->asignacion->asignatura->id ?? null;
                                            $color        = ($asignaturaId && isset($colores[$asignaturaId]))
                                                            ? $colores[$asignaturaId]
                                                            : '#6366f1';
                                            $rHex = hexdec(substr(ltrim($color,'#'), 0, 2));
                                            $gHex = hexdec(substr(ltrim($color,'#'), 2, 2));
                                            $bHex = hexdec(substr(ltrim($color,'#'), 4, 2));
                                            $cardBg  = "background:rgba({$rHex},{$gHex},{$bHex},.1);border-color:rgba({$rHex},{$gHex},{$bHex},.3);color:{$color};";
                                            $asigNombre  = optional($detalle->asignacion->asignatura)->nombre ?? '—';
                                            $grupoNombre = optional($detalle->asignacion->grupo)->nombre_completo ?? '—';
                                            $docNombre   = optional($detalle->asignacion->docente)->nombre_completo ?? '—';
                                            $aulaNombre  = optional($detalle->aula)->nombre ?? '—';
                                        @endphp
                                        <div
                                            class="detalle-card"
                                            style="{{ $cardBg }}"
                                            draggable="true"
                                            data-detalle-id="{{ $detalle->id }}"
                                            data-dia="{{ $diaNum }}"
                                            data-franja-id="{{ $franja->id }}"
                                            data-grupo-id="{{ $detalle->asignacion->grupo_id ?? '' }}"
                                            data-asignatura-id="{{ $detalle->asignacion->asignatura_id ?? '' }}"
                                            data-docente-id="{{ $detalle->asignacion->docente_id ?? '' }}"
                                            data-aula-id="{{ $detalle->aula_id ?? '' }}"
                                            title="{{ $asigNombre }} · {{ $grupoNombre }} · {{ $docNombre }}"
                                        >
                                            {{-- Action buttons --}}
                                            <div class="cell-actions">
                                                <button type="button" class="cell-btn cell-btn-edit"
                                                        onclick="abrirModalEditar(this)"
                                                        title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="cell-btn cell-btn-delete"
                                                        onclick="eliminarDetalle({{ $detalle->id }})"
                                                        title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="asignatura-name">{{ $asigNombre }}</div>
                                            <div class="meta-row">
                                                <i class="bi bi-people-fill"></i>
                                                <span>{{ $grupoNombre }}</span>
                                            </div>
                                            <div class="meta-row">
                                                <i class="bi bi-person-fill"></i>
                                                <span>{{ $docNombre }}</span>
                                            </div>
                                            <div class="meta-row">
                                                <i class="bi bi-door-open-fill"></i>
                                                <span>{{ $aulaNombre }}</span>
                                            </div>
                                        </div>
                                    @else
                                        @unless(Auth::user()->hasRole('Docente'))
                                        <button type="button"
                                                class="cell-add-btn"
                                                onclick="abrirModalNuevo('{{ $diaNum }}', {{ $franja->id }})"
                                                title="Agregar clase">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        @endunless
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach

                @if($franjas->isEmpty())
                    <tr>
                        <td colspan="6" class="text-center py-5" style="color:#9ca3af;font-size:.85rem;">
                            <i class="bi bi-clock d-block mb-2" style="font-size:2rem;color:#d1d5db;"></i>
                            No hay franjas horarias configuradas.
                            <a href="{{ route('admin.horarios.franjas') }}" style="color:var(--primary);">
                                Configurar franjas
                            </a>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── Navigation links ─────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 flex-wrap align-items-center mb-4">
    <span style="font-size:.78rem;color:#9ca3af;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">
        Configuración:
    </span>
    <a href="{{ route('admin.horarios.aulas') }}" class="link-config">
        <i class="bi bi-door-open" style="color:#6366f1;"></i>Aulas
    </a>
    <a href="{{ route('admin.horarios.franjas') }}" class="link-config">
        <i class="bi bi-clock-history" style="color:#0891b2;"></i>Franjas
    </a>
    <a href="{{ route('admin.horarios.disponibilidad') }}" class="link-config">
        <i class="bi bi-person-check" style="color:#059669;"></i>Disponibilidad
    </a>
    <a href="{{ route('admin.horarios.suplencias') }}" class="link-config">
        <i class="bi bi-person-fill-exclamation" style="color:#dc2626;"></i>Suplencias
    </a>
</div>

{{-- ── Toast container ─────────────────────────────────────────────────── --}}
<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

{{-- ══ MODAL: Crear / Editar clase manualmente ══════════════════════════ --}}
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">

            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h6 class="modal-title fw-bold" id="modalDetalleLabel" style="font-size:.97rem;">
                    <i class="bi bi-calendar-plus me-2 text-primary"></i>
                    <span id="modalDetalleTitle">Nueva clase</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="formDetalle" method="POST" novalidate>
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="dia"      id="fDia">
                <input type="hidden" name="franja_id" id="fFranjaId">

                <div class="modal-body px-4 pb-0 pt-3">

                    {{-- Día + Franja (visual, readonly) --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:.81rem;">Día</label>
                            <select name="dia_selector" id="fDiaSelector" class="form-select form-select-sm"
                                    style="border-radius:8px;" onchange="document.getElementById('fDia').value=this.value">
                                <option value="lunes">Lunes</option>
                                <option value="martes">Martes</option>
                                <option value="miercoles">Miércoles</option>
                                <option value="jueves">Jueves</option>
                                <option value="viernes">Viernes</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:.81rem;">Franja horaria</label>
                            <select name="franja_selector" id="fFranjaSelector" class="form-select form-select-sm"
                                    style="border-radius:8px;" onchange="document.getElementById('fFranjaId').value=this.value">
                                @foreach($franjas->where('es_recreo', false) as $franja)
                                <option value="{{ $franja->id }}">
                                    {{ $franja->nombre ?? ('P'.$franja->numero) }}
                                    ({{ \Carbon\Carbon::parse($franja->hora_inicio)->format('H:i') }}
                                    – {{ \Carbon\Carbon::parse($franja->hora_fin)->format('H:i') }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Grupo --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.81rem;">
                            Curso / Grupo <span class="text-danger">*</span>
                        </label>
                        <select name="grupo_id" id="fGrupoId" class="form-select form-select-sm"
                                style="border-radius:8px;" required>
                            <option value="">Seleccionar curso…</option>
                            @foreach($grupos as $grupo)
                            <option value="{{ $grupo->id }}">{{ $grupo->nombre_completo }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-grupo_id"></div>
                    </div>

                    {{-- Asignatura --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.81rem;">
                            Materia <span class="text-danger">*</span>
                        </label>
                        <select name="asignatura_id" id="fAsignaturaId" class="form-select form-select-sm"
                                style="border-radius:8px;" required>
                            <option value="">Seleccionar materia…</option>
                            @foreach($asignaturas as $asig)
                            <option value="{{ $asig->id }}">{{ $asig->nombre }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-asignatura_id"></div>
                    </div>

                    {{-- Docente --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.81rem;">
                            Docente <span class="text-danger">*</span>
                        </label>
                        <select name="docente_id" id="fDocenteId" class="form-select form-select-sm"
                                style="border-radius:8px;" required>
                            <option value="">Seleccionar docente…</option>
                            @foreach($docentes as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->apellidos }}, {{ $doc->nombres }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-docente_id"></div>
                    </div>

                    {{-- Aula --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.81rem;">Aula</label>
                        <select name="aula_id" id="fAulaId" class="form-select form-select-sm"
                                style="border-radius:8px;">
                            <option value="">Sin aula asignada</option>
                            @foreach($aulas as $aula)
                            <option value="{{ $aula->id }}">{{ $aula->nombre }} (cap. {{ $aula->capacidad }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-aula_id"></div>
                    </div>

                    {{-- Error general --}}
                    <div id="formGeneralError" class="alert alert-danger py-2 px-3 d-none" style="border-radius:8px;font-size:.82rem;"></div>

                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal"
                            style="border-radius:8px;">Cancelar</button>
                    <button type="submit" id="btnGuardarDetalle" class="btn btn-primary btn-sm"
                            style="border-radius:8px;min-width:90px;">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerDetalle"></span>
                        <span id="btnGuardarLabel">Guardar</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Build route map for AJAX ────────────────────────── */
    const MOVER_BASE = '/admin/horarios/detalle/';

    /* ── Filter helper ───────────────────────────────────── */
    window.applyFilter = function (param, value) {
        const url = new URL(window.location.href);
        url.searchParams.delete('grupo_id');
        url.searchParams.delete('docente_id');
        if (value) {
            url.searchParams.set(param, value);
        }
        window.location.href = url.toString();
    };

    /* ── Toast helper ────────────────────────────────────── */
    const toastContainer = document.getElementById('toastContainer');

    function showToast(message, type) {
        const el   = document.createElement('div');
        el.className = 'toast-item toast-' + (type || 'success');
        const icon = type === 'error' ? 'bi-x-circle-fill' : 'bi-check-circle-fill';
        el.innerHTML = '<i class="bi ' + icon + '"></i>' + message;
        toastContainer.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .3s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 320);
        }, 3500);
    }

    /* ── Drag & Drop ─────────────────────────────────────── */
    const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let draggedCard  = null;
    let sourceCell   = null;

    function attachDragHandlers(card) {
        card.addEventListener('dragstart', function (e) {
            draggedCard = card;
            sourceCell  = card.closest('td');
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.detalleId);
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');
        });
    }

    document.querySelectorAll('.detalle-card[draggable="true"]').forEach(attachDragHandlers);

    /* ── Drop zone events ────────────────────────────────── */
    document.querySelectorAll('td.drop-zone').forEach(function (cell) {

        cell.addEventListener('dragover', function (e) {
            if (!draggedCard) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            cell.classList.add('drop-target');
        });

        cell.addEventListener('dragleave', function (e) {
            if (!cell.contains(e.relatedTarget)) {
                cell.classList.remove('drop-target');
            }
        });

        cell.addEventListener('drop', function (e) {
            e.preventDefault();
            cell.classList.remove('drop-target');

            if (!draggedCard) return;
            if (cell === sourceCell) return; // dropped on same cell

            const detalleId = draggedCard.dataset.detalleId;
            const newDia    = cell.dataset.dia;
            const newFranja = parseInt(cell.dataset.franjaId, 10);

            /* Optimistic UI ─ move the card immediately */
            const origCell   = sourceCell;
            const existingHint = cell.querySelector('.empty-cell-hint');

            // Put a placeholder in the source cell
            const origHint = document.createElement('div');
            origHint.className = 'empty-cell-hint';
            origHint.innerHTML = '<i class="bi bi-plus" style="font-size:.9rem;"></i>';

            cell.innerHTML = '';
            cell.appendChild(draggedCard);
            if (origCell) {
                origCell.innerHTML = '';
                origCell.appendChild(origHint);
            }

            // Update card attributes
            draggedCard.dataset.dia      = newDia;
            draggedCard.dataset.franjaId = newFranja;

            const movedCard = draggedCard;
            draggedCard     = null;
            sourceCell      = null;

            /* AJAX ─ persist the change */
            fetch(MOVER_BASE + detalleId + '/mover', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    dia:       newDia,
                    franja_id: newFranja,
                }),
            })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (result.ok) {
                    showToast(result.data.message || 'Clase movida correctamente.', 'success');
                } else {
                    const msg = result.data.message || 'No se pudo mover la clase.';
                    showToast(msg, 'error');
                    /* Revert: put card back in origCell */
                    if (origCell) {
                        origCell.innerHTML = '';
                        origCell.appendChild(movedCard);
                    }
                    cell.innerHTML = '';
                    if (existingHint) {
                        cell.appendChild(existingHint);
                    } else {
                        const hint = document.createElement('div');
                        hint.className = 'empty-cell-hint';
                        hint.innerHTML = '<i class="bi bi-plus" style="font-size:.9rem;"></i>';
                        cell.appendChild(hint);
                    }
                }
            })
            .catch(function () {
                showToast('Error de red. No se guardaron los cambios.', 'error');
                if (origCell) {
                    origCell.innerHTML = '';
                    origCell.appendChild(movedCard);
                }
                cell.innerHTML = '';
                const hint = document.createElement('div');
                hint.className = 'empty-cell-hint';
                hint.innerHTML = '<i class="bi bi-plus" style="font-size:.9rem;"></i>';
                cell.appendChild(hint);
            });
        });
    });

})();

/* ══ Manual CRUD ═══════════════════════════════════════════════════════ */
(function () {
    'use strict';

    const horarioId  = {{ $horario->id }};
    const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const modal      = new bootstrap.Modal(document.getElementById('modalDetalle'));
    const form       = document.getElementById('formDetalle');
    let   editandoId = null;

    /* ── Helpers ─────────────────────────────────────────── */
    function limpiarErrores() {
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.getElementById('formGeneralError').classList.add('d-none');
    }

    function mostrarErrores(errors) {
        Object.entries(errors).forEach(([field, msgs]) => {
            const errEl = document.getElementById('err-' + field);
            const inpEl = form.querySelector('[name="' + field + '"]');
            if (errEl) { errEl.textContent = msgs[0]; }
            if (inpEl) { inpEl.classList.add('is-invalid'); }
        });
        const generalErr = document.getElementById('formGeneralError');
        generalErr.textContent = 'Corrige los conflictos indicados.';
        generalErr.classList.remove('d-none');
    }

    function setSelect(id, value) {
        const sel = document.getElementById(id);
        if (sel && value) sel.value = value;
    }

    /* ── Abrir modal: nueva clase ─────────────────────────── */
    window.abrirModalNuevo = function (dia, franjaId) {
        editandoId = null;
        limpiarErrores();
        form.reset();

        document.getElementById('modalDetalleTitle').textContent = 'Nueva clase';
        document.getElementById('btnGuardarLabel').textContent   = 'Guardar';
        document.getElementById('formMethod').value              = 'POST';
        form.action = '/admin/horarios/' + horarioId + '/detalles';

        if (dia)      { setSelect('fDiaSelector', dia);       document.getElementById('fDia').value      = dia; }
        if (franjaId) { setSelect('fFranjaSelector', franjaId); document.getElementById('fFranjaId').value = franjaId; }

        modal.show();
    };

    /* ── Abrir modal: editar clase ────────────────────────── */
    window.abrirModalEditar = function (btn) {
        const card = btn.closest('.detalle-card');
        editandoId = card.dataset.detalleId;
        limpiarErrores();

        document.getElementById('modalDetalleTitle').textContent = 'Editar clase';
        document.getElementById('btnGuardarLabel').textContent   = 'Actualizar';
        document.getElementById('formMethod').value              = 'PUT';
        form.action = '/admin/horarios/' + horarioId + '/detalles/' + editandoId;

        const dia      = card.dataset.dia;
        const franjaId = card.dataset.franjaId;

        setSelect('fDiaSelector',       dia);       document.getElementById('fDia').value      = dia;
        setSelect('fFranjaSelector',    franjaId);  document.getElementById('fFranjaId').value = franjaId;
        setSelect('fGrupoId',           card.dataset.grupoId);
        setSelect('fAsignaturaId',      card.dataset.asignaturaId);
        setSelect('fDocenteId',         card.dataset.docenteId);
        setSelect('fAulaId',            card.dataset.aulaId);

        modal.show();
    };

    /* ── Eliminar clase ───────────────────────────────────── */
    window.eliminarDetalle = function (detalleId) {
        if (!confirm('¿Eliminar esta clase del horario?')) return;

        fetch('/admin/horarios/' + horarioId + '/detalles/' + detalleId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast('Clase eliminada del horario.', 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                showToast(data.message || 'No se pudo eliminar.', 'error');
            }
        })
        .catch(() => showToast('Error de red.', 'error'));
    };

    /* ── Submit del formulario ────────────────────────────── */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        limpiarErrores();

        const btn     = document.getElementById('btnGuardarDetalle');
        const spinner = document.getElementById('spinnerDetalle');
        btn.disabled  = true;
        spinner.classList.remove('d-none');

        const formData = new FormData(form);
        // FormData includes _method field; fetch will send as POST with _method override
        const body = new URLSearchParams(formData).toString();

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
            },
            body: body,
        })
        .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })))
        .then(({ ok, status, data }) => {
            if (ok) {
                modal.hide();
                showToast(editandoId ? 'Clase actualizada.' : 'Clase agregada al horario.', 'success');
                setTimeout(() => location.reload(), 800);
            } else if (status === 422 && data.errors) {
                mostrarErrores(data.errors);
            } else {
                const msg = data.message || 'Error al guardar.';
                document.getElementById('formGeneralError').textContent = msg;
                document.getElementById('formGeneralError').classList.remove('d-none');
            }
        })
        .catch(() => {
            document.getElementById('formGeneralError').textContent = 'Error de red. Intenta nuevamente.';
            document.getElementById('formGeneralError').classList.remove('d-none');
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
        });
    });

    /* Sync selectors → hidden inputs on change */
    document.getElementById('fDiaSelector')?.addEventListener('change', function () {
        document.getElementById('fDia').value = this.value;
    });
    document.getElementById('fFranjaSelector')?.addEventListener('change', function () {
        document.getElementById('fFranjaId').value = this.value;
    });

})();

/* ── Regenerar horario ──────────────────────────────── */
window.regenerarHorario = function () {
    if (!confirm('¿Regenerar este horario? Se reemplazarán todas las clases actuales con el resultado del algoritmo.')) {
        return;
    }

    const btn   = document.getElementById('btnRegenerar');
    const label = btn.querySelector('.btn-regen-label');
    const spin  = btn.querySelector('.btn-regen-spin');
    btn.disabled = true;
    label.classList.add('d-none');
    spin.classList.remove('d-none');

    fetch('{{ route("admin.horarios.regenerar", $horario) }}', {
        method : 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept'      : 'application/json',
            'Content-Type': 'application/json',
        },
        body: '{}',
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (ok && d.ok) {
            showToast(d.message, d.pendientes > 0 ? 'warning' : 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            alert(d.error ?? 'Error al regenerar.');
            btn.disabled = false;
            label.classList.remove('d-none');
            spin.classList.add('d-none');
        }
    })
    .catch(() => {
        alert('Error de red. Intenta nuevamente.');
        btn.disabled = false;
        label.classList.remove('d-none');
        spin.classList.add('d-none');
    });
};

// ── Limpiar horario ─────────────────────────────────────────────────────
async function limpiarHorario() {
    if (!confirm('¿Eliminar todas las clases de este horario?\n\nEl horario quedará vacío. Podrás regenerarlo o agregar celdas manualmente.')) return;

    const btn   = document.getElementById('btnLimpiar');
    const label = btn.querySelector('.btn-limpiar-label');
    const spin  = btn.querySelector('.btn-limpiar-spin');
    btn.disabled = true;
    label.classList.add('d-none');
    spin.classList.remove('d-none');

    try {
        const res = await fetch('{{ route("admin.horarios.limpiar", $horario) }}', {
            method : 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept'      : 'application/json',
            },
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.mensaje, 'success');
            setTimeout(() => location.reload(), 1100);
        } else {
            showToast('No se pudo limpiar el horario.', 'error');
            btn.disabled = false;
            label.classList.remove('d-none');
            spin.classList.add('d-none');
        }
    } catch {
        showToast('Error de red. Intenta nuevamente.', 'error');
        btn.disabled = false;
        label.classList.remove('d-none');
        spin.classList.add('d-none');
    }
}
</script>
@endpush
