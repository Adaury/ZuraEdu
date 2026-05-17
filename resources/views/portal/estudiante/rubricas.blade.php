@extends('layouts.portal-estudiante')
@section('title', 'Mis Rúbricas')
@section('activeKey', 'rubricas')

@section('content')
<div class="prt-page-header">
    <div>
        <h4 class="prt-page-title"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Mis Rúbricas</h4>
        @if($matricula)
        <p class="prt-page-subtitle">{{ $matricula->grupo?->nombre_completo }} — {{ $schoolYear?->nombre }}</p>
        @else
        <p class="prt-page-subtitle">Evaluaciones por criterio aplicadas por tus docentes</p>
        @endif
    </div>
    <a href="{{ route('portal.estudiante.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-house me-1"></i>Inicio
    </a>
</div>

@if(! $matricula)
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-grid-3x3-gap" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">No tienes una matrícula activa.</p>
    </div>
</div>
@elseif($aplicaciones->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-grid-3x3-gap" style="font-size:2.5rem;opacity:.4;"></i>
        <p class="mt-3 mb-0">Tus docentes aún no te han aplicado ninguna rúbrica este año.</p>
    </div>
</div>
@else

@foreach($aplicaciones as $aplic)
@php
    $rubrica   = $aplic->rubrica;
    $niveles   = $rubrica->niveles ?? [];
    $criterios = $rubrica->criterios ?? [];
    $resultados = $aplic->resultados ?? [];
    $pct       = $aplic->porcentaje;
    $barColor  = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
    $badgeBg   = $pct >= 75 ? '#d1fae5' : ($pct >= 50 ? '#fef3c7' : '#fee2e2');
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <div class="fw-bold" style="color:#1e3a6e;">{{ $rubrica->titulo }}</div>
            <small class="text-muted">
                <i class="bi bi-journal me-1"></i>{{ $aplic->asignacion?->asignatura?->nombre ?? '—' }}
                &nbsp;·&nbsp;
                <i class="bi bi-person me-1"></i>{{ $aplic->asignacion?->docente?->nombre_completo ?? '—' }}
                @if($aplic->aplicado_en)
                &nbsp;·&nbsp; {{ $aplic->aplicado_en->format('d/m/Y') }}
                @endif
            </small>
        </div>
        <div style="text-align:right;">
            <div style="font-size:1.4rem;font-weight:900;color:{{ $barColor }};line-height:1;">{{ $pct }}%</div>
            <div style="font-size:.72rem;color:#64748b;">
                {{ number_format($aplic->puntaje, 1) }} / {{ number_format($aplic->puntaje_max, 0) }} pts
            </div>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div style="height:5px;background:#e2e8f0;">
        <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};transition:width .3s;"></div>
    </div>

    <div class="card-body px-4 py-3">
        {{-- Tabla de criterios --}}
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.83rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="width:35%;">Criterio</th>
                        <th style="width:15%;text-align:center;">Pts</th>
                        <th>Nivel obtenido</th>
                        <th style="width:20%;text-align:center;">Puntaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($criterios as $ci => $crit)
                    @php
                        $nivelIdx = $resultados[$ci] ?? null;
                        $nivel    = ($nivelIdx !== null && isset($niveles[$nivelIdx])) ? $niveles[$nivelIdx] : null;
                        $critPts  = $crit['puntos'] ?? 0;
                        $obtenido = $nivel ? round($critPts * ($nivel['pct'] / 100), 1) : null;
                    @endphp
                    <tr>
                        <td class="fw-semibold" style="color:#1e293b;">{{ $crit['nombre'] }}</td>
                        <td style="text-align:center;color:#64748b;">{{ $critPts }}</td>
                        <td>
                            @if($nivel)
                                <span style="background:{{ $nivel['color'] ?? '#94a3b8' }};color:#fff;border-radius:99px;padding:2px 10px;font-size:.75rem;font-weight:700;">
                                    {{ $nivel['nombre'] }}
                                </span>
                                @if(!empty($crit['descriptores'][$nivelIdx]))
                                <div style="font-size:.75rem;color:#64748b;margin-top:2px;">
                                    {{ $crit['descriptores'][$nivelIdx] }}
                                </div>
                                @endif
                            @else
                                <span class="text-muted" style="font-size:.78rem;">Sin evaluar</span>
                            @endif
                        </td>
                        <td style="text-align:center;font-weight:700;color:{{ $obtenido !== null ? $barColor : '#94a3b8' }};">
                            {{ $obtenido !== null ? $obtenido : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($aplic->observaciones)
        <div class="mt-3 p-3" style="background:#f8fafc;border-radius:8px;border-left:3px solid #3b82f6;">
            <div class="fw-semibold mb-1" style="font-size:.78rem;color:#374151;">
                <i class="bi bi-chat-text me-1 text-primary"></i>Observaciones del docente
            </div>
            <p class="mb-0" style="font-size:.83rem;color:#475569;">{{ $aplic->observaciones }}</p>
        </div>
        @endif
    </div>
</div>
@endforeach

@endif
@endsection
