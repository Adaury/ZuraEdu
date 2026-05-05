@extends('layouts.admin')
@section('page-title', $grupo->nombreCorto . ' — Detalle del Grupo')

@push('styles')
<style>
    .section-card {
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        margin-bottom:1.5rem;
    }
    .section-card-header {
        padding:.85rem 1.25rem;
        border-bottom:1px solid #f3f4f6;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
        flex-wrap:wrap;
    }
    .section-card-header .title {
        font-size:.78rem;
        font-weight:700;
        letter-spacing:.1em;
        text-transform:uppercase;
        color:var(--primary);
        display:flex;
        align-items:center;
        gap:.5rem;
    }
    .section-card-body { padding:1.1rem 1.25rem; }
    .stat-pill {
        display:inline-flex;
        align-items:center;
        gap:.4rem;
        padding:.3rem .8rem;
        border-radius:20px;
        font-size:.78rem;
        font-weight:600;
        background:#f0f4ff;
        color:var(--primary);
        border:1px solid #dbeafe;
    }
    .asig-color-dot {
        width:10px;height:10px;
        border-radius:50%;
        flex-shrink:0;
    }
    .table-clean th {
        font-size:.72rem;
        font-weight:700;
        letter-spacing:.07em;
        text-transform:uppercase;
        color:#6b7280;
        background:#f8faff;
        border-bottom:2px solid #e5e7eb;
        white-space:nowrap;
    }
    .table-clean td { font-size:.84rem; vertical-align:middle; }
    .avatar-sm {
        width:32px;height:32px;
        border-radius:50%;
        object-fit:cover;
    }
    .avatar-initials-sm {
        width:32px;height:32px;
        border-radius:50%;
        background:linear-gradient(135deg,#2a4f96,var(--primary));
        color:#fff;
        font-size:.65rem;
        font-weight:700;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }
    .add-form-panel {
        background:#f8faff;
        border:1px dashed #bfdbfe;
        border-radius:10px;
        padding:1rem 1.1rem;
        margin-bottom:.5rem;
    }
    .btn-quick {
        padding:.28rem .7rem;
        font-size:.78rem;
        border-radius:7px;
        font-weight:600;
    }
    .link-module {
        display:flex;
        align-items:center;
        gap:.6rem;
        padding:.6rem .9rem;
        border-radius:10px;
        border:1px solid #e5e7eb;
        background:#fff;
        color:#374151;
        font-size:.82rem;
        font-weight:600;
        text-decoration:none;
        transition:background .15s,border-color .15s,color .15s;
    }
    .link-module:hover {
        background:#eff6ff;
        border-color:var(--primary);
        color:var(--primary);
    }
    .link-module i { font-size:1rem; }
    .tutor-row {
        display:flex;
        align-items:center;
        gap:.9rem;
    }
    .tutor-avatar {
        width:46px;height:46px;
        border-radius:50%;
        background:linear-gradient(135deg,#1e3a6e,#2a5298);
        color:#fff;
        font-size:1rem;
        font-weight:800;
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }
    .badge-estado-activa  { background:#d1fae5;color:#065f46; }
    .badge-estado-retirada{ background:#fee2e2;color:#991b1b; }
    .asig-check-row:hover { background:#f8faff; }
    .asig-check-row:last-child { border-bottom:none !important; }
    .asig-check-row:has(input:checked) { background:#eff6ff; }

    [data-theme="dark"] .section-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .section-card-header { border-bottom-color: #334155; }
    [data-theme="dark"] .stat-pill { background: #162032; border-color: #334155; }
    [data-theme="dark"] .badge-estado-activa { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-estado-retirada { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .asig-check-row:hover { background: #162032; }
    [data-theme="dark"] .asig-check-row:has(input:checked) { background: #0c1f3f; }
</style>
@endpush

@section('content')

@php
    $nivel   = $grupo->grado->nivel;
    $cicloNom = $nivel <= 3 ? 'Primer Ciclo' : 'Segundo Ciclo';
    $nombreCorto = $grupo->nombreCorto;
    $asignacionCount = $grupo->asignaciones->count();
    $matriculaCount  = $grupo->matriculas->count();
@endphp

{{-- ── Breadcrumb / header ─────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.grupos.index') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Grupos
    </a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="mb-0 fw-800" style="font-size:1.55rem;color:var(--primary);">
                {{ $nombreCorto }}
            </h1>
            <span class="badge" style="background:var(--primary);color:#fff;font-size:.68rem;border-radius:8px;padding:.3rem .7rem;">
                {{ $cicloNom }}
            </span>
            @if(!$grupo->activo)
                <span class="badge bg-secondary" style="font-size:.68rem;border-radius:8px;">Inactivo</span>
            @endif
        </div>
        <p class="text-muted mb-0 mt-1" style="font-size:.8rem;">
            {{ $grupo->grado->nombre }} · Sección {{ $grupo->seccion->nombre }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
            @if($grupo->aula) · Aula <strong>{{ $grupo->aula }}</strong> @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.grupos.lista-pdf', $grupo) }}" target="_blank"
           class="btn btn-sm fw-semibold" style="background:#1e3a6e;color:#fff;border-radius:8px;">
            <i class="bi bi-file-earmark-text me-1"></i>Lista PDF
        </a>
        <a href="{{ route('admin.grupos.lista-excel', $grupo) }}"
           class="btn btn-sm fw-semibold" style="background:#166534;color:#fff;border-radius:8px;">
            <i class="bi bi-file-earmark-excel me-1"></i>Lista
        </a>
        <a href="{{ route('admin.grupos.notas-excel', $grupo) }}"
           class="btn btn-sm fw-semibold" style="background:#065f46;color:#fff;border-radius:8px;">
            <i class="bi bi-table me-1"></i>Notas Excel
        </a>
        <a href="{{ route('admin.grupos.notas-pdf', $grupo) }}" target="_blank"
           class="btn btn-sm fw-semibold" style="background:#dc2626;color:#fff;border-radius:8px;">
            <i class="bi bi-file-earmark-pdf me-1"></i>Notas PDF
        </a>
        <a href="{{ route('admin.grupos.asistencia-excel', $grupo) }}"
           class="btn btn-sm fw-semibold" style="background:#0369a1;color:#fff;border-radius:8px;">
            <i class="bi bi-calendar-check me-1"></i>Asistencia
        </a>
        <a href="{{ route('admin.grupos.asistencia-pdf', $grupo) }}" target="_blank"
           class="btn btn-sm fw-semibold" style="background:#dc2626;color:#fff;border-radius:8px;">
            <i class="bi bi-file-earmark-pdf me-1"></i>Asist. PDF
        </a>
        <a href="{{ route('admin.grupos.carnets-pdf', $grupo) }}" target="_blank"
           class="btn btn-sm fw-semibold" style="background:#7c3aed;color:#fff;border-radius:8px;">
            <i class="bi bi-person-badge me-1"></i>Carnets PDF
        </a>
        <a href="{{ route('admin.grupos.edit', $grupo) }}"
           class="btn btn-sm btn-outline-secondary fw-semibold" style="border-radius:8px;">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </div>
</div>

{{-- Flash --}}
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2 px-3" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-exclamation-triangle-fill"></i>{{ session('error') }}
    </div>
@endif

{{-- ── Stats + Quick Links ─────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-auto">
        <span class="stat-pill">
            <i class="bi bi-people-fill"></i>
            <strong>{{ $matriculaCount }}</strong> estudiante{{ $matriculaCount !== 1 ? 's' : '' }}
            @if($grupo->capacidad)
                <span style="color:#9ca3af;font-weight:400;">/ {{ $grupo->capacidad }}</span>
            @endif
        </span>
    </div>
    <div class="col-auto">
        <span class="stat-pill">
            <i class="bi bi-book-fill"></i>
            <strong>{{ $asignacionCount }}</strong> asignatura{{ $asignacionCount !== 1 ? 's' : '' }}
        </span>
    </div>
    <div class="col-auto">
        <span class="stat-pill" style="background:#f0fdf4;color:#166534;border-color:#bbf7d0;">
            <i class="bi bi-person-badge-fill"></i>
            {{ $grupo->tutor ? $grupo->tutor->name : 'Sin maestro guía' }}
        </span>
    </div>
</div>

{{-- ── Accesos rápidos ─────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 flex-wrap mb-4">
    <a href="{{ route('admin.asistencia.index', ['grupo_id' => $grupo->id]) }}" class="link-module">
        <i class="bi bi-calendar-check" style="color:#10b981;"></i>Asistencia
    </a>
    <a href="{{ route('admin.calificaciones.index', ['grupo_id' => $grupo->id]) }}" class="link-module">
        <i class="bi bi-journal-check" style="color:#3b82f6;"></i>Notas
    </a>
    <a href="{{ route('admin.boletines.index', ['grupo_id' => $grupo->id]) }}" class="link-module">
        <i class="bi bi-file-earmark-text" style="color:#8b5cf6;"></i>Boletines
    </a>
    <a href="{{ route('admin.matriculas.index', ['grupo_id' => $grupo->id]) }}" class="link-module">
        <i class="bi bi-person-lines-fill" style="color:#f59e0b;"></i>Matrículas
    </a>
    <a href="{{ route('admin.observaciones.index', ['grupo_id' => $grupo->id]) }}" class="link-module">
        <i class="bi bi-chat-square-text" style="color:#d97706;"></i>Observaciones
    </a>
    <a href="{{ route('admin.planificacion.index', ['asignacion_id' => '']) }}?grupo_id={{ $grupo->id }}" class="link-module">
        <i class="bi bi-journal-text" style="color:#7c3aed;"></i>Planificaciones
    </a>
    <a href="{{ route('admin.rendimiento.porGrupo', ['grupo_id' => $grupo->id]) }}" class="link-module">
        <i class="bi bi-bar-chart-fill" style="color:#0891b2;"></i>Rendimiento
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">

        {{-- ── 1. MAESTRO GUÍA ─────────────────────────────────────────── --}}
        <div class="section-card">
            <div class="section-card-header">
                <span class="title"><i class="bi bi-person-badge-fill"></i>Maestro Guía</span>
                <button class="btn btn-quick btn-outline-primary"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#formTutor">
                    <i class="bi bi-pencil me-1"></i>Cambiar
                </button>
            </div>
            <div class="section-card-body">
                {{-- Current tutor --}}
                @if($grupo->tutor)
                    @php $inicial = strtoupper(substr($grupo->tutor->name, 0, 1)); @endphp
                    <div class="tutor-row">
                        <div class="tutor-avatar">{{ $inicial }}</div>
                        <div>
                            <div class="fw-semibold" style="color:#111827;font-size:.9rem;">
                                {{ $grupo->tutor->name }}
                            </div>
                            <div style="font-size:.74rem;color:#9ca3af;">Maestro/a guía del grupo</div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-2" style="color:#9ca3af;font-size:.84rem;">
                        <i class="bi bi-person-dash d-block mb-1" style="font-size:1.5rem;"></i>
                        Sin maestro guía asignado
                    </div>
                @endif

                {{-- Change form --}}
                <div class="collapse mt-3" id="formTutor">
                    <form action="{{ route('admin.grupos.updateTutor', $grupo) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="mb-2">
                            <label class="form-label fw-semibold mb-1" style="font-size:.8rem;">
                                Seleccionar docente
                            </label>
                            <select name="tutor_docente_id" class="form-select form-select-sm" style="border-radius:8px;">
                                <option value="">— Sin maestro guía —</option>
                                @foreach($docentes as $doc)
                                    <option value="{{ $doc->id }}"
                                        {{ $grupo->tutorDocenteId == $doc->id ? 'selected' : '' }}>
                                        {{ $doc->apellidos }}, {{ $doc->nombres }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100" style="border-radius:8px;">
                            <i class="bi bi-check-lg me-1"></i>Guardar maestro guía
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── 2. RESUMEN RÁPIDO ───────────────────────────────────────── --}}
        <div class="section-card">
            <div class="section-card-header">
                <span class="title"><i class="bi bi-info-circle-fill"></i>Información del grupo</span>
            </div>
            <div class="section-card-body">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width:40%">Grado</td>
                            <td class="fw-semibold">{{ $grupo->grado->nombre }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Sección</td>
                            <td class="fw-semibold">{{ $grupo->seccion->nombre }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Año escolar</td>
                            <td class="fw-semibold">{{ $schoolYear->nombre ?? '—' }}</td>
                        </tr>
                        @if($grupo->aula)
                        <tr>
                            <td class="text-muted">Aula</td>
                            <td class="fw-semibold">{{ $grupo->aula }}</td>
                        </tr>
                        @endif
                        @if($grupo->capacidad)
                        <tr>
                            <td class="text-muted">Capacidad</td>
                            <td class="fw-semibold">{{ $grupo->capacidad }} estudiantes</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Ciclo</td>
                            <td class="fw-semibold">{{ $cicloNom }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Estado</td>
                            <td>
                                <span class="badge {{ $grupo->activo ? 'bg-success' : 'bg-secondary' }}"
                                      style="font-size:.68rem;border-radius:6px;">
                                    {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- col-lg-4 --}}

    <div class="col-lg-8">

        {{-- ── 3. ASIGNATURAS Y DOCENTES ───────────────────────────────── --}}
        <div class="section-card">
            <div class="section-card-header">
                <span class="title"><i class="bi bi-book-fill"></i>Asignaturas y Docentes</span>
                <button class="btn btn-quick"
                        style="background:var(--primary);color:#fff;"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#formAsignacion">
                    <i class="bi bi-plus-lg me-1"></i>Agregar asignatura
                </button>
            </div>

            {{-- Add asignacion form --}}
            <div class="collapse" id="formAsignacion">
                <div class="section-card-body pb-0">
                    <div class="add-form-panel">
                        <p class="fw-semibold mb-2" style="font-size:.8rem;color:var(--primary);">
                            <i class="bi bi-plus-circle me-1"></i>Agregar asignaturas a {{ $nombreCorto }}
                        </p>
                        @if($asignaturasDisponibles->isEmpty())
                            <p class="text-muted mb-0" style="font-size:.82rem;">
                                <i class="bi bi-check-circle me-1 text-success"></i>
                                Todas las asignaturas disponibles ya están asignadas a este grupo.
                            </p>
                        @else
                        <form action="{{ route('admin.asignaciones.store') }}" method="POST" id="formAsig">
                            @csrf
                            <input type="hidden" name="school_year_id"    value="{{ $schoolYear?->id }}">
                            <input type="hidden" name="grupo_id"           value="{{ $grupo->id }}">
                            <input type="hidden" name="redirect_grupo_id"  value="{{ $grupo->id }}">

                            {{-- Lista de asignaturas con checkboxes --}}
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label mb-0" style="font-size:.75rem;font-weight:600;">
                                        Seleccionar asignaturas <span class="text-muted fw-normal">(mín. 1 — máx. todas)</span>
                                    </label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-link p-0 text-primary"
                                                style="font-size:.72rem;font-weight:700;text-decoration:none;"
                                                onclick="toggleAll(true)">
                                            Seleccionar todas
                                        </button>
                                        <span style="color:#d1d5db;font-size:.72rem;">|</span>
                                        <button type="button" class="btn btn-link p-0 text-secondary"
                                                style="font-size:.72rem;font-weight:700;text-decoration:none;"
                                                onclick="toggleAll(false)">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>
                                <div id="asigCheckboxList"
                                     style="max-height:220px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;background:#fff;">
                                    @foreach($asignaturasDisponibles as $asig)
                                    <label class="asig-check-row d-flex align-items-center gap-2 px-3 py-2"
                                           style="cursor:pointer;border-bottom:1px solid #f3f4f6;margin:0;font-size:.84rem;">
                                        <input type="checkbox" name="asignaturas[]" value="{{ $asig->id }}"
                                               class="form-check-input asig-chk flex-shrink-0"
                                               style="width:16px;height:16px;cursor:pointer;"
                                               onchange="actualizarContador()">
                                        @if($asig->color)
                                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $asig->color }};flex-shrink:0;display:inline-block;"></span>
                                        @endif
                                        <span>{{ $asig->nombre }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                <div class="mt-1 d-flex align-items-center gap-2">
                                    <span id="cntAsig" style="font-size:.75rem;color:#6b7280;">
                                        0 seleccionadas
                                    </span>
                                </div>
                            </div>

                            {{-- Aplicar a todas las secciones --}}
                            @php
                                $otrasSeccionesCount = \App\Models\Grupo::where('school_year_id', $schoolYear?->id)
                                    ->where('grado_id', $grupo->grado_id)
                                    ->where('id', '!=', $grupo->id)
                                    ->count();
                            @endphp
                            @if($otrasSeccionesCount > 0)
                            <div class="mb-3 p-2" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;">
                                <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;font-size:.83rem;font-weight:600;color:#1d4ed8;">
                                    <input type="checkbox" name="todas_secciones" value="1"
                                           class="form-check-input flex-shrink-0" style="width:16px;height:16px;cursor:pointer;"
                                           id="chkTodasSecciones">
                                    <span>
                                        <i class="bi bi-layers-fill me-1"></i>
                                        Agregar a <strong>todas las secciones</strong> de {{ $grupo->grado->nombre }}
                                        <span class="fw-normal text-primary" style="font-size:.76rem;">
                                            ({{ $otrasSeccionesCount + 1 }} secciones en total)
                                        </span>
                                    </span>
                                </label>
                                <div style="font-size:.73rem;color:#3b82f6;margin-top:.25rem;padding-left:24px;">
                                    Las asignaturas se agregarán a todos los grupos del mismo grado que no las tengan aún.
                                </div>
                            </div>
                            @endif

                            {{-- Opciones comunes --}}
                            <div class="row g-2 mb-2">
                                <div class="col-sm-6">
                                    <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">
                                        Docente <span class="text-muted fw-normal">(opcional, se puede cambiar después)</span>
                                    </label>
                                    <select name="docente_id" class="form-select form-select-sm" style="border-radius:7px;">
                                        <option value="">— Sin docente asignado —</option>
                                        @foreach($docentes as $doc)
                                            <option value="{{ $doc->id }}">{{ $doc->apellidos }}, {{ $doc->nombres }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Área</label>
                                    <select name="area" class="form-select form-select-sm" style="border-radius:7px;">
                                        <option value="academica" {{ $nivel <= 3 ? 'selected' : '' }}>Académica</option>
                                        <option value="tecnica"   {{ $nivel > 3  ? 'selected' : '' }}>Técnica</option>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Tipo evaluación</label>
                                    <select name="tipo_evaluacion" class="form-select form-select-sm" style="border-radius:7px;">
                                        <option value="indicadores_logro" {{ $nivel <= 3 ? 'selected' : '' }}>Indicadores de Logro</option>
                                        <option value="competencias"      {{ $nivel > 3  ? 'selected' : '' }}>Por Competencia</option>
                                        <option value="componentes">Componentes</option>
                                        <option value="ra">RA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" id="btnGuardarAsig"
                                        class="btn btn-sm btn-primary flex-fill" style="border-radius:7px;" disabled>
                                    <i class="bi bi-check-lg me-1"></i>
                                    <span id="btnAsigLabel">Selecciona al menos una asignatura</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="collapse" data-bs-target="#formAsignacion"
                                        style="border-radius:7px;">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="section-card-body pt-2">
                @if($grupo->asignaciones->isEmpty())
                    <div class="text-center py-3" style="color:#9ca3af;font-size:.84rem;">
                        <i class="bi bi-book d-block mb-1" style="font-size:1.5rem;"></i>
                        Aún no hay asignaturas asignadas a este grupo.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-clean table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Asignatura</th>
                                    <th>Docente</th>
                                    <th class="text-center">Tipo Eval.</th>
                                    <th class="text-center">Área</th>
                                    <th class="text-center">Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo->asignaciones as $asig)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="asig-color-dot"
                                                  style="background:{{ $asig->asignatura->color ?? '#6b7280' }};"></span>
                                            <span class="fw-semibold" style="color:#111827;">
                                                {{ $asig->asignatura->nombre }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($asig->docente)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-initials-sm">
                                                    {{ strtoupper(substr($asig->docente->nombres, 0, 1)) }}{{ strtoupper(substr($asig->docente->apellidos, 0, 1)) }}
                                                </div>
                                                <span>{{ $asig->docente->apellidos }}, {{ $asig->docente->nombres }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">Sin docente</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="font-size:.65rem;border-radius:6px;
                                            background:{{ in_array($asig->tipo_evaluacion, ['ra','competencias']) ? '#ede9fe' : '#dbeafe' }};
                                            color:{{ in_array($asig->tipo_evaluacion, ['ra','competencias']) ? '#5b21b6' : '#1e40af' }};">
                                            @php
                                                $labelTipo = match($asig->tipo_evaluacion) {
                                                    'indicadores_logro' => 'Ind. Logro',
                                                    'competencias'      => 'Competencia',
                                                    'ra'                => 'RA',
                                                    default             => 'Componentes',
                                                };
                                            @endphp
                                            {{ $labelTipo }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="font-size:.65rem;border-radius:6px;
                                            background:{{ $asig->area === 'tecnica' ? '#fef3c7' : '#d1fae5' }};
                                            color:{{ $asig->area === 'tecnica' ? '#92400e' : '#065f46' }};">
                                            {{ ucfirst($asig->area) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $asig->activo ? 'bg-success' : 'bg-secondary' }}"
                                              style="font-size:.65rem;border-radius:6px;">
                                            {{ $asig->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('admin.asistencia.registrar', $asig) }}"
                                               class="btn btn-quick btn-outline-success" title="Registrar asistencia">
                                                <i class="bi bi-calendar-check"></i>
                                            </a>
                                            <form action="{{ route('admin.asignaciones.destroy', $asig) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Eliminar la asignación de {{ $asig->asignatura->nombre }}? Solo es posible si no tiene calificaciones.')">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="redirect_grupo_id" value="{{ $grupo->id }}">
                                                <button type="submit" class="btn btn-quick btn-outline-danger" title="Eliminar asignación">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── 4. ESTUDIANTES MATRICULADOS ─────────────────────────────── --}}
        <div class="section-card">
            <div class="section-card-header">
                <span class="title"><i class="bi bi-people-fill"></i>Estudiantes matriculados
                    <span class="badge bg-primary ms-1" style="font-size:.65rem;border-radius:6px;">
                        {{ $matriculaCount }}
                    </span>
                </span>
                <button class="btn btn-quick"
                        style="background:var(--primary);color:#fff;"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#formMatricula">
                    <i class="bi bi-plus-lg me-1"></i>Matricular estudiante
                </button>
            </div>

            {{-- Enroll form --}}
            <div class="collapse" id="formMatricula">
                <div class="section-card-body pb-0">
                    <div class="add-form-panel">
                        <p class="fw-semibold mb-2" style="font-size:.8rem;color:var(--primary);">
                            <i class="bi bi-person-plus me-1"></i>Matricular en {{ $nombreCorto }}
                        </p>
                        @if($estudiantesDisponibles->isEmpty())
                            <p class="text-muted mb-0" style="font-size:.82rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Todos los estudiantes ya están matriculados en algún grupo del año escolar actual.
                                <a href="{{ route('admin.estudiantes.index') }}">Ver lista general</a>.
                            </p>
                        @else
                            <form action="{{ route('admin.matriculas.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="school_year_id"    value="{{ $schoolYear?->id }}">
                                <input type="hidden" name="grupo_id"          value="{{ $grupo->id }}">
                                <input type="hidden" name="fecha_matricula"   value="{{ now()->toDateString() }}">
                                <input type="hidden" name="redirect_grupo_id" value="{{ $grupo->id }}">
                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-7">
                                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">
                                            Estudiante <span style="color:#9ca3af;font-weight:400;">(sin matrícula este año)</span>
                                        </label>
                                        <select name="estudiante_id" class="form-select form-select-sm" required
                                                style="border-radius:7px;" id="estudianteSelect">
                                            <option value="">— Buscar y seleccionar —</option>
                                            @foreach($estudiantesDisponibles as $est)
                                                <option value="{{ $est->id }}">
                                                    {{ $est->apellidos }}, {{ $est->nombres }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-5">
                                        <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">
                                            Observaciones <span style="color:#9ca3af;">(opcional)</span>
                                        </label>
                                        <input type="text" name="observaciones" class="form-control form-control-sm"
                                               placeholder="Notas opcionales…" style="border-radius:7px;">
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-primary flex-fill" style="border-radius:7px;">
                                            <i class="bi bi-person-check me-1"></i>Confirmar matrícula
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="collapse" data-bs-target="#formMatricula"
                                                style="border-radius:7px;">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="section-card-body pt-2">
                @if($grupo->matriculas->isEmpty())
                    <div class="text-center py-3" style="color:#9ca3af;font-size:.84rem;">
                        <i class="bi bi-people d-block mb-1" style="font-size:1.5rem;"></i>
                        No hay estudiantes matriculados en este grupo.
                        Haz clic en <strong>"Matricular estudiante"</strong> para agregar.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-clean table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th>Estudiante</th>
                                    <th>Matrícula</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo->matriculas as $mat)
                                <tr>
                                    <td class="text-center" style="color:#9ca3af;font-size:.78rem;font-weight:700;">
                                        {{ $mat->numero_orden ?? $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($mat->estudiante->foto)
                                                <img src="{{ asset('storage/'.$mat->estudiante->foto) }}"
                                                     class="avatar-sm" alt="">
                                            @else
                                                <div class="avatar-initials-sm">
                                                    {{ substr($mat->estudiante->nombres, 0, 1) }}{{ substr($mat->estudiante->apellidos, 0, 1) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold" style="color:#111827;font-size:.84rem;">
                                                    {{ $mat->estudiante->apellidos }}, {{ $mat->estudiante->nombres }}
                                                </div>
                                                @if($mat->estudiante->cedula)
                                                    <div style="font-size:.72rem;color:#9ca3af;">{{ $mat->estudiante->cedula }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-family:monospace;font-size:.8rem;color:#2563eb;font-weight:700;">
                                        {{ $mat->estudiante->numero_matricula ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-estado-{{ $mat->estado }}"
                                              style="font-size:.68rem;border-radius:6px;padding:.25rem .55rem;">
                                            {{ ucfirst($mat->estado) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('admin.estudiantes.show', $mat->estudiante) }}"
                                               class="btn btn-quick btn-outline-primary" title="Ver perfil">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.asistencia.reporteEstudiante', $mat) }}"
                                               class="btn btn-quick btn-outline-success" title="Asistencia del estudiante">
                                                <i class="bi bi-calendar-check"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- col-lg-8 --}}
</div>

@endsection

@push('scripts')
<script>
// ── Asignaturas: selección múltiple ──────────────────────────────────────
function actualizarContador() {
    const checks  = document.querySelectorAll('.asig-chk');
    const sel     = Array.from(checks).filter(c => c.checked).length;
    const total   = checks.length;
    const cnt     = document.getElementById('cntAsig');
    const btn     = document.getElementById('btnGuardarAsig');
    const lbl     = document.getElementById('btnAsigLabel');

    if (cnt) cnt.textContent = sel + ' de ' + total + ' seleccionada' + (sel !== 1 ? 's' : '');
    if (btn) btn.disabled = sel === 0;
    if (lbl) {
        lbl.textContent = sel === 0
            ? 'Selecciona al menos una asignatura'
            : (sel === 1 ? 'Agregar 1 asignatura' : 'Agregar ' + sel + ' asignaturas');
    }
}

function toggleAll(estado) {
    document.querySelectorAll('.asig-chk').forEach(c => c.checked = estado);
    actualizarContador();
}

// Make student select searchable with simple filter
(function () {
    const sel = document.getElementById('estudianteSelect');
    if (!sel) return;
    // If there are many students, add a search input above the select
    if (sel.options.length > 10) {
        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Filtrar estudiantes…';
        input.className = 'form-control form-control-sm mb-1';
        input.style.cssText = 'border-radius:7px;font-size:.82rem;';
        sel.parentNode.insertBefore(input, sel);
        input.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            Array.from(sel.options).forEach(opt => {
                opt.hidden = opt.value !== '' && !opt.text.toLowerCase().includes(q);
            });
        });
    }
})();
</script>
@endpush
