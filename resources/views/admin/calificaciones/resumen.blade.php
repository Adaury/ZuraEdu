@extends('layouts.admin')
@section('page-title', 'Resumen de Calificaciones')

@push('styles')
<style>
    .resumen-wrapper {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
    }
    #tabla-resumen {
        border-collapse: separate;
        border-spacing: 0;
        font-size: .8rem;
        width: 100%;
    }
    #tabla-resumen th, #tabla-resumen td {
        border-bottom: 1px solid #e5e7eb;
        border-right: 1px solid #e5e7eb;
        padding: .45rem .5rem;
        white-space: nowrap;
        vertical-align: middle;
    }
    #tabla-resumen thead th {
        background: var(--primary);
        color: #fff;
        text-align: center;
        font-size: .76rem;
        font-weight: 600;
        padding: .6rem .5rem;
        border-color: rgba(255,255,255,.15);
    }
    .th-asig {
        background: var(--primary-dark, #0f1f3d) !important;
        font-size: .75rem !important;
    }
    .th-periodo {
        background: var(--primary-light, #2a4f96) !important;
        font-size: .72rem !important;
        font-weight: 500 !important;
        opacity: .9;
    }
    /* Sticky student column */
    #tabla-resumen th:nth-child(1),
    #tabla-resumen td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 4;
        background: #fff;
        min-width: 200px;
        font-weight: 600;
        color: #1e293b;
    }
    #tabla-resumen thead th:nth-child(1) {
        background: var(--primary-dark, #0f1f3d) !important;
        z-index: 6;
        text-align: left;
        padding-left: .75rem;
    }
    #tabla-resumen tr:nth-child(even) td:nth-child(1) { background: #f9fafb; }
    #tabla-resumen tr:hover td { background: #f0f4ff !important; }
    #tabla-resumen tr:hover td:nth-child(1) { background: #e8eefe !important; }

    /* Cell color classes */
    .nota-ex  { background: #dcfce7; color: #15803d; font-weight: 700; text-align: center; }
    .nota-bu  { background: #dbeafe; color: #1d4ed8; font-weight: 700; text-align: center; }
    .nota-pr  { background: #fef3c7; color: #92400e; font-weight: 700; text-align: center; }
    .nota-in  { background: #fee2e2; color: #991b1b; font-weight: 700; text-align: center; }
    .nota-nu  { color: #9ca3af; text-align: center; }

    .prom-asig { background: #f0f4ff; font-weight: 800; text-align: center; border-left: 2px solid #c7d6f0; }
    .prom-gral { background: #1e3a6e0f; font-weight: 900; text-align: center; font-size: .88rem; border-left: 3px solid var(--primary); }
    .rank-cell { text-align: center; font-weight: 800; }

    .boletin-btn {
        font-size: .72rem;
        padding: .2rem .6rem;
        border-radius: 20px;
        white-space: nowrap;
    }

    [data-theme="dark"] #tabla-resumen th:nth-child(1),
    [data-theme="dark"] #tabla-resumen td:nth-child(1) { background: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] #tabla-resumen tr:nth-child(even) td:nth-child(1) { background: #162032; }
    [data-theme="dark"] #tabla-resumen tr:hover td { background: #1a2640 !important; }
    [data-theme="dark"] #tabla-resumen tr:hover td:nth-child(1) { background: #1e3a5f !important; }
    [data-theme="dark"] #tabla-resumen th, [data-theme="dark"] #tabla-resumen td { border-color: #334155; }
    [data-theme="dark"] .nota-ex { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .nota-bu { background: #0c1f3f; color: #93c5fd; }
    [data-theme="dark"] .nota-pr { background: #1c1000; color: #fcd34d; }
    [data-theme="dark"] .nota-in { background: #1c0000; color: #f87171; }
    [data-theme="dark"] .prom-asig { background: #162032; border-left-color: #334155; }
    [data-theme="dark"] .prom-gral { background: #0c1f3f; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.calificaciones.index') }}" class="text-decoration-none">Calificaciones</a></li>
        <li class="breadcrumb-item active">Resumen</li>
    </ol>
</nav>

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-light,#2a4f96));">
    <div class="card-body py-3 px-4 text-white">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-grid-3x3 me-2"></i>Resumen Multi-Período
                </h5>
                <div class="d-flex flex-wrap gap-3" style="font-size:.83rem;opacity:.88;">
                    <span><i class="bi bi-people me-1"></i>{{ $grupo ? $grupo->nombre_completo : 'Sin grupo' }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $schoolYear->nombre }}</span>
                    <span><i class="bi bi-journal-check me-1"></i>{{ $asignaciones->count() }} asignaturas</span>
                    <span><i class="bi bi-people-fill me-1"></i>{{ $matriculas->count() }} estudiantes</span>
                </div>
            </div>
            @if($grupo)
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('admin.calificaciones.resumen.pdf', ['grupo_id' => $grupo->id]) }}"
                   target="_blank" class="btn btn-danger btn-sm fw-bold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                </a>
                <a href="{{ route('admin.calificaciones.resumen.excel', ['grupo_id' => $grupo->id]) }}"
                   class="btn btn-success btn-sm fw-bold">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                </a>
                <a href="{{ route('admin.calificaciones.progreso.pdf', ['grupo_id' => $grupo->id]) }}"
                   target="_blank" class="btn btn-danger btn-sm fw-bold">
                    <i class="bi bi-bar-chart-line me-1"></i>Progreso PDF
                </a>
                <a href="{{ route('admin.calificaciones.progreso.excel', ['grupo_id' => $grupo->id]) }}"
                   class="btn btn-success btn-sm fw-bold">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i>Progreso Excel
                </a>
                <a href="{{ route('admin.calificaciones.ranking', ['grupo_id' => $grupo->id]) }}"
                   class="btn btn-light btn-sm fw-bold">
                    <i class="bi bi-trophy me-1"></i>Ver Ranking
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

@if(!$grupo)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Selecciona un grupo para ver el resumen. Usa los filtros en la página de <a href="{{ route('admin.calificaciones.index') }}">Calificaciones</a>.
</div>
@elseif($matriculas->isEmpty())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>Este grupo no tiene estudiantes matriculados activos.
</div>
@elseif($asignaciones->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Este grupo no tiene asignaciones registradas.
</div>
@else

{{-- Summary table --}}
<div class="resumen-wrapper">
    <table id="tabla-resumen">
        <thead>
            {{-- Row 1: subject names spanning periods --}}
            <tr>
                <th class="th-asig" rowspan="2">Estudiante</th>
                @foreach($asignaciones as $asig)
                <th class="th-asig" colspan="{{ $periodos->count() + 1 }}">
                    {{ $asig->asignatura->nombre }}
                </th>
                @endforeach
                <th class="th-asig" rowspan="2" style="min-width:80px;">Prom.<br>General</th>
                <th class="th-asig" rowspan="2" style="min-width:50px;">Pos.</th>
                <th class="th-asig" rowspan="2" style="min-width:90px;">Boletín</th>
            </tr>
            {{-- Row 2: period headers per subject --}}
            <tr>
                @foreach($asignaciones as $asig)
                    @foreach($periodos as $per)
                    <th class="th-periodo">P{{ $per->numero }}</th>
                    @endforeach
                    <th class="th-periodo" style="border-left:2px solid rgba(255,255,255,.3);">Prom</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                // Helper: extract nota_final from matrix value (may be Calificacion object or scalar)
                function getNotaFinal($val) {
                    if ($val === null) return null;
                    if (is_object($val)) return $val->nota_final ?? null;
                    return is_numeric($val) ? (float)$val : null;
                }

                // Pre-compute student averages for ranking
                $estudiantesData = [];
                foreach($matriculas as $m) {
                    $todasNotas = [];
                    foreach($asignaciones as $asig) {
                        foreach($periodos as $per) {
                            $raw2 = $matrix[$m->id][$asig->id][$per->id] ?? null;
                            $nota2 = getNotaFinal($raw2);
                            if ($nota2 !== null) $todasNotas[] = $nota2;
                        }
                    }
                    $promGral = count($todasNotas) > 0
                        ? round(array_sum($todasNotas) / count($todasNotas), 2)
                        : null;
                    $estudiantesData[$m->id] = ['prom' => $promGral, 'matricula' => $m];
                }
                // Sort by promedio descending for ranking
                uasort($estudiantesData, function($a, $b) {
                    if ($a['prom'] === null && $b['prom'] === null) return 0;
                    if ($a['prom'] === null) return 1;
                    if ($b['prom'] === null) return -1;
                    return $b['prom'] <=> $a['prom'];
                });
                $posiciones = [];
                $pos = 1;
                foreach($estudiantesData as $mid => $data) {
                    $posiciones[$mid] = $pos++;
                }

                // Helper to get color class
                function notaClass($n) {
                    if ($n === null) return 'nota-nu';
                    if ($n >= 90) return 'nota-ex';
                    if ($n >= 75) return 'nota-bu';
                    if ($n >= 70) return 'nota-pr';
                    return 'nota-in';
                }
            @endphp

            @foreach($matriculas as $m)
            @php
                $promGral = $estudiantesData[$m->id]['prom'];
                $posicion = $posiciones[$m->id];
            @endphp
            <tr>
                <td style="padding-left:.75rem;">
                    {{ $m->estudiante->nombre_completo }}
                </td>
                @foreach($asignaciones as $asig)
                    @php $notasAsig = []; @endphp
                    @foreach($periodos as $per)
                        @php
                            $raw = $matrix[$m->id][$asig->id][$per->id] ?? null;
                            $nota = getNotaFinal($raw);
                        @endphp
                        @if($nota !== null) @php $notasAsig[] = $nota; @endphp @endif
                        <td class="{{ notaClass($nota) }}">
                            {{ $nota !== null ? number_format($nota, 1) : '—' }}
                        </td>
                    @endforeach
                    @php
                        $promAsig = count($notasAsig) > 0
                            ? round(array_sum($notasAsig) / count($notasAsig), 2)
                            : null;
                    @endphp
                    <td class="prom-asig {{ notaClass($promAsig) }}">
                        {{ $promAsig !== null ? number_format($promAsig, 1) : '—' }}
                    </td>
                @endforeach
                <td class="prom-gral {{ notaClass($promGral) }}">
                    {{ $promGral !== null ? number_format($promGral, 1) : '—' }}
                </td>
                <td class="rank-cell">
                    @if($posicion == 1)
                        <span class="badge" style="background:#fbbf24;color:#78350f;">🥇 1</span>
                    @elseif($posicion == 2)
                        <span class="badge" style="background:#94a3b8;color:#1e293b;">🥈 2</span>
                    @elseif($posicion == 3)
                        <span class="badge" style="background:#cd7c2c;color:#fff;">🥉 3</span>
                    @else
                        <span class="text-muted fw-semibold">{{ $posicion }}</span>
                    @endif
                </td>
                <td style="text-align:center;">
                    @if($periodos->isNotEmpty())
                    <a href="{{ route('admin.boletines.ver', [$m->id, $periodos->last()->id]) }}"
                       class="btn btn-outline-primary boletin-btn">
                        <i class="bi bi-file-earmark-text me-1"></i>Boletín
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Legend --}}
<div class="d-flex flex-wrap gap-2 mt-3 align-items-center" style="font-size:.78rem;">
    <span class="text-muted fw-semibold">Indicadores:</span>
    <span class="px-2 py-1 rounded" style="background:#dcfce7;color:#15803d;font-weight:700;">Excelente (90+)</span>
    <span class="px-2 py-1 rounded" style="background:#dbeafe;color:#1d4ed8;font-weight:700;">Bueno (75–89)</span>
    <span class="px-2 py-1 rounded" style="background:#fef3c7;color:#92400e;font-weight:700;">En proceso (70–74)</span>
    <span class="px-2 py-1 rounded" style="background:#fee2e2;color:#991b1b;font-weight:700;">Insuficiente (&lt;70)</span>
</div>

@endif

<div class="mt-3">
    <a href="{{ route('admin.calificaciones.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Volver
    </a>
</div>

@endsection
