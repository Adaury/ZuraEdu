<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 12px; }
    .header .inst { font-size: 11pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 9pt; font-weight: bold; margin-top: 2px; }
    .header .sub { font-size: 8pt; color: #64748b; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #1e3a6e; color: #fff; font-size: 8pt; padding: 6px 4px; text-align: center; }
    td { font-size: 8pt; padding: 5px 4px; border: 1px solid #e2e8f0; text-align: center; vertical-align: middle; min-height: 30px; }
    td.hora { background: #f8fafc; font-weight: bold; font-size: 7.5pt; color: #475569; white-space: nowrap; }
    td.bloque { }
    td.vacio { color: #d1d5db; }
    .asig { font-weight: bold; font-size: 8pt; color: #1e3a6e; }
    .grupo { font-size: 7pt; color: #64748b; }
    .aula { font-size: 6.5pt; color: #9ca3af; font-style: italic; }
    .footer { margin-top: 10px; text-align: right; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Horario — {{ $docente->nombre_completo }}</div>
    <div class="sub">{{ $schoolYear?->nombre ?? '' }} — Generado el {{ now()->format('d/m/Y H:i') }}</div>
</div>

@php
    $diasNombres = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes'];
@endphp

<table>
    <thead>
        <tr>
            <th style="width:80px;">Hora</th>
            @foreach($dias as $dia)
            <th>{{ $diasNombres[$dia] ?? ucfirst($dia) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($franjas as $franja)
        <tr>
            <td class="hora">{{ $franja->hora_inicio ?? '' }}<br>{{ $franja->hora_fin ?? '' }}</td>
            @foreach($dias as $dia)
            @php $det = $grid[$franja->id][$dia] ?? null; @endphp
            @if($det)
            <td class="bloque">
                <div class="asig">{{ $det->asignacion?->asignatura?->nombre ?? '—' }}</div>
                <div class="grupo">{{ $det->asignacion?->grupo?->nombre_completo ?? '' }}</div>
                @if($det->aula)
                <div class="aula">{{ $det->aula->nombre ?? '' }}</div>
                @endif
            </td>
            @else
            <td class="vacio">—</td>
            @endif
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
