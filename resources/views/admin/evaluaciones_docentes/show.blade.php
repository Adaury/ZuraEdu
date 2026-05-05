@extends('layouts.admin')
@section('page-title', 'Detalle de Evaluación')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }

    .nivel-badge {
        font-size:.85rem; font-weight:700; padding:.35rem 1rem;
        border-radius:20px; display:inline-block;
    }
    .nivel-excelente  { background:#dcfce7; color:#166534; }
    .nivel-bueno      { background:#dbeafe; color:#1e40af; }
    .nivel-regular    { background:#fef9c3; color:#854d0e; }
    .nivel-deficiente { background:#fee2e2; color:#991b1b; }

    .criterio-row { display:flex; align-items:center; gap:1rem; padding:.85rem 0; border-bottom:1px solid #f1f5f9; }
    .criterio-row:last-child { border-bottom:none; }
    .criterio-lbl  { flex:1; font-size:.88rem; font-weight:600; color:#374151; }
    .criterio-ptos { font-size:1.1rem; font-weight:800; min-width:2rem; text-align:center; color:var(--primary); }
    .bar-wrap  { flex:1; background:#e2e8f0; border-radius:6px; height:10px; max-width:200px; }
    .bar-fill  { height:10px; border-radius:6px; transition:width .6s ease; }
    .stars-row { font-size:1.1rem; color:#f59e0b; letter-spacing:2px; }

    .kpi-card { text-align:center; padding:1.25rem 1rem; border-radius:14px; border:1px solid #e2e8f0; }
    .kpi-num  { font-size:2rem; font-weight:900; line-height:1; }
    .kpi-lbl  { font-size:.75rem; color:#6b7280; margin-top:.3rem; text-transform:uppercase; letter-spacing:.04em; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif

@php $nivel = $evaluacion->nivelDesempeno(); @endphp

<div class="page-header">
    <div>
        <h1><i class="bi bi-clipboard2-check me-2" style="color:var(--secondary);"></i>Evaluación de Desempeño</h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            {{ $evaluacion->docente->nombre_completo }}
            &nbsp;·&nbsp; {{ $evaluacion->periodo_evaluado }}
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.evaluaciones-docentes.pdf', $evaluacion) }}"
           target="_blank"
           class="btn btn-sm btn-danger">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.evaluaciones-docentes.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- Panel izquierdo --}}
    <div class="col-lg-8">

        {{-- Datos del docente --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-body px-4 py-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.06em;">Docente</div>
                        <div class="fw-700 mt-1" style="font-size:1rem;color:#111827;">{{ $evaluacion->docente->nombre_completo }}</div>
                        <div style="font-size:.82rem;color:#6b7280;">{{ $evaluacion->docente->especialidad ?? '—' }}</div>
                    </div>
                    <div class="col-sm-3">
                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.06em;">Período</div>
                        <div class="fw-600 mt-1" style="color:#374151;">{{ $evaluacion->periodo_evaluado }}</div>
                    </div>
                    <div class="col-sm-3">
                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.06em;">Evaluador</div>
                        <div class="fw-600 mt-1" style="color:#374151;">{{ $evaluacion->evaluador->name ?? '—' }}</div>
                        <div style="font-size:.78rem;color:#9ca3af;">{{ $evaluacion->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Criterios --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header border-0 pt-4 pb-0 px-4">
                <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                    <i class="bi bi-star-half me-2"></i>Resultados por Criterio
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                @php
                $criteriosData = [
                    ['key' => 'puntualidad',         'label' => 'Puntualidad y Asistencia'],
                    ['key' => 'dominio_contenido',    'label' => 'Dominio del Contenido'],
                    ['key' => 'metodologia',          'label' => 'Metodología de Enseñanza'],
                    ['key' => 'relacion_estudiantes', 'label' => 'Relación con Estudiantes'],
                    ['key' => 'planificacion',        'label' => 'Planificación Docente'],
                ];
                $barColors = [5=>'#22c55e',4=>'#84cc16',3=>'#f59e0b',2=>'#f97316',1=>'#ef4444'];
                @endphp

                @foreach($criteriosData as $crit)
                @php
                    $val = $evaluacion->{$crit['key']};
                    $pct = ($val / 5) * 100;
                    $color = $barColors[$val] ?? '#94a3b8';
                @endphp
                <div class="criterio-row">
                    <div class="criterio-lbl">{{ $crit['label'] }}</div>
                    <div class="stars-row">
                        @for($i = 1; $i <= 5; $i++)
                            {!! $i <= $val ? '&#9733;' : '<span style="color:#d1d5db;">&#9734;</span>' !!}
                        @endfor
                    </div>
                    <div class="bar-wrap">
                        <div class="bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                    </div>
                    <div class="criterio-ptos">{{ $val }}/5</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Observaciones --}}
        @if($evaluacion->observaciones)
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header border-0 pt-4 pb-0 px-4">
                <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                    <i class="bi bi-chat-square-text me-2"></i>Observaciones
                </h6>
            </div>
            <div class="card-body px-4 pb-4">
                <p style="font-size:.9rem;color:#374151;line-height:1.7;white-space:pre-wrap;">{{ $evaluacion->observaciones }}</p>
            </div>
        </div>
        @endif

    </div>

    {{-- Panel derecho: resumen --}}
    <div class="col-lg-4">

        {{-- Promedio --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,{{ $nivel['text'] }},{{ $nivel['color'] }});padding:2rem;text-align:center;">
                <div style="font-size:3.5rem;font-weight:900;color:{{ $nivel['text'] }};">
                    {{ number_format($evaluacion->promedio, 2) }}
                </div>
                <div style="font-size:.75rem;color:{{ $nivel['text'] }};opacity:.7;margin-top:.25rem;text-transform:uppercase;letter-spacing:.06em;">Promedio Final</div>
                <div class="mt-2" style="font-size:1.2rem;font-weight:800;
                    background:{{ $nivel['color'] }};color:{{ $nivel['text'] }};
                    display:inline-block;padding:.3rem 1.25rem;border-radius:20px;">
                    {{ $nivel['label'] }}
                </div>
            </div>
        </div>

        {{-- KPIs individuales --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-body px-4 py-3">
                <div class="row g-2">
                    @php
                    $kpis = [
                        ['val' => $evaluacion->puntualidad,         'lbl' => 'Puntualidad',   'color' => '#eff6ff', 'txt' => '#1d4ed8'],
                        ['val' => $evaluacion->dominio_contenido,   'lbl' => 'Dominio',       'color' => '#f0fdf4', 'txt' => '#15803d'],
                        ['val' => $evaluacion->metodologia,         'lbl' => 'Metodología',   'color' => '#fdf4ff', 'txt' => '#7e22ce'],
                        ['val' => $evaluacion->relacion_estudiantes,'lbl' => 'Relación',      'color' => '#fff7ed', 'txt' => '#c2410c'],
                        ['val' => $evaluacion->planificacion,       'lbl' => 'Planificación', 'color' => '#fefce8', 'txt' => '#a16207'],
                    ];
                    @endphp
                    @foreach($kpis as $k)
                    <div class="col-6">
                        <div class="kpi-card" style="background:{{ $k['color'] }};border-color:transparent;">
                            <div class="kpi-num" style="color:{{ $k['txt'] }};">{{ $k['val'] }}</div>
                            <div class="kpi-lbl">{{ $k['lbl'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body px-4 py-3 d-grid gap-2">
                <a href="{{ route('admin.evaluaciones-docentes.pdf', $evaluacion) }}"
                   target="_blank"
                   class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i>Descargar PDF
                </a>
                <a href="{{ route('admin.evaluaciones-docentes.create', ['docente_id' => $evaluacion->docente_id]) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Evaluación al Docente
                </a>
                <button type="button" class="btn btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#modalDelShow">
                    <i class="bi bi-trash me-2"></i>Eliminar Esta Evaluación
                </button>
            </div>
        </div>

    </div>
</div>

{{-- Modal eliminar --}}
<div class="modal fade" id="modalDelShow" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h5 class="fw-700 mb-2">¿Eliminar esta evaluación?</h5>
                <p class="text-muted mb-4" style="font-size:.88rem;">
                    Se eliminará permanentemente la evaluación de
                    <strong>{{ $evaluacion->docente->nombre_completo }}</strong>.
                    Esta acción no se puede deshacer.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ route('admin.evaluaciones-docentes.destroy', $evaluacion) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn px-4"
                                style="background:var(--secondary);color:#fff;border-radius:8px;">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
