@extends('layouts.admin')

@section('page-title', 'Área Académica')

@push('styles')
<style>
    .ciclo-badge {
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        padding: .25rem .6rem;
        border-radius: 20px;
    }
    .docente-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        transition: box-shadow .18s, transform .18s;
    }
    .docente-card:hover {
        box-shadow: 0 4px 20px rgba(30,58,110,.1);
        transform: translateY(-2px);
    }
    .docente-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e3a6e, #2a4f96);
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .asig-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        background: #f0f4ff;
        border: 1px solid #c7d7fd;
        color: #1e3a6e;
        border-radius: 20px;
        font-size: .76rem;
        font-weight: 500;
        padding: .2rem .65rem;
        margin: .15rem;
    }
    .nav-pills .nav-link { font-weight: 600; color: #4b5563; border-radius: 8px; }
    .nav-pills .nav-link.active { background: #1e3a6e; color: #fff; }

    [data-theme="dark"] .docente-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .asig-chip { background: #0c1f3f; border-color: #1d4ed8; color: #93c5fd; }
    [data-theme="dark"] .nav-pills .nav-link { color: #94a3b8; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-book-half me-2"></i>Área Académica
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            Docentes del área académica por ciclo — {{ $schoolYear->nombre ?? 'Sin año activo' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.areas.tecnica') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-tools me-1"></i>Ver Área Técnica
        </a>
        <a href="{{ route('admin.malla.index') }}?area=academica" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-grid-3x3 me-1"></i>Malla Curricular
        </a>
    </div>
</div>

{{-- Tabs de ciclo --}}
<ul class="nav nav-pills mb-4" id="cicloTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="primer-tab" data-bs-toggle="pill"
                data-bs-target="#primer-ciclo" type="button" role="tab">
            <i class="bi bi-1-circle me-1"></i>Primer Ciclo
            <span class="badge bg-primary ms-1" style="font-size:.65rem;">1ro – 3ro</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="segundo-tab" data-bs-toggle="pill"
                data-bs-target="#segundo-ciclo" type="button" role="tab">
            <i class="bi bi-2-circle me-1"></i>Segundo Ciclo
            <span class="badge bg-secondary ms-1" style="font-size:.65rem;">4to – 6to</span>
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ── PRIMER CICLO ────────────────────────────────────── --}}
    <div class="tab-pane fade show active" id="primer-ciclo" role="tabpanel">
        @if($docentesPrimerCiclo->isEmpty())
            <div class="empty-state-enhanced">
                <div class="empty-illustration"><i class="bi bi-person-x"></i></div>
                <div class="empty-title">Sin docentes en Primer Ciclo</div>
                <div class="empty-desc">No hay docentes académicos con asignaciones en 1ro-3ro para el año actual.</div>
            </div>
        @else
            <div class="row g-3">
                @foreach($docentesPrimerCiclo as $docente)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="docente-card p-3 h-100">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="docente-avatar">
                                {{ strtoupper(substr($docente->nombres, 0, 1) . substr($docente->apellidos, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-truncate" style="color:#1e293b;font-size:.9rem;">
                                    {{ $docente->nombre_completo }}
                                </div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $docente->especialidad ?? $docente->cargo ?? 'Docente' }}</div>
                                <div class="d-flex gap-1 mt-1 flex-wrap">
                                    <span class="badge text-bg-success" style="font-size:.65rem;">Académica</span>
                                    <span class="badge text-bg-info" style="font-size:.65rem;">1er Ciclo</span>
                                </div>
                            </div>
                            <a href="{{ route('admin.perfiles.docente', $docente) }}"
                               class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:.25rem .5rem;">
                                <i class="bi bi-person"></i>
                            </a>
                        </div>

                        <div class="mb-2" style="font-size:.78rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">
                            Asignaturas y Cursos
                        </div>
                        <div>
                            @foreach($docente->asignaciones as $asig)
                            <div class="asig-chip">
                                <i class="bi bi-book" style="font-size:.7rem;"></i>
                                {{ $asig->asignatura->nombre }}
                                <span style="color:#6b7280;">|</span>
                                {{ $asig->grupo->nombre_corto ?? ($asig->grupo->grado->nombre . ' ' . $asig->grupo->seccion->nombre) }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-muted mt-3" style="font-size:.8rem;">
                {{ $docentesPrimerCiclo->count() }} docente(s) activo(s) en Primer Ciclo
            </p>
        @endif
    </div>

    {{-- ── SEGUNDO CICLO ───────────────────────────────────── --}}
    <div class="tab-pane fade" id="segundo-ciclo" role="tabpanel">
        @if($docentesSegundoCiclo->isEmpty())
            <div class="empty-state-enhanced">
                <div class="empty-illustration"><i class="bi bi-person-x"></i></div>
                <div class="empty-title">Sin docentes en Segundo Ciclo</div>
                <div class="empty-desc">No hay docentes académicos con asignaciones en 4to-6to para el año actual.</div>
            </div>
        @else
            <div class="row g-3">
                @foreach($docentesSegundoCiclo as $docente)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="docente-card p-3 h-100">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="docente-avatar" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);">
                                {{ strtoupper(substr($docente->nombres, 0, 1) . substr($docente->apellidos, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-truncate" style="color:#1e293b;font-size:.9rem;">
                                    {{ $docente->nombre_completo }}
                                </div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $docente->especialidad ?? $docente->cargo ?? 'Docente' }}</div>
                                <div class="d-flex gap-1 mt-1 flex-wrap">
                                    <span class="badge text-bg-success" style="font-size:.65rem;">Académica</span>
                                    <span class="badge text-bg-warning" style="font-size:.65rem;">2do Ciclo</span>
                                </div>
                            </div>
                            <a href="{{ route('admin.perfiles.docente', $docente) }}"
                               class="btn btn-sm btn-outline-primary" style="font-size:.7rem;padding:.25rem .5rem;">
                                <i class="bi bi-person"></i>
                            </a>
                        </div>

                        <div class="mb-2" style="font-size:.78rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">
                            Asignaturas y Cursos
                        </div>
                        <div>
                            @foreach($docente->asignaciones as $asig)
                            <div class="asig-chip">
                                <i class="bi bi-book" style="font-size:.7rem;"></i>
                                {{ $asig->asignatura->nombre }}
                                <span style="color:#6b7280;">|</span>
                                {{ $asig->grupo->nombre_corto ?? ($asig->grupo->grado->nombre . ' ' . $asig->grupo->seccion->nombre) }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-muted mt-3" style="font-size:.8rem;">
                {{ $docentesSegundoCiclo->count() }} docente(s) activo(s) en Segundo Ciclo
            </p>
        @endif
    </div>

</div>

{{-- ── MATERIAS ACADÉMICAS POR ÁREA ──────────────────────────────── --}}
@if(isset($areasConMaterias) && $areasConMaterias->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header border-bottom d-flex align-items-center gap-2"
         style="background:#eff6ff;font-weight:700;font-size:.85rem;color:#1e40af;">
        <i class="bi bi-book-half"></i>Materias por Área Curricular (Académica)
        <a href="{{ route('admin.asignaturas.create') }}" class="btn btn-sm btn-primary ms-auto" style="border-radius:7px;font-size:.75rem;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Materia
        </a>
    </div>
    <div class="card-body p-0">
        @foreach($areasConMaterias as $area)
        <div style="border-bottom:1px solid #e5e7eb;padding:.75rem 1rem;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="width:12px;height:12px;border-radius:3px;background:{{ $area->color ?? '#3b82f6' }};display:inline-block;"></span>
                <strong style="font-size:.85rem;color:#1e293b;">{{ $area->nombre }}</strong>
                <span class="badge bg-primary" style="font-size:.68rem;">{{ $area->asignaturas_count }} materia(s)</span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
                @foreach($area->asignaturas as $asig)
                <a href="{{ route('admin.asignaturas.edit', $asig) }}"
                   style="background:{{ $asig->color ?? '#3b82f6' }}22;color:{{ $asig->color ?? '#1d4ed8' }};border:1px solid {{ $asig->color ?? '#3b82f6' }}55;border-radius:7px;padding:.22rem .6rem;font-size:.75rem;font-weight:700;text-decoration:none;transition:opacity .15s;"
                   onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                    {{ $asig->codigo ? $asig->codigo.' — ' : '' }}{{ $asig->nombre }}
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
        <div style="padding:.65rem 1rem;">
            <a href="{{ route('admin.asignaturas.index') }}" class="text-decoration-none" style="font-size:.78rem;color:#2563eb;">
                <i class="bi bi-arrow-right me-1"></i>Ver todas las materias académicas
            </a>
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body text-center py-4 text-muted" style="font-size:.85rem;">
        <i class="bi bi-info-circle me-2"></i>
        Para ver materias agrupadas por área, ve a
        <a href="{{ route('admin.asignaturas.index') }}">Asignaturas</a>
        y asigna el área curricular (MINERD) a cada materia.
    </div>
</div>
@endif

@endsection
