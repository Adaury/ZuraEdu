<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; background: #fff; }
    .header { background: #1e3a8a; color: #fff; padding: 14px 20px; border-radius: 8px 8px 0 0; margin-bottom: 0; }
    .header h1 { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
    .header p  { font-size: 10px; opacity: .85; }
    .doc-meta  { background: #f1f5f9; padding: 7px 20px; font-size: 10px; color: #475569; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
    table { width: 100%; border-collapse: collapse; }
    thead th { background: #1e3a8a; color: #fff; padding: 7px 9px; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; text-align: left; }
    tbody td { padding: 6px 9px; border-bottom: 1px solid #f1f5f9; font-size: 10px; vertical-align: middle; }
    tbody tr:nth-child(even) td { background: #dbeafe; }
    .center { text-align: center; }
    .badge { border-radius: 4px; padding: 2px 7px; font-size: 9px; font-weight: 700; }
    .badge-excelente  { background: #dcfce7; color: #166534; }
    .badge-bueno      { background: #dbeafe; color: #1e40af; }
    .badge-regular    { background: #fef9c3; color: #854d0e; }
    .badge-deficiente { background: #fee2e2; color: #991b1b; }
    .stars { color: #f59e0b; font-size: 11px; }
    .footer { margin-top: 14px; text-align: right; font-size: 9px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Evaluaciones de Desempeño Docente</h1>
    <p>Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; {{ $evaluaciones->count() }} evaluación(es)</p>
</div>
<div class="doc-meta">
    Total registros: <strong>{{ $evaluaciones->count() }}</strong>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Docente</th>
            <th>Período</th>
            <th class="center">Punt.</th>
            <th class="center">Dom.</th>
            <th class="center">Met.</th>
            <th class="center">Rel.</th>
            <th class="center">Plan.</th>
            <th class="center">Prom.</th>
            <th>Nivel</th>
            <th>Evaluador</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @foreach($evaluaciones as $i => $ev)
        @php
            $nivel = $ev->nivelDesempeno();
            $badgeClass = match($nivel['label'] ?? '') {
                'Excelente'  => 'badge-excelente',
                'Bueno'      => 'badge-bueno',
                'Regular'    => 'badge-regular',
                default      => 'badge-deficiente',
            };
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $ev->docente?->nombre_completo ?? '—' }}</strong></td>
            <td>{{ $ev->periodo_evaluado }}</td>
            <td class="center">{{ $ev->puntualidad }}/5</td>
            <td class="center">{{ $ev->dominio_contenido }}/5</td>
            <td class="center">{{ $ev->metodologia }}/5</td>
            <td class="center">{{ $ev->relacion_estudiantes }}/5</td>
            <td class="center">{{ $ev->planificacion }}/5</td>
            <td class="center"><strong>{{ number_format($ev->promedio_calculado, 2) }}</strong></td>
            <td><span class="badge {{ $badgeClass }}">{{ $nivel['label'] ?? '—' }}</span></td>
            <td>{{ $ev->evaluador?->name ?? '—' }}</td>
            <td>{{ $ev->created_at?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
