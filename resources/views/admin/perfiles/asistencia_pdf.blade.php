<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #064e3b; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #064e3b; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.est-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 5px; padding: 8px 12px; margin-bottom: 12px; display: flex; gap: 16px; flex-wrap: wrap; }
.est-col { flex: 1; }
.est-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; }
.est-val { font-size: 9.5px; font-weight: 700; color: #1e293b; }

.kpis { display: flex; gap: 10px; margin-bottom: 12px; }
.kpi  { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.kpi .num { font-size: 16px; font-weight: 800; }
.kpi .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
.k-total { background: #eff6ff; } .k-total .num { color: #1d4ed8; }
.k-pres  { background: #dcfce7; } .k-pres .num  { color: #15803d; }
.k-aus   { background: #fee2e2; } .k-aus .num   { color: #dc2626; }
.k-pct   { background: #fef9c3; } .k-pct .num   { color: #92400e; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #064e3b; color: #fff; }
thead th { padding: 5px 7px; font-size: 8px; border: 1px solid #065f46; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0fdf4; }
tbody td { padding: 5px 7px; border: 1px solid #d1fae5; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.bar-wrap { background: #e2e8f0; border-radius: 3px; height: 8px; width: 80px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 8px; border-radius: 3px; display: block; }

.nota-ok  { color: #15803d; font-weight: 700; }
.nota-bad { color: #dc2626; font-weight: 700; }

.alert-note { background: #fef9c3; border: 1px solid #fde68a; border-radius: 4px; padding: 5px 8px; font-size: 8px; color: #854d0e; margin-top: 10px; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">REPORTE DE ASISTENCIA DEL ESTUDIANTE</div>
    <div class="sub">Año Escolar: {{ $schoolYear->nombre }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</div>
</div>

<div class="est-card">
    <div class="est-col">
        <div class="est-lbl">Estudiante</div>
        <div class="est-val">{{ $estudiante->nombre_completo }}</div>
    </div>
    <div class="est-col">
        <div class="est-lbl">Matrícula</div>
        <div class="est-val">{{ $estudiante->matricula ?? '—' }}</div>
    </div>
    <div class="est-col">
        <div class="est-lbl">Grupo</div>
        <div class="est-val">{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</div>
    </div>
</div>

<div class="kpis">
    <div class="kpi k-total">
        <div class="num">{{ $totalGeneral }}</div>
        <div class="lbl">Registros Totales</div>
    </div>
    <div class="kpi k-pres">
        <div class="num">{{ $presGeneral }}</div>
        <div class="lbl">Asistencias</div>
    </div>
    <div class="kpi k-aus">
        <div class="num">{{ $totalGeneral - $presGeneral }}</div>
        <div class="lbl">Ausencias</div>
    </div>
    <div class="kpi k-pct {{ ($pctGeneral ?? 100) < 75 ? '' : '' }}">
        <div class="num" style="color:{{ ($pctGeneral ?? 100) >= 75 ? '#15803d' : '#dc2626' }};">
            {{ $pctGeneral !== null ? $pctGeneral . '%' : '—' }}
        </div>
        <div class="lbl">Promedio General</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="left" style="width:160px;">Asignatura</th>
            <th style="width:55px;">Total</th>
            <th style="width:55px;">Presentes</th>
            <th style="width:55px;">Ausentes</th>
            <th style="width:55px;">Tardanzas</th>
            <th style="width:55px;">Justificadas</th>
            <th style="width:70px;">% Asistencia</th>
            <th style="width:80px;"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($porAsignacion as $a)
        @php $ok = $a['pct'] === null || $a['pct'] >= 75; @endphp
        <tr>
            <td class="left">{{ $a['asignatura'] }}</td>
            <td>{{ $a['total'] }}</td>
            <td style="color:#15803d;font-weight:600;">{{ $a['presentes'] }}</td>
            <td style="color:{{ $a['ausentes']>0?'#dc2626':'#15803d' }};font-weight:600;">{{ $a['ausentes'] }}</td>
            <td style="color:#d97706;">{{ $a['tardanzas'] }}</td>
            <td style="color:#6b7280;">{{ $a['justificado'] }}</td>
            <td class="{{ $ok ? 'nota-ok' : 'nota-bad' }}">
                {{ $a['pct'] !== null ? $a['pct'] . '%' : '—' }}
            </td>
            <td>
                @if($a['pct'] !== null)
                <span class="bar-wrap">
                    <span class="bar-fill" style="width:{{ min($a['pct'],100) }}%;background:{{ $ok ? '#22c55e' : '#ef4444' }};"></span>
                </span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#94a3b8;font-style:italic;">Sin registros.</td></tr>
        @endforelse
    </tbody>
</table>

@if(($pctGeneral ?? 100) < 75)
<div class="alert-note">
    <strong>Atención:</strong> Este estudiante tiene un promedio de asistencia de {{ $pctGeneral }}%, por debajo del 75% mínimo requerido por el MINERD. Puede afectar su situación final.
</div>
@endif

<div class="footer">
    <span>{{ $inst }} — Reporte de Asistencia: {{ $estudiante->nombre_completo }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
