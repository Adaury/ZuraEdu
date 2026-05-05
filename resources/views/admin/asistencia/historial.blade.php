@extends('layouts.admin')
@section('page-title', 'Historial de Asistencia')

@push('styles')
<style>
    .historial-wrapper {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,.07);
    }
    #tabla-historial {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 600px;
        font-size: .81rem;
    }
    #tabla-historial th,
    #tabla-historial td {
        border-bottom: 1px solid #e5e7eb;
        border-right: 1px solid #e5e7eb;
        vertical-align: middle;
        white-space: nowrap;
    }
    #tabla-historial thead th {
        background: var(--primary);
        color: #fff;
        font-weight: 600;
        padding: .55rem .5rem;
        text-align: center;
    }
    /* Sticky student column */
    #tabla-historial th:nth-child(1),
    #tabla-historial td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #fff;
        min-width: 190px;
    }
    #tabla-historial thead th:nth-child(1) {
        background: #0f1f3d;
        z-index: 5;
        text-align: left;
        padding-left: .85rem;
    }
    .td-nombre {
        padding: .45rem .85rem;
        font-weight: 600;
        color: #1e293b;
        font-size: .85rem;
    }
    /* Estado pills */
    .pill-p { background:#dcfce7; color:#15803d; }
    .pill-a { background:#fee2e2; color:#991b1b; }
    .pill-t { background:#fef3c7; color:#92400e; }
    .pill-j { background:#dbeafe; color:#1d4ed8; }
    .pill-vacio { background:#f3f4f6; color:#9ca3af; }

    .estado-cell {
        width: 46px;
        text-align: center;
        padding: .3rem .2rem;
    }
    .estado-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px; height: 26px;
        border-radius: 50%;
        font-size: .72rem;
        font-weight: 800;
    }

    /* PCT column */
    .pct-cell {
        min-width: 80px;
        text-align: center;
        padding: .4rem .6rem;
        font-weight: 800;
        font-size: .86rem;
    }
    .pct-ok   { color: #15803d; }
    .pct-warn { color: #92400e; }
    .pct-bad  { color: #991b1b; }

    /* Summary row */
    #fila-totales td {
        background: #f8faff;
        font-weight: 700;
        font-size: .8rem;
        padding: .4rem .2rem;
        text-align: center;
        border-top: 2px solid #c7d6f0;
    }
    #fila-totales td:nth-child(1) {
        text-align: left;
        padding-left: .85rem;
        color: var(--primary);
    }

    .date-header { font-size: .7rem; color: rgba(255,255,255,.8); }
    .date-day    { font-size: .85rem; font-weight: 700; }

    [data-theme="dark"] #tabla-historial th:nth-child(1),
    [data-theme="dark"] #tabla-historial td:nth-child(1) { background: #1e293b; }
    [data-theme="dark"] #tabla-historial th,
    [data-theme="dark"] #tabla-historial td { border-color: #334155; }
    [data-theme="dark"] #fila-totales td { background: #162032; border-top-color: #334155; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}" class="text-decoration-none">Asistencia</a></li>
        <li class="breadcrumb-item active">Historial</li>
    </ol>
</nav>

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3" style="background:linear-gradient(135deg,var(--primary),#2a4f96);">
    <div class="card-body py-3 px-4 text-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="fw-bold mb-1">{{ optional($asignacion->asignatura)->nombre }} — Historial de Asistencia</h5>
                <div class="d-flex flex-wrap gap-3" style="font-size:.82rem;opacity:.88;">
                    <span><i class="bi bi-people me-1"></i>{{ optional($asignacion->grupo)->nombre_completo }}</span>
                    <span><i class="bi bi-person-badge me-1"></i>{{ optional($asignacion->docente)->nombre_completo ?? 'Sin docente' }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $fechas->count() }} sesiones registradas</span>
                    <span><i class="bi bi-people-fill me-1"></i>{{ $matriculas->count() }} estudiantes</span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end">
                <a href="{{ route('admin.asistencia.historial.pdf', $asignacion) }}" target="_blank"
                   class="btn btn-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </a>
                <a href="{{ route('admin.asistencia.historial.excel', $asignacion) }}"
                   class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                </a>
                <a href="{{ route('admin.asistencia.registrar', $asignacion->id) }}"
                   class="btn btn-light fw-bold px-4">
                    <i class="bi bi-clipboard-check me-2"></i>Registrar Hoy
                </a>
            </div>
        </div>
    </div>
</div>

@if($fechas->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-3">No hay registros de asistencia para esta asignación.</p>
    <a href="{{ route('admin.asistencia.registrar', $asignacion->id) }}" class="btn btn-primary">
        <i class="bi bi-clipboard-check me-2"></i>Registrar Primer Día
    </a>
</div>
@else

{{-- Legend --}}
<div class="d-flex gap-2 mb-3 flex-wrap" style="font-size:.78rem;">
    <span class="badge estado-badge pill-p px-3 py-1">P</span><span class="text-muted me-2">Presente</span>
    <span class="badge estado-badge pill-a px-3 py-1">A</span><span class="text-muted me-2">Ausente</span>
    <span class="badge estado-badge pill-t px-3 py-1">T</span><span class="text-muted me-2">Tardanza</span>
    <span class="badge estado-badge pill-j px-3 py-1">J</span><span class="text-muted me-2">Justificado</span>
    <span class="text-danger fw-bold ms-3"><i class="bi bi-exclamation-triangle me-1"></i>% &lt; 80% = baja asistencia</span>
</div>

<div class="historial-wrapper">
    <table id="tabla-historial">
        <thead>
            <tr>
                <th>Estudiante</th>
                @foreach($fechas as $fecha)
                @php $f = \Carbon\Carbon::parse($fecha); @endphp
                <th class="estado-cell">
                    <div class="date-day">{{ $f->format('d') }}</div>
                    <div class="date-header">{{ $f->locale('es')->isoFormat('ddd') }}</div>
                </th>
                @endforeach
                <th style="min-width:80px;">% Asist.</th>
                <th style="min-width:60px;">Ausent.</th>
                <th style="min-width:60px;">Tardanz.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $m)
            <tr>
                <td class="td-nombre">{{ $m->estudiante?->nombre_completo ?? '—' }}</td>
                @foreach($fechas as $fecha)
                @php
                    $estado = $matriz[$m->id][$fecha->format('Y-m-d')] ?? null;
                    $letra  = match($estado) {
                        'presente'    => 'P',
                        'ausente'     => 'A',
                        'tardanza'    => 'T',
                        'justificado' => 'J',
                        default       => '·',
                    };
                    $cls = match($estado) {
                        'presente'    => 'pill-p',
                        'ausente'     => 'pill-a',
                        'tardanza'    => 'pill-t',
                        'justificado' => 'pill-j',
                        default       => 'pill-vacio',
                    };
                @endphp
                <td class="estado-cell">
                    <span class="estado-badge {{ $cls }}">{{ $letra }}</span>
                </td>
                @endforeach
                {{-- % asistencia --}}
                @php
                    $st  = $stats[$m->id];
                    $pct = $st['pct_asistencia'];
                    $pctCls = $pct === null ? '' : ($pct >= 90 ? 'pct-ok' : ($pct >= 80 ? 'pct-warn' : 'pct-bad'));
                @endphp
                <td class="pct-cell {{ $pctCls }}">
                    {{ $pct !== null ? $pct.'%' : '—' }}
                    @if($pct !== null && $pct < 80)
                        <i class="bi bi-exclamation-triangle-fill ms-1" title="Baja asistencia"></i>
                    @endif
                </td>
                <td class="text-center" style="padding:.4rem .5rem;color:#991b1b;font-weight:700;">
                    {{ $st['ausente'] }}
                </td>
                <td class="text-center" style="padding:.4rem .5rem;color:#92400e;font-weight:700;">
                    {{ $st['tardanza'] }}
                </td>
            </tr>
            @endforeach

            {{-- Totals row --}}
            <tr id="fila-totales">
                <td><i class="bi bi-bar-chart-fill me-1"></i>Totales por fecha</td>
                @foreach($fechas as $fecha)
                @php
                    $fechaStr = $fecha->format('Y-m-d');
                    $presentes = collect($matriz)->filter(fn($dias) => ($dias[$fechaStr] ?? null) === 'presente')->count();
                    $ausentes  = collect($matriz)->filter(fn($dias) => ($dias[$fechaStr] ?? null) === 'ausente')->count();
                    $total     = collect($matriz)->filter(fn($dias) => isset($dias[$fechaStr]))->count();
                @endphp
                <td title="{{ $presentes }}/{{ $total }} presentes" style="font-size:.72rem;">
                    @if($total > 0)
                    <span style="color:#15803d;">{{ $presentes }}</span>
                    @if($ausentes > 0)<span style="color:#991b1b;">/{{ $ausentes }}</span>@endif
                    @else
                    <span style="color:#9ca3af;">—</span>
                    @endif
                </td>
                @endforeach
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

<div class="mt-3">
    <a href="{{ route('admin.asistencia.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
</div>

@endsection
