<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 14px; }
    .header .inst { font-size: 11pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 9.5pt; font-weight: bold; color: #1e3a6e; margin-top: 3px; }
    .header .sub { font-size: 8pt; color: #64748b; margin-top: 2px; }
    .resumen { display: flex; gap: 10px; margin-bottom: 12px; }
    .resumen-box { flex: 1; border-radius: 6px; padding: 8px 10px; text-align: center; }
    .resumen-box .num { font-size: 16pt; font-weight: bold; }
    .resumen-box .lbl { font-size: 7.5pt; color: #64748b; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #1e3a6e; color: #fff; font-size: 8pt; padding: 5px 6px; text-align: left; }
    td { font-size: 8pt; padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
    .con { color: #065f46; font-weight: bold; }
    .sin { color: #991b1b; font-weight: bold; }
    .badge-con { background: #d1fae5; color: #065f46; border-radius: 4px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
    .badge-sin { background: #fee2e2; color: #991b1b; border-radius: 4px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
    .footer { margin-top: 14px; text-align: right; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Cumplimiento de Planificaciones — {{ $schoolYear->nombre }}</div>
    <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }}</div>
</div>

@php
    $total    = $asignaciones->count();
    $conPlan  = $asignaciones->filter(fn($a) => $planIds->contains($a->id))->count();
    $sinPlan  = $total - $conPlan;
    $pct      = $total > 0 ? round($conPlan / $total * 100) : 0;
@endphp

<table style="width:100%;margin-bottom:12px;border-collapse:collapse;">
    <tr>
        <td style="width:25%;text-align:center;background:#eff6ff;border-radius:6px;padding:8px;">
            <div style="font-size:16pt;font-weight:bold;color:#1e3a6e;">{{ $total }}</div>
            <div style="font-size:7.5pt;color:#64748b;">Total Asignaciones</div>
        </td>
        <td style="width:5%;"></td>
        <td style="width:25%;text-align:center;background:#d1fae5;border-radius:6px;padding:8px;">
            <div style="font-size:16pt;font-weight:bold;color:#065f46;">{{ $conPlan }}</div>
            <div style="font-size:7.5pt;color:#064e3b;">Con Planificación</div>
        </td>
        <td style="width:5%;"></td>
        <td style="width:25%;text-align:center;background:#fee2e2;border-radius:6px;padding:8px;">
            <div style="font-size:16pt;font-weight:bold;color:#991b1b;">{{ $sinPlan }}</div>
            <div style="font-size:7.5pt;color:#7f1d1d;">Sin Planificación</div>
        </td>
        <td style="width:5%;"></td>
        <td style="width:25%;text-align:center;background:#fef9c3;border-radius:6px;padding:8px;">
            <div style="font-size:16pt;font-weight:bold;color:#92400e;">{{ $pct }}%</div>
            <div style="font-size:7.5pt;color:#78350f;">Cumplimiento</div>
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Docente</th>
            <th>Asignatura</th>
            <th>Grupo</th>
            <th>Grado</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($asignaciones as $i => $asig)
        @php $tiene = $planIds->contains($asig->id); @endphp
        <tr style="background:{{ $i % 2 === 1 ? '#f8fafc' : '#ffffff' }};">
            <td>{{ $i + 1 }}</td>
            <td>{{ $asig->docente?->nombre_completo ?? '(Sin docente)' }}</td>
            <td>{{ $asig->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $asig->grupo?->seccion?->nombre ?? '—' }}</td>
            <td>{{ $asig->grupo?->grado?->nombre ?? '—' }}</td>
            <td style="text-align:center;">
                @if($tiene)
                    <span class="badge-con">Con planificación</span>
                @else
                    <span class="badge-sin">Sin planificación</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
