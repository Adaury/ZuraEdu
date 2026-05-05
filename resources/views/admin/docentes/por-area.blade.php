@extends('layouts.admin')
@section('page-title', 'Docentes por Área')

@push('styles')
<style>
    /* ── Area tabs ───────────────────────────────── */
    .area-nav .nav-link {
        color: #6b7280;
        font-size: .875rem;
        font-weight: 500;
        border-radius: 8px 8px 0 0;
        padding: .6rem 1.25rem;
        border: 1px solid transparent;
        border-bottom: none;
        transition: color .18s, background .18s;
    }
    .area-nav .nav-link:hover {
        color: var(--primary);
        background: #f0f4fb;
    }
    .area-nav .nav-link.active {
        color: var(--primary);
        font-weight: 700;
        background: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    .area-tab-panel {
        background: #fff;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 8px 8px 8px;
        padding: 1.5rem;
        min-height: 300px;
    }

    /* ── Docente card ────────────────────────────── */
    .docente-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        transition: box-shadow .18s, transform .18s;
        height: 100%;
    }
    .docente-card:hover {
        box-shadow: 0 4px 20px rgba(30,58,110,.12);
        transform: translateY(-2px);
    }
    .docente-avatar {
        width: 52px; height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: #fff;
        font-size: 1.2rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        letter-spacing: .02em;
    }
    .docente-nombre {
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }
    .docente-cargo {
        font-size: .78rem;
        color: #6b7280;
    }
    .docente-especialidad {
        font-size: .78rem;
        color: #374151;
        margin-top: .15rem;
    }
    .asignacion-row {
        background: #f8fafc;
        border-radius: 8px;
        padding: .5rem .75rem;
        margin-bottom: .4rem;
        font-size: .8rem;
    }
    .asignacion-row:last-child { margin-bottom: 0; }
    .asignacion-nombre {
        font-weight: 600;
        color: #1e293b;
    }
    .asignacion-grupo {
        color: #6b7280;
        font-size: .75rem;
    }
    .btn-asistencia {
        font-size: .72rem;
        padding: .2rem .55rem;
        border-radius: 6px;
        white-space: nowrap;
    }
    .section-asignaciones {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--primary);
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: .35rem;
        margin-bottom: .6rem;
        margin-top: .85rem;
    }

    /* ── Empty state ─────────────────────────────── */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }
    .empty-state i {
        font-size: 3rem;
        display: block;
        margin-bottom: 1rem;
        opacity: .5;
    }
    .empty-state p {
        font-size: .9rem;
        margin: 0;
    }

    [data-theme="dark"] .area-nav .nav-link { color: #94a3b8; }
    [data-theme="dark"] .area-nav .nav-link:hover { background: #162032; color: #93c5fd; }
    [data-theme="dark"] .area-nav .nav-link.active { background: #1e293b; border-color: #334155 #334155 #1e293b; color: #93c5fd; }
    [data-theme="dark"] .area-tab-panel { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <div class="flex-grow-1">
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-diagram-2 me-2" style="color:var(--secondary);"></i>Docentes por Área
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.docentes.index') }}" class="text-decoration-none">Docentes</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Por Área</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('admin.docentes.porArea.pdf') . '?area=' . $area }}"
           class="btn btn-sm btn-danger"
           style="border-radius:8px;font-size:.82rem;">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.docentes.porArea.excel') . '?area=' . $area }}"
           class="btn btn-sm btn-success"
           style="border-radius:8px;font-size:.82rem;">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.docentes.import') }}"
           class="btn btn-sm btn-outline-secondary"
           style="border-radius:8px;font-size:.82rem;">
            <i class="bi bi-upload me-1"></i>Importar Docentes
        </a>
        <a href="{{ route('admin.docentes.create') }}"
           class="btn btn-sm"
           style="background:var(--primary);color:#fff;border-radius:8px;font-size:.82rem;">
            <i class="bi bi-person-plus me-1"></i>Nuevo Docente
        </a>
    </div>
</div>

{{-- Area tabs --}}
<ul class="nav area-nav mb-0" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $area === 'tecnica' ? 'active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['area' => 'tecnica']) }}"
           role="tab">
            <i class="bi bi-tools me-1"></i>Área Técnica
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $area === 'administrativa' ? 'active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['area' => 'administrativa']) }}"
           role="tab">
            <i class="bi bi-briefcase me-1"></i>Área Administrativa
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $area === 'otro' ? 'active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['area' => 'otro']) }}"
           role="tab">
            <i class="bi bi-three-dots me-1"></i>Otro
        </a>
    </li>
</ul>

<div class="area-tab-panel">

    @if($docentes->isEmpty())
        {{-- Empty state --}}
        @php
            $areaLabel = match($area) {
                'tecnica'        => 'Técnica',
                'administrativa' => 'Administrativa',
                default          => 'Otro',
            };
        @endphp
        <div class="empty-state">
            <i class="bi bi-person-x"></i>
            <p class="fw-semibold mb-1" style="font-size:1rem;color:#374151;">
                No hay docentes registrados en el Área {{ $areaLabel }}
            </p>
            <p class="text-muted">Puedes agregar docentes manualmente o importarlos desde un archivo CSV.</p>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="{{ route('admin.docentes.create') }}"
                   class="btn btn-sm"
                   style="background:var(--primary);color:#fff;border-radius:8px;">
                    <i class="bi bi-person-plus me-1"></i>Nuevo Docente
                </a>
                <a href="{{ route('admin.docentes.import') }}"
                   class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    <i class="bi bi-upload me-1"></i>Importar CSV
                </a>
            </div>
        </div>

    @else
        <div class="row g-3">
            @foreach($docentes as $docente)
                @php
                    $iniciales = strtoupper(
                        substr($docente->nombres, 0, 1) . substr($docente->apellidos, 0, 1)
                    );
                    $estadoBadgeClass = $docente->estado === 'activo' ? 'bg-success' : 'bg-danger';
                    $estadoLabel = ucfirst($docente->estado ?? 'activo');
                @endphp
                <div class="col-lg-6 col-xl-4">
                    <div class="docente-card">

                        {{-- Header row: avatar + name + badge --}}
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="docente-avatar">{{ $iniciales }}</div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="docente-nombre">
                                        {{ $docente->nombres }} {{ $docente->apellidos }}
                                    </span>
                                    <span class="badge {{ $estadoBadgeClass }} rounded-pill"
                                          style="font-size:.65rem;">
                                        {{ $estadoLabel }}
                                    </span>
                                </div>
                                @if($docente->cargo)
                                    <div class="docente-cargo">
                                        <i class="bi bi-person-gear me-1"></i>{{ $docente->cargo }}
                                    </div>
                                @endif
                                @if($docente->especialidad)
                                    <div class="docente-especialidad">
                                        <i class="bi bi-mortarboard me-1" style="color:var(--primary);"></i>{{ $docente->especialidad }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Asignaciones --}}
                        <div class="section-asignaciones">
                            <i class="bi bi-journal-check me-1"></i>Materias asignadas
                        </div>

                        @if($docente->asignaciones && $docente->asignaciones->isNotEmpty())
                            @foreach($docente->asignaciones as $asignacion)
                                @php
                                    $asignaturaNombre = $asignacion->asignatura->nombre ?? '—';
                                    $grupoLabel = '';
                                    if ($asignacion->grupo) {
                                        $gradoNombre   = $asignacion->grupo->grado->nombre   ?? '';
                                        $seccionNombre = $asignacion->grupo->seccion->nombre ?? '';
                                        $grupoLabel    = trim($gradoNombre . ' ' . $seccionNombre);
                                    }
                                @endphp
                                <div class="asignacion-row">
                                    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                        <div>
                                            <div class="asignacion-nombre">{{ $asignaturaNombre }}</div>
                                            @if($grupoLabel)
                                                <div class="asignacion-grupo">
                                                    <i class="bi bi-people me-1"></i>{{ $grupoLabel }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <a href="{{ route('admin.asistencia.registrar', $asignacion->id) }}"
                                               class="btn btn-primary btn-asistencia"
                                               title="Registrar Asistencia">
                                                <i class="bi bi-clipboard-check me-1"></i>Asistencia
                                            </a>
                                            <a href="{{ route('admin.asistencia.grilla', $asignacion->id) }}"
                                               class="btn btn-outline-secondary btn-asistencia"
                                               title="Ver Grilla">
                                                <i class="bi bi-grid me-1"></i>Grilla
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted mb-0" style="font-size:.8rem;">
                                <i class="bi bi-dash-circle me-1"></i>Sin materias asignadas este año
                            </p>
                        @endif

                    </div>{{-- /.docente-card --}}
                </div>
            @endforeach
        </div>{{-- /.row --}}
    @endif

</div>{{-- /.area-tab-panel --}}

@endsection
