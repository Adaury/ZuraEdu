@extends('layouts.portal')
@section('page-title', 'Instrumentos — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'instrumentos'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-text"></i>Planes
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="h4 mb-1">Instrumentos de Evaluación</h2>
            <p class="text-muted small mb-0">
                {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo ?? '' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.docente.instrumentos.lista-pdf', $asignacion) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
            </a>
            <a href="{{ route('portal.docente.instrumentos.lista-excel', $asignacion) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
            </a>
            <a href="{{ route('portal.docente.instrumentos.create', $asignacion) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Instrumento
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @forelse($instrumentos as $inst)
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h6 class="mb-0">{{ $inst->titulo }}</h6>
                        <span class="badge bg-info text-dark">{{ $inst->tipo_label }}</span>
                        @if($inst->publicado)
                            <span class="badge bg-success">Publicado</span>
                        @else
                            <span class="badge bg-secondary">Borrador</span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        <span><i class="bi bi-list-check me-1"></i>{{ $inst->criterios->count() }} criterio(s)</span>
                        @if($inst->competencia)
                            <span class="ms-3">{{ Str::limit($inst->competencia, 60) }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('portal.docente.instrumentos.show', [$asignacion, $inst]) }}"
                    class="btn btn-sm btn-outline-primary ms-3">
                    <i class="bi bi-pencil-square me-1"></i> Evaluar
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-check display-4 text-muted d-block mb-3"></i>
            <p class="text-muted mb-3">No has creado instrumentos de evaluación para esta asignación.</p>
            <a href="{{ route('portal.docente.instrumentos.create', $asignacion) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Crear instrumento
            </a>
        </div>
    </div>
    @endforelse
</div>
@endsection
