<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #1e293b; }
@page { size: letter landscape; margin: .8cm 1cm; }

.header { text-align: center; margin-bottom: 8px; border-bottom: 2px solid #1e3a6e; padding-bottom: 7px; }
.header .inst  { font-size: 11px; font-weight: bold; color: #1e3a6e; text-transform: uppercase; }
.header .titulo{ font-size: 10px; font-weight: bold; color: #0f172a; margin-top: 4px; }
.header .sub   { font-size: 7px; color: #6b7280; margin-top: 2px; }

/* Un mini-horario por grupo */
.grupo-section { margin-bottom: 8px; page-break-inside: avoid; }
.grupo-titulo  { background: #1e3a6e; color: #fff; font-size: 7.5px; font-weight: 700;
                 padding: 3px 6px; border-radius: 3px 3px 0 0; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #dbeafe; }
thead th { padding: 2px 3px; font-size: 6.5px; border: 1px solid #bfdbfe; text-align: center;
           font-weight: 700; color: #1e3a6e; }
thead th.franja-h { background: #1e3a6e; color: #fff; width: 48px; }
tbody td { border: 1px solid #e2e8f0; font-size: 6px; text-align: center; vertical-align: middle;
           padding: 2px; height: 18px; }
tbody td.franja-col { background: #f0f4ff; font-size: 6.5px; font-weight: 700; color: #374151;
                      border-right: 2px solid #1e3a6e; white-space: nowrap; }
.cell-asig { font-weight: 700; font-size: 6.5px; color: #1e3a6e; }
.cell-doc  { font-size: 5.5px; color: #64748b; }

.footer { margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 5px;
          display: flex; justify-content: space-between; font-size: 6.5px; color: #94a3b8; }

/* Usar 2 columnas para los grupos */
.grupos-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.grupo-wrap  { width: calc(50% - 4px); }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">HORARIO MAESTRO DEL CENTRO — TODOS LOS GRUPOS</div>
    <div class="sub">
        Año Escolar: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; Horario: {{ $horario->nombre ?? 'Publicado' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

@php
$diasLabel = ['lunes'=>'Lun','martes'=>'Mar','miercoles'=>'Mié','jueves'=>'Jue','viernes'=>'Vie','sabado'=>'Sáb'];
@endphp

<div class="grupos-grid">
@foreach($grupos as $grupo)
@php
    $grupoId  = $grupo->id;
    $grupoGrid = $grid[$grupoId] ?? [];
@endphp
<div class="grupo-wrap">
    <div class="grupo-titulo">{{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}</div>
    <table>
        <thead>
            <tr>
                <th class="franja-h">Hora</th>
                @foreach($dias as $dia)
                <th>{{ $diasLabel[$dia] ?? ucfirst(substr($dia,0,3)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($franjas as $franja)
            <tr>
                <td class="franja-col">
                    {{ $franja->hora_inicio ?? '' }}<br>
                    <span style="font-weight:400;font-size:5.5px;color:#94a3b8;">{{ $franja->hora_fin ?? '' }}</span>
                </td>
                @foreach($dias as $dia)
                @php $det = $grupoGrid[$dia][$franja->id] ?? null; @endphp
                <td>
                    @if($det)
                        <div class="cell-asig">{{ mb_strimwidth($det->asignacion?->asignatura?->nombre ?? '—', 0, 12, '…') }}</div>
                        <div class="cell-doc">{{ mb_strimwidth($det->asignacion?->docente?->apellidos ?? '', 0, 10, '…') }}</div>
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach
</div>

<div class="footer">
    <span>{{ $inst }} — Horario Maestro</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
