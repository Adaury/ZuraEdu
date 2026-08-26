@extends('layouts.admin')
@section('page-title', 'Importación de Calificaciones')

@if($importacion->en_curso)
<meta http-equiv="refresh" content="3">
@endif

@push('styles')
<style>
    .import-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.75rem;
    }
    .estado-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .3rem .8rem; border-radius: 20px;
        font-size: .8rem; font-weight: 700; letter-spacing: .02em;
    }
    .contador-box { text-align: center; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1rem; }
    .contador-box .num { font-size: 1.6rem; font-weight: 800; }
    .contador-box .lbl { font-size: .72rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .spinner-import { width: 2.5rem; height: 2.5rem; }
    [data-theme="dark"] .import-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .contador-box { border-color: #334155; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.calificaciones.import') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-journal-arrow-up me-2" style="color:var(--secondary);"></i>Estado de la Importación
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                <li class="breadcrumb-item"><a href="{{ route('admin.calificaciones.index') }}" class="text-decoration-none">Calificaciones</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.calificaciones.import') }}" class="text-decoration-none">Importar</a></li>
                <li class="breadcrumb-item active">Lote #{{ $importacion->id }}</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.875rem;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

<div class="import-card">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <div class="fw-bold" style="font-size:1.05rem;">
                {{ $importacion->asignacion->asignatura->nombre ?? '—' }}
                <span class="text-muted fw-normal">
                    — {{ $importacion->asignacion->grupo->grado->nombre ?? '' }} {{ $importacion->asignacion->grupo->seccion->nombre ?? '' }}
                </span>
            </div>
            <div class="text-muted small mt-1">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ $importacion->archivo_original }}
                &middot; enviado {{ $importacion->created_at->diffForHumans() }}
                @if($importacion->periodo)
                &middot; Período {{ $importacion->periodo->nombre }}
                @endif
            </div>
        </div>
        <span class="estado-badge {{ $importacion->color_estado }}">{{ $importacion->estado_nombre }}</span>
    </div>

    @if($importacion->en_curso)
        <div class="d-flex align-items-center gap-3 py-4">
            <div class="spinner-border text-primary spinner-import" role="status"></div>
            <div>
                <div class="fw-semibold">Procesando tu archivo…</div>
                <div class="text-muted small">Esta página se actualiza sola cada pocos segundos. Puedes cerrarla y volver más tarde — te avisaremos por notificación cuando termine.</div>
            </div>
        </div>
    @elseif($importacion->estado === 'completado')
        <div class="row g-3 mb-3">
            <div class="col-4">
                <div class="contador-box">
                    <div class="num text-primary">{{ $importacion->total_filas }}</div>
                    <div class="lbl">Filas leídas</div>
                </div>
            </div>
            <div class="col-4">
                <div class="contador-box">
                    <div class="num text-success">{{ $importacion->importados }}</div>
                    <div class="lbl">Importadas</div>
                </div>
            </div>
            <div class="col-4">
                <div class="contador-box">
                    <div class="num {{ $importacion->omitidos > 0 ? 'text-danger' : 'text-muted' }}">{{ $importacion->omitidos }}</div>
                    <div class="lbl">Omitidas</div>
                </div>
            </div>
        </div>

        @if(!empty($importacion->errores))
            <div class="alert alert-warning mb-0" style="border-radius:10px;font-size:.875rem;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <strong>{{ count($importacion->errores) }} fila(s) con observaciones:</strong>
                    <button class="btn btn-sm btn-link ms-auto p-0 text-warning-emphasis"
                            type="button" data-bs-toggle="collapse" data-bs-target="#erroresListEstado">
                        Ver detalles <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <div class="collapse" id="erroresListEstado">
                    <ul class="mb-0 mt-2 ps-3" style="font-size:.82rem;">
                        @foreach($importacion->errores as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <div class="alert alert-success mb-0" style="border-radius:10px;font-size:.875rem;">
                <i class="bi bi-check-circle-fill me-1"></i>Todas las filas se importaron sin observaciones.
            </div>
        @endif
    @elseif($importacion->estado === 'fallido')
        <div class="alert alert-danger mb-0" style="border-radius:10px;font-size:.875rem;">
            <i class="bi bi-x-octagon-fill me-1"></i>
            <strong>La importación falló:</strong> {{ $importacion->error_fatal ?? 'Error desconocido.' }}
            <div class="small mt-1">Verifica el formato del archivo e inténtalo de nuevo.</div>
        </div>
    @endif
</div>

@endsection
