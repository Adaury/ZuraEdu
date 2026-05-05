<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #064e3b; padding-bottom: 10px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #064e3b; text-transform: uppercase; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 6px; }

.meta { display: flex; justify-content: space-between; margin-bottom: 10px;
        background: #f0fdf4; padding: 6px 10px; border-radius: 4px; font-size: 8.5px;
        border: 1px solid #bbf7d0; }
.meta strong { color: #0f172a; }

.summary-row { display: flex; gap: 12px; margin-bottom: 12px; }
.summary-box { flex: 1; text-align: center; padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
.summary-box .num  { font-size: 18px; font-weight: 800; }
.summary-box .lbl  { font-size: 7.5px; color: #64748b; margin-top: 2px; }
.box-regular  { background: #dcfce7; }
.box-regular .num { color: #15803d; }
.box-critica  { background: #fee2e2; }
.box-critica .num { color: #dc2626; }
.box-total    { background: #eff6ff; }
.box-total .num   { color: #1d4ed8; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #064e3b; color: #fff; }
thead th { padding: 5px 6px; text-align: center; font-size: 8px; border: 1px solid #064e3b; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0fdf4; }
tbody td { padding: 5px 6px; border: 1px solid #d1fae5; font-size: 8.5px; text-align: center; }
tbody td.name { text-align: left; font-weight: 600; }

.bar-wrap  { background: #e2e8f0; border-radius: 4px; height: 7px; width: 80px; display: inline-block; vertical-align: middle; }
.bar-fill  { height: 7px; border-radius: 4px; display: block; }
.bar-ok    { background: #22c55e; }
.bar-warn  { background: #ef4444; }

.badge-regular  { background: #dcfce7; color: #15803d; font-weight: 700; padding: 2px 6px; border-radius: 10px; font-size: 7.5px; }
.badge-critica  { background: #fee2e2; color: #dc2626; font-weight: 700; padding: 2px 6px; border-radius: 10px; font-size: 7.5px; }
.badge-sin      { background: #f1f5f9; color: #94a3b8; font-weight: 600; padding: 2px 6px; border-radius: 10px; font-size: 7.5px; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }

.alert-note { background: #fef9c3; border: 1px solid #fde68a; border-radius: 4px;
              padding: 5px 8px; font-size: 7.5px; color: #854d0e; margin-top: 10px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="inst">{{ $boletinConfig?->nombre_institucion ?? config('app.name') }}</div>
    <div class="sub">{{ $boletinConfig?->director ? 'Director/a: ' . $boletinConfig->director : '' }}</div>
    <div class="titulo">REPORTE DE ASISTENCIA INSTITUCIONAL</div>
    <div class="sub" style="margin-top:4px;">
        {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
        &nbsp;|&nbsp; Año Escolar: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- Resumen --}}
@php
    $totalEst = count($datos);
    $regular  = collect($datos)->where('estado', 'Regular')->count();
    $critica  = collect($datos)->where('estado', 'Crítica')->count();
    $sinReg   = collect($datos)->where('estado', 'Sin registro')->count();
@endphp

<div class="meta">
    <div><strong>Grupo:</strong> {{ $grupo->nombre_completo ?? $grupo->grado->nombre . ' ' . $grupo->seccion->nombre }}</div>
    <div><strong>Total estudiantes:</strong> {{ $totalEst }}</div>
    <div><strong>Umbral MINERD:</strong> 75% de asistencia mínima</div>
</div>

<div class="summary-row">
    <div class="summary-box box-total">
        <div class="num">{{ $totalEst }}</div>
        <div class="lbl">Total Estudiantes</div>
    </div>
    <div class="summary-box box-regular">
        <div class="num">{{ $regular }}</div>
        <div class="lbl">Asistencia Regular (≥75%)</div>
    </div>
    <div class="summary-box box-critica">
        <div class="num">{{ $critica }}</div>
        <div class="lbl">Asistencia Crítica (&lt;75%)</div>
    </div>
    <div class="summary-box" style="background:#f8faff;">
        <div class="num" style="color:#94a3b8;">{{ $sinReg }}</div>
        <div class="lbl">Sin Registro</div>
    </div>
</div>

{{-- Tabla --}}
<table>
    <thead>
        <tr>
            <th style="width:28px;">#</th>
            <th class="left">Estudiante</th>
            <th style="width:90px;">% Asistencia Prom.</th>
            <th style="width:100px;"></th>
            <th style="width:80px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos as $i => $d)
        @php
            $pct = $d['avg_asistencia'];
            $esOk = $pct !== null && $pct >= 75;
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="name">{{ $d['estudiante']?->nombre_completo ?? ($d['estudiante']?->nombres . ' ' . $d['estudiante']?->apellidos) }}</td>
            <td style="font-weight:700;color:{{ $pct === null ? '#94a3b8' : ($esOk ? '#15803d' : '#dc2626') }};">
                {{ $pct !== null ? number_format($pct, 1) . '%' : '—' }}
            </td>
            <td>
                @if($pct !== null)
                <span class="bar-wrap">
                    <span class="bar-fill {{ $esOk ? 'bar-ok' : 'bar-warn' }}"
                          style="width: {{ min($pct, 100) }}%;"></span>
                </span>
                @endif
            </td>
            <td>
                @if($pct === null)
                    <span class="badge-sin">Sin registro</span>
                @elseif($esOk)
                    <span class="badge-regular">Regular</span>
                @else
                    <span class="badge-critica">Crítica</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($critica > 0)
<div class="alert-note">
    <strong>Nota:</strong> {{ $critica }} estudiante(s) con asistencia crítica (&lt;75%). Según el Reglamento del MINERD,
    los estudiantes con menos del 75% de asistencia pueden ser afectados en su promoción.
</div>
@endif

{{-- Firmas --}}
<div style="margin-top:24px; display:flex; gap:30px;">
    <div style="flex:1; text-align:center; border-top:1px solid #94a3b8; padding-top:6px; font-size:8px; color:#475569;">
        Encargado/a de Docencia
    </div>
    <div style="flex:1; text-align:center; border-top:1px solid #94a3b8; padding-top:6px; font-size:8px; color:#475569;">
        Director/a del Centro
    </div>
    <div style="flex:1; text-align:center; border-top:1px solid #94a3b8; padding-top:6px; font-size:8px; color:#475569;">
        Sello del Centro
    </div>
</div>

<div class="footer">
    <span>{{ $boletinConfig?->nombre_institucion ?? config('app.name') }} — Sistema SGE</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
