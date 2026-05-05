<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:8px 12px; margin-bottom:8px; }
    .header h1 { font-size:10pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:6.5pt; font-weight:bold;
               padding:3px 4px; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f0f4ff; }
    tbody td { padding:3px 4px; font-size:7pt; border:1px solid #e5e7eb; vertical-align:middle; }
    tbody td.center { text-align:center; }
    .footer { margin-top:10px; font-size:6.5pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:5px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Directorio de Estudiantes</h1>
    <p>
        {{ $sy?->nombre ?? date('Y') }} &nbsp;·&nbsp;
        @if($ciclo) Ciclo: {{ $ciclo }} &nbsp;·&nbsp; @endif
        {{ $estudiantes->count() }} estudiante(s) &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </p>
</div>

@if($estudiantes->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">Sin estudiantes registrados.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th style="width:55px;" class="left">Matrícula</th>
            <th class="left" style="min-width:110px;">Apellidos</th>
            <th class="left" style="min-width:90px;">Nombres</th>
            <th style="width:65px;">Cédula</th>
            <th style="width:55px;">F. Nac.</th>
            <th style="width:30px;">Sx</th>
            <th style="width:70px;">Grupo</th>
            <th class="left" style="min-width:90px;">Representante</th>
            <th style="width:60px;">Teléfono</th>
        </tr>
    </thead>
    <tbody>
    @foreach($estudiantes as $i => $est)
    @php $mat = $est->matriculas->first(); $rep = $est->representantes->first(); @endphp
    <tr>
        <td class="center" style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td>{{ $est->matricula ?? '—' }}</td>
        <td style="font-weight:600;">{{ $est->apellidos ?? $est->apellido ?? '—' }}</td>
        <td>{{ $est->nombres ?? $est->nombre ?? '—' }}</td>
        <td class="center">{{ $est->cedula ?? '—' }}</td>
        <td class="center">{{ $est->fecha_nacimiento ? \Carbon\Carbon::parse($est->fecha_nacimiento)->format('d/m/Y') : '—' }}</td>
        <td class="center">{{ strtoupper(substr($est->sexo ?? '—', 0, 1)) }}</td>
        <td class="center">
            {{ $mat?->grupo ? ($mat->grupo->grado->nombre ?? '') . ' ' . ($mat->grupo->seccion->nombre ?? '') : '—' }}
        </td>
        <td>{{ $rep ? trim(($rep->nombres ?? $rep->nombre ?? '') . ' ' . ($rep->apellidos ?? $rep->apellido ?? '')) : '—' }}</td>
        <td class="center">{{ $rep?->celular ?? $rep?->telefono ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Directorio de Estudiantes &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Total: {{ $estudiantes->count() }} estudiante(s)
</div>
</body>
</html>
