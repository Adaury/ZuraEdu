@extends('layouts.portal')
@section('page-title', 'Mis Solicitudes')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'solicitudes'])
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-send-fill me-2 text-primary"></i>Mis Solicitudes</h5>
        <small class="text-muted">Gestiones y peticiones enviadas a la institución</small>
    </div>
    <a href="{{ route('portal.docente.solicitudes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nueva Solicitud
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 px-3" role="alert" style="font-size:.85rem;">
    {{ session('success') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    @php
    $statItems = [
        ['label'=>'Pendientes',    'val'=>$stats['pendientes'], 'color'=>'#d97706','bg'=>'#fffbeb','icon'=>'bi-clock-fill'],
        ['label'=>'En Proceso',    'val'=>$stats['en_proceso'], 'color'=>'#2563eb','bg'=>'#eff6ff','icon'=>'bi-arrow-repeat'],
        ['label'=>'Total enviadas','val'=>$stats['total'],      'color'=>'#6366f1','bg'=>'#eef2ff','icon'=>'bi-send-fill'],
    ];
    @endphp
    @foreach($statItems as $s)
    <div class="col-4">
        <div class="card border-0 shadow-sm py-3 px-3 d-flex flex-row align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:10px;background:{{ $s['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $s['icon'] }}" style="color:{{ $s['color'] }};font-size:1rem;"></i>
            </div>
            <div>
                <div class="fw-bold" style="font-size:1.25rem;color:{{ $s['color'] }};">{{ $s['val'] }}</div>
                <div style="font-size:.72rem;color:#64748b;">{{ $s['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Lista --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($solicitudes->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="mt-2 mb-0">No has enviado ninguna solicitud aún</p>
            <a href="{{ route('portal.docente.solicitudes.create') }}" class="btn btn-primary btn-sm mt-3">
                <i class="bi bi-plus-circle me-1"></i>Enviar primera solicitud
            </a>
        </div>
        @else
        <div class="list-group list-group-flush">
            @foreach($solicitudes as $sol)
            @php $cfg = $sol->estado_config; @endphp
            <a href="{{ route('portal.docente.solicitudes.show', $sol) }}"
               class="list-group-item list-group-item-action px-4 py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1 me-3" style="min-width:0;">
                        <div class="fw-semibold mb-1" style="font-size:.9rem;">{{ $sol->asunto }}</div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.7rem;">
                                {{ $sol->tipo_label }}
                            </span>
                            @if($sol->fecha_inicio)
                            <small class="text-muted">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $sol->fecha_inicio->format('d/m/Y') }}
                                @if($sol->fecha_fin && $sol->fecha_fin != $sol->fecha_inicio)
                                – {{ $sol->fecha_fin->format('d/m/Y') }}
                                @endif
                            </small>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <span class="badge rounded-pill" style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};font-size:.7rem;border:1px solid {{ $cfg['color'] }}40;">
                            {{ $cfg['label'] }}
                        </span>
                        <small class="text-muted">{{ $sol->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="px-3 py-2 border-top">
            {{ $solicitudes->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
