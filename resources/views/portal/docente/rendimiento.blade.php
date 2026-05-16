@extends('layouts.portal')
@section('page-title', 'Rendimiento — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'rendimiento'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.estudiantes', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-people-fill"></i>Estudiantes
    </a>
    <a href="{{ route('portal.docente.rendimiento', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-graph-up-arrow"></i>Rendimiento
    </a>
@endsection

@push('styles')
<style>
.stat-card {
    background:#fff; border-radius:12px; border:1px solid #e2e8f0;
    padding:.85rem 1rem; display:flex; flex-direction:column; gap:.2rem;
}
.stat-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; }
.stat-val   { font-size:1.6rem; font-weight:900; line-height:1; }

.rango-bar-wrap { display:flex; align-items:center; gap:.6rem; margin-bottom:.5rem; }
.rango-bar-bg   { flex:1; background:#f1f5f9; border-radius:99px; height:10px; overflow:hidden; }
.rango-bar-fill { height:100%; border-radius:99px; transition:width .4s; }

.est-row { border-bottom:1px solid #f1f5f9; transition:background .1s; }
.est-row:hover { background:#f8faff; }
.est-row.riesgo { background:#fff5f5; }
.est-row.riesgo:hover { background:#fee2e2; }

.periodo-nota {
    display:inline-block; min-width:36px; text-align:center;
    font-size:.8rem; font-weight:700; border-radius:7px; padding:.18rem .3rem;
}
.pn-ok  { background:#dcfce7; color:#15803d; }
.pn-med { background:#fef9c3; color:#92400e; }
.pn-low { background:#fee2e2; color:#dc2626; }
.pn-nil { background:#f1f5f9; color:#94a3b8; }

.nota-final-badge {
    display:inline-block; min-width:44px; text-align:center;
    font-weight:900; font-size:.9rem; border-radius:8px; padding:.22rem .4rem;
}
.nf-excelente { background:#dbeafe; color:#1d4ed8; }
.nf-ok        { background:#dcfce7; color:#15803d; }
.nf-med       { background:#fef9c3; color:#92400e; }
.nf-low       { background:#fee2e2; color:#dc2626; }
.nf-nil       { background:#f1f5f9; color:#94a3b8; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <a href="{{ route('portal.docente.historial-notas', $asignacion) }}"
       style="background:#eef2ff;color:#4f46e5;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;margin-top:.1rem;">
        <i class="bi bi-activity"></i>Historial de notas
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-graph-up-arrow" style="color:#1e3a8a;"></i>
            Reporte de Rendimiento
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
</div>

{{-- KPIs principales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.65rem;margin-bottom:1rem;">
    <div class="stat-card">
        <span class="stat-label">Promedio</span>
        <span class="stat-val" style="color:{{ $promedio !== null ? ($promedio >= 70 ? '#15803d' : ($promedio >= 65 ? '#92400e' : '#dc2626')) : '#94a3b8' }};">
            {{ $promedio !== null ? number_format($promedio, 1) : '—' }}
        </span>
        <span style="font-size:.7rem;color:#64748b;">de 100 pts</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Aprobados</span>
        <span class="stat-val" style="color:#15803d;">{{ $aprobados }}</span>
        <span style="font-size:.7rem;color:#64748b;">de {{ $matriculas->count() }} estudiantes</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Reprobados</span>
        <span class="stat-val" style="color:#dc2626;">{{ $reprobados }}</span>
        <span style="font-size:.7rem;color:#64748b;">nota &lt; 65</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Sin nota</span>
        <span class="stat-val" style="color:#94a3b8;">{{ $rangos['sin_nota'] }}</span>
        <span style="font-size:.7rem;color:#64748b;">pendientes</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">En riesgo</span>
        <span class="stat-val" style="color:#f59e0b;">{{ $enRiesgo->count() }}</span>
        <span style="font-size:.7rem;color:#64748b;">requieren atención</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;" class="grid-rpt">

    {{-- Distribución por rangos --}}
    <div class="prt-card">
        <div class="prt-card-header">
            <i class="bi bi-bar-chart-fill" style="color:#3b82f6;"></i>
            <h3>Distribución de notas</h3>
        </div>
        <div style="padding:.75rem 1rem;">
            @php
            $total = $matriculas->count();
            $rangosConfig = [
                '100-90' => ['label' => 'Excelente (90–100)', 'color' => '#3b82f6'],
                '89-80'  => ['label' => 'Muy bien (80–89)',  'color' => '#10b981'],
                '79-70'  => ['label' => 'Bien (70–79)',      'color' => '#22c55e'],
                '69-65'  => ['label' => 'Aprobado (65–69)', 'color' => '#f59e0b'],
                '<65'    => ['label' => 'Reprobado (<65)',  'color' => '#ef4444'],
                'sin_nota' => ['label' => 'Sin nota',        'color' => '#94a3b8'],
            ];
            @endphp
            @foreach($rangosConfig as $clave => $cfg)
            @php $cnt = $rangos[$clave]; $pct = $total > 0 ? round($cnt / $total * 100) : 0; @endphp
            <div class="rango-bar-wrap">
                <span style="font-size:.72rem;color:#374151;width:140px;flex-shrink:0;">{{ $cfg['label'] }}</span>
                <div class="rango-bar-bg">
                    <div class="rango-bar-fill" style="width:{{ $pct }}%;background:{{ $cfg['color'] }};"></div>
                </div>
                <span style="font-size:.75rem;font-weight:700;color:{{ $cfg['color'] }};width:28px;text-align:right;flex-shrink:0;">{{ $cnt }}</span>
                <span style="font-size:.68rem;color:#94a3b8;width:30px;flex-shrink:0;">{{ $pct }}%</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Estudiantes en riesgo --}}
    <div class="prt-card">
        <div class="prt-card-header">
            <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;"></i>
            <h3>Estudiantes en riesgo</h3>
            <span style="margin-left:auto;background:#fee2e2;color:#dc2626;border-radius:99px;font-size:.68rem;font-weight:700;padding:.15rem .55rem;">
                {{ $enRiesgo->count() }}
            </span>
        </div>
        @if($enRiesgo->isEmpty())
        <div style="padding:1.5rem;text-align:center;color:#15803d;font-size:.82rem;">
            <i class="bi bi-check-circle-fill" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
            ¡Todos los estudiantes con nota están aprobados!
        </div>
        @else
        <div style="padding:0;">
            @foreach($enRiesgo as $fila)
            <div style="padding:.55rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
                <div style="width:30px;height:30px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-person-fill" style="color:#dc2626;font-size:.8rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.8rem;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $fila['matricula']->estudiante->apellidos }}, {{ $fila['matricula']->estudiante->nombres }}
                    </div>
                    @if($fila['pctAsist'] !== null && $fila['pctAsist'] < 80)
                    <div style="font-size:.68rem;color:#f59e0b;">
                        <i class="bi bi-calendar-x me-1"></i>Asistencia: {{ $fila['pctAsist'] }}%
                    </div>
                    @endif
                </div>
                <span class="nota-final-badge nf-low">{{ number_format($fila['notaFinal'], 1) }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

{{-- Tabla completa de estudiantes --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-table" style="color:#7c3aed;"></i>
        <h3>Detalle por estudiante</h3>
        <span style="margin-left:auto;font-size:.72rem;color:#64748b;">{{ $matriculas->count() }} estudiantes</span>
    </div>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:.5rem 1rem;text-align:left;font-size:.7rem;font-weight:700;color:#374151;min-width:170px;">#</th>
                    @foreach($periodos as $periodo)
                    <th style="padding:.45rem .4rem;text-align:center;font-size:.68rem;font-weight:700;color:#2563eb;min-width:55px;">
                        P{{ $periodo->numero }}
                    </th>
                    @endforeach
                    <th style="padding:.45rem .5rem;text-align:center;font-size:.68rem;font-weight:700;color:#1e3a8a;min-width:65px;">Final</th>
                    <th style="padding:.45rem .5rem;text-align:center;font-size:.68rem;font-weight:700;color:#64748b;min-width:70px;">Asistencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $i => $fila)
                @php
                    $nf = $fila['notaFinal'];
                    $nfClass = $nf === null ? 'nf-nil' : ($nf >= 90 ? 'nf-excelente' : ($nf >= 70 ? 'nf-ok' : ($nf >= 65 ? 'nf-med' : 'nf-low')));
                @endphp
                <tr class="est-row {{ $fila['enRiesgo'] ? 'riesgo' : '' }}">
                    <td style="padding:.5rem 1rem;font-size:.8rem;color:#1e293b;">
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <span style="font-size:.65rem;color:#94a3b8;width:18px;flex-shrink:0;">{{ $i + 1 }}</span>
                            <span style="font-weight:600;">{{ $fila['matricula']->estudiante->apellidos }}, {{ $fila['matricula']->estudiante->nombres }}</span>
                            @if($fila['enRiesgo'])
                            <i class="bi bi-exclamation-circle-fill" style="color:#dc2626;font-size:.7rem;" title="En riesgo"></i>
                            @endif
                        </div>
                    </td>
                    @foreach($periodos as $periodo)
                    @php
                        $np = $fila['notasPeriodo'][$periodo->numero] ?? null;
                        $npClass = $np === null ? 'pn-nil' : ($np >= 70 ? 'pn-ok' : ($np >= 65 ? 'pn-med' : 'pn-low'));
                    @endphp
                    <td style="padding:.4rem .3rem;text-align:center;">
                        <span class="periodo-nota {{ $npClass }}">
                            {{ $np !== null ? number_format($np, 0) : '—' }}
                        </span>
                    </td>
                    @endforeach
                    <td style="padding:.4rem .5rem;text-align:center;">
                        <span class="nota-final-badge {{ $nfClass }}">
                            {{ $nf !== null ? number_format($nf, 1) : '—' }}
                        </span>
                    </td>
                    <td style="padding:.4rem .5rem;text-align:center;font-size:.78rem;">
                        @if($fila['pctAsist'] !== null)
                        <span style="color:{{ $fila['pctAsist'] >= 80 ? '#15803d' : '#dc2626' }};font-weight:700;">
                            {{ $fila['pctAsist'] }}%
                        </span>
                        @else
                        <span style="color:#94a3b8;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
