<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:11px 16px; margin-bottom:12px; }
    .header h1 { font-size:13pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:8pt; opacity:.85; }
    .meta { font-size:8pt; color:#6b7280; margin-bottom:10px; }
    .meta span { background:#f1f5f9; padding:2px 8px; border-radius:4px; margin-right:6px; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:bold; padding:5px 6px; text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 6px; font-size:8pt; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
    .badge-tecnica      { background:#dbeafe; color:#1d4ed8; padding:1px 5px; border-radius:3px; font-size:7pt; font-weight:700; }
    .badge-academica    { background:#dcfce7; color:#166534; padding:1px 5px; border-radius:3px; font-size:7pt; font-weight:700; }
    .badge-sin-doc      { background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:3px; font-size:7pt; }
    .footer { margin-top:18px; font-size:7pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:7px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Asignaciones</h1>
    <p>{{ $sy?->nombre ?? '' }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="meta">
    <span><strong>Total:</strong> {{ $asignaciones->count() }} asignaciones</span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th>Asignatura</th>
            <th style="width:60px;">Área</th>
            <th>Grupo</th>
            <th>Docente</th>
        </tr>
    </thead>
    <tbody>
    @foreach($asignaciones as $i => $a)
    <tr>
        <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $a->asignatura?->nombre ?? '—' }}</td>
        <td>
            @if($a->area === 'tecnica')
            <span class="badge-tecnica">Técnica</span>
            @else
            <span class="badge-academica">Académica</span>
            @endif
        </td>
        <td>{{ ($a->grupo?->grado?->nombre ?? '') . ' ' . ($a->grupo?->seccion?->nombre ?? '') }}</td>
        <td>
            @if($a->docente)
            {{ $a->docente->nombre_completo }}
            @else
            <span class="badge-sin-doc">Sin asignar</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Lista de Asignaciones &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
