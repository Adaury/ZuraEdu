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
    tbody td { padding:4px 5px; font-size:8pt; border:1px solid #e5e7eb; vertical-align:top; }
    .badge { display:inline-block; border-radius:20px; padding:.15rem .55rem; font-size:7pt; font-weight:700; }
    .badge-ac { background:#fef9c3; color:#854d0e; }
    .badge-co { background:#fce7f3; color:#9d174d; }
    .badge-po { background:#d1fae5; color:#065f46; }
    .badge-ge { background:#f3f4f6; color:#374151; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Registro de Observaciones</h1>
    <p>
        {{ $asignacion->asignatura?->nombre ?? 'Asignatura' }} &nbsp;·&nbsp;
        {{ $asignacion->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        Docente: {{ $docente->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </p>
</div>

@if($observaciones->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">No hay observaciones registradas.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="left" style="min-width:120px;">Estudiante</th>
            <th style="width:70px;">Tipo</th>
            <th class="left">Observación</th>
            <th style="width:55px;">Fecha</th>
        </tr>
    </thead>
    <tbody>
    @foreach($observaciones as $i => $obs)
    @php
        $badgeClass = match($obs->tipo) {
            'academica'  => 'badge-ac',
            'conductual' => 'badge-co',
            'positiva'   => 'badge-po',
            default      => 'badge-ge',
        };
        $tipoLabel = match($obs->tipo) {
            'academica'  => 'Académica',
            'conductual' => 'Conductual',
            'positiva'   => 'Positiva',
            default      => ucfirst($obs->tipo ?? ''),
        };
    @endphp
    <tr>
        <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ ($obs->estudiante?->apellidos ?? '') . ', ' . ($obs->estudiante?->nombres ?? '') }}</td>
        <td style="text-align:center;"><span class="badge {{ $badgeClass }}">{{ $tipoLabel }}</span></td>
        <td>{{ $obs->texto ?? $obs->descripcion ?? '' }}</td>
        <td style="text-align:center;color:#6b7280;">{{ $obs->created_at?->format('d/m/Y') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Observaciones — {{ $asignacion->asignatura?->nombre ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Total: {{ $observaciones->count() }} registro(s)
</div>
</body>
</html>
