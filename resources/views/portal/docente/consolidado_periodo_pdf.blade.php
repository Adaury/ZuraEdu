<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1e293b; background: #fff; }

.header { background: #1e293b; color: #fff; padding: 9px 13px; margin-bottom: 9px; }
.header-title { font-size: 11pt; font-weight: bold; }
.header-sub   { font-size: 7.5pt; opacity: .8; margin-top: 2px; }
.header-meta  { font-size: 6.5pt; opacity: .6; margin-top: 3px; }

/* KPIs */
.kpi-row { width: 100%; border-collapse: collapse; margin-bottom: 9px; }
.kpi-cell { text-align: center; border: 1px solid #e2e8f0; padding: 5px 4px; border-radius: 5px; }
.kpi-num { font-size: 12pt; font-weight: bold; line-height: 1.1; }
.kpi-lbl { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }

/* Barra progreso */
.prog-wrap { background: #f1f5f9; border-radius: 5px; padding: 5px 8px; margin-bottom: 9px; border: 1px solid #e2e8f0; }
.prog-bg { height: 7px; border-radius: 99px; background: #e2e8f0; overflow: hidden; margin-top: 3px; }
.prog-fill { height: 7px; border-radius: 99px; }

/* Tabla */
.tabla { width: 100%; border-collapse: collapse; }
.tabla th {
    background: #1e293b; color: #fff; padding: 4px 5px;
    font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: .04em;
    text-align: center; white-space: nowrap;
}
.tabla th.left { text-align: left; }
.tabla td { padding: 3.5px 5px; border-bottom: 1px solid #f1f5f9; font-size: 7.5pt; vertical-align: middle; text-align: center; }
.tabla td.left { text-align: left; }
.tabla tr:nth-child(even) td { background: #f8fafc; }
.tabla tr.row-vacio td   { background: #fff5f5; }
.tabla tr.row-parcial td { background: #fffbeb; }

.nc { display: inline-block; min-width: 26px; text-align: center; border-radius: 4px; padding: 1px 3px; font-size: 7pt; font-weight: bold; }
.nc-ok  { background: #dcfce7; color: #15803d; }
.nc-med { background: #fef9c3; color: #92400e; }
.nc-low { background: #fee2e2; color: #dc2626; }
.nc-nil { background: #f1f5f9; color: #94a3b8; }

.eb { display: inline-block; padding: 1px 5px; border-radius: 99px; font-size: 6.5pt; font-weight: bold; }
.eb-completo { background: #dcfce7; color: #15803d; }
.eb-parcial  { background: #fef9c3; color: #92400e; }
.eb-vacio    { background: #fee2e2; color: #dc2626; }

.tfoot-row td { background: #eff6ff !important; font-weight: bold; border-top: 2px solid #93c5fd; }

.footer { margin-top: 10px; padding-top: 7px; border-top: 1px solid #e2e8f0; font-size: 6pt; color: #94a3b8; }
.firma-row { width: 100%; border-collapse: collapse; margin-top: 22px; }
.firma-row td { width: 50%; text-align: center; padding: 0 12px; vertical-align: top; }
.firma-line { border-top: 1px solid #475569; margin-top: 28px; padding-top: 3px; font-size: 7pt; }
</style>
</head>
<body>

{{-- Encabezado --}}
<div class="header">
    <div class="header-title">
        Consolidado del Período — {{ $periodo?->nombre ?? "Período {$periodoNum}" }}
        &nbsp;·&nbsp; {{ $asignacion->asignatura?->nombre ?? '—' }}
    </div>
    <div class="header-sub">
        {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }}
        &nbsp;·&nbsp; Docente: {{ $docente->apellidos ?? '' }}, {{ $docente->nombres ?? '' }}
        @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
    </div>
    <div class="header-meta">
        Generado: {{ now()->format('d/m/Y H:i') }}
        &nbsp;·&nbsp; Estudiantes: {{ $totEstudiantes }}
        &nbsp;·&nbsp; Progreso: {{ $pctIngreso }}%
    </div>
</div>

{{-- KPIs --}}
<table class="kpi-row">
    <tr>
        <td class="kpi-cell">
            <div class="kpi-num" style="color:#1e293b;">{{ $totEstudiantes }}</div>
            <div class="kpi-lbl">Total</div>
        </td>
        <td class="kpi-cell" style="border-color:#86efac;">
            <div class="kpi-num" style="color:#15803d;">{{ $nCompletos }}</div>
            <div class="kpi-lbl">Completos</div>
        </td>
        <td class="kpi-cell" style="border-color:#fde68a;">
            <div class="kpi-num" style="color:#d97706;">{{ $nParciales }}</div>
            <div class="kpi-lbl">Parciales</div>
        </td>
        <td class="kpi-cell" style="border-color:#fca5a5;">
            <div class="kpi-num" style="color:#dc2626;">{{ $nVacios }}</div>
            <div class="kpi-lbl">Sin notas</div>
        </td>
        <td class="kpi-cell" style="border-color:#93c5fd;">
            <div class="kpi-num" style="color:{{ $promedioGrupo === null ? '#94a3b8' : ($promedioGrupo >= 70 ? '#15803d' : ($promedioGrupo >= 65 ? '#d97706' : '#dc2626')) }};">
                {{ $promedioGrupo !== null ? number_format($promedioGrupo, 1) : '—' }}
            </div>
            <div class="kpi-lbl">Prom. Grupo</div>
        </td>
        <td class="kpi-cell" style="border-color:{{ $pctIngreso >= 80 ? '#86efac' : ($pctIngreso >= 50 ? '#fde68a' : '#fca5a5') }};">
            <div class="kpi-num" style="color:{{ $pctIngreso >= 80 ? '#15803d' : ($pctIngreso >= 50 ? '#d97706' : '#dc2626') }};">{{ $pctIngreso }}%</div>
            <div class="kpi-lbl">% Ingresado</div>
        </td>
    </tr>
</table>

{{-- Barra de progreso --}}
<div class="prog-wrap">
    <div style="display:table;width:100%;">
        <div style="display:table-cell;font-size:7.5pt;font-weight:bold;color:#475569;">
            Progreso de ingreso de notas
        </div>
        <div style="display:table-cell;text-align:right;font-size:8pt;font-weight:bold;color:{{ $pctIngreso >= 80 ? '#15803d' : ($pctIngreso >= 50 ? '#d97706' : '#dc2626') }};">
            {{ $pctIngreso }}%
        </div>
    </div>
    <div class="prog-bg">
        <div class="prog-fill" style="width:{{ $pctIngreso }}%;background:{{ $pctIngreso >= 80 ? '#22c55e' : ($pctIngreso >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
    </div>
    <div style="font-size:6pt;color:#94a3b8;margin-top:2px;">
        {{ $filas->sum('llenas') }} / {{ $totEstudiantes * count($componentes) }} campos llenados
    </div>
</div>

{{-- Tabla --}}
<table class="tabla">
    <thead>
        <tr>
            <th class="left" style="min-width:150px;"># Estudiante</th>
            @foreach($etiquetasComp as $etq)
            <th>{{ $etq }}</th>
            @endforeach
            <th>Nota {{ $periodo?->nombre ?? "P{$periodoNum}" }}</th>
            <th>Estado</th>
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
            <strong>{{ $fila['matricula']->numero_orden ?? ($i + 1) }}.</strong>
            {{ $fila['matricula']->estudiante?->apellidos }}, {{ $fila['matricula']->estudiante?->nombres }}
        </td>
        @foreach($componentes as $campo)
        @php
            $v = $fila['notas'][$campo] ?? null;
            $chipCls = 'nc-nil';
            if ($v !== null) $chipCls = $v >= 70 ? 'nc-ok' : ($v >= 65 ? 'nc-med' : 'nc-low');
        @endphp
        <td><span class="nc {{ $chipCls }}">{{ $v !== null ? number_format($v, 1) : '—' }}</span></td>
        @endforeach
        <td style="font-weight:800;color:{{ $nfColor }};">
            {{ $nf !== null ? number_format($nf, 1) : '—' }}
        </td>
        <td>
            @if($fila['estado'] === 'completo')
            <span class="eb eb-completo">&#10003; Completo</span>
            @elseif($fila['estado'] === 'parcial')
            <span class="eb eb-parcial">{{ $fila['llenas'] }}/{{ $fila['totalComp'] }}</span>
            @else
            <span class="eb eb-vacio">Sin notas</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    @if($promedioGrupo !== null)
    <tfoot>
        <tr class="tfoot-row">
            <td class="left" colspan="{{ count($componentes) + 1 }}"
                style="font-size:6.5pt;text-transform:uppercase;letter-spacing:.05em;color:#1d4ed8;">
                Promedio del Grupo
            </td>
            <td style="font-weight:900;color:#1d4ed8;text-align:center;">{{ number_format($promedioGrupo, 1) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Firmas --}}
<table class="firma-row">
    <tr>
        <td>
            <div class="firma-line">
                Docente: {{ $docente->apellidos ?? '' }}, {{ $docente->nombres ?? '' }}
            </div>
        </td>
        <td>
            <div class="firma-line">Director(a) / Coordinador Académico</div>
        </td>
    </tr>
</table>

<div class="footer">
    Documento generado automáticamente · {{ config('app.name') }} · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
