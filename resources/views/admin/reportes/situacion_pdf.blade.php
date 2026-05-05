<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 6px; }

.meta { display: flex; justify-content: space-between; margin-bottom: 10px;
        background: #f1f5f9; padding: 6px 10px; border-radius: 4px; font-size: 8.5px; }
.meta strong { color: #0f172a; }

.summary-row { display: flex; gap: 12px; margin-bottom: 12px; }
.summary-box { flex: 1; text-align: center; padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
.summary-box .num  { font-size: 18px; font-weight: 800; }
.summary-box .lbl  { font-size: 7.5px; color: #64748b; margin-top: 2px; }
.box-aprobado  { background: #dcfce7; }
.box-aprobado .num { color: #15803d; }
.box-reprobado { background: #fee2e2; }
.box-reprobado .num{ color: #dc2626; }
.box-total     { background: #eff6ff; }
.box-total .num    { color: #1d4ed8; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 6px; text-align: center; font-size: 8px; border: 1px solid #1e40af; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody td { padding: 5px 6px; border: 1px solid #e2e8f0; font-size: 8.5px; text-align: center; }
tbody td.name { text-align: left; font-weight: 600; }

.badge-aprobado  { background: #dcfce7; color: #15803d; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
.badge-reprobado { background: #fee2e2; color: #dc2626; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
.badge-sin       { background: #f1f5f9; color: #94a3b8; font-weight: 600; padding: 2px 6px; border-radius: 10px; }

.progress-bar { background: #e2e8f0; border-radius: 4px; height: 8px; width: 100%; }
.progress-fill { height: 8px; border-radius: 4px; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="inst">{{ $boletinConfig?->nombre_institucion ?? config('app.name') }}</div>
    <div class="sub">{{ $boletinConfig?->director ? 'Director: ' . $boletinConfig->director : '' }}</div>
    <div class="titulo">SITUACIÓN FINAL — APROBADOS Y REPROBADOS</div>
    <div class="sub" style="margin-top:4px;">
        {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
        &nbsp;|&nbsp; Año Escolar: {{ $schoolYear?->nombre ?? '—' }}
    </div>
</div>

{{-- Meta --}}
<div class="meta">
    <div><span>Grupo: </span><strong>{{ $grupo->nombre_completo }}</strong></div>
    <div><span>Total estudiantes: </span><strong>{{ count($datos) }}</strong></div>
    <div><span>Generado: </span><strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
</div>

{{-- Summary boxes --}}
@php
    $totalApr = collect($datos)->where('situacion_general', 'Aprobado')->count();
    $totalRep = collect($datos)->whereIn('situacion_general', ['Con materias reprobadas'])->count();
    $totalSR  = collect($datos)->where('situacion_general', 'Sin registro')->count();
@endphp
<div class="summary-row">
    <div class="summary-box box-total">
        <div class="num">{{ count($datos) }}</div>
        <div class="lbl">Total Estudiantes</div>
    </div>
    <div class="summary-box box-aprobado">
        <div class="num">{{ $totalApr }}</div>
        <div class="lbl">Aprobados</div>
    </div>
    <div class="summary-box box-reprobado">
        <div class="num">{{ $totalRep }}</div>
        <div class="lbl">Con Reprobadas</div>
    </div>
    <div class="summary-box" style="background:#fff7ed;">
        <div class="num" style="color:#b45309;">{{ $totalSR }}</div>
        <div class="lbl">Sin Registro</div>
    </div>
    <div class="summary-box" style="background:#eff6ff;">
        <div class="num" style="color:#1d4ed8;">
            {{ count($datos) > 0 ? round($totalApr / count($datos) * 100) : 0 }}%
        </div>
        <div class="lbl">% Aprobación</div>
    </div>
</div>

{{-- Tabla --}}
<table>
    <thead>
        <tr>
            <th class="left" style="width:20px;">#</th>
            <th class="left" style="width:160px;">Estudiante</th>
            <th>Promedio</th>
            <th>Aprobadas</th>
            <th>Reprobadas</th>
            <th>Sin Reg.</th>
            <th>Total</th>
            <th>% Aprobación</th>
            <th style="width:100px;">Situación Final</th>
        </tr>
    </thead>
    <tbody>
    @foreach($datos as $d)
        <tr>
            <td style="color:#94a3b8; font-size:7.5px;">{{ $loop->iteration }}</td>
            <td class="name">{{ $d['estudiante']?->apellidos }}, {{ $d['estudiante']?->nombres }}</td>
            <td style="font-weight:700; color:{{ $d['promedio'] !== null ? ($d['promedio'] >= 70 ? '#15803d' : '#dc2626') : '#94a3b8' }};">
                {{ $d['promedio'] !== null ? number_format($d['promedio'], 2) : '—' }}
            </td>
            <td style="color:#15803d; font-weight:700;">{{ $d['aprobadas'] }}</td>
            <td style="color:{{ $d['reprobadas'] > 0 ? '#dc2626' : '#94a3b8' }}; font-weight:{{ $d['reprobadas'] > 0 ? '700' : '400' }};">
                {{ $d['reprobadas'] }}
            </td>
            <td style="color:#94a3b8;">{{ $d['sin_registro'] ?? 0 }}</td>
            <td>{{ $d['total'] }}</td>
            <td>
                @if($d['total'] > 0)
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:{{ $d['pct_aprobadas'] }}%; background:{{ $d['pct_aprobadas'] >= 70 ? '#16a34a' : '#dc2626' }};"></div>
                    </div>
                    <div style="font-size:7.5px; margin-top:2px; color:#475569;">{{ $d['pct_aprobadas'] }}%</div>
                @else
                    <span style="color:#94a3b8;">—</span>
                @endif
            </td>
            <td>
                @if($d['situacion_general'] === 'Aprobado')
                    <span class="badge-aprobado">Aprobado</span>
                @elseif($d['situacion_general'] === 'Con materias reprobadas')
                    <span class="badge-reprobado">Con Reprobadas</span>
                @else
                    <span class="badge-sin">Sin Registro</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    <span>{{ config('app.name') }}</span>
    <span>Documento oficial — generado el {{ now()->format('d/m/Y H:i:s') }}</span>
</div>

</body>
</html>
