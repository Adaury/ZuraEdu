<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Horario — {{ $estudiante->nombre_completo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:8.5pt; color:#1a1a2e; }
@page { size:letter landscape; margin:1cm 1.4cm; }

.hdr { border:2px solid #1e3a6e; border-radius:4px; margin-bottom:.75rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:7px 12px; display:flex; align-items:center; gap:12px; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:1px; }

.doc-title { text-align:center; margin:.5rem 0 .2rem; font-size:11pt; font-weight:900; color:#1e3a6e; }
.doc-meta  { text-align:center; font-size:8pt; color:#6b7280; margin-bottom:.6rem; }

table { width:100%; border-collapse:collapse; }
th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:700;
     padding:5px 7px; text-align:center; }
.franja-col { background:#f0f4ff; font-size:7.5pt; font-weight:700; color:#374151;
              padding:5px 7px; text-align:center; border-right:2px solid #1e3a6e;
              white-space:nowrap; }
td { padding:5px 6px; border:1px solid #d1d5db; text-align:center; vertical-align:middle;
     font-size:7.5pt; }
.cell-mat  { font-weight:700; font-size:8pt; color:#1e3a6e; }
.cell-doc  { font-size:6.5pt; color:#6b7280; margin-top:1px; }
.cell-aula { font-size:6.5pt; color:#9ca3af; }
td.empty   { background:#fafafa; }
.footer { margin-top:.6rem; font-size:7pt; color:#9ca3af; display:flex; justify-content:space-between; }
</style>
</head>
<body>

<div class="hdr">
    <div class="hdr-top">{{ strtoupper($inst) }} — HORARIO DE CLASES</div>
    <div class="hdr-body">
        <div>
            <div class="inst-name">{{ $inst }}</div>
            <div class="inst-sub">
                Estudiante: <strong>{{ $estudiante->nombre_completo }}</strong>
                &nbsp;·&nbsp;
                Grupo: <strong>{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</strong>
                &nbsp;·&nbsp;
                Año: <strong>{{ $schoolYear->nombre }}</strong>
            </div>
        </div>
    </div>
</div>

@php
    $diasLabel = [
        'lunes'     => 'Lunes',
        'martes'    => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves'    => 'Jueves',
        'viernes'   => 'Viernes',
        'sabado'    => 'Sábado',
    ];
    $dias = is_array($diasConfig) ? $diasConfig : ['lunes','martes','miercoles','jueves','viernes'];
@endphp

<table>
    <thead>
        <tr>
            <th style="width:80px;">Hora</th>
            @foreach($dias as $dia)
            <th>{{ $diasLabel[$dia] ?? ucfirst($dia) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($franjasHorario as $franja)
        <tr>
            <td class="franja-col">
                {{ $franja->hora_inicio ?? '' }}<br>
                <span style="font-weight:400;color:#9ca3af;">{{ $franja->hora_fin ?? '' }}</span>
            </td>
            @foreach($dias as $dia)
            @php $detalle = $gridHorario[$franja->id][$dia] ?? null; @endphp
            <td class="{{ $detalle ? '' : 'empty' }}">
                @if($detalle)
                    <div class="cell-mat">{{ $detalle->asignacion?->asignatura?->nombre ?? '—' }}</div>
                    @if($detalle->asignacion?->docente)
                    <div class="cell-doc">{{ $detalle->asignacion->docente->apellidos ?? '' }}</div>
                    @endif
                    @if($detalle->aula)
                    <div class="cell-aula">{{ $detalle->aula->nombre ?? '' }}</div>
                    @endif
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — {{ $estudiante->nombre_completo }}</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
