@extends('layouts.portal')

@section('title', 'Mis Observaciones')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'observaciones'])
@endsection

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-chat-square-text me-2"></i>Mis Observaciones</h4>
        @if($matricula)
        <p class="prt-page-subtitle">{{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('portal.estudiante.observaciones.pdf') }}" target="_blank" class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('portal.estudiante.observaciones.excel') }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('portal.estudiante.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Inicio
        </a>
    </div>
</div>

@if($observaciones->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-chat-square-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No tienes observaciones registradas.</p>
    </div>
</div>
@else

@php
$tipoColors = [
    'felicitacion'     => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'bi-star-fill'],
    'llamada_atencion' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-exclamation-triangle-fill'],
    'compromiso'       => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'icon' => 'bi-file-earmark-check-fill'],
    'informativa'      => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-info-circle-fill'],
];
@endphp

<div class="d-flex flex-column gap-3">
@foreach($observaciones as $obs)
@php
    $tc = $tipoColors[$obs->tipo] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-chat-text-fill'];
    $label = match($obs->tipo) {
        'felicitacion'     => 'Felicitación',
        'llamada_atencion' => 'Llamada de Atención',
        'compromiso'       => 'Compromiso',
        'informativa'      => 'Informativa',
        default            => ucfirst($obs->tipo ?? 'Observación'),
    };
@endphp
<div class="card border-0 shadow-sm" style="border-left:4px solid {{ $tc['color'] }} !important;">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span style="background:{{ $tc['bg'] }};color:{{ $tc['color'] }};padding:.2rem .65rem;border-radius:20px;font-size:.7rem;font-weight:700;">
                        <i class="bi {{ $tc['icon'] }} me-1"></i>{{ $label }}
                    </span>
                    @if($obs->asignacion)
                    <span style="font-size:.75rem;color:#6b7280;">
                        <i class="bi bi-journal me-1"></i>{{ $obs->asignacion?->asignatura?->nombre }}
                    </span>
                    @endif
                    @if($obs->periodo)
                    <span style="font-size:.75rem;color:#6b7280;">
                        <i class="bi bi-calendar3 me-1"></i>Período {{ $obs->periodo?->numero }}
                    </span>
                    @endif
                </div>
                <p class="mb-0" style="font-size:.875rem;color:#374151;line-height:1.6;">{{ $obs->descripcion }}</p>
            </div>
            <div style="font-size:.75rem;color:#9ca3af;white-space:nowrap;text-align:right;">
                <div>{{ $obs->docente?->nombre_completo ?? 'Docente' }}</div>
                <div>{{ $obs->created_at?->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>
</div>
@endforeach
</div>

@endif
@endsection
