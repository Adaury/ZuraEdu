@extends('layouts.portal')
@section('page-title', 'Mensaje')
@section('portal-name', 'Portal Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'mensajes'])
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('portal.padre.mensajes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h6 class="fw-bold mb-0 ms-1">{{ $mensaje->asunto }}</h6>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <div class="fw-semibold mb-1">{{ $mensaje->asunto }}</div>
                <small class="text-muted">
                    <i class="bi bi-person me-1"></i><strong>De:</strong> {{ $mensaje->remitente?->name ?? '—' }}
                    &nbsp;·&nbsp; {{ $mensaje->created_at->format('d/m/Y H:i') }}
                </small>
            </div>
            <div class="card-body px-4 py-4">
                <div style="white-space:pre-wrap;line-height:1.7;">{{ $mensaje->cuerpo }}</div>
            </div>
            @if($mensaje->adjunto_path)
            <div class="card-footer bg-light py-2 px-4">
                <a href="{{ route('admin.comunicaciones.adjunto', $mensaje) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-paperclip me-1"></i>{{ $mensaje->adjunto_nombre ?? 'Descargar adjunto' }}
                </a>
            </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-3">
                <a href="{{ route('portal.padre.mensajes.create', ['reply_to' => $mensaje->id]) }}"
                   class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-reply me-1"></i>Responder
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
