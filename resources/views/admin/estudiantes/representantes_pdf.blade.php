<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7pt; font-weight:bold;
               padding:4px 5px; text-align:center; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:3.5px 5px; font-size:7.5pt; border:1px solid #e5e7eb;
               text-align:center; vertical-align:middle; }
    tbody td.left { text-align:left; }
    .footer { margin-top:12px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Directorio de Representantes</h1>
    <p>Año escolar: {{ $sy?->nombre ?? date('Y') }} &nbsp;·&nbsp; Total: {{ $representantes->count() }} representante(s) &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="left" style="min-width:120px;">Apellidos y Nombres</th>
            <th style="width:75px;">Cédula</th>
            <th style="width:75px;">Teléfono</th>
            <th class="left" style="min-width:110px;">Email</th>
            <th class="left" style="min-width:100px;">Hijo(s)</th>
            <th style="width:80px;">Grupo(s)</th>
        </tr>
    </thead>
    <tbody>
    @foreach($representantes as $i => $rep)
    @php
        $hijos  = $rep->estudiantes->map(fn($e) => trim($e->apellidos . ' ' . $e->nombres))->implode('; ');
        $grupos = $rep->estudiantes->flatMap(fn($e) => $e->matriculas->map(fn($m) =>
            $m->grupo ? (($m->grupo->grado?->nombre ?? '') . ' ' . ($m->grupo->seccion?->nombre ?? '')) : null
        ))->filter()->unique()->implode('; ');
    @endphp
    <tr>
        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td class="left" style="font-weight:600;">{{ trim(($rep->apellidos ?? '') . ', ' . ($rep->nombres ?? '')) }}</td>
        <td>{{ $rep->cedula ?? '—' }}</td>
        <td>{{ $rep->telefono ?? '—' }}</td>
        <td class="left">{{ $rep->email ?? '—' }}</td>
        <td class="left">{{ $hijos ?: '—' }}</td>
        <td>{{ $grupos ?: '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Directorio de Representantes &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
