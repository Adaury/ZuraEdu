@extends('layouts.portal-estudiante')
@section('title', 'Mis Recursos')
@section('activeKey', 'mis-recursos')

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-folder2-open me-2"></i>Mis Recursos</h4>
        @if($matricula)
        <p class="prt-page-subtitle">{{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}</p>
        @else
        <p class="prt-page-subtitle">Materiales de estudio compartidos por tus docentes</p>
        @endif
    </div>
    <a href="{{ route('portal.estudiante.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-house me-1"></i>Inicio
    </a>
</div>

@if(! $matricula)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-folder-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No tienes una matrícula activa.</p>
    </div>
</div>
@elseif($asignaciones->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-folder-x" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">Tus docentes aún no han publicado recursos este año.</p>
    </div>
</div>
@else

@foreach($asignaciones as $asignacion)
@php
    $tipos = [
        'documento'  => ['icon' => 'bi-file-earmark-text',   'color' => '#3b82f6'],
        'video'      => ['icon' => 'bi-play-circle-fill',    'color' => '#ef4444'],
        'enlace'     => ['icon' => 'bi-link-45deg',          'color' => '#8b5cf6'],
        'imagen'     => ['icon' => 'bi-image-fill',          'color' => '#10b981'],
        'audio'      => ['icon' => 'bi-music-note-beamed',   'color' => '#f59e0b'],
        'presentacion'=> ['icon'=> 'bi-file-earmark-slides', 'color' => '#6366f1'],
    ];
@endphp
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div style="width:38px;height:38px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-journals" style="color:#fff;font-size:1rem;"></i>
        </div>
        <div>
            <div class="fw-bold" style="color:#1e3a6e;">{{ $asignacion->asignatura?->nombre }}</div>
            <small class="text-muted">
                <i class="bi bi-person me-1"></i>{{ $asignacion->docente?->nombre_completo ?? '—' }}
                &nbsp;·&nbsp; {{ $asignacion->recursos->count() }} recurso(s)
            </small>
        </div>
        <a href="{{ route('portal.estudiante.recursos', $asignacion) }}" class="btn btn-sm btn-outline-primary ms-auto">
            <i class="bi bi-arrow-right me-1"></i>Ver todos
        </a>
    </div>

    <div class="row g-2">
        @foreach($asignacion->recursos->take(4) as $recurso)
        @php
            $ti = $tipos[$recurso->tipo] ?? ['icon' => 'bi-file-earmark', 'color' => '#64748b'];
        @endphp
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:3px solid {{ $ti['color'] }}!important;">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <i class="bi {{ $ti['icon'] }}" style="font-size:1.1rem;color:{{ $ti['color'] }};flex-shrink:0;"></i>
                    <div style="min-width:0;">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem;color:#1e293b;">
                            {{ $recurso->titulo }}
                        </div>
                        <small class="text-muted" style="font-size:.72rem;">{{ ucfirst($recurso->tipo) }}</small>
                    </div>
                    @if($recurso->url)
                    <a href="{{ $recurso->url }}" target="_blank"
                       class="ms-auto btn btn-sm p-1" style="color:{{ $ti['color'] }};"
                       title="Abrir">
                        <i class="bi bi-box-arrow-up-right" style="font-size:.8rem;"></i>
                    </a>
                    @elseif($recurso->archivo_path)
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($recurso->archivo_path) }}" target="_blank"
                       class="ms-auto btn btn-sm p-1" style="color:{{ $ti['color'] }};"
                       title="Descargar">
                        <i class="bi bi-download" style="font-size:.8rem;"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

        @if($asignacion->recursos->count() > 4)
        <div class="col-12">
            <a href="{{ route('portal.estudiante.recursos', $asignacion) }}"
               class="btn btn-sm btn-outline-secondary w-100" style="font-size:.8rem;">
                <i class="bi bi-plus-circle me-1"></i>
                Ver {{ $asignacion->recursos->count() - 4 }} recurso(s) más
            </a>
        </div>
        @endif
    </div>
</div>
@endforeach

@endif
@endsection
