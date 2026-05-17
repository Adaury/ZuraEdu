@extends('layouts.admin')
@section('page-title', 'Rúbrica: ' . $rubrica->titulo)

@section('content')
<div class="container-fluid px-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0">{{ $rubrica->titulo }}</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.rubricas.index') }}">Rúbricas</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($rubrica->titulo, 40) }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.rubricas.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    {{-- Info + KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2" style="color:#1e3a6e;">Información de la Rúbrica</div>
                    <table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">
                        <tr>
                            <td class="text-muted" style="width:110px;">Docente</td>
                            <td class="fw-semibold">{{ $rubrica->docente?->nombre_completo ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Asignatura</td>
                            <td>{{ $rubrica->asignatura?->nombre ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Criterios</td>
                            <td>{{ count($criterios) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pts. máximos</td>
                            <td>{{ collect($criterios)->sum('puntos') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Niveles</td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @foreach($niveles as $niv)
                                    <span style="background:{{ $niv['color'] ?? '#94a3b8' }};color:#fff;border-radius:99px;font-size:.68rem;padding:2px 8px;font-weight:700;">
                                        {{ $niv['nombre'] }} ({{ $niv['pct'] }}%)
                                    </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @if($rubrica->descripcion)
                        <tr>
                            <td class="text-muted">Descripción</td>
                            <td style="color:#475569;">{{ $rubrica->descripcion }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="row g-3 h-100">
                <div class="col-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center py-3">
                            <div style="font-size:2rem;font-weight:900;color:#1e40af;line-height:1.1;">{{ $totalAplicadas }}</div>
                            <div class="text-muted" style="font-size:.75rem;">Evaluados</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center py-3">
                            @php $pctColor = $promedioGlobal >= 75 ? '#10b981' : ($promedioGlobal >= 50 ? '#f59e0b' : '#ef4444'); @endphp
                            <div style="font-size:2rem;font-weight:900;color:{{ $pctColor }};line-height:1.1;">
                                {{ $promedioGlobal }}%
                            </div>
                            <div class="text-muted" style="font-size:.75rem;">Promedio global</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center py-3">
                            @php $pct60 = $totalAplicadas ? round($sobre60 / $totalAplicadas * 100) : 0; @endphp
                            <div style="font-size:2rem;font-weight:900;color:#059669;line-height:1.1;">{{ $pct60 }}%</div>
                            <div class="text-muted" style="font-size:.75rem;">≥ 60% (aprobados)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($totalAplicadas === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-grid-3x3-gap" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
            <p class="mb-0">Esta rúbrica aún no ha sido aplicada a ningún estudiante.</p>
        </div>
    </div>
    @else

    {{-- Distribución por criterio --}}
    @if(count($criterios) > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold" style="font-size:.85rem;color:#1e3a6e;border-bottom:1px solid #e5e7eb;">
            <i class="bi bi-bar-chart-steps me-1"></i>Distribución por Criterio
        </div>
        <div class="card-body p-3">
            @foreach($criterios as $ci => $crit)
            @php
                $dist     = $distribucion[$ci] ?? [];
                $total    = array_sum($dist);
                $critPts  = $crit['puntos'] ?? 0;
            @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-semibold" style="font-size:.82rem;color:#1e293b;">{{ $crit['nombre'] }}</span>
                    <span class="text-muted" style="font-size:.75rem;">{{ $critPts }} pts</span>
                </div>
                <div class="d-flex rounded overflow-hidden" style="height:22px;">
                    @foreach($niveles as $ni => $niv)
                    @php
                        $count = $dist[$ni] ?? 0;
                        $pctBar = $total > 0 ? round($count / $total * 100) : 0;
                    @endphp
                    @if($pctBar > 0)
                    <div style="width:{{ $pctBar }}%;background:{{ $niv['color'] ?? '#94a3b8' }};display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff;min-width:{{ $pctBar < 8 ? '22px' : '0' }};"
                         title="{{ $niv['nombre'] }}: {{ $count }} ({{ $pctBar }}%)">
                        {{ $pctBar >= 8 ? $count : '' }}
                    </div>
                    @endif
                    @endforeach
                    @if($total === 0)
                    <div style="width:100%;background:#e2e8f0;height:22px;"></div>
                    @endif
                </div>
                <div class="d-flex gap-3 mt-1 flex-wrap">
                    @foreach($niveles as $ni => $niv)
                    @php $count = $dist[$ni] ?? 0; @endphp
                    <span style="font-size:.7rem;color:#64748b;">
                        <span style="display:inline-block;width:8px;height:8px;background:{{ $niv['color'] ?? '#94a3b8' }};border-radius:50%;margin-right:3px;"></span>
                        {{ $niv['nombre'] }}: {{ $count }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Resultados por asignación --}}
    @foreach($porAsignacion as $asigId => $aplics)
    @php $primeraAplic = $aplics->first(); @endphp
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-journals text-white" style="font-size:.9rem;"></i>
            </div>
            <div>
                <span class="fw-bold" style="color:#1e3a6e;">
                    {{ $primeraAplic?->asignacion?->asignatura?->nombre ?? '—' }}
                </span>
                <span class="text-muted ms-2" style="font-size:.82rem;">
                    {{ $primeraAplic?->asignacion?->grupo?->nombre_completo ?? '' }}
                </span>
            </div>
            <span class="badge bg-secondary ms-auto">{{ $aplics->count() }} evaluados</span>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle" style="font-size:.82rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th>Estudiante</th>
                            @foreach($criterios as $ci => $crit)
                            <th style="text-align:center;min-width:80px;">
                                {{ Str::limit($crit['nombre'], 18) }}
                            </th>
                            @endforeach
                            <th style="text-align:center;width:80px;">Puntaje</th>
                            <th style="text-align:center;width:70px;">%</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($aplics as $aplic)
                    @php
                        $pct      = $aplic->porcentaje;
                        $barColor = $pct >= 75 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                    @endphp
                    <tr>
                        <td class="fw-semibold" style="color:#1e293b;">
                            {{ $aplic->matricula?->estudiante?->nombre_completo ?? '—' }}
                            @if($aplic->aplicado_en)
                            <div class="text-muted" style="font-weight:400;font-size:.72rem;">
                                {{ $aplic->aplicado_en->format('d/m/Y') }}
                            </div>
                            @endif
                        </td>
                        @foreach($criterios as $ci => $crit)
                        @php
                            $nivelIdx = $aplic->resultados[$ci] ?? null;
                            $nivel    = ($nivelIdx !== null && isset($niveles[$nivelIdx])) ? $niveles[$nivelIdx] : null;
                        @endphp
                        <td style="text-align:center;">
                            @if($nivel)
                            <span style="background:{{ $nivel['color'] ?? '#94a3b8' }};color:#fff;border-radius:99px;font-size:.7rem;padding:2px 8px;font-weight:700;">
                                {{ $nivel['nombre'] }}
                            </span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endforeach
                        <td style="text-align:center;font-weight:700;color:{{ $barColor }};">
                            {{ number_format($aplic->puntaje, 1) }} / {{ number_format($aplic->puntaje_max, 0) }}
                        </td>
                        <td style="text-align:center;">
                            <div style="font-size:.8rem;font-weight:700;color:{{ $barColor }};">{{ $pct }}%</div>
                            <div style="height:4px;background:#e2e8f0;border-radius:99px;margin-top:2px;">
                                <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:99px;"></div>
                            </div>
                        </td>
                        <td style="font-size:.77rem;color:#475569;max-width:180px;">
                            {{ $aplic->observaciones ? Str::limit($aplic->observaciones, 60) : '' }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    @endif

</div>
@endsection
