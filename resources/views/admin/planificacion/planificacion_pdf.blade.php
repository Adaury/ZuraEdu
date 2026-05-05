<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo{ font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 7.5px; color: #6b7280; margin-top: 3px; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.meta-table td { border: 1px solid #bfdbfe; padding: 4px 7px; font-size: 8px; vertical-align: middle; }
.meta-table .lbl { background: #eff6ff; font-weight: 700; color: #1e40af; width: 90px; }

.ra-table thead tr { background: #1e40af; color: #fff; }
.ra-table thead th { padding: 4px 6px; font-size: 7.5px; border: 1px solid #1e3a8a; text-align: left; }
.ra-table tbody td { padding: 4px 6px; border: 1px solid #bfdbfe; font-size: 7.5px; vertical-align: top; }
.ra-table tbody tr:nth-child(even) { background: #f0f7ff; }

.act-table { border: 1px solid #bfdbfe; }
.act-table .lbl { background: #eff6ff; font-weight: 700; color: #1e40af; padding: 4px 7px; font-size: 8px; border-right: 1px solid #bfdbfe; width: 120px; }
.act-table td { border-bottom: 1px solid #e2e8f0; padding: 4px 7px; font-size: 8px; }

.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
.firma-row { display: flex; gap: 24px; margin-top: 18px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 5px; font-size: 7.5px; color: #475569; margin-top: 22px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">PLANIFICACIÓN {{ strtoupper($planificacion->tipo === 'ra' ? 'POR RESULTADOS DE APRENDIZAJE' : 'DE ACTIVIDADES') }}</div>
    <div class="sub">
        {{ $planificacion->asignacion?->asignatura?->nombre ?? '' }}
        &nbsp;·&nbsp; {{ $planificacion->asignacion?->grupo?->nombre_completo ?? '' }}
        &nbsp;·&nbsp; Año: {{ $planificacion->schoolYear?->nombre ?? '' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

{{-- Metadata --}}
<table class="meta-table">
    <tr>
        <td class="lbl">Familia Prof.</td>
        <td>{{ $planificacion->familia_profesional ?? '—' }}</td>
        <td class="lbl">Denominación</td>
        <td>{{ $planificacion->denominacion ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Módulo</td>
        <td colspan="3">{{ $planificacion->modulo_nombre ?? $planificacion->asignacion?->asignatura?->nombre ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Docente</td>
        <td>{{ $planificacion->asignacion?->docente?->nombre_completo ?? '—' }}</td>
        <td class="lbl">Código MF</td>
        <td>{{ $planificacion->mf_codigo ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha Inicio</td>
        <td>{{ $planificacion->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
        <td class="lbl">Fecha Final</td>
        <td>{{ $planificacion->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @if($planificacion->uc_codigo)
    <tr>
        <td class="lbl">Unidad de Competencia</td>
        <td colspan="3">{{ $planificacion->uc_codigo }}</td>
    </tr>
    @endif
</table>

@if($planificacion->tipo === 'ra' && $planificacion->raItems->isNotEmpty())
<table class="ra-table">
    <thead>
        <tr>
            <th style="width:130px;">Resultados de Aprendizaje</th>
            <th style="width:130px;">Elementos de Capacidad</th>
            <th style="width:80px;">Fechas</th>
            <th>Actividades de E-A</th>
            <th style="width:100px;">Instrumento</th>
            <th style="width:110px;">Contenidos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($planificacion->raItems as $item)
        <tr>
            <td>
                @if($item->ra_codigo)<strong>{{ $item->ra_codigo }}:</strong> @endif
                {{ $item->ra_descripcion }}
            </td>
            <td style="white-space:pre-line;">{{ $item->elementos_capacidad ?? '—' }}</td>
            <td>
                @if($item->fecha_inicio){{ \Carbon\Carbon::parse($item->fecha_inicio)->format('d/m/Y') }}@endif
                @if($item->fecha_fin) — {{ \Carbon\Carbon::parse($item->fecha_fin)->format('d/m/Y') }}@endif
            </td>
            <td style="white-space:pre-line;">{{ $item->actividades ?? '—' }}</td>
            <td>{{ $item->instrumento_evaluacion ?? '—' }}</td>
            <td style="white-space:pre-line;">{{ $item->contenidos ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($planificacion->tipo === 'actividad')
@php $act = $planificacion->actividades->first(); @endphp
@if($act)
<table class="act-table">
    <tr><td class="lbl">Objetivo General</td><td style="white-space:pre-line;">{{ $act->objetivo_general ?? '—' }}</td></tr>
    <tr><td class="lbl">Competencias</td><td style="white-space:pre-line;">{{ $act->competencias ?? '—' }}</td></tr>
    <tr><td class="lbl">Contenidos</td><td style="white-space:pre-line;">{{ $act->contenidos ?? '—' }}</td></tr>
    <tr><td class="lbl">Actividades</td><td style="white-space:pre-line;">{{ $act->actividades ?? '—' }}</td></tr>
    <tr><td class="lbl">Evaluación</td><td style="white-space:pre-line;">{{ $act->evaluacion ?? '—' }}</td></tr>
    <tr><td class="lbl">Recursos</td><td style="white-space:pre-line;">{{ $act->recursos ?? '—' }}</td></tr>
</table>
@endif
@endif

<div class="firma-row">
    <div class="firma-box">{{ $planificacion->asignacion?->docente?->nombre_completo ?? 'Docente' }}</div>
    <div class="firma-box">Coordinador/a Académico</div>
    <div class="firma-box">Director/a del Centro</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Planificación Docente</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
