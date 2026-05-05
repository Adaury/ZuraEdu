<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.est-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 5px;
            padding: 8px 12px; margin-bottom: 12px; display: flex; gap: 20px; flex-wrap: wrap; }
.est-col { flex: 1; }
.est-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; }
.est-val { font-size: 10px; font-weight: 700; color: #1e293b; }

.section-title { font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #1e40af;
                 border-bottom: 1px solid #bfdbfe; padding-bottom: 3px; margin: 10px 0 7px; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 4px 6px; font-size: 8px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 4px 6px; border: 1px solid #bfdbfe; font-size: 8.5px; text-align: center; }
tbody td.left { text-align: left; }

.nota-ok  { color: #15803d; font-weight: 700; }
.nota-bad { color: #dc2626; font-weight: 700; }

.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">REPORTE DE CALIFICACIONES — {{ strtoupper($estudiante->nombre_completo) }}</div>
    <div class="sub">Año Escolar: {{ $schoolYear?->nombre ?? '—' }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}</div>
</div>

<div class="est-card">
    <div class="est-col">
        <div class="est-lbl">Estudiante</div>
        <div class="est-val">{{ $estudiante->nombre_completo }}</div>
    </div>
    <div class="est-col">
        <div class="est-lbl">Grupo</div>
        <div class="est-val">{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</div>
    </div>
    <div class="est-col">
        <div class="est-lbl">Matrícula</div>
        <div class="est-val">{{ $estudiante->matricula ?? '—' }}</div>
    </div>
</div>

{{-- Calificaciones Académicas (Segundo Ciclo) --}}
@if($calificacionesAcademicas->isNotEmpty())
<div class="section-title">Calificaciones Académicas</div>
<table>
    <thead>
        <tr>
            <th class="left">Asignatura</th>
            <th style="width:45px;">P1</th>
            <th style="width:45px;">P2</th>
            <th style="width:45px;">P3</th>
            <th style="width:45px;">P4</th>
            <th style="width:60px;">Cal. Final</th>
            <th style="width:60px;">Situación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($calificacionesAcademicas as $c)
        <tr>
            <td class="left">{{ $c->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $c->comp1_p1 ?? '—' }}</td>
            <td>{{ $c->comp1_p2 ?? '—' }}</td>
            <td>{{ $c->comp1_p3 ?? '—' }}</td>
            <td>{{ $c->comp1_p4 ?? '—' }}</td>
            <td class="{{ ($c->nota_final ?? 0) >= 60 ? 'nota-ok' : 'nota-bad' }}">
                {{ $c->nota_final !== null ? number_format($c->nota_final, 1) : '—' }}
            </td>
            <td>
                @if($c->situacion === 'A') <span style="color:#15803d;font-weight:700;">Aprobado</span>
                @elseif($c->situacion === 'R') <span style="color:#dc2626;font-weight:700;">Reprobado</span>
                @else <span style="color:#94a3b8;">—</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Calificaciones Técnicas (Primer Ciclo) por período --}}
@if($calificaciones->isNotEmpty())
@foreach($periodos as $periodo)
@php $calsPeriodo = $calificaciones->get($periodo->id, collect()); @endphp
@if($calsPeriodo->isNotEmpty())
<div class="section-title">{{ $periodo->nombre }}</div>
<table>
    <thead>
        <tr>
            <th class="left">Asignatura</th>
            <th style="width:40px;">C1</th>
            <th style="width:40px;">C2</th>
            <th style="width:40px;">C3</th>
            <th style="width:40px;">C4</th>
            <th style="width:50px;">PC</th>
            <th style="width:60px;">Cal. Final</th>
        </tr>
    </thead>
    <tbody>
        @foreach($calsPeriodo as $c)
        <tr>
            <td class="left">{{ $c->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $c->comp1 ?? '—' }}</td>
            <td>{{ $c->comp2 ?? '—' }}</td>
            <td>{{ $c->comp3 ?? '—' }}</td>
            <td>{{ $c->comp4 ?? '—' }}</td>
            <td>{{ $c->pc ?? '—' }}</td>
            <td class="{{ ($c->nota_final ?? 0) >= 60 ? 'nota-ok' : 'nota-bad' }}">
                {{ $c->nota_final !== null ? number_format($c->nota_final, 1) : '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endforeach
@endif

<div class="footer">
    <span>{{ $inst }} — Calificaciones de {{ $estudiante->nombre_completo }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
