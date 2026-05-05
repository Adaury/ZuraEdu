@extends('layouts.portal')
@section('page-title', 'Planes de Clase — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planes
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="h4 mb-1">Planes de Clase</h2>
            <p class="text-muted small mb-0">
                {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo ?? '' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.docente.planes-clase.lista-pdf', $asignacion) }}" target="_blank" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
            </a>
            <a href="{{ route('portal.docente.planes-clase.lista-excel', $asignacion) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
            </a>
            <a href="{{ route('portal.docente.planes-clase.create', $asignacion) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Plan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @forelse($planes as $plan)
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="mb-0 text-truncate">{{ $plan->titulo }}</h6>
                        {{-- Toggle publicado --}}
                        <form method="POST"
                              action="{{ route('portal.docente.planes-clase.toggle', [$asignacion, $plan]) }}"
                              class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="badge border-0 {{ $plan->publicado ? 'bg-success' : 'bg-secondary' }}"
                                style="cursor:pointer;"
                                title="{{ $plan->publicado ? 'Clic para pasar a borrador' : 'Clic para publicar' }}">
                                @if($plan->publicado)
                                    <i class="bi bi-eye-fill me-1"></i>Publicado
                                @else
                                    <i class="bi bi-eye-slash me-1"></i>Borrador
                                @endif
                            </button>
                        </form>
                        @if($plan->tieneArchivo())
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-paperclip me-1"></i>{{ strtoupper(pathinfo($plan->archivo_nombre, PATHINFO_EXTENSION)) }}
                            </span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        <span class="text-capitalize me-3"><i class="bi bi-calendar3 me-1"></i>{{ $plan->tipo_plan }}</span>
                        @if($plan->semana)<span class="me-3">Semana #{{ $plan->semana }}</span>@endif
                        @if($plan->fecha_inicio)
                            <span><i class="bi bi-calendar-range me-1"></i>{{ $plan->fecha_inicio->format('d/m/Y') }}
                            @if($plan->fecha_fin) – {{ $plan->fecha_fin->format('d/m/Y') }} @endif</span>
                        @endif
                    </div>
                    @if($plan->estrategias_nombres && count($plan->estrategias_nombres))
                    <div class="mt-2">
                        @foreach(array_slice($plan->estrategias_nombres, 0, 3) as $est)
                            <span class="badge bg-light text-dark border me-1 small">{{ $est }}</span>
                        @endforeach
                        @if(count($plan->estrategias_nombres) > 3)
                            <span class="text-muted small">+{{ count($plan->estrategias_nombres) - 3 }} más</span>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <a href="{{ route('portal.docente.planes-clase.show', [$asignacion, $plan]) }}"
                        class="btn btn-sm btn-outline-primary" title="Ver detalle">
                        <i class="bi bi-eye"></i>
                    </a>
                    @if($plan->tieneArchivo())
                    <a href="{{ route('portal.docente.planes-clase.download', [$asignacion, $plan]) }}"
                        class="btn btn-sm btn-outline-info" title="Descargar archivo">
                        <i class="bi bi-download"></i>
                    </a>
                    @endif
                    <form method="POST" action="{{ route('portal.docente.planes-clase.destroy', [$asignacion, $plan]) }}"
                          onsubmit="return confirm('¿Eliminar este plan?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-journal-text display-4 text-muted d-block mb-3"></i>
            <p class="text-muted mb-3">No has creado planes de clase para esta asignación aún.</p>
            <a href="{{ route('portal.docente.planes-clase.create', $asignacion) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Crear primer plan
            </a>
        </div>
    </div>
    @endforelse
</div>
@endsection
