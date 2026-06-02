<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1e293b; }
    .header { text-align: center; margin-bottom: 18px; border-bottom: 3px solid #1e3a6e; padding-bottom: 10px; }
    .header h1 { font-size: 13pt; color: #1e3a6e; font-weight: bold; }
    .header p { font-size: 9pt; color: #6b7280; }
    .section-title { background: #1e3a6e; color: #fff; padding: 5px 10px; font-size: 9pt; font-weight: bold; margin: 12px 0 0; }
    table { width: 100%; border-collapse: collapse; }
    thead tr th { background: #eff6ff; color: #1e3a6e; font-size: 8.5pt; padding: 5px 7px; border: 1px solid #bfdbfe; text-align: center; }
    tbody tr td { font-size: 8.5pt; padding: 4px 7px; border: 1px solid #e2e8f0; text-align: center; }
    tbody tr td:first-child { text-align: left; }
    .subtotal td { background: #dbeafe; font-weight: bold; }
    .total-row td { background: #1e3a6e; color: #fff; font-weight: bold; padding: 6px 7px; }
    .even { background: #f8faff; }
    .footer { margin-top: 20px; font-size: 8pt; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $si }}</h1>
    @if($cod)<p>Código del Centro: {{ $cod }}</p>@endif
    <p>Reporte Consolidado de Matrícula — {{ $schoolYear?->nombre ?? '' }}</p>
    <p>Generado el {{ now()->format('d/m/Y') }}
        @if($dir) — Director/a: {{ $dir }} @endif
    </p>
</div>

@foreach($reporte as $gradoNombre => $grupos)
<div class="section-title">{{ $gradoNombre }}</div>
<table>
    <thead>
        <tr>
            <th style="text-align:left;">Sección</th>
            <th>Total</th>
            <th>Masculino</th>
            <th>Femenino</th>
            <th>Activos</th>
            <th>Retirados</th>
            <th>Transferidos</th>
            <th>% Activos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grupos as $i => $g)
        @php $pct = $g->total > 0 ? round($g->activos / $g->total * 100) : 0; @endphp
        <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
            <td>{{ $gradoNombre }} {{ $g->seccion }}</td>
            <td>{{ $g->total }}</td>
            <td>{{ $g->masculino }}</td>
            <td>{{ $g->femenino }}</td>
            <td>{{ $g->activos }}</td>
            <td>{{ $g->retirados }}</td>
            <td>{{ $g->transferidos }}</td>
            <td>{{ $pct }}%</td>
        </tr>
        @endforeach
        <tr class="subtotal">
            <td>Subtotal</td>
            <td>{{ $grupos->sum('total') }}</td>
            <td>{{ $grupos->sum('masculino') }}</td>
            <td>{{ $grupos->sum('femenino') }}</td>
            <td>{{ $grupos->sum('activos') }}</td>
            <td>{{ $grupos->sum('retirados') }}</td>
            <td>{{ $grupos->sum('transferidos') }}</td>
            <td>{{ $grupos->sum('total') > 0 ? round($grupos->sum('activos')/$grupos->sum('total')*100) : 0 }}%</td>
        </tr>
    </tbody>
</table>
@endforeach

<br>
<table>
    <tbody>
        <tr class="total-row">
            <td>TOTAL GENERAL</td>
            <td>{{ $totales['total'] }}</td>
            <td>{{ $totales['masculino'] }}</td>
            <td>{{ $totales['femenino'] }}</td>
            <td>{{ $totales['activos'] }}</td>
            <td>{{ $totales['retirados'] }}</td>
            <td>{{ $totales['transferidos'] }}</td>
            <td>{{ $totales['total'] > 0 ? round($totales['activos']/$totales['total']*100) : 0 }}%</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    Sistema de Gestión Educativa — {{ config('app.name') }} — {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
