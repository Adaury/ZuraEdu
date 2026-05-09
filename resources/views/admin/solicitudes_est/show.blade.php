@extends('layouts.admin')
@section('title', 'Solicitud de Estudiante')

@section('content')
@php $ec = $estados[$solicitud->estado] ?? $estados['pendiente']; @endphp

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.solicitudes-est.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div class="flex-grow-1">
        <h1 class="h5 fw-bold mb-0">Solicitud #{{ $solicitud->id }}</h1>
        <p class="text-muted small mb-0">{{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
    </div>
    <span class="badge fs-6 px-3 py-2" style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};">
        {{ $ec['label'] }}
    </span>
</div>

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif

<div class="row g-4">
    {{-- Datos --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-file-earmark-text me-2 text-primary"></i>Detalle de la solicitud
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Estudiante</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $solicitud->estudiante?->nombre_completo ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Tipo</dt>
                    <dd class="col-sm-8">{{ $tipos[$solicitud->tipo] ?? $solicitud->tipo }}</dd>

                    <dt class="col-sm-4 text-muted">Asunto</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $solicitud->asunto }}</dd>

                    @if($solicitud->fecha_evento)
                    <dt class="col-sm-4 text-muted">Fecha del evento</dt>
                    <dd class="col-sm-8">{{ $solicitud->fecha_evento->format('d/m/Y') }}</dd>
                    @endif
                </dl>

                <hr>
                <div class="text-muted small fw-semibold mb-2">Descripción</div>
                <p class="small mb-0" style="white-space:pre-line;">{{ $solicitud->descripcion }}</p>

                @if($solicitud->adjunto)
                <hr>
                <div class="text-muted small fw-semibold mb-2">Adjunto</div>
                <a href="{{ asset('storage/' . $solicitud->adjunto) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-paperclip"></i> Ver archivo adjunto
                </a>
                @endif
            </div>
        </div>

        {{-- Respuesta previa --}}
        @if($solicitud->respuesta)
        <div class="card border-0 shadow-sm border-start border-4 border-success">
            <div class="card-header bg-white small fw-semibold">
                <i class="bi bi-chat-text-fill text-success me-2"></i>Respuesta registrada
                <span class="text-muted fw-normal ms-2">
                    por {{ $solicitud->respondidoPor?->name ?? '—' }} · {{ $solicitud->respondido_en?->format('d/m/Y H:i') }}
                </span>
            </div>
            <div class="card-body small">
                <p class="mb-0" style="white-space:pre-line;">{{ $solicitud->respuesta }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Formulario respuesta --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-reply-fill me-2 text-primary"></i>Responder solicitud
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.solicitudes-est.responder', $solicitud) }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label small fw-bold">Estado</label>
                    <div class="d-grid gap-2">
                        @foreach($estados as $k => $v)
                        <label class="d-flex align-items-center gap-2 p-2 rounded border cursor-pointer"
                               style="cursor:pointer;background:{{ $solicitud->estado === $k ? $v['bg'] : '#fff' }};border-color:{{ $solicitud->estado === $k ? $v['color'] : '#dee2e6' }}!important;">
                            <input type="radio" name="estado" value="{{ $k }}" {{ $solicitud->estado === $k ? 'checked' : '' }}>
                            <span class="small fw-semibold" style="color:{{ $v['color'] }};">{{ $v['label'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Respuesta / Observaciones</label>
                    <textarea name="respuesta" rows="5" class="form-control form-control-sm"
                              placeholder="Escribe la respuesta para el estudiante...">{{ old('respuesta', $solicitud->respuesta) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle-fill me-1"></i> Guardar respuesta
                </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
