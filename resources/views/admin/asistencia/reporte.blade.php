@extends('layouts.admin')
@section('page-title', 'Reporte de Asistencia')

@push('styles')
<style>
    .table th {
        font-size: .77rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8faff;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
        font-size: .86rem;
    }
    .fila-alerta   { background: #fff1f2 !important; }
    .fila-advertencia { background: #fffbeb !important; }
    .pct-badge {
        display: inline-block;
        padding: .2em .6em;
        border-radius: 12px;
        font-size: .78rem;
        font-weight: 700;
    }
    .pct-ok     { background: #dcfce7; color: #15803d; }
    .pct-warn   { background: #fef3c7; color: #92400e; }
    .pct-danger { background: #fee2e2; color: #991b1b; }
    .stat-cell  { text-align: center; font-weight: 600; }
    .summary-stat {
        padding: .75rem 1.25rem;
        border-radius: 10px;
        text-align: center;
    }

    [data-theme="dark"] .fila-alerta { background: #1c0000 !important; }
    [data-theme="dark"] .fila-advertencia { background: #1c1000 !important; }
    [data-theme="dark"] .pct-ok { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .pct-warn { background: #1c1000; color: #fcd34d; }
    [data-theme="dark"] .pct-danger { background: #1c0000; color: #f87171; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}" class="text-decoration-none">Asistencia</a></li>
        <li class="breadcrumb-item active">Reporte</li>
    </ol>
</nav>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.asistencia.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="bi bi-bar-chart me-2"></i>Reporte de Asistencia
            </h4>
            <div class="text-muted" style="font-size:.82rem;">
                {{ $asignacion->asignatura?->nombre ?? '—' }} — {{ $asignacion->grupo?->nombre_completo ?? '—' }}
                @if($asignacion->docente)
                · {{ $asignacion->docente?->nombre_completo ?? '' }}
                @endif
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.asistencia.reporte.pdf', $asignacion) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.asistencia.reporte.excel', $asignacion) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.asistencia.grilla', $asignacion) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar3-range me-1"></i>Ver Grilla
        </a>
    </div>
</div>

{{-- Summary cards --}}
@php
    $statsArr     = array_values($stats);
    $totalAlumnos = count($statsArr);
    $conRegistros = collect($statsArr)->where('total', '>', 0)->count();
    $alertas      = collect($statsArr)->where('alerta', true)->count();
    $promedioArr  = collect($statsArr)->filter(fn($s) => $s['porcentaje'] !== null)->pluck('porcentaje');
    $promedio     = $promedioArr->count() > 0 ? round($promedioArr->avg(), 1) : null;
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="summary-stat" style="background:#e0e7ff;">
            <div style="font-size:1.6rem;font-weight:800;color:#3730a3;">{{ $totalAlumnos }}</div>
            <div style="font-size:.78rem;color:#4338ca;font-weight:600;">Estudiantes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-stat" style="background:#dcfce7;">
            @if($promedio !== null)
            <div style="font-size:1.6rem;font-weight:800;color:#15803d;">{{ $promedio }}%</div>
            @else
            <div style="font-size:1.6rem;font-weight:800;color:#9ca3af;">—</div>
            @endif
            <div style="font-size:.78rem;color:#16a34a;font-weight:600;">Promedio Asistencia</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-stat" style="background:#fef3c7;">
            <div style="font-size:1.6rem;font-weight:800;color:#92400e;">{{ $conRegistros }}</div>
            <div style="font-size:.78rem;color:#b45309;font-weight:600;">Con Registros</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-stat" style="background:#{{ $alertas > 0 ? 'fee2e2' : 'f0fdf4' }};">
            <div style="font-size:1.6rem;font-weight:800;color:#{{ $alertas > 0 ? '991b1b' : '15803d' }};">{{ $alertas }}</div>
            <div style="font-size:.78rem;color:#{{ $alertas > 0 ? 'dc2626' : '16a34a' }};font-weight:600;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Alertas (&lt;75%)
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-table me-2"></i>Detalle por Estudiante
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:40px;">#</th>
                        <th>Estudiante</th>
                        <th class="stat-cell" style="color:#15803d;">Pres.</th>
                        <th class="stat-cell" style="color:#991b1b;">Aus.</th>
                        <th class="stat-cell" style="color:#92400e;">Tarde</th>
                        <th class="stat-cell" style="color:#1d4ed8;">Excusa</th>
                        <th class="stat-cell" style="color:#6b21a8;">Retiro</th>
                        <th class="stat-cell">Total</th>
                        <th class="stat-cell pe-4">% Asist.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats as $s)
                    @php
                        $rowClass = '';
                        if ($s['porcentaje'] !== null) {
                            if ($s['porcentaje'] < 75) $rowClass = 'fila-alerta';
                            elseif ($s['porcentaje'] < 85) $rowClass = 'fila-advertencia';
                        }
                        $pctClass = 'pct-ok';
                        if ($s['porcentaje'] !== null && $s['porcentaje'] < 75) $pctClass = 'pct-danger';
                        elseif ($s['porcentaje'] !== null && $s['porcentaje'] < 85) $pctClass = 'pct-warn';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="ps-4 text-muted" style="font-size:.78rem;">{{ $loop->iteration }}</td>
                        <td>
                            <span class="fw-semibold">{{ optional($s['matricula']->estudiante)->nombre_completo ?? '—' }}</span>
                            @if($s['alerta'])
                            <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Asistencia crítica"></i>
                            @endif
                        </td>
                        <td class="stat-cell" style="color:#15803d;">{{ $s['presente'] }}</td>
                        <td class="stat-cell" style="color:#991b1b;">{{ $s['ausente'] }}</td>
                        <td class="stat-cell" style="color:#92400e;">{{ $s['tarde'] }}</td>
                        <td class="stat-cell" style="color:#1d4ed8;">{{ $s['excusa'] }}</td>
                        <td class="stat-cell" style="color:#6b21a8;">{{ $s['retiro'] }}</td>
                        <td class="stat-cell text-muted">{{ $s['total'] }}</td>
                        <td class="stat-cell pe-4">
                            @if($s['porcentaje'] !== null)
                            <span class="pct-badge {{ $pctClass }}">{{ $s['porcentaje'] }}%</span>
                            @else
                            <span class="text-muted" style="font-size:.78rem;">Sin datos</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-people" style="font-size:3rem;opacity:.3;"></i>
                            <p class="mt-3 mb-0">No hay estudiantes matriculados activos.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Legend --}}
<div class="mt-3 d-flex flex-wrap gap-3 align-items-center" style="font-size:.78rem;color:#64748b;">
    <span><i class="bi bi-square-fill" style="color:#dcfce7;"></i> Verde: ≥85% asistencia</span>
    <span><i class="bi bi-square-fill" style="color:#fef3c7;"></i> Amarillo: 75–84%</span>
    <span><i class="bi bi-square-fill" style="color:#fee2e2;"></i> Rojo: &lt;75% (alerta)</span>
    <span class="ms-auto text-muted">Pres. + Tarde se cuentan como asistencia efectiva.</span>
</div>

@endsection
