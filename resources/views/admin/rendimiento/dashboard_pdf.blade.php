<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #7c3aed; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #7c3aed; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8.5px; color: #6b7280; margin-top: 3px; }

.kpis { display: flex; gap: 10px; margin-bottom: 14px; }
.kpi  { flex: 1; text-align: center; background: #f5f3ff; border: 1px solid #ddd6fe;
        border-radius: 6px; padding: 8px 5px; }
.kpi .num { font-size: 18px; font-weight: 800; color: #7c3aed; }
.kpi .lbl { font-size: 7.5px; color: #6b7280; margin-top: 2px; }
.kpi.verde .num { color: #15803d; } .kpi.verde { background: #dcfce7; border-color: #bbf7d0; }
.kpi.rojo .num  { color: #dc2626; } .kpi.rojo  { background: #fee2e2; border-color: #fca5a5; }
.kpi.azul .num  { color: #1d4ed8; } .kpi.azul  { background: #dbeafe; border-color: #bfdbfe; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #7c3aed; color: #fff; }
thead th { padding: 5px 6px; font-size: 8px; text-align: center; border: 1px solid #6d28d9; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f5f3ff; }
tbody td { padding: 5px 6px; border: 1px solid #e0d9f7; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; vertical-align: middle; margin-right: 3px; }
.dot-ok     { background: #22c55e; }
.dot-warn   { background: #f59e0b; }
.dot-danger { background: #ef4444; }

.bar-wrap { background: #e2e8f0; border-radius: 3px; height: 8px; width: 80px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 8px; border-radius: 3px; display: block; }

.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">INFORME DE RENDIMIENTO ACADÉMICO INSTITUCIONAL</div>
    <div class="sub">
        Año Escolar: {{ $schoolYear->nombre }}
        @if($periodo) &nbsp;·&nbsp; Período: {{ $periodo->nombre }} @else &nbsp;·&nbsp; Todos los períodos @endif
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- KPIs --}}
<div class="kpis">
    <div class="kpi azul">
        <div class="num">{{ $totalEst }}</div>
        <div class="lbl">Estudiantes</div>
    </div>
    <div class="kpi">
        <div class="num">{{ $cacheData->count() }}</div>
        <div class="lbl">Grupos</div>
    </div>
    <div class="kpi {{ $promedioInst >= 70 ? 'verde' : 'rojo' }}">
        <div class="num">{{ $promedioInst ? number_format($promedioInst, 1) : '—' }}</div>
        <div class="lbl">Promedio Institucional</div>
    </div>
    <div class="kpi {{ $tasaAprobacion >= 80 ? 'verde' : ($tasaAprobacion >= 60 ? '' : 'rojo') }}">
        <div class="num">{{ $tasaAprobacion !== null ? $tasaAprobacion . '%' : '—' }}</div>
        <div class="lbl">Tasa de Aprobación</div>
    </div>
    <div class="kpi rojo">
        <div class="num">{{ $totalRiesgo }}</div>
        <div class="lbl">En Riesgo</div>
    </div>
</div>

{{-- Tabla por grupo --}}
<table>
    <thead>
        <tr>
            <th class="left">Grupo</th>
            <th style="width:55px;">Estudiantes</th>
            <th style="width:70px;">Promedio</th>
            <th style="width:90px;"></th>
            <th style="width:55px;">En Riesgo</th>
            <th style="width:65px;">Aprobación</th>
            <th style="width:55px;">Semáforo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cacheData as $row)
        @php
            $prom = $row->promedio_grupo;
            $aprobPct = $row->total_estudiantes > 0
                ? round(($row->total_estudiantes - $row->total_riesgo) / $row->total_estudiantes * 100, 1)
                : null;
            $dotCls = match($row->semaforo) { 'danger' => 'dot-danger', 'warning' => 'dot-warn', default => 'dot-ok' };
            $barColor = $prom >= 70 ? '#22c55e' : ($prom >= 60 ? '#f59e0b' : '#ef4444');
        @endphp
        <tr>
            <td class="left" style="font-weight:600;">{{ $row->grupo?->nombre_completo ?? '—' }}</td>
            <td>{{ $row->total_estudiantes }}</td>
            <td style="font-weight:800;color:{{ $prom >= 70 ? '#15803d' : '#dc2626' }};">
                {{ $prom ? number_format($prom, 1) : '—' }}
            </td>
            <td>
                @if($prom)
                <span class="bar-wrap">
                    <span class="bar-fill" style="width:{{ min($prom, 100) }}%;background:{{ $barColor }};"></span>
                </span>
                @endif
            </td>
            <td style="color:{{ $row->total_riesgo > 0 ? '#dc2626' : '#15803d' }};font-weight:700;">
                {{ $row->total_riesgo }}
            </td>
            <td style="color:{{ ($aprobPct ?? 0) >= 70 ? '#15803d' : '#dc2626' }};font-weight:700;">
                {{ $aprobPct !== null ? $aprobPct . '%' : '—' }}
            </td>
            <td style="text-align:center;">
                <span class="dot {{ $dotCls }}"></span>
                <span style="font-size:7.5px;color:#6b7280;">
                    {{ match($row->semaforo) { 'danger'=>'Crítico','warning'=>'Alerta',default=>'OK' } }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top:10px;background:#f8faff;border:1px solid #e0d9f7;border-radius:4px;padding:6px 10px;font-size:8px;color:#6b7280;">
    <strong style="color:#7c3aed;">Leyenda:</strong>
    <span class="dot dot-ok" style="margin-left:8px;"></span> Promedio ≥70 &nbsp;
    <span class="dot dot-warn"></span> Entre 60–70 &nbsp;
    <span class="dot dot-danger"></span> &lt;60 o alto riesgo
</div>

<div class="footer">
    <span>{{ $inst }} — Informe de Rendimiento Académico</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
