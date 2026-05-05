<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 0; }
    .header { background: #1e3a6e; color: #fff; padding: 12px 16px; margin-bottom: 14px; }
    .header h1 { font-size: 13pt; margin: 0 0 2px; }
    .header p  { font-size: 8pt; margin: 0; opacity: .85; }
    .info-row { display: flex; gap: 20px; margin-bottom: 12px; font-size: 8.5pt; }
    .info-box { background: #f0f4f8; border-radius: 4px; padding: 6px 10px; }
    .info-box strong { display: block; font-size: 7.5pt; color: #6b7280; text-transform: uppercase; }
    h3 { font-size: 9.5pt; color: #1e3a6e; margin: 10px 0 4px; border-bottom: 1.5px solid #1e3a6e; padding-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table th { background: #1e3a6e; color: #fff; font-size: 7.5pt; padding: 4px 6px; text-align: center; }
    table td { font-size: 8pt; padding: 3px 6px; border: 1px solid #e5e7eb; text-align: center; }
    table tr:nth-child(even) td { background: #f8faff; }
    .badge-p { background: #dcfce7; color: #166534; padding: 1px 5px; border-radius: 3px; font-size: 7pt; }
    .badge-a { background: #fee2e2; color: #991b1b; padding: 1px 5px; border-radius: 3px; font-size: 7pt; }
    .badge-t { background: #fef3c7; color: #92400e; padding: 1px 5px; border-radius: 3px; font-size: 7pt; }
    .footer { margin-top: 18px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 7.5pt; color: #9ca3af; text-align: center; }
    .semaforo { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
    .sem-g { background: #22c55e; }
    .sem-y { background: #f59e0b; }
    .sem-r { background: #ef4444; }
</style>
</head>
<body>

<div class="header">
    <h1>Reporte Mensual de Asistencia</h1>
    <p>Politécnico Salesiano Arquides Calderón (PSAC) &mdash; {{ $nombreMes }}</p>
</div>

<table style="margin-bottom:12px;border:none;">
    <tr>
        <td style="border:none;padding:0 8px 0 0;width:33%;">
            <div class="info-box">
                <strong>Estudiante</strong>
                {{ $matricula->estudiante?->apellidos ?? '—' }}, {{ $matricula->estudiante?->nombres ?? '' }}
            </div>
        </td>
        <td style="border:none;padding:0 8px 0 0;width:33%;">
            <div class="info-box">
                <strong>Grupo</strong>
                {{ $matricula->grupo?->nombre_completo ?? '—' }}
            </div>
        </td>
        <td style="border:none;padding:0;width:33%;">
            <div class="info-box">
                <strong>Período</strong>
                {{ $nombreMes }}
            </div>
        </td>
    </tr>
</table>

@if($porAsignacion->isEmpty())
    <p style="color:#9ca3af;text-align:center;margin:30px 0;">No hay registros de asistencia para este mes.</p>
@else

@foreach($porAsignacion as $asiId => $data)
<h3>{{ $data['asignatura'] }}</h3>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data['registros'] as $reg)
    <tr>
        <td>{{ \Carbon\Carbon::parse($reg->fecha)->format('d/m/Y') }}</td>
        <td>
            @php $e = $reg->estado; @endphp
            @if(in_array($e, ['presente','tarde']))
                <span class="badge-p">{{ ucfirst($e) }}</span>
            @else
                <span class="badge-a">{{ ucfirst($e) }}</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
<table style="margin-top:-6px;margin-bottom:12px;">
    <thead>
        <tr>
            <th>Total clases</th>
            <th>Presente</th>
            <th>Ausente</th>
            <th>Tarde</th>
            <th>Excusa</th>
            <th>% Asistencia</th>
        </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $data['total'] }}</td>
        <td>{{ $data['presente'] }}</td>
        <td>{{ $data['ausente'] }}</td>
        <td>{{ $data['tarde'] }}</td>
        <td>{{ $data['excusa'] }}</td>
        <td>
            @php $pct = $data['pct']; @endphp
            @if($pct !== null)
                <span class="{{ $pct >= 80 ? 'badge-p' : ($pct >= 70 ? 'badge-t' : 'badge-a') }}">{{ $pct }}%</span>
            @else —
            @endif
        </td>
    </tr>
    </tbody>
</table>
@endforeach

@endif

<div class="footer">
    Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} &mdash; SGE · PSAC &mdash; AprendeTicPaulino
</div>
</body>
</html>
