@extends('layouts.portal-estudiante')
@section('title', 'Mensaje')
@section('activeKey', 'mensajes')

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-envelope-open-fill me-2"></i>{{ \Illuminate\Support\Str::limit($mensaje->asunto, 50) }}</h4>
    </div>
    <a href="{{ route('portal.estudiante.mensajes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
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
                <div style="white-space:pre-wrap;line-height:1.7;font-size:.92rem;">{{ $mensaje->cuerpo }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body px-4 py-3 d-flex flex-column gap-2">
                @if($esDestinatario)
                <a href="{{ route('portal.estudiante.mensajes.create', ['reply_to' => $mensaje->id]) }}"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-reply me-1"></i>Responder
                </a>
                @endif
                <a href="{{ route('portal.estudiante.mensajes.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Bandeja
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
