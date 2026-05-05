@extends('layouts.admin')
@section('page-title', 'Calificaciones')

@push('styles')
<style>
    .grupo-card {
        cursor: pointer;
        border: 2px solid #e5e7eb;
        transition: border-color .18s, box-shadow .18s, transform .12s;
        border-radius: 10px;
    }
    .grupo-card:hover {
        border-color: var(--primary-light);
        box-shadow: 0 4px 14px rgba(30,58,110,.12);
        transform: translateY(-1px);
    }
    .grupo-card.selected {
        border-color: var(--primary);
        background: #eef3fb;
        box-shadow: 0 4px 16px rgba(30,58,110,.18);
    }
    .grupo-card .grupo-badge {
        width: 46px; height: 46px;
        background: var(--primary);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #fff; flex-shrink: 0;
    }
    .grupo-card.selected .grupo-badge { background: var(--secondary); }

    .asignacion-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: .75rem 1rem;
        margin-bottom: .5rem;
        display: flex; align-items: center; gap: .85rem;
        background: #fff;
        transition: background .15s, border-color .15s;
        cursor: pointer;
    }
    .asignacion-item:hover { background: #f8faff; border-color: #c7d6f0; }
    .asignacion-item .asig-icon {
        width: 38px; height: 38px;
        background: #eef3fb;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: var(--primary); font-size: 1rem; flex-shrink: 0;
    }
    .asignacion-item.selected {
        background: #eef3fb; border-color: var(--primary);
    }

    .panel-right {
        min-height: 300px;
        background: #f8faff;
        border-radius: 12px;
        border: 1px dashed #c7d6f0;
    }
    .panel-placeholder {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; min-height: 300px; color: #9ca3af;
    }
    #btn-ir-grilla { min-width: 160px; }
    .badge-ra { background: #f3e8ff; color: #7c3aed; font-size: .7rem; padding: .2em .5em; border-radius: 10px; font-weight: 700; }
    .badge-tecnica { background: #fef3c7; color: #92400e; font-size: .7rem; padding: .2em .5em; border-radius: 10px; font-weight: 700; }

    [data-theme="dark"] .grupo-card { border-color: #334155; background: #1e293b; }
    [data-theme="dark"] .grupo-card.selected { background: #1e3a5f; border-color: var(--primary); }
    [data-theme="dark"] .asignacion-item { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .asignacion-item:hover { background: #1a2640; border-color: #4b6a9e; }
    [data-theme="dark"] .asignacion-item .asig-icon { background: #162032; }
    [data-theme="dark"] .asignacion-item.selected { background: #1e3a5f; border-color: var(--primary); }
    [data-theme="dark"] .panel-right { background: #162032; border-color: #334155; }
    [data-theme="dark"] .badge-ra { background: #2e1065; color: #c4b5fd; }
    [data-theme="dark"] .badge-tecnica { background: #1c1000; color: #fcd34d; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-journal-check me-2"></i>Registro de Calificaciones
            @isset($contexto)
                <span class="badge ms-2 px-2 py-1" style="font-size:.7rem;background:var(--primary-light);color:var(--primary);border-radius:8px;font-weight:700;vertical-align:middle;">{{ $contexto }}</span>
            @endisset
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            @isset($docente)
                Tus asignaturas asignadas para este año escolar.
            @else
                Selecciona un grupo, luego la asignatura y el período para acceder a la grilla de notas.
            @endisset
        </p>
    </div>
    @if($schoolYear)
    <span class="badge rounded-pill px-3 py-2" style="background:var(--accent-light);color:#92400e;font-size:.8rem;border:1px solid #fcd34d;">
        <i class="bi bi-calendar2-check me-1"></i>{{ $schoolYear->nombre }}
    </span>
    @endif
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@isset($docente)
{{-- ═══════════════════════════════════════════════════════════════════════════
     DOCENTE VIEW — agrupado por Grupo/Grado, separado por Área
     ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- Tabs Académica / Técnica --}}
<ul class="nav nav-tabs mb-4" id="area-tabs">
    <li class="nav-item">
        <a class="nav-link active" id="tab-acad" href="#" onclick="switchArea('academica');return false;">
            <i class="bi bi-book me-1"></i>Área Académica
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-tec" href="#" onclick="switchArea('tecnica');return false;">
            <i class="bi bi-tools me-1"></i>Área Técnica
        </a>
    </li>
</ul>

@php
    $todasLasAsigs = collect($asignacionesPorGrupo)->flatten(1);
    $hayAcademicas = $todasLasAsigs->where('area', 'academica')->isNotEmpty();
    $hayTecnicas   = $todasLasAsigs->where('area', 'tecnica')->isNotEmpty();
@endphp

{{-- Panel Área Académica --}}
<div id="panel-academica">
    @if($hayAcademicas)
        <div class="row g-4">
            @foreach($asignacionesPorGrupo as $grupoId => $asigs)
                @php $asigAcad = $asigs->where('area', 'academica'); @endphp
                @if($asigAcad->isNotEmpty())
                    @php $grupo = $asigs->first()->grupo; @endphp
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header py-3" style="background:var(--primary);color:#fff;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold d-flex align-items-center gap-2" style="font-size:1rem;">
                                            {{ $grupo->nombre_completo ?? $grupo->nombre }}
                                            <span class="badge" style="font-size:.6rem;border-radius:6px;padding:.15rem .45rem;
                                                background:{{ ($grupo->grado->nivel ?? 0) <= 3 ? 'rgba(219,234,254,.4)' : 'rgba(209,250,229,.4)' }};
                                                color:#fff;border:1px solid rgba(255,255,255,.3);">
                                                {{ ($grupo->grado->nivel ?? 0) <= 3 ? 'Primer Ciclo' : '2do Ciclo' }}
                                            </span>
                                        </div>
                                        <div class="d-flex gap-3" style="font-size:.75rem;opacity:.85;">
                                            <span>{{ $asigAcad->count() }} {{ $asigAcad->count() === 1 ? 'materia' : 'materias' }}</span>
                                            @if($grupo->tutor)
                                                <span><i class="bi bi-star-fill me-1" style="color:#fbbf24;"></i>Guía: {{ $grupo->tutor->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;">Materia</th>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;">Tipo</th>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;text-align:center;">Planilla Académica</th>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;text-align:center;">Asistencia</th>
                                                @if($periodos->isNotEmpty())
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;text-align:center;">Indicadores de Logro</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($asigAcad as $asi)
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div style="width:32px;height:32px;background:#eef3fb;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                                            <i class="bi bi-journal-text" style="color:var(--primary);font-size:.85rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold" style="font-size:.88rem;">{{ $asi->asignatura->nombre }}</span>
                                                    </div>
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    @if(in_array($asi->tipo_evaluacion, ['ra','competencias']))
                                                        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;">{{ $asi->tipo_evaluacion === 'competencias' ? 'Competencia' : 'RA' }}</span>
                                                    @else
                                                        <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.72rem;">{{ $asi->tipo_evaluacion === 'indicadores_logro' ? 'Ind. Logro' : 'Académica' }}</span>
                                                    @endif
                                                </td>
                                                {{-- Académica: planilla anual completa (4 competencias × 4 períodos) --}}
                                                <td style="vertical-align:middle;text-align:center;">
                                                    <a href="{{ route('admin.calificaciones.grilla', ['asignacion_id' => $asi->id]) }}"
                                                       class="btn btn-sm btn-primary" style="font-size:.78rem;">
                                                        <i class="bi bi-table me-1"></i>Planilla Académica
                                                    </a>
                                                </td>
                                                <td style="vertical-align:middle;text-align:center;">
                                                    <a href="{{ route('admin.asistencia.registrar', $asi->id) }}"
                                                       class="btn btn-sm btn-outline-success" style="font-size:.78rem;">
                                                        <i class="bi bi-calendar-check me-1"></i>Asistencia
                                                    </a>
                                                </td>
                                                @if($periodos->isNotEmpty())
                                                <td style="vertical-align:middle;text-align:center;">
                                                    <a href="{{ route('admin.indicadores.evaluaciones', ['asignacion_id' => $asi->id, 'periodo_id' => $periodos->first()->id]) }}"
                                                       class="btn btn-sm btn-outline-info" style="font-size:.78rem;">
                                                        <i class="bi bi-check2-all me-1"></i>Indicadores
                                                    </a>
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-book" style="font-size:2rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">No tienes materias académicas asignadas.</p>
            </div>
        </div>
    @endif
</div>

{{-- Panel Área Técnica --}}
<div id="panel-tecnica" style="display:none;">
    @if($hayTecnicas)
        <div class="row g-4">
            @foreach($asignacionesPorGrupo as $grupoId => $asigs)
                @php $asigTec = $asigs->where('area', 'tecnica'); @endphp
                @if($asigTec->isNotEmpty())
                    @php $grupo = $asigs->first()->grupo; @endphp
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header py-3" style="background:var(--primary);color:#fff;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold d-flex align-items-center gap-2" style="font-size:1rem;">
                                            {{ $grupo->nombre_completo ?? $grupo->nombre }}
                                            <span class="badge" style="font-size:.6rem;border-radius:6px;padding:.15rem .45rem;
                                                background:{{ ($grupo->grado->nivel ?? 0) <= 3 ? 'rgba(219,234,254,.4)' : 'rgba(209,250,229,.4)' }};
                                                color:#fff;border:1px solid rgba(255,255,255,.3);">
                                                {{ ($grupo->grado->nivel ?? 0) <= 3 ? 'Primer Ciclo' : '2do Ciclo' }}
                                            </span>
                                        </div>
                                        <div class="d-flex gap-3" style="font-size:.75rem;opacity:.85;">
                                            <span>{{ $asigTec->count() }} {{ $asigTec->count() === 1 ? 'materia' : 'materias' }}</span>
                                            @if($grupo->tutor)
                                                <span><i class="bi bi-star-fill me-1" style="color:#fbbf24;"></i>Guía: {{ $grupo->tutor->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;">Materia</th>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;">Tipo</th>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;text-align:center;">Calificaciones</th>
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;text-align:center;">Asistencia</th>
                                                @if($periodos->isNotEmpty())
                                                <th style="font-size:.8rem;font-weight:600;color:#374151;text-align:center;">Indicadores de Logro</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($asigTec as $asi)
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div style="width:32px;height:32px;background:#eef3fb;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                                            <i class="bi bi-journal-text" style="color:var(--primary);font-size:.85rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold" style="font-size:.88rem;">{{ $asi->asignatura->nombre }}</span>
                                                    </div>
                                                </td>
                                                <td style="vertical-align:middle;">
                                                    @if(in_array($asi->tipo_evaluacion, ['ra','competencias']))
                                                        <span class="badge" style="background:#f3e8ff;color:#7c3aed;font-size:.72rem;">{{ $asi->tipo_evaluacion === 'competencias' ? 'Competencia' : 'RA' }}</span>
                                                    @else
                                                        <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem;">Técnica</span>
                                                    @endif
                                                </td>
                                                <td style="vertical-align:middle;text-align:center;">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size:.78rem;">
                                                            <i class="bi bi-journal-check me-1"></i>Período
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @foreach($periodos as $p)
                                                            <li>
                                                                <a class="dropdown-item" style="font-size:.82rem;"
                                                                   href="{{ route('admin.calificaciones.grilla', ['asignacion_id' => $asi->id, 'periodo_id' => $p->id]) }}">
                                                                    <i class="bi bi-calendar3 me-2 text-muted"></i>{{ $p->nombre }}
                                                                </a>
                                                            </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td style="vertical-align:middle;text-align:center;">
                                                    <a href="{{ route('admin.asistencia.registrar', $asi->id) }}"
                                                       class="btn btn-sm btn-outline-success" style="font-size:.78rem;">
                                                        <i class="bi bi-calendar-check me-1"></i>Asistencia
                                                    </a>
                                                </td>
                                                @if($periodos->isNotEmpty())
                                                <td style="vertical-align:middle;text-align:center;">
                                                    <a href="{{ route('admin.indicadores.evaluaciones', ['asignacion_id' => $asi->id, 'periodo_id' => $periodos->first()->id]) }}"
                                                       class="btn btn-sm btn-outline-info" style="font-size:.78rem;">
                                                        <i class="bi bi-check2-all me-1"></i>Indicadores
                                                    </a>
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-tools" style="font-size:2rem;opacity:.3;"></i>
                <p class="mt-2 mb-0">No tienes materias técnicas asignadas.</p>
            </div>
        </div>
    @endif
</div>

@if($asignacionesPorGrupo->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:#d1d5db;"></i>
        <p class="mt-3 mb-0 text-muted">No tienes asignaturas asignadas para este año escolar.</p>
        <small class="text-muted">Contacta al coordinador para que te asigne cursos.</small>
    </div>
</div>
@endif

<script>
function switchArea(area) {
    document.getElementById('panel-academica').style.display = area === 'academica' ? '' : 'none';
    document.getElementById('panel-tecnica').style.display   = area === 'tecnica'   ? '' : 'none';
    document.getElementById('tab-acad').classList.toggle('active', area === 'academica');
    document.getElementById('tab-tec').classList.toggle('active', area === 'tecnica');
}
</script>

@else
{{-- ═══════════════════════════════════════════════════════════════════════════
     ADMIN / COORDINATOR VIEW — grupos selector
     ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="row g-4">
    {{-- Left: Grupos --}}
    <div class="col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Grupos / Cursos</h6>
            </div>
            <div class="card-body p-3" style="max-height:520px;overflow-y:auto;">
                <div class="px-1 pb-2 pt-1">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 pe-1">
                            <i class="bi bi-search text-muted" style="font-size:.78rem;"></i>
                        </span>
                        <input type="text"
                               id="filtro-grupos-cal"
                               class="form-control border-start-0 ps-1"
                               placeholder="Buscar grupo..."
                               autocomplete="off"
                               style="font-size:.81rem;">
                    </div>
                </div>
                @forelse($grupos as $grupo)
                <div class="grupo-card p-3 mb-2 d-flex align-items-center gap-3"
                     data-grupo-id="{{ $grupo->id }}"
                     data-grupo-nombre="{{ $grupo->nombre_completo }}"
                     onclick="seleccionarGrupo(this)">
                    <div class="grupo-badge">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold" style="font-size:.92rem;">{{ $grupo->nombre_completo }}</div>
                        <div class="text-muted" style="font-size:.78rem;">
                            <i class="bi bi-diagram-3 me-1"></i>
                            {{ $grupo->asignaciones->count() }} asignacion(es)
                            &nbsp;&bull;&nbsp;
                            <i class="bi bi-people me-1"></i>
                            {{ $grupo->matriculas()->activas()->count() }} estudiantes
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted" style="font-size:.8rem;"></i>
                </div>
                @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-0">No hay grupos para este año escolar.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right: Asignaciones + Periodo --}}
    <div class="col-lg-7 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-book me-2 text-primary"></i>Asignaturas del Grupo</h6>
                <span id="grupo-seleccionado-badge" class="badge bg-primary d-none"></span>
            </div>
            <div class="card-body p-3">

                <div id="panel-placeholder" class="panel-placeholder">
                    <i class="bi bi-arrow-left-circle" style="font-size:2.5rem;opacity:.3;"></i>
                    <p class="mt-3 mb-0" style="font-size:.9rem;">Selecciona un grupo para ver sus asignaturas</p>
                </div>

                <div id="panel-asignaciones" style="display:none;">
                    <div id="lista-asignaciones"></div>

                    <hr class="my-3">

                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6" id="periodo-section">
                            <label class="form-label fw-semibold mb-1" style="font-size:.85rem;">
                                <i class="bi bi-calendar3 me-1"></i>Período
                            </label>
                            <select id="select-periodo" class="form-select" style="font-size:.88rem;">
                                <option value="">-- Seleccionar período --</option>
                                @foreach($periodos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->numero }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <form id="form-ir-grilla" method="GET" action="{{ route('admin.calificaciones.grilla') }}">
                                <input type="hidden" id="input-asignacion-id" name="asignacion_id" value="">
                                <input type="hidden" id="input-periodo-id" name="periodo_id" value="">
                                <button type="submit" id="btn-ir-grilla" class="btn btn-primary w-100" disabled>
                                    <i class="bi bi-table me-2" id="btn-grilla-icon"></i>
                                    <span id="btn-grilla-text">Ir a Grilla de Notas</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="alerta-seleccion" class="alert alert-warning mt-3 py-2 d-none" style="font-size:.83rem;">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <span id="alerta-texto">Selecciona una asignatura y un período para continuar.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3" style="background:linear-gradient(135deg,#eef3fb,#f8faff);">
            <div class="card-body py-3 px-4">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="fw-bold" style="color:var(--primary);font-size:1.1rem;">{{ $grupos->count() }}</div>
                        <div class="text-muted" style="font-size:.75rem;">Grupos</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold" style="color:var(--primary);font-size:1.1rem;">{{ $periodos->count() }}</div>
                        <div class="text-muted" style="font-size:.75rem;">Períodos</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold" style="color:var(--secondary);font-size:1.1rem;">
                            {{ $grupos->sum(fn($g) => $g->asignaciones->count()) }}
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">Asignaciones</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $asignacionesPorGrupoJs = [];
    foreach($grupos as $g) {
        $asignacionesPorGrupoJs[$g->id] = $g->asignaciones->map(function($a) {
            return [
                'id'      => $a->id,
                'materia' => optional($a->asignatura)->nombre ?? '—',
                'docente' => optional($a->docente)->nombre_completo ?? 'Sin docente',
                'es_ra'   => in_array($a->tipo_evaluacion, ['ra', 'competencias']),
                'area'    => $a->area ?? 'academica',
            ];
        })->values();
    }
@endphp
<script>
    const asignacionesPorGrupo   = @json($asignacionesPorGrupoJs);
    const ROUTE_GRILLA           = "{{ route('admin.calificaciones.grilla') }}";
    const ROUTE_PLANILLA_AC      = "{{ route('admin.calificaciones.planilla-academica') }}";
</script>

<script>
// Filtro de grupos — calificaciones
(function() {
    const inp = document.getElementById('filtro-grupos-cal');
    if (!inp) return;
    inp.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('[data-grupo-id]').forEach(el => {
            const txt = el.textContent.toLowerCase();
            el.style.display = (q === '' || txt.includes(q)) ? '' : 'none';
        });
    });
})();

let selectedAsignacionId = null;
let selectedGrupoId      = null;

function seleccionarGrupo(el) {
    document.querySelectorAll('.grupo-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    const grupoId     = el.dataset.grupoId;
    const grupoNombre = el.dataset.grupoNombre;
    selectedGrupoId   = grupoId;
    selectedAsignacionId = null;

    const badge = document.getElementById('grupo-seleccionado-badge');
    badge.textContent = grupoNombre;
    badge.classList.remove('d-none');

    document.getElementById('input-asignacion-id').value = '';
    document.getElementById('input-periodo-id').value    = '';
    actualizarBoton();

    const asignaciones = asignacionesPorGrupo[grupoId] || [];
    const lista = document.getElementById('lista-asignaciones');

    if (asignaciones.length === 0) {
        lista.innerHTML = `<div class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size:1.8rem;opacity:.4;"></i>
            <p class="mt-2 mb-0" style="font-size:.85rem;">Este grupo no tiene asignaciones.</p>
        </div>`;
    } else {
        lista.innerHTML = asignaciones.map(a => `
            <div class="asignacion-item" data-asignacion-id="${a.id}" onclick="seleccionarAsignacion(this)">
                <div class="asig-icon"><i class="bi bi-journal-text"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:.9rem;">
                        ${a.materia}
                        ${a.es_ra ? '<span class="badge-ra ms-1">RA</span>' : ''}
                        ${a.area === 'tecnica' ? '<span class="badge-tecnica ms-1">Técnica</span>' : ''}
                    </div>
                    <div class="text-muted" style="font-size:.78rem;"><i class="bi bi-person me-1"></i>${a.docente}</div>
                </div>
                <i class="bi bi-circle asig-check text-muted" style="font-size:1rem;"></i>
            </div>
        `).join('');
    }

    document.getElementById('panel-placeholder').style.display = 'none';
    const panelAsig = document.getElementById('panel-asignaciones');
    panelAsig.style.display = 'block';
    panelAsig.classList.remove('panel-fade-in');
    void panelAsig.offsetWidth;
    panelAsig.classList.add('panel-fade-in');
}

function seleccionarAsignacion(el) {
    // Walk up to the .asignacion-item in case a child element was clicked
    const item = el.closest('.asignacion-item') || el;

    document.querySelectorAll('#lista-asignaciones .asignacion-item').forEach(function(i) {
        i.style.background  = '';
        i.style.borderColor = '';
        const chk = i.querySelector('.asig-check');
        if (chk) chk.className = 'bi bi-circle asig-check text-muted';
    });

    item.style.background  = '#eef3fb';
    item.style.borderColor = 'var(--primary)';
    const chk = item.querySelector('.asig-check');
    if (chk) chk.className = 'bi bi-check-circle-fill asig-check text-primary';

    selectedAsignacionId = item.dataset.asignacionId;
    document.getElementById('input-asignacion-id').value = selectedAsignacionId;
    actualizarBoton();
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'select-periodo') {
        document.getElementById('input-periodo-id').value = e.target.value;
        actualizarBoton();
    }
});

function actualizarBoton() {
    const btn       = document.getElementById('btn-ir-grilla');
    const form      = document.getElementById('form-ir-grilla');
    const asigId    = document.getElementById('input-asignacion-id').value;
    const periodoId = document.getElementById('input-periodo-id').value;

    // Detect area of the selected asignacion
    const asig = asigId && selectedGrupoId
        ? (asignacionesPorGrupo[selectedGrupoId] || []).find(a => a.id == asigId)
        : null;
    const esAcademica = asig?.area === 'academica';

    // Show/hide period selector
    const periodoSection = document.getElementById('periodo-section');
    if (periodoSection) periodoSection.style.display = esAcademica ? 'none' : '';

    // Update button label & form action
    const btnText = document.getElementById('btn-grilla-text');
    const btnIcon = document.getElementById('btn-grilla-icon');
    if (esAcademica) {
        if (btnText) btnText.textContent = 'Ver Planilla Anual';
        if (btnIcon) btnIcon.className = 'bi bi-grid-3x3-gap me-2';
        form.action = ROUTE_PLANILLA_AC;
        document.getElementById('input-periodo-id').value = '';
    } else {
        if (btnText) btnText.textContent = 'Ir a Grilla de Notas';
        if (btnIcon) btnIcon.className = 'bi bi-table me-2';
        form.action = ROUTE_GRILLA;
    }

    const ok = esAcademica ? !!asigId : (asigId && periodoId);
    btn.disabled = !ok;

    const alerta = document.getElementById('alerta-seleccion');
    const alertaTxt = document.getElementById('alerta-texto');
    if (!ok && selectedGrupoId) {
        alerta.classList.remove('d-none');
        if (alertaTxt) alertaTxt.textContent = esAcademica
            ? 'Selecciona una asignatura para ver la planilla anual.'
            : 'Selecciona una asignatura y un período para continuar.';
    } else {
        alerta.classList.add('d-none');
    }
}
</script>

@endisset

@endsection
