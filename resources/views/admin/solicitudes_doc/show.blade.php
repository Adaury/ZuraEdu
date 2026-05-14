@extends('layouts.admin')
@section('page-title', 'Solicitud de Docente')

@section('content')
@php $ec = $estados[$solicitudDocente->estado] ?? $estados['pendiente']; @endphp

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.solicitudes-docente.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div class="flex-grow-1">
        <h5 class="fw-bold mb-0">Solicitud #{{ $solicitudDocente->id }}</h5>
        <p class="text-muted small mb-0">{{ $solicitudDocente->created_at->format('d/m/Y H:i') }}</p>
    </div>
    <span class="badge fs-6 px-3 py-2 rounded-pill"
          style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};border:1px solid {{ $ec['color'] }}40;">
        {{ $ec['label'] }}
    </span>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    {{-- Datos --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-file-earmark-text me-2 text-primary"></i>Detalle de la solicitud
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:.85rem;">
                    <dt class="col-sm-4 text-muted">Docente</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $solicitudDocente->docente?->nombre_completo ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Cédula</dt>
                    <dd class="col-sm-8">{{ $solicitudDocente->docente?->cedula ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Tipo</dt>
                    <dd class="col-sm-8">{{ $tipos[$solicitudDocente->tipo] ?? $solicitudDocente->tipo }}</dd>

                    <dt class="col-sm-4 text-muted">Asunto</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $solicitudDocente->asunto }}</dd>

                    @if($solicitudDocente->fecha_inicio)
                    <dt class="col-sm-4 text-muted">Período</dt>
                    <dd class="col-sm-8">
                        {{ $solicitudDocente->fecha_inicio->format('d/m/Y') }}
                        @if($solicitudDocente->fecha_fin && $solicitudDocente->fecha_fin != $solicitudDocente->fecha_inicio)
                        – {{ $solicitudDocente->fecha_fin->format('d/m/Y') }}
                        @else
                        (1 día)
                        @endif
                    </dd>
                    @endif
                </dl>

                <hr>
                <div class="text-muted fw-semibold mb-2" style="font-size:.8rem;">Descripción / Justificación</div>
                <p style="white-space:pre-line;font-size:.85rem;" class="mb-0">{{ $solicitudDocente->descripcion }}</p>

                @if($solicitudDocente->adjunto)
                <hr>
                <a href="{{ asset('storage/' . $solicitudDocente->adjunto) }}" target="_blank"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-paperclip me-1"></i>Ver documento adjunto
                </a>
                @endif
            </div>
        </div>

        @if($solicitudDocente->respuesta)
        <div class="card border-0 shadow-sm" style="border-left:4px solid {{ $ec['color'] }} !important;">
            <div class="card-header bg-white" style="font-size:.82rem;">
                <i class="bi bi-chat-text-fill me-2" style="color:{{ $ec['color'] }};"></i>
                <strong>Respuesta registrada</strong>
                <span class="text-muted fw-normal ms-2">
                    por {{ $solicitudDocente->respondidoPor?->name ?? '—' }}
                    · {{ $solicitudDocente->respondido_en?->format('d/m/Y H:i') }}
                </span>
            </div>
            <div class="card-body" style="font-size:.85rem;">
                <p class="mb-0" style="white-space:pre-line;">{{ $solicitudDocente->respuesta }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Formulario respuesta --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold" style="font-size:.88rem;">
                <i class="bi bi-reply-fill me-2 text-primary"></i>Responder solicitud
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.solicitudes-docente.responder', $solicitudDocente) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.82rem;">Estado</label>
                        <div class="d-grid gap-2">
                            @foreach($estados as $k => $v)
                            <label class="d-flex align-items-center gap-2 p-2 rounded border"
                                   style="cursor:pointer;background:{{ $solicitudDocente->estado === $k ? $v['bg'] : '#fff' }};border-color:{{ $solicitudDocente->estado === $k ? $v['color'] : '#dee2e6' }}!important;">
                                <input type="radio" name="estado" value="{{ $k }}"
                                       {{ $solicitudDocente->estado === $k ? 'checked' : '' }}>
                                <span class="fw-semibold" style="color:{{ $v['color'] }};font-size:.85rem;">{{ $v['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:.82rem;">Respuesta / Observaciones</label>
                        <textarea name="respuesta" rows="5" class="form-control form-control-sm"
                                  placeholder="Escribe la respuesta para el docente...">{{ old('respuesta', $solicitudDocente->respuesta) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle-fill me-1"></i>Guardar respuesta
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
