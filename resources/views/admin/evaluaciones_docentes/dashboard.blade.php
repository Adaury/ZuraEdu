@extends('layouts.admin')
@section('page-title', 'Dashboard Evaluaciones Docentes')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }

    /* KPI cards */
    .kpi-card { border-radius:14px; border:none; padding:1.5rem 1.25rem; text-align:center; }
    .kpi-num  { font-size:2.4rem; font-weight:900; line-height:1; }
    .kpi-lbl  { font-size:.75rem; color:#6b7280; margin-top:.35rem; text-transform:uppercase; letter-spacing:.05em; }

    /* Nivel badges */
    .badge-excelente  { background:#dcfce7; color:#166534; }
    .badge-bueno      { background:#dbeafe; color:#1e40af; }
    .badge-regular    { background:#fef9c3; color:#854d0e; }
    .badge-deficiente { background:#fee2e2; color:#991b1b; }

    /* Barra nivel distribución */
    .nivel-bar-wrap { height:12px; border-radius:6px; background:#e2e8f0; overflow:hidden; }
    .nivel-bar-fill { height:12px; border-radius:6px; transition:width .7s ease; }

    /* Ranking */
    .rank-row { display:flex; align-items:center; gap:.75rem; padding:.75rem 0; border-bottom:1px solid #f1f5f9; }
    .rank-row:last-child { border-bottom:none; }
    .rank-num  { min-width:2rem; font-size:1rem; font-weight:900; color:#9ca3af; text-align:center; }
    .rank-num.top1 { color:#f59e0b; }
    .rank-num.top2 { color:#94a3b8; }
    .rank-num.top3 { color:#b45309; }
    .rank-name { flex:1; font-size:.88rem; font-weight:600; color:#1e293b; }
    .rank-sub  { font-size:.75rem; color:#9ca3af; }
    .rank-bar-wrap { width:110px; height:8px; border-radius:4px; background:#e2e8f0; }
    .rank-bar-fill { height:8px; border-radius:4px; }
    .rank-score { min-width:3.5rem; text-align:right; font-size:1rem; font-weight:800; }

    [data-theme="dark"] .rank-row { border-color:#1e293b; }
    [data-theme="dark"] .nivel-bar-wrap { background:#334155; }
    [data-theme="dark"] .rank-bar-wrap  { background:#334155; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header">
    <div>
        <h1><i class="bi bi-bar-chart-line me-2" style="color:var(--secondary);"></i>Dashboard Desempeño Docente</h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">Resumen institucional de evaluaciones</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.evaluaciones-docentes.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-list-ul me-1"></i>Ver Evaluaciones
        </a>
        <a href="{{ route('admin.evaluaciones-docentes.create') }}" class="btn btn-sm px-3 py-2 fw-600"
           style="background:var(--primary);color:#fff;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Evaluación
        </a>
    </div>
</div>

{{-- KPIs principales --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card kpi-card shadow-sm" style="background:#eff6ff;">
            <div class="kpi-num" style="color:#1d4ed8;">{{ $total }}</div>
            <div class="kpi-lbl">Total Evaluaciones</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card shadow-sm" style="background:#f0fdf4;">
            <div class="kpi-num" style="color:#15803d;">
                {{ $promedioInstitucional !== null ? number_format($promedioInstitucional, 2) : '—' }}
            </div>
            <div class="kpi-lbl">Promedio Institucional</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card shadow-sm" style="background:#dcfce7;">
            <div class="kpi-num" style="color:#166534;">{{ $niveles['Excelente'] }}%</div>
            <div class="kpi-lbl">Nivel Excelente</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card shadow-sm" style="background:#fef3c7;">
            <div class="kpi-num" style="color:#d97706;">{{ $ranking->count() }}</div>
            <div class="kpi-lbl">Docentes Evaluados</div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Distribución por Nivel --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-header border-0 pt-4 pb-0 px-4">
                <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                    <i class="bi bi-pie-chart me-2"></i>Distribución por Nivel
                </h6>
                <p class="text-muted mb-0 mt-1" style="font-size:.78rem;">
                    Porcentaje de evaluaciones por nivel de desempeño
                </p>
            </div>
            <div class="card-body px-4 pb-4 pt-3">

                @if($total === 0)
                    <div class="text-center py-4" style="color:#9ca3af;">
                        <i class="bi bi-bar-chart" style="font-size:2.5rem;opacity:.4;display:block;"></i>
                        <p class="mt-2 mb-0" style="font-size:.85rem;">Sin datos aún</p>
                    </div>
                @else
                @php
                    $nivelesConfig = [
                        'Excelente'  => ['color'=>'#22c55e', 'bg'=>'#dcfce7', 'txt'=>'#166534'],
                        'Bueno'      => ['color'=>'#3b82f6', 'bg'=>'#dbeafe', 'txt'=>'#1e40af'],
                        'Regular'    => ['color'=>'#f59e0b', 'bg'=>'#fef9c3', 'txt'=>'#854d0e'],
                        'Deficiente' => ['color'=>'#ef4444', 'bg'=>'#fee2e2', 'txt'=>'#991b1b'],
                    ];
                @endphp

                @foreach($nivelesConfig as $nombre => $cfg)
                @php $pct = $niveles[$nombre] ?? 0; @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-size:.82rem;font-weight:600;
                                     background:{{ $cfg['bg'] }};color:{{ $cfg['txt'] }};
                                     padding:.18rem .6rem;border-radius:20px;">{{ $nombre }}</span>
                        <span style="font-size:.88rem;font-weight:700;color:{{ $cfg['txt'] }};">{{ $pct }}%</span>
                    </div>
                    <div class="nivel-bar-wrap">
                        <div class="nivel-bar-fill" style="width:{{ $pct }}%;background:{{ $cfg['color'] }};"></div>
                    </div>
                </div>
                @endforeach

                {{-- Promedio institucional grande --}}
                @if($promedioInstitucional !== null)
                <div class="mt-4 p-3 text-center" style="background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:12px;">
                    <div style="font-size:.72rem;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.06em;">Promedio Institucional</div>
                    <div style="font-size:2.8rem;font-weight:900;color:#fff;line-height:1.1;">{{ number_format($promedioInstitucional, 2) }}</div>
                    @php
                        $nivelInst = $promedioInstitucional >= 4.5 ? 'Excelente' : ($promedioInstitucional >= 3.5 ? 'Bueno' : ($promedioInstitucional >= 2.5 ? 'Regular' : 'Deficiente'));
                    @endphp
                    <div style="display:inline-block;background:rgba(255,255,255,.2);border-radius:20px;padding:.2rem .9rem;font-size:.82rem;font-weight:700;color:#fff;margin-top:.4rem;">
                        {{ $nivelInst }}
                    </div>
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Ranking de Docentes --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-header border-0 pt-4 pb-0 px-4 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
                        <i class="bi bi-trophy me-2"></i>Ranking de Docentes
                    </h6>
                    <p class="text-muted mb-0 mt-1" style="font-size:.78rem;">Ordenado por promedio de evaluaciones</p>
                </div>
                @if($ranking->count() > 0)
                <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:.75rem;">
                    {{ $ranking->count() }} docentes
                </span>
                @endif
            </div>
            <div class="card-body px-4 pb-4 pt-3">

                @if($ranking->isEmpty())
                    <div class="text-center py-5" style="color:#9ca3af;">
                        <i class="bi bi-person-x" style="font-size:2.5rem;opacity:.4;display:block;"></i>
                        <p class="mt-2 mb-0" style="font-size:.85rem;">No hay docentes evaluados aún.</p>
                    </div>
                @else

                @php
                    $maxProm = $ranking->first()->promedio_general ?? 5;
                    $barColores = [
                        'Excelente'  => '#22c55e',
                        'Bueno'      => '#3b82f6',
                        'Regular'    => '#f59e0b',
                        'Deficiente' => '#ef4444',
                    ];
                @endphp

                @foreach($ranking->take(10) as $i => $doc)
                @php
                    $prom  = $doc->promedio_general;
                    $nivel = $prom >= 4.5 ? 'Excelente' : ($prom >= 3.5 ? 'Bueno' : ($prom >= 2.5 ? 'Regular' : 'Deficiente'));
                    $bColor= $barColores[$nivel];
                    $barW  = $maxProm > 0 ? round(($prom / 5) * 100) : 0;
                    $rankClass = $i === 0 ? 'top1' : ($i === 1 ? 'top2' : ($i === 2 ? 'top3' : ''));
                @endphp
                <div class="rank-row">
                    <div class="rank-num {{ $rankClass }}">
                        @if($i === 0) <i class="bi bi-trophy-fill"></i>
                        @elseif($i === 1) <i class="bi bi-award-fill"></i>
                        @elseif($i === 2) <i class="bi bi-award"></i>
                        @else {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="rank-name">
                        {{ $doc->nombre_completo }}
                        <div class="rank-sub">{{ $doc->especialidad ?? '' }} · {{ $doc->total_evaluaciones }} eval.</div>
                    </div>
                    <div class="rank-bar-wrap">
                        <div class="rank-bar-fill" style="width:{{ $barW }}%;background:{{ $bColor }};"></div>
                    </div>
                    <div class="rank-score" style="color:{{ $bColor }};">{{ number_format($prom, 2) }}</div>
                    <div>
                        <span class="badge-nivel badge-{{ strtolower($nivel) }}"
                              style="font-size:.7rem;font-weight:700;padding:.2rem .55rem;border-radius:20px;
                                     background:{{ $nivelesConfig[$nivel]['bg'] ?? '#f3f4f6' }};
                                     color:{{ $nivelesConfig[$nivel]['txt'] ?? '#374151' }};">
                            {{ $nivel }}
                        </span>
                    </div>
                </div>
                @endforeach

                @if($ranking->count() > 10)
                <div class="text-center mt-3">
                    <a href="{{ route('admin.evaluaciones-docentes.index') }}" style="font-size:.82rem;color:var(--primary);">
                        Ver todos ({{ $ranking->count() }} docentes)
                    </a>
                </div>
                @endif

                @endif
            </div>
        </div>
    </div>

</div>

{{-- Tabla resumen completa --}}
@if($ranking->count() > 0)
<div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden;">
    <div class="card-header border-0 pt-4 pb-0 px-4">
        <h6 class="fw-700 mb-0" style="color:var(--primary);font-size:.95rem;">
            <i class="bi bi-table me-2"></i>Detalle por Docente
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
            <thead style="background:#f8faff;border-bottom:2px solid #e5e7eb;">
                <tr>
                    <th class="ps-4 py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">#</th>
                    <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">Docente</th>
                    <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">Evaluaciones</th>
                    <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">Promedio</th>
                    <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">Nivel</th>
                    <th class="py-3 pe-4 text-end" style="color:#6b7280;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ranking as $i => $doc)
                @php
                    $prom  = $doc->promedio_general;
                    $nivel = $prom >= 4.5 ? 'Excelente' : ($prom >= 3.5 ? 'Bueno' : ($prom >= 2.5 ? 'Regular' : 'Deficiente'));
                    $nCfg  = $nivelesConfig[$nivel];
                @endphp
                <tr>
                    <td class="ps-4 py-3" style="color:#9ca3af;font-weight:700;">{{ $i + 1 }}</td>
                    <td class="py-3">
                        <div class="fw-600" style="color:#111827;">{{ $doc->nombre_completo }}</div>
                        <div style="font-size:.75rem;color:#9ca3af;">{{ $doc->especialidad ?? '' }}</div>
                    </td>
                    <td class="py-3 text-center" style="font-weight:700;color:#374151;">{{ $doc->total_evaluaciones }}</td>
                    <td class="py-3 text-center">
                        <span style="font-size:1.1rem;font-weight:900;color:{{ $nCfg['txt'] }};">{{ number_format($prom, 2) }}</span>
                    </td>
                    <td class="py-3 text-center">
                        <span style="font-size:.78rem;font-weight:700;padding:.25rem .65rem;border-radius:20px;
                                     background:{{ $nCfg['bg'] }};color:{{ $nCfg['txt'] }};">
                            {{ $nivel }}
                        </span>
                    </td>
                    <td class="py-3 pe-4 text-end">
                        <a href="{{ route('admin.evaluaciones-docentes.index', ['docente_id' => $doc->id]) }}"
                           class="btn btn-sm btn-outline-primary" style="border-radius:6px;font-size:.78rem;"
                           title="Ver evaluaciones">
                            <i class="bi bi-eye me-1"></i>Ver
                        </a>
                        <a href="{{ route('admin.evaluaciones-docentes.create', ['docente_id' => $doc->id]) }}"
                           class="btn btn-sm btn-outline-success ms-1" style="border-radius:6px;font-size:.78rem;"
                           title="Nueva evaluación">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
