<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1e293b; }
@page { size: letter landscape; margin: 1cm 1.2cm; }

.header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #1e3a6e; padding-bottom: 8px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e3a6e; text-transform: uppercase; }
.header .titulo{ font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 4px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 2px; }

.meta { display: flex; gap: 16px; margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 10px; background: #f8faff; }
.meta-item { flex: 1; }
.meta-lbl { font-size: 6.5px; font-weight: 700; text-transform: uppercase; color: #94a3b8; }
.meta-val { font-size: 9px; font-weight: 700; color: #1e293b; }
.meta-line { flex: 1; border-bottom: 1px dashed #94a3b8; min-width: 120px; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #1e3a6e; color: #fff; }
thead th { padding: 4px 3px; font-size: 7px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; padding-left: 6px; }
tbody tr { height: 18px; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody td { border: 1px solid #d1d5db; font-size: 7.5px; text-align: center; vertical-align: middle; padding: 2px 3px; }
tbody td.name { text-align: left; padding-left: 5px; font-weight: 600; min-width: 120px; }
.cell-empty { background: #fff; }

.footer { margin-top: 10px; display: flex; gap: 28px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 7.5px; color: #475569; margin-top: 22px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">LISTA DE ASISTENCIA</div>
    <div class="sub">{{ $asignacion->asignatura?->nombre ?? '—' }} — {{ $asignacion->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp; Año Escolar: {{ $sy?->nombre ?? '—' }}</div>
</div>

<div class="meta">
    <div class="meta-item">
        <div class="meta-lbl">Docente</div>
        <div class="meta-val">{{ $asignacion->docente?->nombre_completo ?? '____________________________________' }}</div>
    </div>
    <div class="meta-item">
        <div class="meta-lbl">Asignatura</div>
        <div class="meta-val">{{ $asignacion->asignatura?->nombre ?? '—' }}</div>
    </div>
    <div class="meta-item">
        <div class="meta-lbl">Grupo</div>
        <div class="meta-val">{{ $asignacion->grupo?->nombre_completo ?? '—' }}</div>
    </div>
    <div class="meta-item">
        <div class="meta-lbl">Período</div>
        <div class="meta-val">___________________________</div>
    </div>
    <div class="meta-item">
        <div class="meta-lbl">Total Estudiantes</div>
        <div class="meta-val">{{ $matriculas->count() }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th class="left" style="width:130px;">Nombre del Estudiante</th>
            @for($c = 1; $c <= $numColumnas; $c++)
            <th style="width:{{ max(20, intval(420 / $numColumnas)) }}px;">
                Clase {{ $c }}<br>
                <span style="font-size:5.5px;opacity:.7;">Fecha:</span><br>
                <span style="font-size:5.5px;opacity:.7;">___/___</span>
            </th>
            @endfor
            <th style="width:35px;">Total<br>Asis.</th>
            <th style="width:35px;">Total<br>Aus.</th>
            <th style="width:35px;">%</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="name">{{ ($mat->estudiante->apellidos ?? $mat->estudiante->apellido ?? '') . ', ' . ($mat->estudiante->nombres ?? $mat->estudiante->nombre ?? '') }}</td>
            @for($c = 1; $c <= $numColumnas; $c++)
            <td class="cell-empty">&nbsp;</td>
            @endfor
            <td></td>
            <td></td>
            <td></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <div class="firma-box">Docente: {{ $asignacion->docente?->nombre_completo ?? '' }}</div>
    <div class="firma-box">Coordinador/a Académico</div>
    <div class="firma-box">Generado: {{ now()->format('d/m/Y') }}</div>
</div>
</body>
</html>
