@extends('layouts.admin')

@section('page-title', 'Rendimiento por Grupo')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-people me-2"></i>Rendimiento por Grupo
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">{{ $schoolYear->nombre ?? '' }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($grupoId)
        <a href="{{ route('admin.rendimiento.porGrupo.pdf', ['grupo_id' => $grupoId]) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.rendimiento.porGrupo.excel', ['grupo_id' => $grupoId]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        @endif
        <a href="{{ route('admin.rendimiento.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>
</div>

{{-- Selector de grupo --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0" style="font-size:.85rem;">Seleccionar Grupo:</label>
            <select name="grupo_id" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                <option value="">— Seleccionar —</option>
                @foreach($grupos as $g)
                <option value="{{ $g->id }}" {{ $grupoId == $g->id ? 'selected' : '' }}>
                    {{ $g->nombre_corto ?? ($g->grado->nombre . ' ' . $g->seccion->nombre) }}
                </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

@if($grupoId && $detalle)
@php
    $semaforoColor = $detalle->semaforo === 'success' ? '#16a34a' : ($detalle->semaforo === 'warning' ? '#d97706' : '#dc2626');
    $semaforoBg    = $detalle->semaforo === 'success' ? '#dcfce7' : ($detalle->semaforo === 'warning' ? '#fef3c7' : '#fee2e2');
@endphp

{{-- Header del grupo --}}
<div class="card border-0 shadow-sm mb-4" style="border-left:5px solid {{ $semaforoColor }} !important;">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5 class="fw-bold mb-0" style="color:#1e293b;">
                    {{ optional($detalle->grupo)->nombre_corto ?? 'Grupo' }}
                </h5>
                <div class="text-muted" style="font-size:.82rem;">
                    {{ optional(optional($detalle->grupo)->grado)->nombre ?? '' }}
                    @php $nivel = optional(optional($detalle->grupo)->grado)->nivel ?? 0; @endphp
                    · {{ $nivel <= 3 ? 'Primer Ciclo' : 'Segundo Ciclo' }}
                </div>
                <div class="mt-1" style="font-size:.78rem;color:#6b7280;">
                    Calculado: {{ $detalle->calculado_en->diffForHumans() }}
                </div>
            </div>
            <div class="col-md-8">
                <div class="row g-3 text-center">
                    <div class="col-3">
                        <div class="fw-black" style="font-size:1.6rem;color:{{ $semaforoColor }};">
                            {{ $detalle->promedio_grupo ? number_format($detalle->promedio_grupo, 1) : '—' }}
                        </div>
                        <div style="font-size:.72rem;color:#9ca3af;">Promedio</div>
                    </div>
                    <div class="col-3">
                        <div class="fw-black" style="font-size:1.6rem;color:#1e293b;">{{ $detalle->total_estudiantes }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">Estudiantes</div>
                    </div>
                    <div class="col-3">
                        <div class="fw-black" style="font-size:1.6rem;color:#16a34a;">{{ $detalle->total_aprobados }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">Aprobados</div>
                    </div>
                    <div class="col-3">
                        <div class="fw-black" style="font-size:1.6rem;color:#dc2626;">{{ $detalle->total_riesgo }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">En Riesgo</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Distribución --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="fw-bold mb-0">Distribución de Calificaciones</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach([
                ['label' => 'Excelente', 'pct' => $detalle->pct_excelente, 'color' => '#22c55e', 'desc' => '≥ 90'],
                ['label' => 'Bueno',     'pct' => $detalle->pct_bueno,     'color' => '#84cc16', 'desc' => '80–89'],
                ['label' => 'Regular',   'pct' => $detalle->pct_regular,   'color' => '#f59e0b', 'desc' => '70–79'],
                ['label' => 'En Riesgo', 'pct' => $detalle->pct_bajo,      'color' => '#ef4444', 'desc' => '< 70'],
            ] as $d)
            <div class="col-6 col-md-3">
                <div class="text-center p-3 rounded-2" style="background:#f8fafc;border:1px solid #e5e7eb;">
                    <div class="fw-black" style="font-size:1.4rem;color:{{ $d['color'] }};">
                        {{ number_format($d['pct'], 1) }}%
                    </div>
                    <div class="fw-semibold" style="font-size:.82rem;color:#1e293b;">{{ $d['label'] }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;">{{ $d['desc'] }} puntos</div>
                    <div class="mt-2" style="height:6px;border-radius:3px;background:#e5e7eb;">
                        <div style="height:100%;border-radius:3px;background:{{ $d['color'] }};width:{{ $d['pct'] }}%;"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Alerta si hay riesgo --}}
@if($detalle->total_riesgo > 0)
<div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill mt-1" style="flex-shrink:0;"></i>
    <div>
        <strong>{{ $detalle->total_riesgo }} estudiante(s) en riesgo académico.</strong>
        Estos estudiantes tienen promedio menor a 70 puntos. Se recomienda intervención del coordinador y docentes.
    </div>
</div>
@else
<div class="alert alert-success d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill"></i>
    <strong>¡Excelente!</strong> Todos los estudiantes de este grupo están aprobados.
</div>
@endif

@elseif($grupoId && !$detalle)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No hay datos de rendimiento calculados para este grupo.
    <form method="POST" action="{{ route('admin.rendimiento.recalcular') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-link p-0 alert-link" style="vertical-align:baseline;">Recalcular datos</button>
    </form>.
</div>
@else
<div class="empty-state-enhanced">
    <div class="empty-illustration"><i class="bi bi-people"></i></div>
    <div class="empty-title">Selecciona un grupo</div>
    <div class="empty-desc">Elige un grupo del selector superior para ver su detalle de rendimiento.</div>
</div>
@endif
@endsection
