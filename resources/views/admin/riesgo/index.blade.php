@extends('layouts.admin')
@section('title', 'Academic Risk Score')

@section('content')
<style>
.ars-nivel-bar { height: 8px; border-radius: 99px; background: #e5e7eb; overflow: hidden; }
.ars-nivel-bar span { display: block; height: 100%; border-radius: 99px; transition: width .6s ease; }
.ars-score-pill { display: inline-flex; align-items: center; gap: 5px; font-weight: 800; font-size: .82rem; border-radius: 99px; padding: 3px 10px; }
.ars-dim { display: flex; flex-direction: column; gap: 3px; }
.ars-dim-bar { height: 5px; border-radius: 99px; background: #f1f5f9; overflow: hidden; }
.ars-dim-bar span { display: block; height: 100%; border-radius: 99px; }
</style>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-800 mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-shield-exclamation" style="color:#ef4444;"></i>
            Academic Risk Score
        </h1>
        <div class="text-muted small">
            {{ $schoolYear?->nombre ?? 'Sin año escolar' }}
            @if($ultimoCalc)
                · Último cálculo: {{ \Carbon\Carbon::parse($ultimoCalc)->diffForHumans() }}
            @endif
        </div>
    </div>
    <form method="POST" action="{{ route('admin.riesgo.calcular') }}" class="d-inline" id="formCalcAll">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm fw-700 d-flex align-items-center gap-2" onclick="this.disabled=true;this.innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Calculando…';this.form.submit();">
            <i class="bi bi-calculator-fill"></i> Calcular / Actualizar Scores
        </button>
    </form>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 small">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Resumen por nivel --}}
<div class="row g-3 mb-4">
@php
$nivCfg = \App\Models\AcademicRiskScore::NIVELES;
$nivelesOrden = ['sin_riesgo','bajo','moderado','alto','critico'];
@endphp
@foreach($nivelesOrden as $niv)
@php $cfg = $nivCfg[$niv]; $cnt = $resumen[$niv] ?? 0; $pct = $totalEst > 0 ? round($cnt/$totalEst*100) : 0; @endphp
<div class="col-6 col-md">
    <a href="{{ route('admin.riesgo.index', ['nivel' => $niv] + request()->except('nivel','page')) }}"
       style="text-decoration:none;">
    <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid {{ $cfg['color'] }} !important; background: {{ $cfg['bg'] }}; border-radius: 14px;">
        <div class="card-body p-3 text-center">
            <div class="fw-900 mb-1" style="font-size:1.8rem; color:{{ $cfg['color'] }};">{{ $cnt }}</div>
            <div class="fw-700 small" style="color:{{ $cfg['color'] }};">{{ $cfg['label'] }}</div>
            <div class="text-muted" style="font-size:.72rem;">{{ $pct }}% del total</div>
        </div>
    </div>
    </a>
</div>
@endforeach
</div>

{{-- Barra global de distribución --}}
@if($totalEst > 0)
<div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-700 small">Distribución de riesgo</span>
            <span class="text-muted small">{{ $totalEst }} estudiantes evaluados</span>
        </div>
        <div style="height:16px; border-radius:99px; overflow:hidden; display:flex; gap:2px;">
            @foreach($nivelesOrden as $niv)
            @php $cfg=$nivCfg[$niv]; $cnt=$resumen[$niv]??0; $w=$totalEst>0?round($cnt/$totalEst*100,1):0; @endphp
            @if($w > 0)
            <div style="width:{{ $w }}%; background:{{ $cfg['color'] }}; border-radius:99px;" title="{{ $cfg['label'] }}: {{ $cnt }}"></div>
            @endif
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-3 mt-2">
            @foreach($nivelesOrden as $niv)
            @php $cfg=$nivCfg[$niv]; $cnt=$resumen[$niv]??0; @endphp
            <span style="font-size:.72rem; color:{{ $cfg['color'] }}; font-weight:700;">
                <i class="bi bi-circle-fill" style="font-size:.5rem;"></i> {{ $cfg['label'] }}: {{ $cnt }}
            </span>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Filtros --}}
<form method="GET" class="card border-0 shadow-sm mb-3 p-3" style="border-radius:14px;">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Buscar estudiante…">
        </div>
        <div class="col-md-3">
            <select name="grupo_id" class="form-select form-select-sm">
                <option value="">Todos los grupos</option>
                @foreach($grupos as $g)
                <option value="{{ $g->id }}" {{ $grupoFiltro == $g->id ? 'selected' : '' }}>
                    {{ $g->grado?->nombre }} {{ $g->seccion?->nombre ?? '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="nivel" class="form-select form-select-sm">
                <option value="">Todos los niveles</option>
                @foreach($nivCfg as $key => $cfg)
                <option value="{{ $key }}" {{ $nivelFiltro === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-funnel-fill"></i> Filtrar
            </button>
            <a href="{{ route('admin.riesgo.index') }}" class="btn btn-outline-secondary btn-sm">✕</a>
        </div>
    </div>
</form>

{{-- Tabla de estudiantes --}}
<div class="card border-0 shadow-sm" style="border-radius:16px; overflow:hidden;">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:.84rem;">
        <thead style="background:#f8fafc; border-bottom:2px solid #e2e8f0;">
            <tr>
                <th class="ps-3 py-2">Estudiante</th>
                <th>Grupo</th>
                <th style="min-width:160px;">Score de Riesgo</th>
                <th class="text-center">Académico</th>
                <th class="text-center">Asistencia</th>
                <th class="text-center">Disciplina</th>
                <th class="text-center">Tendencia</th>
                <th class="text-end pe-3">Calculado</th>
            </tr>
        </thead>
        <tbody>
        @forelse($scores as $ars)
        @php
            $cfg    = $ars->nivel_config;
            $mat    = $ars->estudiante?->matriculas->first();
            $nombre = $ars->estudiante?->nombre_completo ?? '—';
        @endphp
        <tr onclick="window.location='{{ route('admin.riesgo.show', $ars) }}'" style="cursor:pointer;">
            <td class="ps-3">
                <div class="fw-700">{{ $nombre }}</div>
                <div class="text-muted" style="font-size:.74rem;">{{ $ars->estudiante?->matricula ?? '' }}</div>
            </td>
            <td>
                @if($mat)
                <span class="badge bg-light text-dark border fw-700" style="font-size:.72rem;">
                    {{ $mat->grupo?->grado?->nombre }} {{ $mat->grupo?->seccion?->nombre }}
                </span>
                @else —@endif
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <span class="ars-score-pill" style="background:{{ $cfg['color'] }}22; color:{{ $cfg['color'] }};">
                        {{ $ars->score }}
                    </span>
                    <div class="ars-nivel-bar flex-grow-1">
                        <span style="width:{{ $ars->score }}%; background:{{ $cfg['color'] }};"></span>
                    </div>
                    <span class="badge fw-700" style="background:{{ $cfg['color'] }}22; color:{{ $cfg['color'] }}; font-size:.68rem;">{{ $cfg['label'] }}</span>
                </div>
            </td>
            <td class="text-center">
                <div class="ars-dim">
                    <span style="font-size:.75rem; font-weight:700; color:{{ $ars->dim_academico > 60 ? '#ef4444' : ($ars->dim_academico > 30 ? '#f59e0b' : '#22c55e') }}">{{ round($ars->dim_academico) }}</span>
                    <div class="ars-dim-bar"><span style="width:{{ $ars->dim_academico }}%; background:{{ $ars->dim_academico > 60 ? '#ef4444' : ($ars->dim_academico > 30 ? '#f59e0b' : '#22c55e') }};"></span></div>
                    <span class="text-muted" style="font-size:.68rem;">{{ $ars->materias_en_riesgo }}/{{ $ars->total_materias }} mat.</span>
                </div>
            </td>
            <td class="text-center">
                <div class="ars-dim">
                    <span style="font-size:.75rem; font-weight:700; color:{{ $ars->dim_asistencia > 60 ? '#ef4444' : ($ars->dim_asistencia > 30 ? '#f59e0b' : '#22c55e') }}">{{ round($ars->dim_asistencia) }}</span>
                    <div class="ars-dim-bar"><span style="width:{{ $ars->dim_asistencia }}%; background:{{ $ars->dim_asistencia > 60 ? '#ef4444' : ($ars->dim_asistencia > 30 ? '#f59e0b' : '#22c55e') }};"></span></div>
                    <span class="text-muted" style="font-size:.68rem;">{{ $ars->pct_asistencia !== null ? round($ars->pct_asistencia).'%' : '—' }}</span>
                </div>
            </td>
            <td class="text-center">
                <div class="ars-dim">
                    <span style="font-size:.75rem; font-weight:700; color:{{ $ars->dim_disciplina > 60 ? '#ef4444' : ($ars->dim_disciplina > 30 ? '#f59e0b' : '#22c55e') }}">{{ round($ars->dim_disciplina) }}</span>
                    <div class="ars-dim-bar"><span style="width:{{ $ars->dim_disciplina }}%; background:{{ $ars->dim_disciplina > 60 ? '#ef4444' : ($ars->dim_disciplina > 30 ? '#f59e0b' : '#22c55e') }};"></span></div>
                    <span class="text-muted" style="font-size:.68rem;">{{ $ars->faltas_graves + $ars->suspensiones }} grave{{ $ars->faltas_graves + $ars->suspensiones === 1 ? '' : 's' }}</span>
                </div>
            </td>
            <td class="text-center">
                <div class="ars-dim">
                    <span style="font-size:.75rem; font-weight:700; color:{{ $ars->dim_tendencia > 60 ? '#ef4444' : ($ars->dim_tendencia > 30 ? '#f59e0b' : '#22c55e') }}">{{ round($ars->dim_tendencia) }}</span>
                    <div class="ars-dim-bar"><span style="width:{{ $ars->dim_tendencia }}%; background:{{ $ars->dim_tendencia > 60 ? '#ef4444' : ($ars->dim_tendencia > 30 ? '#f59e0b' : '#22c55e') }};"></span></div>
                    <span class="text-muted" style="font-size:.68rem;">
                        @if($ars->dim_tendencia <= 10) Mejorando
                        @elseif($ars->dim_tendencia <= 30) Estable
                        @elseif($ars->dim_tendencia <= 60) Declive leve
                        @else Declive
                        @endif
                    </span>
                </div>
            </td>
            <td class="text-end text-muted pe-3" style="font-size:.72rem;">
                {{ \Carbon\Carbon::parse($ars->calculado_en)->diffForHumans() }}
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted py-5">
            @if($totalEst === 0)
                No hay scores calculados aún.
                <a href="#" onclick="document.getElementById('formCalcAll').submit()" class="text-primary fw-700">Calcular ahora</a>
            @else
                No se encontraron estudiantes con los filtros aplicados.
            @endif
        </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Paginación --}}
@if($scores->hasPages())
<div class="d-flex justify-content-center mt-3">
    {{ $scores->links() }}
</div>
@endif

{{-- Leyenda metodología --}}
<div class="card border-0 mt-4" style="border-radius:14px; background:#f8fafc;">
    <div class="card-body p-3">
        <p class="fw-700 small mb-2"><i class="bi bi-info-circle text-primary"></i> Metodología del Score</p>
        <div class="row g-3 small text-muted">
            <div class="col-md-3">
                <strong class="text-dark">Académico (40%)</strong><br>
                Basado en el % de materias con nota < 70 y el promedio general.
            </div>
            <div class="col-md-3">
                <strong class="text-dark">Asistencia (30%)</strong><br>
                Calculado desde el % de asistencia acumulado en el año.
            </div>
            <div class="col-md-3">
                <strong class="text-dark">Disciplina (20%)</strong><br>
                Pondera tardanzas, faltas leves, graves y suspensiones.
            </div>
            <div class="col-md-3">
                <strong class="text-dark">Tendencia (10%)</strong><br>
                Compara promedios entre períodos para detectar declive.
            </div>
        </div>
    </div>
</div>

@endsection
