<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; }
    .header { background:#1e3a8a; color:#fff; padding:14px 20px; margin-bottom:16px; }
    .header h1 { font-size:13px; font-weight:700; margin-bottom:2px; }
    .header p  { font-size:9px; opacity:.85; }
    h2 { font-size:11px; font-weight:700; color:#1e3a8a; border-bottom:1.5px solid #bfdbfe; padding-bottom:4px; margin:14px 0 8px; }
    table { width:100%; border-collapse:collapse; margin-bottom:10px; }
    th { background:#eff6ff; color:#1e3a8a; font-size:9px; font-weight:700; padding:5px 8px; border:1px solid #bfdbfe; text-align:left; }
    td { padding:5px 8px; border:1px solid #e2e8f0; font-size:9px; vertical-align:top; }
    tr:nth-child(even) td { background:#f8fafc; }
    .badge { display:inline-block; border-radius:4px; padding:1px 6px; font-size:8px; font-weight:700; }
    .badge-ok    { background:#dcfce7; color:#15803d; }
    .badge-warn  { background:#fef9c3; color:#92400e; }
    .badge-empty { background:#f1f5f9; color:#64748b; }
    .total-ok   { color:#15803d; font-weight:800; }
    .total-warn { color:#dc2626; font-weight:800; }
    .bar-wrap { background:#f1f5f9; border-radius:3px; height:7px; margin-top:3px; }
    .bar { height:7px; border-radius:3px; }
    .page-break { page-break-after: always; }
    .obs { font-size:8.5px; color:#64748b; font-style:italic; margin-top:3px; }
    .no-data { color:#94a3b8; font-style:italic; font-size:9px; padding:6px 0; }
</style>
</head>
<body>

<div class="header">
    <h1>Plan de Evaluación por Período</h1>
    <p>{{ $asignacion->asignatura?->nombre }} · {{ $asignacion->grupo?->nombre_completo ?? '' }} · Año {{ $schoolYear?->nombre }}</p>
    <p>Docente: {{ $docente?->nombre_completo ?? auth()->user()->name }} · Generado: {{ now()->format('d/m/Y') }}</p>
</div>

{{-- Resumen de pesos por período --}}
<h2>Distribución de pesos por período</h2>
<table>
    <thead>
        <tr>
            <th>Período</th>
            @foreach($categorias as $cat)
            <th style="text-align:center;">{{ $cat['label'] }}</th>
            @endforeach
            <th style="text-align:center;">Total</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($periodos as $periodo)
        @php $plan = $planes[$periodo->id] ?? null; $total = $plan?->total ?? 0; @endphp
        <tr>
            <td><strong>{{ $periodo->nombre }}</strong></td>
            @foreach($categorias as $campo => $cat)
            <td style="text-align:center;">{{ $plan?->$campo ?? 0 }}%</td>
            @endforeach
            <td style="text-align:center;" class="{{ $total === 100 ? 'total-ok' : 'total-warn' }}">{{ $total }}%</td>
            <td>
                @if($total === 100)
                    <span class="badge badge-ok">✔ Completo</span>
                @elseif($total > 0)
                    <span class="badge badge-warn">Incompleto</span>
                @else
                    <span class="badge badge-empty">Sin definir</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Detalle por período --}}
@foreach($periodos as $periodo)
@php
    $plan       = $planes[$periodo->id] ?? null;
    $instPeriod = $instrumentosPorPeriodo[$periodo->id] ?? collect();
    $total      = $plan?->total ?? 0;
@endphp

<h2>{{ $periodo->nombre }}
    @if($periodo->fecha_inicio) · {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }} @endif
</h2>

@if($plan)
    {{-- Barra visual de pesos --}}
    <table style="margin-bottom:6px;">
        <thead>
            <tr>
                @foreach($categorias as $campo => $cat)
                @if($plan->$campo > 0)
                <th style="text-align:center;width:{{ $plan->$campo }}%;">{{ $cat['label'] }}</th>
                @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($categorias as $campo => $cat)
                @if($plan->$campo > 0)
                <td style="text-align:center;background:{{ $cat['color'] }}22;color:{{ $cat['color'] }};font-weight:800;">{{ $plan->$campo }}%</td>
                @endif
                @endforeach
            </tr>
        </tbody>
    </table>
    @if($plan->observaciones)
    <p class="obs">Observaciones: {{ $plan->observaciones }}</p>
    @endif
@else
    <p class="no-data">No se ha definido el plan de evaluación para este período.</p>
@endif

{{-- Instrumentos --}}
@if($instPeriod->isNotEmpty())
<table style="margin-top:6px;">
    <thead>
        <tr>
            <th>Instrumento</th>
            <th>Tipo</th>
            <th style="text-align:center;">Criterios</th>
            <th style="text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($instPeriod as $inst)
        <tr>
            <td>{{ $inst->titulo }}</td>
            <td>{{ $inst->tipo_label }}</td>
            <td style="text-align:center;">{{ $inst->criterios->count() }}</td>
            <td style="text-align:center;">
                @if($inst->publicado)
                    <span class="badge badge-ok">Publicado</span>
                @else
                    <span class="badge badge-empty">Borrador</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p class="no-data" style="margin-top:4px;">Sin instrumentos asignados a este período.</p>
@endif

@if(!$loop->last)
<div style="height:10px;"></div>
@endif
@endforeach

</body>
</html>
