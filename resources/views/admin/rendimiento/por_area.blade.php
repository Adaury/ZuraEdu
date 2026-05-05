@extends('layouts.admin')

@section('page-title', 'Rendimiento por Área')

@push('styles')
<style>
    .area-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }
    .area-card-header {
        padding: 1.25rem 1.5rem;
        color: #fff;
    }
    .stat-box {
        background: rgba(255,255,255,.15);
        border-radius: 8px;
        padding: .6rem 1rem;
        text-align: center;
    }
    .bar-wrap { background: #f0f4f8; border-radius: 6px; height: 10px; overflow: hidden; }
    .bar-fill  { height: 100%; border-radius: 6px; transition: width .6s ease; }

    [data-theme="dark"] .area-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .bar-wrap { background: #334155; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-graph-up-arrow me-2"></i>Rendimiento por Área
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            Comparativa Académica vs Técnica — {{ $schoolYear->nombre ?? '' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.rendimiento.porArea.pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.rendimiento.porArea.excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>
</div>

@if(!empty($sinAnio))
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No hay año escolar activo.</div>
@else

<div class="row g-4 mb-4">

    {{-- ÁREA ACADÉMICA --}}
    <div class="col-md-6">
        <div class="area-card">
            <div class="area-card-header" style="background:linear-gradient(135deg,#1e3a6e,#2a4f96);">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="font-size:1.8rem;"><i class="bi bi-book-half"></i></div>
                    <div>
                        <div class="fw-bold fs-5">Área Académica</div>
                        <div style="font-size:.8rem;opacity:.85;">Calificaciones de materias generales</div>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div class="stat-box">
                        <div class="fw-black" style="font-size:1.5rem;">
                            {{ $academica->promedio ? number_format($academica->promedio, 1) : '—' }}
                        </div>
                        <div style="font-size:.7rem;opacity:.85;">Promedio General</div>
                    </div>
                    <div class="stat-box">
                        <div class="fw-black" style="font-size:1.5rem;">
                            {{ number_format($academica->total ?? 0) }}
                        </div>
                        <div style="font-size:.7rem;opacity:.85;">Calificaciones</div>
                    </div>
                </div>
            </div>
            <div class="p-3">
                @if($academica->promedio)
                @php
                    $prom = round($academica->promedio, 1);
                    $color = $prom >= 80 ? '#22c55e' : ($prom >= 70 ? '#f59e0b' : '#ef4444');
                    $pct   = min(100, $prom);
                @endphp
                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;color:#6b7280;">
                    <span>Nivel de rendimiento</span>
                    <span class="fw-bold" style="color:{{ $color }};">
                        {{ $prom >= 80 ? 'Bueno' : ($prom >= 70 ? 'Regular' : 'Bajo') }}
                    </span>
                </div>
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:.72rem;color:#9ca3af;">
                    <span>0</span><span>70</span><span>80</span><span>100</span>
                </div>
                @else
                <p class="text-muted text-center py-3" style="font-size:.83rem;">
                    <i class="bi bi-info-circle me-1"></i>Sin calificaciones publicadas aún.
                </p>
                @endif
            </div>
        </div>
    </div>

    {{-- ÁREA TÉCNICA --}}
    <div class="col-md-6">
        <div class="area-card">
            <div class="area-card-header" style="background:linear-gradient(135deg,#c0392b,#e74c3c);">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="font-size:1.8rem;"><i class="bi bi-tools"></i></div>
                    <div>
                        <div class="fw-bold fs-5">Área Técnica</div>
                        <div style="font-size:.8rem;opacity:.85;">Calificaciones de especialidades técnicas</div>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div class="stat-box">
                        <div class="fw-black" style="font-size:1.5rem;">
                            {{ $tecnica->promedio ? number_format($tecnica->promedio, 1) : '—' }}
                        </div>
                        <div style="font-size:.7rem;opacity:.85;">Promedio General</div>
                    </div>
                    <div class="stat-box">
                        <div class="fw-black" style="font-size:1.5rem;">
                            {{ number_format($tecnica->total ?? 0) }}
                        </div>
                        <div style="font-size:.7rem;opacity:.85;">Calificaciones</div>
                    </div>
                </div>
            </div>
            <div class="p-3">
                @if($tecnica->promedio)
                @php
                    $prom = round($tecnica->promedio, 1);
                    $color = $prom >= 80 ? '#22c55e' : ($prom >= 70 ? '#f59e0b' : '#ef4444');
                    $pct   = min(100, $prom);
                @endphp
                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;color:#6b7280;">
                    <span>Nivel de rendimiento</span>
                    <span class="fw-bold" style="color:{{ $color }};">
                        {{ $prom >= 80 ? 'Bueno' : ($prom >= 70 ? 'Regular' : 'Bajo') }}
                    </span>
                </div>
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:.72rem;color:#9ca3af;">
                    <span>0</span><span>70</span><span>80</span><span>100</span>
                </div>
                @else
                <p class="text-muted text-center py-3" style="font-size:.83rem;">
                    <i class="bi bi-info-circle me-1"></i>Sin calificaciones publicadas aún.
                </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Comparativa visual --}}
@if($academica->promedio || $tecnica->promedio)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h6 class="fw-bold mb-0">Comparativa Visual</h6>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div style="font-size:.82rem;font-weight:600;color:#1e3a6e;min-width:120px;">Área Académica</div>
            <div class="flex-grow-1">
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:{{ min(100, $academica->promedio ?? 0) }}%;background:#1e3a6e;"></div>
                </div>
            </div>
            <div class="fw-black" style="min-width:40px;text-align:right;color:#1e3a6e;">
                {{ $academica->promedio ? number_format($academica->promedio, 1) : '—' }}
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div style="font-size:.82rem;font-weight:600;color:#c0392b;min-width:120px;">Área Técnica</div>
            <div class="flex-grow-1">
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:{{ min(100, $tecnica->promedio ?? 0) }}%;background:#c0392b;"></div>
                </div>
            </div>
            <div class="fw-black" style="min-width:40px;text-align:right;color:#c0392b;">
                {{ $tecnica->promedio ? number_format($tecnica->promedio, 1) : '—' }}
            </div>
        </div>

        <div class="alert alert-info mt-4 py-2" style="font-size:.82rem;">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Nota MINERD:</strong> La nota mínima de aprobación es <strong>70 puntos</strong>.
            Estudiantes con promedio &lt;70 en alguna asignatura se consideran en riesgo académico.
        </div>
    </div>
</div>
@endif
@endif
@endsection
