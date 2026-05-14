@extends('layouts.admin')
@section('page-title', 'Comunicaciones Internas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-envelope-fill me-2 text-primary"></i>Comunicaciones</h4>
        <small class="text-muted">Mensajes internos entre usuarios del sistema</small>
    </div>
    <a href="{{ route('admin.comunicaciones.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Redactar
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 px-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" id="comTabs">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'recibidos' ? 'active' : '' }}"
           href="{{ route('admin.comunicaciones.index', ['tab' => 'recibidos']) }}">
            <i class="bi bi-inbox me-1"></i>Recibidos
            @if($noLeidos > 0 && $tab !== 'recibidos')
            <span class="badge bg-danger ms-1">{{ $noLeidos }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'enviados' ? 'active' : '' }}"
           href="{{ route('admin.comunicaciones.index', ['tab' => 'enviados']) }}">
            <i class="bi bi-send me-1"></i>Enviados
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'circulares' ? 'active' : '' }}"
           href="{{ route('admin.comunicaciones.index', ['tab' => 'circulares']) }}">
            <i class="bi bi-broadcast me-1"></i>Circulares
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($mensajes->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="mt-2 mb-0">No hay mensajes en esta bandeja</p>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach($mensajes as $item)
            @php
                if ($tab === 'recibidos') {
                    $msg    = $item->mensaje;
                    $leido  = !is_null($item->leido_at);
                    $remNom = $msg->remitente?->name ?? '—';
                    $asunto = $msg->asunto;
                    $fecha  = $msg->created_at;
                    $link   = route('admin.comunicaciones.show', $msg);
                } else {
                    $msg    = $item;
                    $leido  = true;
                    $dests  = $msg->destinatarios->take(3)->map(fn($d) => $d->destinatario?->name ?? '?');
                    $remNom = $dests->implode(', ') . ($msg->destinatarios->count() > 3 ? '…' : '');
                    $asunto = $msg->asunto;
                    $fecha  = $msg->created_at;
                    $link   = route('admin.comunicaciones.show', $msg);
                }
            @endphp
            <a href="{{ $link }}"
               class="list-group-item list-group-item-action px-4 py-3 {{ !$leido ? 'fw-semibold bg-light' : '' }}"
               style="border-left:3px solid {{ !$leido ? '#2563eb' : 'transparent' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1 me-3" style="min-width:0;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            @if(!$leido)
                            <span class="badge bg-primary" style="font-size:.6rem;padding:.2rem .45rem;">Nuevo</span>
                            @endif
                            @if($tab === 'circulares' || (isset($msg) && $msg->tipo === 'circular'))
                            <span class="badge bg-warning text-dark" style="font-size:.6rem;">Circular</span>
                            @endif
                            <span class="fw-{{ !$leido ? 'bold' : 'normal' }}" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $asunto }}
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-person me-1"></i>
                            {{ $tab === 'recibidos' ? 'De: ' . $remNom : 'Para: ' . $remNom }}
                        </small>
                    </div>
                    <small class="text-muted flex-shrink-0">{{ $fecha->diffForHumans() }}</small>
                </div>
            </a>
            @endforeach
        </div>
        <div class="px-3 py-2 border-top">
            {{ $mensajes->appends(['tab' => $tab])->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
