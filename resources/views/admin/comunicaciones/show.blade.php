@extends('layouts.admin')
@section('page-title', 'Ver Mensaje')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.comunicaciones.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h5 class="fw-bold mb-0 ms-1">{{ $mensaje->asunto }}</h5>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="fw-bold" style="font-size:1rem;">{{ $mensaje->asunto }}</div>
                        <small class="text-muted">
                            <i class="bi bi-person-fill me-1"></i>
                            <strong>De:</strong> {{ $mensaje->remitente?->name ?? '—' }}
                            &nbsp;·&nbsp;
                            {{ $mensaje->created_at->format('d/m/Y H:i') }}
                        </small>
                    </div>
                    @if($mensaje->tipo === 'circular')
                    <span class="badge bg-warning text-dark">Circular</span>
                    @elseif($mensaje->tipo === 'grupal')
                    <span class="badge bg-info text-dark">Grupal</span>
                    @else
                    <span class="badge bg-primary">Individual</span>
                    @endif
                </div>
            </div>
            <div class="card-body px-4 py-4">
                <div style="white-space:pre-wrap;line-height:1.7;color:#1e293b;">{{ $mensaje->cuerpo }}</div>
            </div>

            {{-- Adjunto --}}
            @if($mensaje->adjunto_path)
            <div class="card-footer bg-light py-2 px-4">
                <a href="{{ route('admin.comunicaciones.adjunto', $mensaje) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-paperclip me-1"></i>{{ $mensaje->adjunto_nombre ?? 'Descargar adjunto' }}
                </a>
            </div>
            @endif
        </div>

        {{-- Destinatarios --}}
        @if($mensaje->destinatarios->isNotEmpty())
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom py-2 px-4">
                <small class="fw-semibold text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">
                    Destinatarios ({{ $mensaje->destinatarios->count() }})
                </small>
            </div>
            <div class="card-body px-4 py-2">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($mensaje->destinatarios as $d)
                    <span class="badge {{ $d->leido_at ? 'bg-success' : 'bg-secondary' }}" style="font-size:.72rem;">
                        <i class="bi bi-{{ $d->leido_at ? 'check2' : 'clock' }} me-1"></i>
                        {{ $d->destinatario?->name ?? '?' }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Acciones --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-3 d-flex flex-column gap-2">
                <a href="{{ route('admin.comunicaciones.create', ['reply_to' => $mensaje->id]) }}"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-reply me-1"></i>Responder
                </a>
                @if(auth()->id() === $mensaje->remitente_id || $esDestinatario)
                <form method="POST" action="{{ route('admin.comunicaciones.destroy', $mensaje) }}"
                      onsubmit="return confirm('¿Eliminar este mensaje?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
