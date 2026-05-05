@extends('layouts.admin')

@section('page-title', 'Semáforo de Rendimiento')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-circle-fill me-2" style="color:#22c55e;"></i>Semáforo de Rendimiento
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            {{ $schoolYear->nombre ?? '' }} — ordenado de menor a mayor promedio
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.rendimiento.semaforo.pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.rendimiento.semaforo.excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>
</div>

@if(!empty($sinAnio))
    <div class="alert alert-warning">No hay año escolar activo.</div>
@elseif($grupos->isEmpty())
    <div class="alert alert-info">No hay datos de rendimiento calculados. Ve al Dashboard y haz clic en Recalcular.</div>
@else
<div class="row g-3">
    @foreach($grupos as $cache)
    @php
        $bg    = $cache->semaforo === 'success' ? '#f0fdf4' : ($cache->semaforo === 'warning' ? '#fffbeb' : '#fef2f2');
        $borde = $cache->semaforo === 'success' ? '#86efac' : ($cache->semaforo === 'warning' ? '#fde68a' : '#fca5a5');
        $color = $cache->semaforo === 'success' ? '#15803d' : ($cache->semaforo === 'warning' ? '#b45309' : '#b91c1c');
        $icono = $cache->semaforo === 'success' ? 'bi-emoji-smile-fill' : ($cache->semaforo === 'warning' ? 'bi-emoji-neutral-fill' : 'bi-emoji-frown-fill');
    @endphp
    <div class="col-6 col-md-4 col-lg-3">
        <div class="p-3 rounded-3 border h-100 text-center" style="background:{{ $bg }};border-color:{{ $borde }} !important;">
            <i class="bi {{ $icono }}" style="font-size:2rem;color:{{ $color }};"></i>
            <div class="fw-bold mt-2" style="font-size:1rem;color:#1e293b;">
                {{ optional($cache->grupo)->nombre_corto ?? 'Grupo ' . $cache->grupo_id }}
            </div>
            <div class="fw-black mt-1" style="font-size:1.6rem;color:{{ $color }};">
                {{ $cache->promedio_grupo ? number_format($cache->promedio_grupo, 1) : '—' }}
            </div>
            <div style="font-size:.72rem;color:#6b7280;">promedio general</div>
            <div class="d-flex justify-content-center gap-2 mt-2 flex-wrap" style="font-size:.72rem;">
                <span class="badge" style="background:#22c55e;">{{ $cache->pct_excelente }}% Exc</span>
                <span class="badge" style="background:#f59e0b;">{{ $cache->pct_regular }}% Reg</span>
                @if($cache->total_riesgo > 0)
                <span class="badge bg-danger">{{ $cache->total_riesgo }} riesgo</span>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4 p-3 rounded-3 border d-flex gap-4 flex-wrap align-items-center" style="background:#f8fafc;font-size:.82rem;">
    <span class="fw-semibold">Leyenda:</span>
    <span><span style="color:#15803d;font-size:1rem;">●</span> Verde: promedio ≥ 80</span>
    <span><span style="color:#b45309;font-size:1rem;">●</span> Amarillo: promedio ≥ 70</span>
    <span><span style="color:#b91c1c;font-size:1rem;">●</span> Rojo: promedio &lt; 70 — Requiere atención</span>
</div>
@endif
@endsection
