@extends('layouts.admin')
@section('page-title', 'Mensajes')

@push('styles')
<style>
.msg-item { display:flex; gap:.85rem; padding:.85rem 1.1rem; border-bottom:1px solid var(--bs-border-color,#e5e7eb); cursor:pointer; transition:background .15s; text-decoration:none; color:inherit; }
.msg-item:hover { background:#f8faff; }
.msg-item.unread { background:#eff6ff; border-left:3px solid #3b82f6; }
.msg-avatar { width:38px; height:38px; border-radius:50%; background:var(--primary,#1e3a6e); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.88rem; font-weight:800; flex-shrink:0; }
.msg-asunto { font-size:.87rem; font-weight:700; color:#1e293b; }
.msg-item.unread .msg-asunto { color:#1d4ed8; }
.msg-preview { font-size:.77rem; color:#6b7280; margin-top:.15rem; }
.msg-fecha { font-size:.7rem; color:#94a3b8; white-space:nowrap; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-envelope-fill me-2"></i>Mensajes Internos
        </h4>
        @if($noLeidos > 0)
        <span class="badge bg-danger ms-2">{{ $noLeidos }} sin leer</span>
        @endif
    </div>
    <a href="{{ route('admin.mensajes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Nuevo Mensaje
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a href="?tab=recibidos" class="nav-link {{ $tab === 'recibidos' ? 'active' : '' }}">
            <i class="bi bi-inbox me-1"></i>Recibidos
            @if($noLeidos > 0)<span class="badge bg-danger ms-1">{{ $noLeidos }}</span>@endif
        </a>
    </li>
    <li class="nav-item">
        <a href="?tab=enviados" class="nav-link {{ $tab === 'enviados' ? 'active' : '' }}">
            <i class="bi bi-send me-1"></i>Enviados
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm">
    @if($tab === 'recibidos')
        @forelse($recibidos as $msg)
        <a href="{{ route('admin.mensajes.show', $msg) }}" class="msg-item {{ $msg->leido ? '' : 'unread' }}">
            <div class="msg-avatar">{{ strtoupper(substr($msg->remitente->name ?? '?', 0, 2)) }}</div>
            <div style="flex:1;min-width:0;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="msg-asunto">{{ $msg->asunto }}</div>
                    <div class="msg-fecha">{{ $msg->created_at->diffForHumans() }}</div>
                </div>
                <div class="msg-preview">
                    <strong>{{ $msg->remitente->name ?? '—' }}</strong>
                    · {{ \Illuminate\Support\Str::limit(strip_tags($msg->cuerpo), 80) }}
                </div>
                @if($msg->respuestas->count() > 0)
                <div style="font-size:.68rem;color:#6366f1;margin-top:.2rem;">
                    <i class="bi bi-reply-all me-1"></i>{{ $msg->respuestas->count() }} respuesta(s)
                </div>
                @endif
            </div>
        </a>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:3rem;opacity:.3;display:block;margin-bottom:.75rem;"></i>
            No tienes mensajes recibidos.
        </div>
        @endforelse
        <div class="p-3">{{ $recibidos->links() }}</div>
    @else
        @forelse($enviados as $msg)
        <a href="{{ route('admin.mensajes.show', $msg) }}" class="msg-item">
            <div class="msg-avatar" style="background:#6b7280;">{{ strtoupper(substr($msg->destinatario->name ?? '?', 0, 2)) }}</div>
            <div style="flex:1;min-width:0;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="msg-asunto">{{ $msg->asunto }}</div>
                    <div class="msg-fecha">{{ $msg->created_at->diffForHumans() }}</div>
                </div>
                <div class="msg-preview">
                    <strong>Para: {{ $msg->destinatario->name ?? '—' }}</strong>
                    · {{ \Illuminate\Support\Str::limit(strip_tags($msg->cuerpo), 80) }}
                </div>
                @if($msg->leido)
                <div style="font-size:.68rem;color:#10b981;margin-top:.2rem;">
                    <i class="bi bi-check2-all me-1"></i>Leído {{ $msg->leido_en?->diffForHumans() }}
                </div>
                @else
                <div style="font-size:.68rem;color:#6b7280;margin-top:.2rem;">
                    <i class="bi bi-check me-1"></i>Sin leer
                </div>
                @endif
            </div>
        </a>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-send" style="font-size:3rem;opacity:.3;display:block;margin-bottom:.75rem;"></i>
            No has enviado mensajes.
        </div>
        @endforelse
        <div class="p-3">{{ $enviados->links() }}</div>
    @endif
</div>
@endsection
