<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta Final de Calificaciones — {{ $grupo->nombre_completo ?? 'Grupo' }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans', Arial, sans-serif; font-size:7.5pt; color:#111827; line-height:1.25; }
@page { size: legal landscape; margin: .6cm .7cm; }

.titulo-acta { text-align:center; font-size:14pt; font-weight:900; letter-spacing:.04em; margin-bottom:6px; color:#111; }

/* ── Encabezado institucional ── */
.hdr { border:1.5px solid #111; margin-bottom:8px; }
.hdr table { width:100%; border-collapse:collapse; }
.hdr td { border:1px solid #111; padding:3px 5px; vertical-align:middle; font-size:7pt; }
.hdr-logo { width:70px; text-align:center; }
.hdr-logo img { max-width:60px; max-height:55px; }
.grado-badge { text-align:center; width:150px; }
.grado-num { font-size:20pt; font-weight:900; color:#1d4ed8; line-height:1; }
.grado-txt { font-size:6.5pt; font-weight:700; }
.ciclo-txt { font-size:8pt; font-weight:900; letter-spacing:.03em; }
.lbl { font-weight:800; background:#e5e7eb; font-size:6.3pt; text-transform:uppercase; white-space:nowrap; }
.val { font-weight:700; }
.chk-cell { font-size:6.3pt; }
.chk-box { display:inline-block; width:8px; height:8px; border:1px solid #111; text-align:center;
           line-height:8px; font-size:6.5pt; font-weight:900; margin-right:2px; }

/* ── Tabla principal ── */
.acta-table { width:100%; table-layout:fixed; border-collapse:collapse; font-size:6.2pt; }
.acta-table th, .acta-table td { border:1px solid #111; padding:1.5px 2px; text-align:center; vertical-align:middle; overflow:hidden; }
.acta-table thead th { background:#1e3a6e; color:#fff; font-weight:700; }
.acta-table td.col-nombre, .acta-table th.col-nombre { text-align:left !important; font-weight:600; overflow:visible; white-space:normal; }
.leyenda { font-size:5.8pt; color:#4b5563; margin-top:2px; }
.acta-table tbody tr:nth-child(even) td { background:#f8fafc; }

.firma-table { width:100%; border-collapse:collapse; margin-top:14px; }
.firma-table td { text-align:center; padding:0 10px; vertical-align:bottom; font-size:7pt; }
.firma-linea { border-top:1.3px solid #111; margin-top:24px; padding-top:3px; font-weight:700; }
.firma-cargo { font-size:6.3pt; color:#4b5563; }
</style>
</head>
<body>

<div class="titulo-acta">ACTA FINAL DE CALIFICACIONES</div>

{{-- ══════════════════════ ENCABEZADO ══════════════════════ --}}
@php
    $tandaOpts  = ['jee' => 'JEE', 'matutina' => 'MATUTINO', 'vespertina' => 'VESPERTINA', 'nocturna' => 'NOCTURNA'];
    $sectorOpts = ['publico' => 'PÚBLICO', 'privado' => 'PRIVADO', 'semioficial' => 'SEMIOFICIAL'];
    $zonaOpts   = ['rural' => 'RURAL', 'urbana' => 'URBANA', 'otra' => 'OTRA'];
@endphp
<div class="hdr">
    <table>
        <tr>
            <td class="hdr-logo" rowspan="6">
                @if($logoPath = \App\Models\ConfigInstitucional::get('logo'))
                    <img src="{{ public_path('storage/' . $logoPath) }}" alt="Logo">
                @else
                    <div style="font-size:6pt;font-weight:700;">GOBIERNO<br>REPÚBLICA<br>DOMINICANA</div>
                @endif
            </td>
            <td class="grado-badge" rowspan="6">
                <div class="grado-num" style="font-size:12pt;">{{ $grupo->grado?->nombre ?? '—' }}</div>
                <div class="ciclo-txt">PRIMER CICLO</div>
                <div class="grado-txt">NIVEL SECUNDARIO</div>
            </td>
            <td class="lbl" style="width:130px;">NOMBRE DEL CENTRO</td>
            <td class="val" colspan="3">{{ $inst['nombre_institucion'] }}</td>
            <td class="lbl" style="width:110px;">CÓDIGO DEL CENTRO</td>
            <td class="val">{{ $inst['codigo_centro'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">DIRECCIÓN REGIONAL</td>
            <td class="val" colspan="3">{{ $inst['regional'] ?: '—' }}</td>
            <td class="lbl">DISTRITO EDUCATIVO</td>
            <td class="val">{{ $inst['distrito'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">TANDA</td>
            <td class="chk-cell" colspan="3">
                @foreach($tandaOpts as $key => $label)
                    <span class="chk-box">{{ $inst['tanda'] === $key ? 'X' : '' }}</span> {{ $label }} &nbsp;
                @endforeach
            </td>
            <td class="lbl">DIRECTOR/A DISTRITO</td>
            <td class="val">{{ $inst['director_distrito'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">SECTOR</td>
            <td class="chk-cell" colspan="3">
                @foreach($sectorOpts as $key => $label)
                    <span class="chk-box">{{ $inst['sector'] === $key ? 'X' : '' }}</span> {{ $label }} &nbsp;
                @endforeach
            </td>
            <td class="lbl">DIRECTOR/A DEL CENTRO</td>
            <td class="val">{{ $inst['nombre_director'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">ZONA</td>
            <td class="chk-cell" colspan="3">
                @foreach($zonaOpts as $key => $label)
                    <span class="chk-box">{{ $inst['zona'] === $key ? 'X' : '' }}</span> {{ $label }} &nbsp;
                @endforeach
            </td>
            <td class="lbl">SECRETARIO/A DOCENTE DEL CENTRO</td>
            <td class="val">{{ $inst['secretario_docente'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">AÑO ESCOLAR</td>
            <td class="val" colspan="3">{{ $schoolYear->nombre }}</td>
            <td class="lbl">SECCIÓN</td>
            <td class="val">{{ $grupo->seccion?->nombre ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- ══════════════════════ TABLA PRINCIPAL ══════════════════════ --}}
@php
    // Anchos calculados en % para que la tabla quepa en una sola página sin
    // importar cuántas asignaturas tenga el grupo (nunca se desborda ni se
    // recorta -- se achica automáticamente si hay muchas materias).
    $wNo     = 2.5;
    $wNombre = 19;
    $wSit    = 3; // x2 columnas de Situación Final
    $numAsig = max(count($asignaciones), 1);
    $wSub    = round((100 - $wNo - $wNombre - ($wSit * 2)) / ($numAsig * 4), 3);
@endphp
<table class="acta-table">
    <colgroup>
        <col style="width:{{ $wNo }}%">
        <col style="width:{{ $wNombre }}%">
        @foreach($asignaciones as $asi)
            <col style="width:{{ $wSub }}%"><col style="width:{{ $wSub }}%"><col style="width:{{ $wSub }}%"><col style="width:{{ $wSub }}%">
        @endforeach
        <col style="width:{{ $wSit }}%"><col style="width:{{ $wSit }}%">
    </colgroup>
    <thead>
        <tr>
            <th rowspan="2">No.</th>
            <th class="col-nombre" rowspan="2">Apellidos y Nombres</th>
            @foreach($asignaciones as $asi)
                <th colspan="4">{{ \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '—', 22) }}</th>
            @endforeach
            <th colspan="2">Situación Final</th>
        </tr>
        <tr>
            @foreach($asignaciones as $asi)
                <th style="font-size:5.3pt;">F</th>
                <th style="font-size:5.3pt;">C</th>
                <th style="font-size:5.3pt;">E</th>
                <th style="font-size:5.3pt;">Es</th>
            @endforeach
            <th style="font-size:5.3pt;">Prom.</th>
            <th style="font-size:5.3pt;">Rep.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filas as $fila)
        <tr>
            <td>{{ $fila['orden'] }}</td>
            <td class="col-nombre">
                {{ $fila['matricula']->estudiante?->apellidos ?? '' }}<br>{{ $fila['matricula']->estudiante?->nombres ?? '—' }}
            </td>
            @foreach($asignaciones as $asi)
                @php $n = $fila['asignaturas'][$asi->id]; @endphp
                <td>{{ $n['final_ano'] !== null ? number_format($n['final_ano'], 0) : '—' }}</td>
                <td>{{ $n['completivo'] !== null ? number_format($n['completivo'], 0) : '' }}</td>
                <td>{{ $n['extraordinario'] !== null ? number_format($n['extraordinario'], 0) : '' }}</td>
                <td>{{ $n['especial'] !== null ? number_format($n['especial'], 0) : '' }}</td>
            @endforeach
            <td style="font-weight:900;">{{ $fila['situacion_final'] === 'A' ? 'X' : '' }}</td>
            <td style="font-weight:900;">{{ $fila['situacion_final'] === 'R' ? 'X' : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="leyenda">F = Final de Año &nbsp;·&nbsp; C = Completivo &nbsp;·&nbsp; E = Extraordinario &nbsp;·&nbsp; Es = Prueba Especial &nbsp;·&nbsp; Prom. = Promovido &nbsp;·&nbsp; Rep. = Reprobado</div>

{{-- ══════════════════════ FIRMAS ══════════════════════ --}}
<table class="firma-table">
    <tr>
        <td>
            <div class="firma-linea">{!! $grupo->tutor?->name ?: '&nbsp;' !!}</div>
            <div class="firma-cargo">Maestro/a Guía</div>
        </td>
        <td>
            <div class="firma-linea">{!! $inst['nombre_director'] ?: '&nbsp;' !!}</div>
            <div class="firma-cargo">Director/a del Centro</div>
        </td>
        <td>
            <div class="firma-linea">{!! $inst['secretario_docente'] ?: '&nbsp;' !!}</div>
            <div class="firma-cargo">Secretario/a Docente del Centro</div>
        </td>
    </tr>
</table>

</body>
</html>
