<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1a1a2e; }
    @page { margin: 1cm 1.2cm; }

    .header { text-align: center; margin-bottom: 4px; }
    .header .inst { font-size: 13pt; font-weight: 900; color: #1e3a6e; }
    .header .titulo { font-size: 10pt; font-weight: 700; color: #c0392b; text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }
    .header .fecha { font-size: 8pt; color: #6b7280; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead th {
        background: #1e3a6e; color: #fff; padding: 5px 6px; text-align: left;
        font-size: 8pt; text-transform: uppercase; letter-spacing: .04em;
    }
    tbody td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 8.5pt; vertical-align: top; }
    tbody tr:nth-child(even) { background: #f9fbff; }

    .muted { color: #9ca3af; font-size: 7.5pt; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7.5pt; font-weight: 700; }
    .b-success  { background: #d1fae5; color: #065f46; }
    .b-warning  { background: #fef3c7; color: #92400e; }
    .b-info     { background: #dbeafe; color: #1e40af; }
    .b-danger   { background: #fee2e2; color: #991b1b; }
    .b-secondary{ background: #f3f4f6; color: #4b5563; }

    .footer { text-align: center; font-size: 7pt; color: #9ca3af; margin-top: 10px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">Historial de Accesos — Carnet+</div>
    <div class="fecha">Fecha: {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>Persona</th>
            <th>Carnet</th>
            <th>Grupo</th>
            <th>Evento</th>
            <th>Estado</th>
            <th>Zona</th>
            <th>Hora</th>
            <th>Dispositivo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($accesos as $acc)
        @php $badge = $acc->estado_badge; @endphp
        <tr>
            <td>{{ $acc->carnet?->nombre_completo ?? '—' }}</td>
            <td class="muted">{{ $acc->carnet?->numero_carnet ?? '—' }}</td>
            <td>{{ $acc->carnet?->matricula?->grupo?->nombre_completo ?? '—' }}</td>
            <td>{{ \App\Models\CarnetAcceso::TIPOS_EVENTO[$acc->tipo_evento] ?? $acc->tipo_evento }}</td>
            <td><span class="badge b-{{ $badge['color'] }}">{{ $badge['label'] }}</span></td>
            <td>{{ $acc->zona?->nombre ?? '—' }}</td>
            <td>{{ $acc->hora }}</td>
            <td class="muted">{{ $acc->dispositivo ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;padding:20px;color:#9ca3af;">
                No hay registros de acceso para esta fecha.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Generado el {{ now()->format('d/m/Y H:i') }} — ZuraEdu Carnet+</div>

</body>
</html>
