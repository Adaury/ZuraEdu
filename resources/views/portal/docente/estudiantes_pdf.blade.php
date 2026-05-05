<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:bold;
               padding:4px 5px; text-align:center; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 5px; font-size:8pt; border:1px solid #e5e7eb;
               text-align:center; vertical-align:middle; }
    tbody td.left { text-align:left; font-weight:600; }
    .ap { color:#065f46; font-weight:700; }
    .re { color:#991b1b; font-weight:700; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Nómina de Estudiantes</h1>
    <p>
        {{ $asignacion->asignatura?->nombre ?? 'Asignatura' }} &nbsp;·&nbsp;
        {{ $asignacion->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        Docente: {{ $docente->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th class="left" style="min-width:150px;">Apellidos, Nombres</th>
            <th style="width:80px;">Cédula</th>
            <th style="width:60px;">Nota Final</th>
            <th style="width:70px;">% Asistencia</th>
            <th style="min-width:80px;">Firma</th>
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas as $i => $m)
    @php $nota = $m->_nota; $asist = $m->_asist; @endphp
    <tr>
        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td class="left">{{ ($m->estudiante?->apellidos ?? '') . ', ' . ($m->estudiante?->nombres ?? '') }}</td>
        <td>{{ $m->estudiante?->cedula ?? '—' }}</td>
        <td class="{{ $nota !== null ? ($nota >= 65 ? 'ap' : 're') : '' }}">
            {{ $nota !== null ? number_format($nota, 1) : '—' }}
        </td>
        <td class="{{ $asist !== null ? ($asist >= 80 ? 'ap' : ($asist >= 60 ? '' : 're')) : '' }}">
            {{ $asist !== null ? $asist . '%' : '—' }}
        </td>
        <td style="height:18px;"></td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Nómina — {{ $asignacion->asignatura?->nombre ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Total: {{ $matriculas->count() }} estudiante(s) &nbsp;·&nbsp; Aprobado ≥ 65
</div>
</body>
</html>
