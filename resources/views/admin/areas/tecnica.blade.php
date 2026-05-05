@extends('layouts.admin')

@section('page-title', 'Área Técnica')

@push('styles')
<style>
    .especialidad-header {
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.25rem;
        color: #fff;
    }
    .docente-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        transition: box-shadow .18s;
    }
    .docente-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .docente-avatar {
        width: 44px; height: 44px;
        border-radius: 50%;
        color: #fff;
        font-weight: 700;
        font-size: .9rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .asig-chip {
        display: inline-flex; align-items: center; gap: .3rem;
        border-radius: 20px;
        font-size: .74rem;
        font-weight: 500;
        padding: .18rem .6rem;
        margin: .12rem;
        border: 1px solid;
    }
    .coordinador-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(255,255,255,.18);
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 20px;
        padding: .3rem .8rem;
        font-size: .8rem;
        color: #fff;
    }
    .nav-pills .nav-link { font-weight: 600; border-radius: 8px; }
    .nav-pills .nav-link.active { color: #fff; }

    [data-theme="dark"] .docente-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .nav-pills .nav-link { color: #94a3b8; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-tools me-2"></i>Área Técnica
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            Segundo Ciclo — 4to a 6to · Especialidades técnicas — {{ $schoolYear->nombre ?? 'Sin año activo' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.areas.academica') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-book-half me-1"></i>Ver Área Académica
        </a>
        @if(Auth::user()->hasAnyRole(['Administrador','Director']))
        <a href="{{ route('admin.especialidades.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-gear me-1"></i>Gestionar Especialidades
        </a>
        @endif
    </div>
</div>

{{-- Badge informativo de ciclo --}}
<div class="alert alert-primary d-flex align-items-center gap-2 py-2 mb-4" style="font-size:.84rem;">
    <i class="bi bi-info-circle-fill"></i>
    <span>El Área Técnica corresponde únicamente al <strong>Segundo Ciclo (4to, 5to y 6to de Secundaria)</strong>. El Primer Ciclo no cuenta con especialidades técnicas.</span>
</div>

@if($especialidades->isEmpty())
    <div class="empty-state-enhanced">
        <div class="empty-illustration"><i class="bi bi-tools"></i></div>
        <div class="empty-title">Sin especialidades configuradas</div>
        <div class="empty-desc">Configura las especialidades técnicas del politécnico para comenzar.</div>
        @if(Auth::user()->hasAnyRole(['Administrador','Director']))
        <div class="empty-actions">
            <a href="{{ route('admin.especialidades.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Agregar Especialidad
            </a>
        </div>
        @endif
    </div>
@else

{{-- Tabs de especialidades --}}
<ul class="nav nav-pills mb-4 flex-wrap gap-1" id="espTabs" role="tablist">
    @foreach($especialidades as $index => $esp)
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                id="esp-tab-{{ $esp->id }}"
                data-bs-toggle="pill"
                data-bs-target="#esp-{{ $esp->id }}"
                type="button"
                style="{{ $index === 0 ? 'background:' . $esp->color . ';' : 'color:' . $esp->color . ';border:1px solid ' . $esp->color . ';' }}">
            <i class="bi {{ $esp->icono }} me-1"></i>
            {{ $esp->nombre }}
            <span class="badge ms-1" style="font-size:.6rem;background:rgba(255,255,255,.25);">
                {{ $esp->docentes->count() }}
            </span>
        </button>
    </li>
    @endforeach
    @if($docentesSinEspecialidad->isNotEmpty())
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="sin-esp-tab" data-bs-toggle="pill"
                data-bs-target="#sin-especialidad" type="button">
            <i class="bi bi-question-circle me-1"></i>Sin Especialidad
            <span class="badge bg-secondary ms-1" style="font-size:.6rem;">{{ $docentesSinEspecialidad->count() }}</span>
        </button>
    </li>
    @endif
</ul>

<div class="tab-content">
    @foreach($especialidades as $index => $esp)
    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="esp-{{ $esp->id }}" role="tabpanel">

        {{-- Header de especialidad --}}
        <div class="especialidad-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2"
             style="background:{{ $esp->color }};">
            <div class="d-flex align-items-center gap-3">
                <i class="bi {{ $esp->icono }}" style="font-size:1.75rem;opacity:.9;"></i>
                <div>
                    <div class="fw-bold fs-5">{{ $esp->nombre }}</div>
                    <div style="font-size:.82rem;opacity:.85;">
                        {{ $esp->descripcion ?? 'Especialidad Técnica — Segundo Ciclo' }}
                    </div>
                </div>
            </div>
            @if($esp->coordinador)
            <div class="coordinador-badge">
                <i class="bi bi-person-check-fill"></i>
                Coordinador: <strong>{{ $esp->coordinador->nombre_completo }}</strong>
            </div>
            @else
            <div class="coordinador-badge" style="opacity:.6;">
                <i class="bi bi-person-dash"></i>
                Sin coordinador asignado
            </div>
            @endif
        </div>

        {{-- Docentes del Segundo Ciclo en esta especialidad --}}
        @if($esp->docentes->isEmpty())
            <div class="text-center py-4 text-muted" style="font-size:.88rem;">
                <i class="bi bi-person-x fs-3 d-block mb-2 opacity-50"></i>
                Sin docentes asignados a esta especialidad en el año actual.
            </div>
        @else
            <div class="row g-3">
                @foreach($esp->docentes as $docente)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="docente-card p-3 h-100">
                        @include('admin.areas._docente_card', [
                            'docente' => $docente,
                            'asigs'   => $docente->asignaciones,
                            'color'   => $esp->color,
                        ])
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endforeach

    {{-- Sin especialidad --}}
    @if($docentesSinEspecialidad->isNotEmpty())
    <div class="tab-pane fade" id="sin-especialidad" role="tabpanel">
        <div class="alert alert-warning d-flex gap-2 align-items-start mb-3">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>Docentes técnicos sin especialidad asignada.</strong>
                Asígnales una especialidad desde la configuración de especialidades.
            </div>
        </div>
        <div class="row g-3">
            @foreach($docentesSinEspecialidad as $docente)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="docente-card p-3 h-100">
                    @include('admin.areas._docente_card', [
                        'docente' => $docente,
                        'asigs'   => $docente->asignaciones,
                        'color'   => '#6b7280',
                    ])
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── MATERIAS TÉCNICAS ─────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header border-bottom d-flex align-items-center gap-2"
         style="background:#fff5f5;font-weight:700;font-size:.85rem;color:#c0392b;">
        <i class="bi bi-book-fill"></i>Materias del Área Técnica
        <a href="{{ route('admin.asignaturas.create') }}" class="btn btn-sm btn-danger ms-auto" style="border-radius:7px;font-size:.75rem;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Materia
        </a>
    </div>
    <div class="card-body p-0">
        @if($materiasTecnicas->isEmpty())
        <div class="text-center py-4 text-muted" style="font-size:.85rem;">
            No hay materias técnicas registradas.
            <a href="{{ route('admin.asignaturas.create') }}">Agregar la primera.</a>
        </div>
        @else
        <div style="overflow-x:auto;">
        <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
            <thead style="background:#fff5f5;font-size:.74rem;color:#c0392b;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">
                <tr>
                    <th class="ps-3">Materia</th>
                    <th>Código</th>
                    <th class="text-center">Núm. RA</th>
                    <th>Área curricular</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($materiasTecnicas as $mat)
            <tr>
                <td class="ps-3">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $mat->color ?? '#6b7280' }};margin-right:.4rem;vertical-align:middle;"></span>
                    <strong>{{ $mat->nombre }}</strong>
                </td>
                <td><code style="font-size:.75rem;">{{ $mat->codigo ?? '—' }}</code></td>
                <td class="text-center">
                    @if($mat->num_ra > 0)
                    <span class="badge bg-danger" style="font-size:.7rem;">{{ $mat->num_ra }} RA</span>
                    @else
                    <span class="text-muted" style="font-size:.72rem;">Componentes</span>
                    @endif
                </td>
                <td>
                    @if($mat->area_id && $mat->areaNormalizada)
                    <span style="background:{{ $mat->areaNormalizada->color ?? '#e5e7eb' }}22;color:{{ $mat->areaNormalizada->color ?? '#374151' }};border-radius:6px;padding:.1rem .45rem;font-size:.72rem;font-weight:700;">
                        {{ $mat->areaNormalizada->nombre }}
                    </span>
                    @else
                    <span class="text-muted" style="font-size:.72rem;">Sin asignar</span>
                    @endif
                </td>
                <td class="text-end pe-3">
                    <a href="{{ route('admin.asignaturas.edit', $mat) }}"
                       class="btn btn-sm btn-outline-secondary" style="font-size:.7rem;padding:.15rem .5rem;border-radius:5px;">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

@endsection
