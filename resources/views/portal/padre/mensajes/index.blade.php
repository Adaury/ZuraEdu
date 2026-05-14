@extends('layouts.portal')
@section('page-title', 'Mis Mensajes')
@section('portal-name', 'Portal Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'mensajes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.mensajes.index') }}" class="prt-nav-item active">
        <i class="bi bi-envelope-fill"></i>Mensajes
    </a>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-envelope-fill me-2 text-primary"></i>Mensajes</h5>
        <small class="text-muted">Comunicación con la institución</small>
    </div>
    <a href="{{ route('portal.padre.mensajes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Redactar
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 px-3" role="alert" style="font-size:.85rem;">
    {{ session('success') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'recibidos' ? 'active' : '' }}"
           href="{{ route('portal.padre.mensajes.index', ['tab' => 'recibidos']) }}">
            <i class="bi bi-inbox me-1"></i>Recibidos
            @if($noLeidos > 0 && $tab !== 'recibidos')
            <span class="badge bg-danger ms-1">{{ $noLeidos }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'enviados' ? 'active' : '' }}"
           href="{{ route('portal.padre.mensajes.index', ['tab' => 'enviados']) }}">
            <i class="bi bi-send me-1"></i>Enviados
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($mensajes->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="mt-2 mb-0">No hay mensajes aquí</p>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach($mensajes as $item)
            @php
                if ($tab === 'recibidos') {
                    $msg   = $item->mensaje;
                    $leido = !is_null($item->leido_at);
                    $de    = $msg->remitente?->name ?? '—';
                    $link  = route('portal.padre.mensajes.show', $msg);
                } else {
                    $msg   = $item;
                    $leido = true;
                    $de    = $msg->destinatarios->take(2)->map(fn($d) => $d->destinatario?->name ?? '?')->implode(', ');
                    $link  = route('portal.padre.mensajes.show', $msg);
                }
            @endphp
            <a href="{{ $link }}"
               class="list-group-item list-group-item-action px-4 py-3 {{ !$leido ? 'fw-semibold bg-light' : '' }}"
               style="border-left:3px solid {{ !$leido ? '#2563eb' : 'transparent' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1 me-2" style="min-width:0;">
                        <div class="mb-1">
                            @if(!$leido)
                            <span class="badge bg-primary me-1" style="font-size:.6rem;">Nuevo</span>
                            @endif
                            {{ $msg->asunto }}
                        </div>
                        <small class="text-muted">
                            {{ $tab === 'recibidos' ? 'De: ' . $de : 'Para: ' . $de }}
                        </small>
                    </div>
                    <small class="text-muted flex-shrink-0">{{ $msg->created_at->diffForHumans() }}</small>
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
