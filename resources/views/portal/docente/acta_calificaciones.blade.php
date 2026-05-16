@extends('layouts.portal')
@section('page-title', 'Acta de Calificaciones — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'acta-calificaciones', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
<a href="{{ route('portal.docente.acta-calificaciones', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-file-earmark-spreadsheet-fill"></i>Acta</a>
@endsection

@push('styles')
<style>
.kpi-card { text-align:center; padding:.85rem .5rem; border-radius:12px; }
.kpi-val  { font-size:1.55rem; font-weight:900; line-height:1.1; }
.kpi-lbl  { font-size:.65rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-top:.2rem; }

.rng-row  { display:flex; align-items:center; gap:.6rem; margin-bottom:.45rem; }
.rng-lbl  { width:54px; font-size:.72rem; font-weight:700; text-align:right; flex-shrink:0; }
.rng-bar  { flex:1; background:#f1f5f9; border-radius:99px; height:14px; overflow:hidden; }
.rng-fill { height:14px; border-radius:99px; transition:.4s; }
.rng-cnt  { width:28px; font-size:.72rem; font-weight:700; text-align:right; flex-shrink:0; }

.acta-table { width:100%; border-collapse:collapse; font-size:.78rem; }
.acta-table th { background:#1e3a6e; color:#fff; padding:.5rem .6rem; text-align:center; font-size:.7rem; font-weight:700; }
.acta-table th.left { text-align:left; }
.acta-table td { padding:.45rem .6rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acta-table tbody tr:nth-child(even) td { background:#f8faff; }
.nota-ok  { color:#15803d; font-weight:700; }
.nota-bad { color:#dc2626; font-weight:700; }
.nota-med { color:#d97706; font-weight:700; }
.per-avg  { background:#eff6ff; font-weight:700; color:#1e40af; font-size:.75rem; text-align:center; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1.2rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
       style="color:#1e3a6e;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:.3rem;">
        <i class="bi bi-arrow-left"></i>Calificaciones
    </a>
    <span style="color:#cbd5e1;">›</span>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;color:#1e293b;">
            Acta de Calificaciones — {{ $asignacion->asignatura?->nombre ?? '—' }}
        </h1>
        <div style="font-size:.72rem;color:#64748b;margin-top:.1rem;">
            {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre }}
            &nbsp;·&nbsp;
            {{ $docente->nombre_completo }}
            @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
            &nbsp;·&nbsp;
            <span style="font-weight:700;color:{{ $esTecnica ? '#7c3aed' : '#2563eb' }};">
                {{ $esTecnica ? 'Área Técnica' : 'Área Académica' }}
            </span>
        </div>
    </div>
    <a href="{{ route('portal.docente.acta.pdf', $asignacion) }}"
       style="background:#1e3a6e;color:#fff;border-radius:9px;padding:.48rem 1.1rem;font-size:.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;flex-shrink:0;">
        <i class="bi bi-download"></i>Descargar PDF
    </a>
</div>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.65rem;margin-bottom:1.2rem;">
    <div class="prt-card kpi-card">
        <div class="kpi-val" style="color:#1e3a6e;">{{ $stats['total'] }}</div>
        <div class="kpi-lbl">Estudiantes</div>
    </div>
    <div class="prt-card kpi-card">
        <div class="kpi-val" style="color:#10b981;">{{ $stats['aprobados'] }}</div>
        <div class="kpi-lbl">Aprobados</div>
    </div>
    <div class="prt-card kpi-card">
        <div class="kpi-val" style="color:#ef4444;">{{ $stats['reprobados'] }}</div>
        <div class="kpi-lbl">Reprobados</div>
    </div>
    <div class="prt-card kpi-card">
        <div class="kpi-val" style="color:#2563eb;">{{ $stats['promedio'] ?? '—' }}</div>
        <div class="kpi-lbl">Promedio Clase</div>
    </div>
    <div class="prt-card kpi-card">
        <div class="kpi-val" style="color:#f59e0b;">{{ $stats['maximo'] ?? '—' }}</div>
        <div class="kpi-lbl">Nota Máxima</div>
    </div>
    <div class="prt-card kpi-card">
        <div class="kpi-val" style="color:#94a3b8;">{{ $stats['minimo'] ?? '—' }}</div>
        <div class="kpi-lbl">Nota Mínima</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem;margin-bottom:1.2rem;">

    {{-- Distribución por rango --}}
    <div class="prt-card" style="padding:1rem;">
        <div class="prt-card-header" style="margin-bottom:.85rem;color:#1e3a6e;">
            <i class="bi bi-bar-chart-steps me-2"></i>Distribución de Notas
        </div>
        @php
            $total = $stats['con_notas'] ?: 1;
            $rangos = [
                ['label'=>'90–100','key'=>'90_100','color'=>'#10b981'],
                ['label'=>'80–89', 'key'=>'80_89', 'color'=>'#3b82f6'],
                ['label'=>'70–79', 'key'=>'70_79', 'color'=>'#f59e0b'],
                ['label'=>'60–69', 'key'=>'60_69', 'color'=>'#f97316'],
                ['label'=>'< 60',  'key'=>'menos60','color'=>'#ef4444'],
            ];
        @endphp
        @foreach($rangos as $r)
        @php $cnt = $stats['rangos'][$r['key']]; $pct = round($cnt/$total*100); @endphp
        <div class="rng-row">
            <div class="rng-lbl" style="color:{{ $r['color'] }};">{{ $r['label'] }}</div>
            <div class="rng-bar">
                <div class="rng-fill" style="width:{{ $pct }}%;background:{{ $r['color'] }};"></div>
            </div>
            <div class="rng-cnt" style="color:{{ $r['color'] }};">{{ $cnt }}</div>
        </div>
        @endforeach
        @if($stats['con_notas'] > 0)
        <div style="margin-top:.6rem;padding-top:.6rem;border-top:1px solid #f1f5f9;font-size:.7rem;color:#94a3b8;">
            {{ $stats['aprobados'] }} aprobados de {{ $stats['con_notas'] }} con notas
            ({{ $stats['con_notas'] > 0 ? round($stats['aprobados']/$stats['con_notas']*100) : 0 }}%)
        </div>
        @endif
    </div>

    {{-- Promedio por período --}}
    <div class="prt-card" style="padding:1rem;">
        <div class="prt-card-header" style="margin-bottom:.85rem;color:#1e3a6e;">
            <i class="bi bi-calendar3 me-2"></i>Promedio por Período
        </div>
        @foreach($periodos as $p)
        @php $prom = $promediosPeriodo[$p->numero] ?? null; @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f8fafc;">
            <span style="font-size:.8rem;font-weight:600;color:#475569;">{{ $p->nombre }}</span>
            @if($prom !== null)
            <div style="display:flex;align-items:center;gap:.5rem;">
                <div style="background:#f0f6ff;border-radius:99px;height:8px;width:70px;overflow:hidden;">
                    <div style="height:8px;border-radius:99px;width:{{ $prom }}%;background:{{ $prom >= 70 ? '#3b82f6' : '#f59e0b' }};"></div>
                </div>
                <span style="font-weight:800;font-size:.85rem;color:{{ $prom >= 70 ? '#1d4ed8' : ($prom >= 60 ? '#d97706' : '#dc2626') }};">{{ $prom }}</span>
            </div>
            @else
            <span style="color:#cbd5e1;font-size:.75rem;">Sin datos</span>
            @endif
        </div>
        @endforeach
    </div>

</div>

{{-- Tabla de estudiantes --}}
<div class="prt-card" style="overflow-x:auto;margin-bottom:1.2rem;">
    <div class="prt-card-header" style="padding:1rem 1rem .6rem;color:#1e3a6e;">
        <i class="bi bi-people me-2"></i>Nómina de Calificaciones
        <span style="float:right;font-size:.7rem;color:#94a3b8;font-weight:400;">{{ $stats['total'] }} estudiante(s)</span>
    </div>
    <table class="acta-table">
        <thead>
            <tr>
                <th style="width:22px;">#</th>
                <th class="left" style="min-width:150px;">Estudiante</th>
                @foreach($periodos as $p)
                <th>{{ $p->nombre }}</th>
                @endforeach
                <th style="background:#0f2252;">Promedio</th>
                <th style="background:#0f2252;">Situación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matriculas as $i => $mat)
            @php $d = $estudiantesData[$mat->id]; @endphp
            <tr>
                <td style="text-align:center;color:#94a3b8;font-size:.72rem;">{{ $i+1 }}</td>
                <td>
                    <span style="font-weight:700;font-size:.82rem;">
                        {{ $mat->estudiante?->apellidos ?? $mat->estudiante?->apellido ?? '' }},
                        {{ $mat->estudiante?->nombres ?? $mat->estudiante?->nombre ?? '' }}
                    </span>
                </td>
                @foreach($periodos as $p)
                @php $np = $d['notasPeriodos'][$p->numero] ?? null; @endphp
                <td style="text-align:center;" class="{{ $np !== null ? ($np >= 70 ? 'nota-ok' : ($np >= 60 ? 'nota-med' : 'nota-bad')) : '' }}">
                    {{ $np ?? '—' }}
                </td>
                @endforeach
                <td style="text-align:center;font-weight:800;font-size:.85rem;" class="{{ $d['notaFinal'] !== null ? ($d['notaFinal'] >= 70 ? 'nota-ok' : ($d['notaFinal'] >= 60 ? 'nota-med' : 'nota-bad')) : '' }}">
                    {{ $d['notaFinal'] ?? '—' }}
                </td>
                <td style="text-align:center;font-size:.75rem;font-weight:700;">
                    @if($d['situacion'] === 'A')
                        <span style="background:#dcfce7;color:#15803d;padding:.15rem .5rem;border-radius:99px;font-size:.67rem;">Aprobado</span>
                    @elseif($d['situacion'] === 'R')
                        <span style="background:#fee2e2;color:#dc2626;padding:.15rem .5rem;border-radius:99px;font-size:.67rem;">Reprobado</span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach

            {{-- Fila de promedios --}}
            @if($stats['con_notas'] > 0)
            <tr>
                <td colspan="2" class="per-avg" style="text-align:right;font-size:.7rem;color:#94a3b8;font-weight:600;font-style:italic;padding:.5rem .6rem;">
                    Promedio clase →
                </td>
                @foreach($periodos as $p)
                @php $prom = $promediosPeriodo[$p->numero] ?? null; @endphp
                <td class="per-avg" style="color:{{ $prom !== null ? ($prom >= 70 ? '#1d4ed8' : ($prom >= 60 ? '#d97706' : '#dc2626')) : '#94a3b8' }};">
                    {{ $prom ?? '—' }}
                </td>
                @endforeach
                <td class="per-avg" style="background:#dbeafe;color:{{ $stats['promedio'] !== null ? ($stats['promedio'] >= 70 ? '#1e40af' : ($stats['promedio'] >= 60 ? '#d97706' : '#dc2626')) : '#94a3b8' }};">
                    {{ $stats['promedio'] ?? '—' }}
                </td>
                <td class="per-avg"></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Botón de descarga final --}}
<div style="display:flex;justify-content:center;gap:.75rem;flex-wrap:wrap;padding-bottom:1.5rem;">
    <a href="{{ route('portal.docente.acta.pdf', $asignacion) }}"
       style="background:#1e3a6e;color:#fff;border-radius:10px;padding:.6rem 1.5rem;font-size:.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.45rem;">
        <i class="bi bi-file-earmark-pdf-fill"></i>Descargar Acta PDF (Horizontal)
    </a>
    <a href="{{ route('portal.docente.calificaciones.exportar-excel', $asignacion) }}"
       style="background:#166534;color:#fff;border-radius:10px;padding:.6rem 1.5rem;font-size:.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.45rem;">
        <i class="bi bi-file-earmark-excel-fill"></i>Exportar Excel
    </a>
</div>

@endsection
