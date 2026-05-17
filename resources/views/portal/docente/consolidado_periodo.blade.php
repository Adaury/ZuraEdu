@extends('layouts.portal')
@section('page-title', 'Consolidado del Período — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'consolidado-periodo', 'asignacion' => $asignacion])
@endsection

@section('bottom-nav')
<a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item"><i class="bi bi-house-fill"></i>Inicio</a>
<a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item"><i class="bi bi-journal-check"></i>Notas</a>
<a href="{{ route('portal.docente.consolidado-periodo', $asignacion) }}" class="prt-nav-item active"><i class="bi bi-clipboard-data-fill"></i>Consolidado</a>
@endsection

@push('styles')
<style>
.period-tab {
    padding: .35rem .85rem; border-radius: 8px; font-size: .78rem; font-weight: 700;
    text-decoration: none; color: #64748b; background: #f1f5f9; border: 1.5px solid transparent;
    transition: all .15s;
}
.period-tab:hover { background: #e2e8f0; color: #1e293b; }
.period-tab.active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }

.kpi-card {
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px;
    padding: .75rem .85rem; text-align: center;
}
.kpi-num { font-size: 1.45rem; font-weight: 900; line-height: 1.1; }
.kpi-lbl { font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-top: 2px; }

.cons-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
.cons-table th {
    background: #1e293b; color: #fff; padding: .5rem .65rem;
    font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    text-align: center; white-space: nowrap;
}
.cons-table th.left { text-align: left; }
.cons-table td { padding: .5rem .65rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; text-align: center; }
.cons-table td.left { text-align: left; }
.cons-table tr:hover td { background: #f8faff; }
.cons-table tr.row-vacio td { background: #fff5f5; }
.cons-table tr.row-parcial td { background: #fffbeb; }
.cons-table tr.row-completo:hover td { background: #f0fdf4; }

.nota-chip {
    display: inline-block; min-width: 32px; text-align: center;
    border-radius: 6px; padding: .15rem .3rem; font-size: .75rem; font-weight: 700;
}
.nc-ok  { background: #dcfce7; color: #15803d; }
.nc-med { background: #fef9c3; color: #92400e; }
.nc-low { background: #fee2e2; color: #dc2626; }
.nc-nil { background: #f1f5f9; color: #94a3b8; font-style: italic; font-size: .68rem; }

.estado-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .18rem .5rem; border-radius: 99px; font-size: .68rem; font-weight: 700;
}
.eb-completo { background: #dcfce7; color: #15803d; }
.eb-parcial  { background: #fef9c3; color: #92400e; }
.eb-vacio    { background: #fee2e2; color: #dc2626; }

.prog-bar { height: 8px; border-radius: 99px; background: #e2e8f0; overflow: hidden; margin-top: 6px; }
.prog-fill { height: 100%; border-radius: 99px; transition: width .5s; }
</style>
@endpush

@section('content')

{{-- Cabecera --}}
<div style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;flex-shrink:0;">
        <i class="bi bi-arrow-left"></i>Calificaciones
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-clipboard-data-fill" style="color:#1e3a8a;margin-right:.3rem;"></i>
            Consolidado del Período
        </h1>
        <div style="font-size:.75rem;color:#64748b;margin-top:.15rem;">
            {{ $asignacion->asignatura?->nombre }} &mdash; {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.docente.consolidado-periodo.pdf', array_merge(['asignacion' => $asignacion->id], ['periodo' => $periodoNum])) }}"
       target="_blank"
       style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.35rem;flex-shrink:0;">
        <i class="bi bi-file-earmark-pdf-fill"></i>PDF
    </a>
</div>

{{-- Selector de período --}}
<div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
    <span style="font-size:.74rem;font-weight:700;color:#64748b;">Período:</span>
    @foreach($periodos as $p)
    <a href="{{ route('portal.docente.consolidado-periodo', ['asignacion' => $asignacion->id, 'periodo' => $p->numero]) }}"
       class="period-tab {{ $periodoNum === $p->numero ? 'active' : '' }}">
        {{ $p->nombre }}
        @if($p->activo ?? false)
        <span style="font-size:.6rem;background:#22c55e;color:#fff;border-radius:99px;padding:.05rem .3rem;margin-left:.2rem;">activo</span>
        @endif
    </a>
    @endforeach
</div>

{{-- KPIs --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(115px,1fr));gap:.65rem;margin-bottom:1rem;">
    <div class="kpi-card">
        <div class="kpi-num" style="color:#1e293b;">{{ $totEstudiantes }}</div>
        <div class="kpi-lbl">Estudiantes</div>
    </div>
    <div class="kpi-card" style="border-color:#86efac;">
        <div class="kpi-num" style="color:#15803d;">{{ $nCompletos }}</div>
        <div class="kpi-lbl">Completos</div>
    </div>
    <div class="kpi-card" style="border-color:#fde68a;">
        <div class="kpi-num" style="color:#d97706;">{{ $nParciales }}</div>
        <div class="kpi-lbl">Parciales</div>
    </div>
    <div class="kpi-card" style="border-color:#fca5a5;">
        <div class="kpi-num" style="color:#dc2626;">{{ $nVacios }}</div>
        <div class="kpi-lbl">Sin notas</div>
    </div>
    <div class="kpi-card" style="border-color:#93c5fd;">
        <div class="kpi-num" style="color:{{ $promedioGrupo === null ? '#94a3b8' : ($promedioGrupo >= 70 ? '#15803d' : ($promedioGrupo >= 65 ? '#d97706' : '#dc2626')) }};">
            {{ $promedioGrupo !== null ? number_format($promedioGrupo, 1) : '—' }}
        </div>
        <div class="kpi-lbl">Prom. Grupo</div>
    </div>
    <div class="kpi-card" style="border-color:{{ $pctIngreso >= 80 ? '#86efac' : ($pctIngreso >= 50 ? '#fde68a' : '#fca5a5') }};">
        <div class="kpi-num" style="color:{{ $pctIngreso >= 80 ? '#15803d' : ($pctIngreso >= 50 ? '#d97706' : '#dc2626') }};">{{ $pctIngreso }}%</div>
        <div class="kpi-lbl">% Ingresado</div>
    </div>
</div>

{{-- Barra global de progreso --}}
<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.65rem 1rem;margin-bottom:1rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem;">
        <span style="font-size:.74rem;font-weight:700;color:#475569;">
            <i class="bi bi-pencil-fill me-1" style="color:#3b82f6;"></i>
            Progreso de ingreso de notas — {{ $periodo?->nombre ?? "Período {$periodoNum}" }}
        </span>
        <span style="font-size:.8rem;font-weight:800;color:{{ $pctIngreso >= 80 ? '#15803d' : ($pctIngreso >= 50 ? '#d97706' : '#dc2626') }};">
            {{ $pctIngreso }}%
        </span>
    </div>
    <div class="prog-bar">
        <div class="prog-fill" style="width:{{ $pctIngreso }}%;background:{{ $pctIngreso >= 80 ? '#22c55e' : ($pctIngreso >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
    </div>
    <div style="font-size:.68rem;color:#94a3b8;margin-top:.4rem;">
        {{ $filas->sum('llenas') }} / {{ $totEstudiantes * (count($componentes)) }} campos llenados &nbsp;·&nbsp;
        {{ $nCompletos }} completo(s), {{ $nParciales }} parcial(es), {{ $nVacios }} sin notas
    </div>
</div>

{{-- Tabla --}}
<div class="prt-card" style="overflow:hidden;padding:0;">
    <div style="overflow-x:auto;">
        <table class="cons-table">
            <thead>
                <tr>
                    <th class="left" style="min-width:180px;"># Estudiante</th>
                    @foreach($etiquetasComp as $etq)
                    <th>{{ $etq }}</th>
                    @endforeach
                    <th>Nota {{ $periodo?->nombre ?? "P{$periodoNum}" }}</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($filas as $i => $fila)
            @php
                $nf = $fila['notaPeriodo'];
                $nfColor = $nf === null ? '#94a3b8' : ($nf >= 70 ? '#15803d' : ($nf >= 65 ? '#d97706' : '#dc2626'));
            @endphp
            <tr class="row-{{ $fila['estado'] }}">
                <td class="left">
                    <span style="color:#94a3b8;font-size:.68rem;font-weight:700;margin-right:.35rem;">
                        {{ $fila['matricula']->numero_orden ?? ($i + 1) }}.
                    </span>
                    <span style="font-weight:700;color:#1e293b;">
                        {{ $fila['matricula']->estudiante?->apellidos }}, {{ $fila['matricula']->estudiante?->nombres }}
                    </span>
                    @if($fila['matricula']->estudiante?->matricula)
                    <div style="font-size:.63rem;color:#94a3b8;">{{ $fila['matricula']->estudiante->matricula }}</div>
                    @endif
                </td>
                @foreach($componentes as $campo)
                @php
                    $v = $fila['notas'][$campo] ?? null;
                    $chipClass = 'nc-nil';
                    if ($v !== null) {
                        $chipClass = $v >= 70 ? 'nc-ok' : ($v >= 65 ? 'nc-med' : 'nc-low');
                    }
                @endphp
                <td>
                    <span class="nota-chip {{ $chipClass }}">{{ $v !== null ? number_format($v, 1) : '—' }}</span>
                </td>
                @endforeach
                <td>
                    <span style="font-weight:800;font-size:.82rem;color:{{ $nfColor }};">
                        {{ $nf !== null ? number_format($nf, 1) : '—' }}
                    </span>
                </td>
                <td>
                    @if($fila['estado'] === 'completo')
                    <span class="estado-badge eb-completo"><i class="bi bi-check-circle-fill"></i>Completo</span>
                    @elseif($fila['estado'] === 'parcial')
                    <span class="estado-badge eb-parcial">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        {{ $fila['llenas'] }}/{{ $fila['totalComp'] }}
                    </span>
                    @else
                    <span class="estado-badge eb-vacio"><i class="bi bi-x-circle-fill"></i>Sin notas</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}"
                       style="color:#3b82f6;font-size:.72rem;text-decoration:none;"
                       title="Ir a calificaciones">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
            @if($promedioGrupo !== null)
            <tfoot>
                <tr style="background:#f0f9ff;">
                    <td class="left" colspan="{{ count($componentes) + 1 }}"
                        style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#1d4ed8;padding:.6rem .65rem;">
                        Promedio del Grupo
                    </td>
                    <td style="font-weight:900;font-size:.88rem;color:#1d4ed8;text-align:center;">
                        {{ number_format($promedioGrupo, 1) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Leyenda --}}
<div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.85rem;font-size:.7rem;color:#64748b;">
    <span><span class="nota-chip nc-ok">70+</span> Aprobado</span>
    <span><span class="nota-chip nc-med">65+</span> En riesgo</span>
    <span><span class="nota-chip nc-low">&lt;65</span> Reprobado</span>
    <span><span class="nota-chip nc-nil">—</span> Sin ingresar</span>
</div>

@endsection
