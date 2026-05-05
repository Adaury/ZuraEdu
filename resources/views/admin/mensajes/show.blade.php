@extends('layouts.admin')
@section('page-title', $mensaje->asunto)

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.mensajes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Bandeja
    </a>
    <h5 class="fw-bold mb-0 flex-grow-1" style="color:var(--primary);">
        {{ $mensaje->asunto }}
    </h5>
    <form method="POST" action="{{ route('admin.mensajes.archivar', $mensaje) }}" class="d-inline">
        @csrf @method('PATCH')
        <button class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-archive me-1"></i>Archivar
        </button>
    </form>
</div>

{{-- Mensaje original --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
        <div class="d-flex align-items-center gap-2">
            <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;">
                {{ strtoupper(substr($mensaje->remitente->name ?? '?', 0, 2)) }}
            </div>
            <div>
                <div style="font-size:.85rem;font-weight:700;">{{ $mensaje->remitente->name ?? '—' }}</div>
                <div style="font-size:.72rem;color:#6b7280;">→ {{ $mensaje->destinatario->name ?? '—' }}</div>
            </div>
        </div>
        <div style="font-size:.75rem;color:#94a3b8;">{{ $mensaje->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="card-body" style="font-size:.9rem;line-height:1.7;white-space:pre-line;">{{ $mensaje->cuerpo }}</div>
</div>

{{-- Respuestas --}}
@foreach($mensaje->respuestas as $r)
<div class="card border-0 shadow-sm mb-3 ms-4" style="border-left:3px solid {{ $r->remitente_id === auth()->id() ? '#6366f1' : '#3b82f6' }}!important;">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
        <div class="d-flex align-items-center gap-2">
            <div style="width:30px;height:30px;border-radius:50%;background:{{ $r->remitente_id === auth()->id() ? '#6366f1' : '#3b82f6' }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.78rem;">
                {{ strtoupper(substr($r->remitente->name ?? '?', 0, 2)) }}
            </div>
            <div style="font-size:.82rem;font-weight:700;">{{ $r->remitente->name ?? '—' }}</div>
        </div>
        <div style="font-size:.72rem;color:#94a3b8;">{{ $r->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="card-body" style="font-size:.88rem;line-height:1.7;white-space:pre-line;">{{ $r->cuerpo }}</div>
</div>
@endforeach

{{-- Formulario de respuesta --}}
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 px-3">
        <span class="fw-semibold" style="font-size:.85rem;"><i class="bi bi-reply me-1"></i>Responder</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.mensajes.store') }}">
            @csrf
            <input type="hidden" name="destinatario_id" value="{{ $mensaje->remitente_id === auth()->id() ? $mensaje->destinatario_id : $mensaje->remitente_id }}">
            <input type="hidden" name="asunto" value="RE: {{ $mensaje->asunto }}">
            <input type="hidden" name="mensaje_padre_id" value="{{ $mensaje->id }}">
            <textarea name="cuerpo" rows="4" class="form-control mb-3" placeholder="Escribe tu respuesta..." required
                      style="font-size:.88rem;resize:vertical;"></textarea>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-send-fill me-1"></i>Enviar Respuesta
            </button>
        </form>
    </div>
</div>
@endsection
