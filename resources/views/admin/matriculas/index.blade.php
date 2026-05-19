@extends('layouts.admin')

@section('page-title', 'Matrículas')

@push('styles')
<style>
    .filter-bar {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.25rem;
    }
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
    }
    .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #2563eb;
        padding: .75rem 1rem;
        white-space: nowrap;
    }
    .table tbody td {
        padding: .7rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }
    .student-photo {
        width: 34px; height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
    }
    .student-photo-placeholder {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        font-weight: 800;
        color: #fff;
        border: 2px solid #e5e7eb;
        flex-shrink: 0;
    }
    .badge-estado {
        font-size: .68rem;
        font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 20px;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .badge-activa   { background: #d1fae5; color: #065f46; }
    .badge-retirada { background: #fee2e2; color: #991b1b; }
    .badge-trasladada { background: #fef3c7; color: #92400e; }
    .badge-promovida { background: #dbeafe; color: #1e40af; }
    .grupo-chip {
        background: #eef2ff;
        color: var(--primary);
        border-radius: 6px;
        padding: .15rem .5rem;
        font-size: .75rem;
        font-weight: 700;
    }
    .btn-action {
        padding: .22rem .5rem;
        font-size: .75rem;
        border-radius: 6px;
        line-height: 1.4;
    }
    .stats-pill {
        background: #f0f4f8;
        border: 1px solid #dde3ef;
        border-radius: 8px;
        padding: .35rem .75rem;
        font-size: .78rem;
        color: var(--primary);
        font-weight: 600;
    }
    .nombre-mat {
        font-weight: 700;
        color: #1d4ed8;
        font-size: .83rem;
        line-height: 1.2;
    }
    .filter-label {
        font-size: .75rem;
        font-weight: 600;
        color: #2563eb;
    }

    /* ── Dark mode ─────────────────────────────────────── */
    [data-theme="dark"] .filter-bar {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .table-card {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-theme="dark"] .table thead th {
        background: #1e3a8a !important;
        border-color: #334155 !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .table tbody td {
        border-color: #334155 !important;
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .table tbody tr:hover td {
        background: #334155 !important;
    }
    [data-theme="dark"] .student-photo,
    [data-theme="dark"] .student-photo-placeholder {
        border-color: #334155 !important;
    }
    [data-theme="dark"] .grupo-chip {
        background: rgba(59,130,246,.18) !important;
        color: #93c5fd !important;
    }
    [data-theme="dark"] .stats-pill {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #60a5fa !important;
    }
    [data-theme="dark"] .nombre-mat { color: #93c5fd !important; }
    [data-theme="dark"] .filter-label { color: #60a5fa !important; }
    [data-theme="dark"] .pagination-footer {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #60a5fa !important;
    }
</style>
@endpush

@section('content')

{{-- ── Alerta: sin año escolar activo ────────────────────────────────────── --}}
@unless($schoolYear)
<div class="alert border-0 mb-4" role="alert"
     style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border-left:4px solid #f97316 !important;border-radius:12px;padding:1.1rem 1.4rem;">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5rem;color:#f97316;flex-shrink:0;margin-top:.1rem;"></i>
        <div style="flex:1;">
            <div style="font-weight:800;font-size:.95rem;color:#9a3412;margin-bottom:.25rem;">
                No hay un Año Escolar activo
            </div>
            <div style="font-size:.83rem;color:#c2410c;line-height:1.5;">
                Para registrar y visualizar matrículas necesitas crear un Año Escolar y marcarlo como activo.
                Sin eso el sistema no puede asignar estudiantes a grupos ni generar registros académicos.
            </div>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                <a href="{{ route('admin.school-years.create') }}"
                   class="btn btn-sm fw-bold"
                   style="background:#f97316;color:#fff;border-radius:8px;padding:.4rem 1rem;font-size:.82rem;">
                    <i class="bi bi-plus-circle me-1"></i>Crear Año Escolar
                </a>
                <a href="{{ route('admin.school-years.index') }}"
                   class="btn btn-sm fw-semibold"
                   style="background:#fff3e0;color:#c2410c;border:1px solid #fdba74;border-radius:8px;padding:.4rem 1rem;font-size:.82rem;">
                    <i class="bi bi-gear me-1"></i>Ver Años Escolares
                </a>
            </div>
        </div>
    </div>
</div>
@endunless

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-3 p-slide-up">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-card-list me-2"></i>Matrículas
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            @if($schoolYear)
                Año escolar: <strong>{{ $schoolYear->nombre }}</strong> &mdash;
            @endif
            <span class="stats-pill">
                <i class="bi bi-people me-1"></i>{{ $matriculas->total() }} matrícula{{ $matriculas->total() !== 1 ? 's' : '' }}
            </span>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.matriculas.resumen') }}" class="btn btn-outline-primary btn-sm fw-semibold">
            <i class="bi bi-bar-chart-line me-1"></i>Resumen
        </a>
        <a href="{{ route('admin.matriculas.lista-pdf', request()->query()) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.matriculas.lista-excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button class="btn btn-sm fw-semibold btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalMasivo">
            <i class="bi bi-people me-1"></i>Masivo
        </button>
        <a href="{{ route('admin.matriculas.create') }}" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Matrícula
        </a>
    </div>
</div>

{{-- Stats rápidas --}}
@if($schoolYear)
<div class="d-flex gap-2 flex-wrap mb-3">
    @php
        $totalActivas    = $stats['activa']      ?? 0;
        $totalRetiradas  = $stats['retirada']    ?? 0;
        $totalTransferidas = $stats['transferida'] ?? 0;
        $totalAll        = collect($stats)->sum();
    @endphp
    <div class="stats-pill"><i class="bi bi-check-circle-fill text-success me-1"></i><strong>{{ $totalActivas }}</strong> activas</div>
    @if($totalRetiradas > 0)
    <div class="stats-pill"><i class="bi bi-x-circle-fill text-danger me-1"></i><strong>{{ $totalRetiradas }}</strong> retiradas</div>
    @endif
    @if($totalTransferidas > 0)
    <div class="stats-pill"><i class="bi bi-arrow-left-right text-warning me-1"></i><strong>{{ $totalTransferidas }}</strong> transferidas</div>
    @endif
    @if($inscPendientes > 0)
    <a href="{{ route('admin.inscripciones.index') }}" class="stats-pill text-decoration-none" style="background:#fffbeb;border-color:#fbbf24;color:#b45309;">
        <i class="bi bi-clock-fill me-1" style="color:#f59e0b"></i><strong>{{ $inscPendientes }}</strong> inscripciones pendientes de asignar
    </a>
    @endif
</div>
@endif

{{-- Tabs de ciclo --}}
@php
    $cicloActual = $ciclo ?? '';
    $qTodos    = http_build_query(array_merge(request()->except('ciclo','grupo_id','page'), []));
    $qPrimer   = http_build_query(array_merge(request()->except('ciclo','grupo_id','page'), ['ciclo' => 'primer_ciclo']));
    $qSegundo  = http_build_query(array_merge(request()->except('ciclo','grupo_id','page'), ['ciclo' => 'segundo_ciclo']));
@endphp
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.matriculas.index') }}{{ $qTodos ? '?'.$qTodos : '' }}"
       class="btn btn-sm fw-semibold {{ $cicloActual === '' ? 'active' : '' }}"
       style="border-radius:8px;border:1.5px solid #e5e7eb;background:{{ $cicloActual==='' ? 'var(--primary)' : '#f8fafc' }};color:{{ $cicloActual==='' ? '#fff' : '#374151' }};font-size:.78rem;">
        Todos los ciclos
    </a>
    <a href="{{ route('admin.matriculas.index') }}?{{ $qPrimer }}"
       class="btn btn-sm fw-semibold"
       style="border-radius:8px;border:1.5px solid #bfdbfe;background:{{ $cicloActual==='primer_ciclo' ? '#1e40af' : '#eff6ff' }};color:{{ $cicloActual==='primer_ciclo' ? '#fff' : '#1e40af' }};font-size:.78rem;">
        <i class="bi bi-1-circle me-1"></i>Primer Ciclo <span style="opacity:.7;font-weight:400;">(1ro – 3ro)</span>
    </a>
    <a href="{{ route('admin.matriculas.index') }}?{{ $qSegundo }}"
       class="btn btn-sm fw-semibold"
       style="border-radius:8px;border:1.5px solid #6ee7b7;background:{{ $cicloActual==='segundo_ciclo' ? '#065f46' : '#ecfdf5' }};color:{{ $cicloActual==='segundo_ciclo' ? '#fff' : '#065f46' }};font-size:.78rem;">
        <i class="bi bi-2-circle me-1"></i>Segundo Ciclo <span style="opacity:.7;font-weight:400;">(4to – 6to)</span>
    </a>
</div>

{{-- Session alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="filter-bar p-slide-up p-delay-1">
    <form method="GET" action="{{ route('admin.matriculas.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-sm-5 col-md-4">
            <label class="form-label mb-1 filter-label">
                <i class="bi bi-search me-1"></i>Buscar estudiante
            </label>
            <input type="text" name="search" class="form-control form-control-sm" style="border-radius:8px;"
                   value="{{ request('search') }}"
                   placeholder="Nombre, apellido o N° matrícula...">
        </div>
        <input type="hidden" name="ciclo" value="{{ $cicloActual }}">
        <div class="col-12 col-sm-4 col-md-3">
            <label class="form-label mb-1 filter-label">
                <i class="bi bi-grid me-1"></i>Grupo
            </label>
            <select name="grupo_id" class="form-select form-select-sm" style="border-radius:8px;">
                <option value="">Todos los grupos</option>
                @php
                    $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                    $gruposPorCiclo = $grupos->groupBy(fn($g) => $g->grado->ciclo ?? 'primer_ciclo');
                @endphp
                @foreach(['primer_ciclo' => 'Primer Ciclo (1ro–3ro)', 'segundo_ciclo' => 'Segundo Ciclo (4to–6to)'] as $cKey => $cLabel)
                    @if($gruposPorCiclo->has($cKey))
                        @if($cicloActual === '')
                            <optgroup label="{{ $cLabel }}">
                        @endif
                        @foreach($gruposPorCiclo[$cKey] as $g)
                            @php $label = ($niveles[$g->grado->nivel ?? 0] ?? '') . ' ' . ($g->seccion->nombre ?? ''); @endphp
                            <option value="{{ $g->id }}" {{ request('grupo_id') == $g->id ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                        @if($cicloActual === '')
                            </optgroup>
                        @endif
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-12 col-sm-3 col-md-2">
            <label class="form-label mb-1 filter-label">
                <i class="bi bi-tag me-1"></i>Estado
            </label>
            <select name="estado" class="form-select form-select-sm" style="border-radius:8px;">
                <option value="">Todos</option>
                <option value="activa"      {{ request('estado') === 'activa'      ? 'selected' : '' }}>Activa</option>
                <option value="retirada"    {{ request('estado') === 'retirada'    ? 'selected' : '' }}>Retirada</option>
                <option value="trasladada"  {{ request('estado') === 'trasladada'  ? 'selected' : '' }}>Trasladada</option>
                <option value="promovida"   {{ request('estado') === 'promovida'   ? 'selected' : '' }}>Promovida</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-sm fw-semibold" style="background:var(--primary);color:#fff;border-radius:8px;padding:.38rem .9rem;">
                <i class="bi bi-funnel me-1"></i>Filtrar
            </button>
            @if(request()->hasAny(['search','grupo_id','estado']))
                <a href="{{ route('admin.matriculas.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:8px;padding:.38rem .75rem;">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="table-card p-slide-up p-delay-2">
    @if($matriculas->isEmpty())
        @if($schoolYear && !request()->hasAny(['search','grupo_id','estado']) && ($totalGruposAnio === 0 || $totalEstudiantesActivos === 0))
        {{-- ── Panel de pasos de configuración ── --}}
        <div class="p-4">
            <div class="text-center mb-4">
                <i class="bi bi-rocket-takeoff" style="font-size:2.5rem;color:#6366f1;display:block;margin-bottom:.5rem;"></i>
                <h6 class="fw-bold mb-1" style="color:#1e3a8a;">Configura el sistema antes de matricular</h6>
                <p class="text-muted" style="font-size:.82rem;">Completa los siguientes pasos en orden para poder registrar matrículas.</p>
            </div>
            <div class="row g-3 justify-content-center">
                {{-- Paso 1: Año Escolar --}}
                <div class="col-12 col-md-4">
                    <div class="text-center p-3 rounded-3" style="border:2px solid #d1fae5;background:#f0fdf4;">
                        <div style="width:36px;height:36px;background:#059669;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;margin-bottom:.6rem;">1</div>
                        <div style="font-weight:700;font-size:.85rem;color:#065f46;margin-bottom:.3rem;">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Año Escolar
                        </div>
                        <div style="font-size:.75rem;color:#047857;">
                            {{ $schoolYear->nombre }} — <span class="fw-bold">Activo</span>
                        </div>
                    </div>
                </div>
                {{-- Paso 2: Grupos --}}
                <div class="col-12 col-md-4">
                    @if($totalGruposAnio > 0)
                    <div class="text-center p-3 rounded-3" style="border:2px solid #d1fae5;background:#f0fdf4;">
                        <div style="width:36px;height:36px;background:#059669;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;margin-bottom:.6rem;">2</div>
                        <div style="font-weight:700;font-size:.85rem;color:#065f46;margin-bottom:.3rem;">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Grupos / Cursos
                        </div>
                        <div style="font-size:.75rem;color:#047857;">{{ $totalGruposAnio }} grupo(s) creado(s)</div>
                    </div>
                    @else
                    <div class="text-center p-3 rounded-3" style="border:2px solid #fde68a;background:#fffbeb;">
                        <div style="width:36px;height:36px;background:#f59e0b;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;margin-bottom:.6rem;">2</div>
                        <div style="font-weight:700;font-size:.85rem;color:#92400e;margin-bottom:.3rem;">
                            <i class="bi bi-exclamation-circle-fill" style="color:#f59e0b;"></i> Grupos / Cursos
                        </div>
                        <div style="font-size:.75rem;color:#b45309;margin-bottom:.6rem;">No hay grupos para este año</div>
                        <a href="{{ route('admin.grupos.create') }}" class="btn btn-sm fw-bold"
                           style="background:#f59e0b;color:#fff;border-radius:7px;font-size:.75rem;padding:.3rem .75rem;">
                            <i class="bi bi-plus-lg me-1"></i>Crear Grupos
                        </a>
                    </div>
                    @endif
                </div>
                {{-- Paso 3: Estudiantes --}}
                <div class="col-12 col-md-4">
                    @if($totalEstudiantesActivos > 0)
                    <div class="text-center p-3 rounded-3" style="border:2px solid #d1fae5;background:#f0fdf4;">
                        <div style="width:36px;height:36px;background:#059669;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;margin-bottom:.6rem;">3</div>
                        <div style="font-weight:700;font-size:.85rem;color:#065f46;margin-bottom:.3rem;">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Estudiantes
                        </div>
                        <div style="font-size:.75rem;color:#047857;">{{ $totalEstudiantesActivos }} estudiante(s) activo(s)</div>
                    </div>
                    @else
                    <div class="text-center p-3 rounded-3" style="border:2px solid #fde68a;background:#fffbeb;">
                        <div style="width:36px;height:36px;background:#f59e0b;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;margin-bottom:.6rem;">3</div>
                        <div style="font-weight:700;font-size:.85rem;color:#92400e;margin-bottom:.3rem;">
                            <i class="bi bi-exclamation-circle-fill" style="color:#f59e0b;"></i> Estudiantes
                        </div>
                        <div style="font-size:.75rem;color:#b45309;margin-bottom:.6rem;">No hay estudiantes activos</div>
                        <a href="{{ route('admin.estudiantes.create') }}" class="btn btn-sm fw-bold"
                           style="background:#f59e0b;color:#fff;border-radius:7px;font-size:.75rem;padding:.3rem .75rem;">
                            <i class="bi bi-plus-lg me-1"></i>Registrar Estudiantes
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @if($totalGruposAnio > 0 && $totalEstudiantesActivos > 0)
            <div class="text-center mt-4">
                <a href="{{ route('admin.matriculas.create') }}" class="btn fw-bold"
                   style="background:var(--primary);color:#fff;border-radius:8px;padding:.55rem 1.4rem;">
                    <i class="bi bi-plus-lg me-1"></i>Registrar primera matrícula
                </a>
            </div>
            @endif
        </div>
        @else
        <div class="text-center py-5 px-3">
            <i class="bi bi-card-list" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#60a5fa;"></i>
            <h6 class="fw-semibold mb-1" style="color:#2563eb;">No se encontraron matrículas</h6>
            <p class="text-muted mb-3" style="font-size:.83rem;">
                @if(request()->hasAny(['search','grupo_id','estado']))
                    Intenta ajustar los filtros de búsqueda.
                @else
                    Aún no hay matrículas registradas para este año escolar.
                @endif
            </p>
            @if($schoolYear)
            <a href="{{ route('admin.matriculas.create') }}" class="btn btn-sm" style="background:var(--primary);color:#fff;border-radius:8px;">
                <i class="bi bi-plus-lg me-1"></i>Registrar matrícula
            </a>
            @endif
        </div>
        @endif
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estudiante</th>
                        <th>Grupo</th>
                        <th>Fecha Matrícula</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matriculas as $m)
                        <tr>
                            <td>
                                <span style="font-size:.75rem;font-weight:700;color:#2563eb;font-family:monospace;">
                                    #{{ str_pad($m->numero_orden ?? $m->id, 3, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($m->estudiante->foto)
                                        <img src="{{ asset('storage/' . $m->estudiante->foto) }}"
                                             alt="" class="student-photo">
                                    @else
                                        <div class="student-photo-placeholder">
                                            {{ strtoupper(substr($m->estudiante->nombres ?? '?', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="nombre-mat">
                                            {{ $m->estudiante->nombre_completo }}
                                        </div>
                                        <div style="font-size:.7rem;color:#2563eb;font-weight:700;font-family:monospace;">
                                            Nº {{ $m->estudiante->numero_matricula ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($m->grupo)
                                    @php
                                        $niveles = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                                        $pref = $niveles[$m->grupo->grado->nivel ?? 0] ?? ($m->grupo->grado->nivel.'mo');
                                        $gLabel = $pref . ' ' . ($m->grupo->seccion->nombre ?? '');
                                    @endphp
                                    <span class="grupo-chip">{{ $gLabel }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:.82rem;">
                                    {{ $m->fecha_matricula ? $m->fecha_matricula->format('d/m/Y') : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-estado badge-{{ $m->estado ?? 'activa' }}">
                                    {{ ucfirst($m->estado ?? 'activa') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    <a href="{{ route('admin.matriculas.show', $m) }}"
                                       class="btn btn-action"
                                       style="background:#f0f4f8;color:var(--primary);border:1px solid #e5e7eb;"
                                       title="Ver detalles">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-action" type="button" data-bs-toggle="dropdown"
                                                style="background:#f0f4f8;color:#374151;border:1px solid #e5e7eb;">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow"
                                            style="min-width:160px;border-radius:10px;border:1px solid #e5e7eb;font-size:.8rem;">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.matriculas.constancia', $m) }}" target="_blank">
                                                    <i class="bi bi-file-earmark-text text-primary me-2"></i>Constancia PDF
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            @if(($m->estado ?? 'activa') === 'activa')
                                                <li>
                                                    <button class="dropdown-item text-danger"
                                                            onclick="abrirCambioEstado({{ $m->id }},'{{ addslashes($m->estudiante->nombre_completo) }}','retirada')">
                                                        <i class="bi bi-x-circle me-2"></i>Retirar
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item"  style="color:#d97706;"
                                                            onclick="abrirCambioEstado({{ $m->id }},'{{ addslashes($m->estudiante->nombre_completo) }}','transferida')">
                                                        <i class="bi bi-arrow-left-right me-2"></i>Transferir
                                                    </button>
                                                </li>
                                            @else
                                                <li>
                                                    <button class="dropdown-item text-success"
                                                            onclick="abrirCambioEstado({{ $m->id }},'{{ addslashes($m->estudiante->nombre_completo) }}','activa')">
                                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reactivar
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($matriculas->hasPages())
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top pagination-footer" style="background:#fafbff;">
                <span style="font-size:.78rem;color:#2563eb;font-weight:600;">
                    Mostrando {{ $matriculas->firstItem() }}–{{ $matriculas->lastItem() }} de {{ $matriculas->total() }}
                </span>
                <div>
                    {{ $matriculas->links() }}
                </div>
            </div>
        @endif
    @endif
</div>

{{-- Modal: Cambiar Estado (compartido) --}}
<div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:var(--primary);">
                    <i class="bi bi-tag me-2"></i>Cambiar Estado de Matrícula
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCambiarEstado" method="POST">
                @csrf @method('PATCH')
                <input type="hidden" name="estado" id="estadoInput">
                <div class="modal-body">
                    <p class="mb-3" style="font-size:.875rem;">
                        Cambiar estado de <strong id="nombreEstudiante"></strong> a:
                        <span id="badgeNuevoEstado" class="badge-estado ms-1"></span>
                    </p>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;">Motivo (opcional)</label>
                        <textarea name="motivo" class="form-control" rows="2"
                                  style="border-radius:8px;font-size:.875rem;"
                                  placeholder="Ingresa el motivo del cambio..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarEstado" class="btn btn-sm fw-semibold"
                            style="background:var(--primary);color:#fff;border-radius:8px;">
                        <i class="bi bi-check-lg me-1"></i>Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Matrícula Masiva --}}
<div class="modal fade" id="modalMasivo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" style="color:var(--primary);">
                    <i class="bi bi-people me-2"></i>Matrícula Masiva
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.matriculas.masiva') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if($estudiantesDisp->isEmpty())
                        <div class="alert alert-info border-0" style="border-radius:8px;background:#eff6ff;">
                            <i class="bi bi-info-circle me-2"></i>
                            Todos los estudiantes activos ya están matriculados en este año escolar.
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;">Grupo destino *</label>
                                <select name="grupo_id" class="form-select" style="border-radius:8px;font-size:.875rem;" required>
                                    <option value="">— Seleccionar grupo —</option>
                                    @php
                                        $nivelesM = [1=>'1ro',2=>'2do',3=>'3ro',4=>'4to',5=>'5to',6=>'6to'];
                                        $gruposPorCicloM = $grupos->groupBy(fn($g) => $g->grado->ciclo ?? 'primer_ciclo');
                                    @endphp
                                    @foreach(['primer_ciclo' => 'Primer Ciclo', 'segundo_ciclo' => 'Segundo Ciclo'] as $ck => $cl)
                                        @if($gruposPorCicloM->has($ck))
                                            <optgroup label="{{ $cl }}">
                                                @foreach($gruposPorCicloM[$ck] as $g)
                                                    @php $lbl = ($nivelesM[$g->grado->nivel ?? 0] ?? '') . ' ' . ($g->seccion->nombre ?? ''); @endphp
                                                    <option value="{{ $g->id }}">{{ $lbl }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;">Fecha de matrícula *</label>
                                <input type="date" name="fecha_matricula" class="form-control"
                                       style="border-radius:8px;font-size:.875rem;"
                                       value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" style="font-size:.8rem;font-weight:600;">
                                    Estudiantes a matricular *
                                    <span class="text-muted" style="font-weight:400;">({{ $estudiantesDisp->count() }} disponibles)</span>
                                </label>
                                <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                    <div style="padding:.5rem .75rem;background:#f8fafc;border-bottom:1px solid #e5e7eb;
                                                display:flex;justify-content:space-between;align-items:center;">
                                        <span style="font-size:.75rem;color:#2563eb;font-weight:700;">Seleccionar estudiantes</span>
                                        <div class="d-flex gap-2">
                                            <button type="button" onclick="toggleAllMasivo(true)"
                                                    style="background:#eff6ff;color:#2563eb;border:none;border-radius:6px;
                                                           font-size:.72rem;padding:.2rem .55rem;cursor:pointer;">
                                                <i class="bi bi-check-all me-1"></i>Todos
                                            </button>
                                            <button type="button" onclick="toggleAllMasivo(false)"
                                                    style="background:#f3f4f6;color:#6b7280;border:none;border-radius:6px;
                                                           font-size:.72rem;padding:.2rem .55rem;cursor:pointer;">
                                                Ninguno
                                            </button>
                                        </div>
                                    </div>
                                    <div id="listaMasivoEstudiantes" style="max-height:220px;overflow-y:auto;padding:.4rem 0;">
                                        @foreach($estudiantesDisp as $est)
                                            <label class="d-flex align-items-center gap-2 px-3 py-1"
                                                   style="cursor:pointer;font-size:.83rem;">
                                                <input type="checkbox" name="estudiante_ids[]" value="{{ $est->id }}"
                                                       class="form-check-input masivo-check" style="margin-top:0;flex-shrink:0;">
                                                <span style="font-weight:600;color:#1e293b;">
                                                    {{ $est->apellidos }}, {{ $est->nombres }}
                                                </span>
                                                @if($est->numero_matricula)
                                                    <span style="font-size:.7rem;color:#2563eb;font-family:monospace;">
                                                        {{ $est->numero_matricula }}
                                                    </span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mt-1" style="font-size:.73rem;color:#6b7280;">
                                    <span id="masivoSelCount">0</span> estudiante(s) seleccionado(s)
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;">
                        Cerrar
                    </button>
                    @if($estudiantesDisp->isNotEmpty())
                        <button type="submit" class="btn btn-sm fw-semibold"
                                style="background:var(--primary);color:#fff;border-radius:8px;">
                            <i class="bi bi-people me-1"></i>Matricular Seleccionados
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function abrirCambioEstado(id, nombre, nuevoEstado) {
    const form = document.getElementById('formCambiarEstado');
    form.action = '{{ url("admin/matriculas") }}/' + id + '/estado';

    document.getElementById('estadoInput').value = nuevoEstado;
    document.getElementById('nombreEstudiante').textContent = nombre;

    const badge = document.getElementById('badgeNuevoEstado');
    const btn   = document.getElementById('btnConfirmarEstado');

    const cfg = {
        activa:      { cls: 'badge-activa',    label: 'ACTIVA',     btn: '#059669' },
        retirada:    { cls: 'badge-retirada',  label: 'RETIRADA',   btn: '#dc2626' },
        transferida: { cls: 'badge-trasladada',label: 'TRANSFERIDA',btn: '#d97706' },
    };
    const c = cfg[nuevoEstado] || cfg.activa;
    badge.className = 'badge-estado ' + c.cls + ' ms-1';
    badge.textContent = c.label;
    btn.style.background = c.btn;
    btn.style.color = '#fff';

    form.querySelector('textarea[name="motivo"]').value = '';
    new bootstrap.Modal(document.getElementById('modalCambiarEstado')).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('listaMasivoEstudiantes');
    if (container) container.addEventListener('change', updateMasivoCount);
});
function updateMasivoCount() {
    const n = document.querySelectorAll('.masivo-check:checked').length;
    const el = document.getElementById('masivoSelCount');
    if (el) el.textContent = n;
}
function toggleAllMasivo(checked) {
    document.querySelectorAll('.masivo-check').forEach(c => c.checked = checked);
    updateMasivoCount();
}
</script>
@endpush

@endsection
