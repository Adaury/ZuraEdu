<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { background: #1e3a6e; color:#fff; padding: 11px 16px; margin-bottom:12px; }
    .header h1 { font-size:13pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:8pt; opacity:.85; }
    .meta { font-size:8pt; color:#6b7280; margin-bottom:10px; }
    .meta span { background:#f1f5f9; padding:2px 8px; border-radius:4px; margin-right:6px; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:bold; padding:5px 6px; text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 6px; font-size:8pt; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
    .footer { margin-top:18px; font-size:7pt; color:#9ca3af; text-align:center; border-top:1px solid #e5e7eb; padding-top:7px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Lista de Matriculados</h1>
    <p>{{ $schoolYear?->nombre ?? '' }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="meta">
    <span><strong>Total:</strong> {{ $matriculas->count() }} estudiantes</span>
    <span><strong>Estado:</strong> Activas</span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th>Apellidos</th>
            <th>Nombres</th>
            <th>No. Matrícula</th>
            <th>Cédula</th>
            <th>Grupo</th>
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas as $i => $m)
    <tr>
        <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $m->estudiante?->apellidos ?? '—' }}</td>
        <td>{{ $m->estudiante?->nombres ?? '—' }}</td>
        <td style="font-family:monospace;font-size:7.5pt;">{{ $m->numero_matricula ?? '—' }}</td>
        <td style="font-size:7.5pt;color:#374151;">{{ $m->estudiante?->cedula ?? '—' }}</td>
        <td>{{ ($m->grupo?->grado?->nombre ?? '') . ' ' . ($m->grupo?->seccion?->nombre ?? '') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Lista de Matriculados &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
