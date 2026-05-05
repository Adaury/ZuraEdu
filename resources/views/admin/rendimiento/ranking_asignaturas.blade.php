@extends('layouts.admin')

@section('page-title', 'Ranking de Asignaturas')

@push('styles')
<style>
    .rk-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1.5rem;
    }
    [data-theme="dark"] .rk-card { background: #1e293b; border-color: #334155; }

    .rk-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .75rem 1rem;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
        background: #fff;
        margin-bottom: .5rem;
        transition: box-shadow .15s;
    }
    .rk-row:hover { box-shadow: 0 2px 12px rgba(30,58,110,.07); }
    [data-theme="dark"] .rk-row { background: #1e293b; border-color: #334155; }

    .rk-pos {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .85rem; flex-shrink: 0;
    }
    .rk-pos-1 { background: #fef9c3; color: #92400e; }
    .rk-pos-2 { background: #f1f5f9; color: #475569; }
    .rk-pos-3 { background: #fef3c7; color: #b45309; }
    .rk-pos-n { background: #f8fafc; color: #94a3b8; }

    .progress-rk { height: 8px; border-radius: 4px; background: #f1f5f9; overflow: hidden; flex: 1; }
    .progress-rk-fill { height: 100%; border-radius: 4px; transition: width .4s ease; }
    .fill-success { background: linear-gradient(90deg, #22c55e, #16a34a); }
    .fill-warning { background: linear-gradient(90deg, #fbbf24, #d97706); }
    .fill-danger  { background: linear-gradient(90deg, #f87171, #dc2626); }
</style>
@endpush

@section('content')

@if(!empty($sinAnio))
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No hay un año escolar activo configurado.</div>
@else

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-trophy me-2"></i>Ranking de Asignaturas
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            {{ $schoolYear->nombre }} — Asignaturas ordenadas por promedio grupal
        </p>
    </div>
    <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

{{-- Filtros --}}
<div class="rk-card mb-4">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
        <label class="fw-semibold mb-0" style="font-size:.85rem;">Grupo:</label>
        <select name="grupo_id" class="form-select form-select-sm" style="max-width:240px;" onchange="this.form.submit()">
            <option value="">— Seleccionar —</option>
            @foreach($grupos as $g)
            <option value="{{ $g->id }}" {{ $grupoId == $g->id ? 'selected' : '' }}>
                {{ $g->nombre_completo ?? $g->grado?->nombre . ' ' . $g->seccion?->nombre }}
            </option>
            @endforeach
        </select>

        <label class="fw-semibold mb-0" style="font-size:.85rem;">Período:</label>
        <select name="periodo" class="form-select form-select-sm" style="max-width:130px;" onchange="this.form.submit()">
            @foreach([1 => 'Período 1', 2 => 'Período 2', 3 => 'Período 3', 4 => 'Período 4'] as $num => $label)
            <option value="{{ $num }}" {{ $periodo == $num ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

@if($grupoId && count($ranking) > 0)

{{-- Leyenda --}}
<div class="d-flex gap-3 mb-3 flex-wrap" style="font-size:.78rem;color:#6b7280;">
    <span><span style="color:#16a34a;font-weight:700;">●</span> Aprobado ≥70</span>
    <span><span style="color:#d97706;font-weight:700;">●</span> Regular ≥60</span>
    <span><span style="color:#dc2626;font-weight:700;">●</span> Alerta &lt;60</span>
</div>

<div class="rk-card">
    <div class="fw-bold mb-3" style="color:#111827;">
        <i class="bi bi-list-ol me-2" style="color:#2563eb;"></i>
        Período {{ $periodo }} — {{ count($ranking) }} asignatura(s)
    </div>

    @foreach($ranking as $idx => $item)
    @php
        $pos   = $idx + 1;
        $pctW  = min($item['promedio'], 100);
        $cls   = $item['semaforo'] === 'success' ? 'fill-success' : ($item['semaforo'] === 'warning' ? 'fill-warning' : 'fill-danger');
        $color = $item['semaforo'] === 'success' ? '#16a34a'       : ($item['semaforo'] === 'warning' ? '#d97706'       : '#dc2626');
        $posCls = $pos === 1 ? 'rk-pos-1' : ($pos === 2 ? 'rk-pos-2' : ($pos === 3 ? 'rk-pos-3' : 'rk-pos-n'));
    @endphp
    <div class="rk-row">
        {{-- Posición --}}
        <div class="rk-pos {{ $posCls }}">{{ $pos }}</div>

        {{-- Nombre --}}
        <div style="min-width:160px;flex:0 0 160px;">
            <div class="fw-semibold" style="font-size:.88rem;color:#111827;line-height:1.2;">
                {{ $item['asignatura'] }}
            </div>
            <div style="font-size:.72rem;color:#9ca3af;">
                {{ $item['total'] }} estudiante(s)
            </div>
        </div>

        {{-- Barra de progreso --}}
        <div class="progress-rk">
            <div class="progress-rk-fill {{ $cls }}" style="width:{{ $pctW }}%;"></div>
        </div>

        {{-- Promedio --}}
        <div style="min-width:52px;text-align:right;">
            <span class="fw-bold" style="font-size:1.1rem;color:{{ $color }};">
                {{ number_format($item['promedio'], 1) }}
            </span>
        </div>

        {{-- % Reprobados --}}
        <div style="min-width:90px;text-align:right;">
            <span class="badge {{ $item['pct_reprobados'] > 30 ? 'text-bg-danger' : ($item['pct_reprobados'] > 0 ? 'text-bg-warning' : 'text-bg-success') }}"
                  style="font-size:.72rem;">
                {{ $item['pct_reprobados'] }}% reprobados
            </span>
        </div>
    </div>
    @endforeach
</div>

@elseif($grupoId)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No hay calificaciones registradas para este grupo y período.
</div>
@else
<div class="alert alert-secondary">
    <i class="bi bi-hand-index me-2"></i>Selecciona un grupo y un período para ver el ranking.
</div>
@endif

@endif
@endsection
