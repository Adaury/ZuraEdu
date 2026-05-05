<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Promoción — {{ $grupo->nombre_completo ?? 'Grupo' }}</title>
<style>
/* ═══════════════════════════════════════════════════
   RESET & BASE (landscape letter)
═══════════════════════════════════════════════════ */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 8pt;
    color: #1a1a2e;
    background: #fff;
    line-height: 1.3;
}
@page {
    size: letter landscape;
    margin: .8cm 1cm .8cm 1cm;
}

/* ═══════════════════════════════════════════════════
   ENCABEZADO INSTITUCIONAL
═══════════════════════════════════════════════════ */
.hdr-outer {
    border: 2px solid #1e3a6e;
    border-radius: 3px;
    margin-bottom: 6px;
    overflow: hidden;
}
.hdr-top {
    background: #1e3a6e;
    color: #fff;
    text-align: center;
    font-size: 6.5pt;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    padding: 2px 0;
}
.hdr-body { background: #fff; }
.hdr-table { width: 100%; border-collapse: collapse; }
.hdr-table td { padding: 6px 8px; vertical-align: middle; }

.hdr-logo-cell {
    width: 65px;
    text-align: center;
    border-right: 1px solid #e5e7eb;
}
.logo-img { height: 50px; max-width: 60px; object-fit: contain; }
.logo-abbr-box {
    width: 50px; height: 50px; border-radius: 5px;
    background: #1e3a6e; color: #fff;
    font-size: 12pt; font-weight: 900;
    display: inline-block; text-align: center; line-height: 50px;
}
.hdr-center-cell { text-align: center; }
.inst-republica { font-size: 6pt; font-weight: 700; letter-spacing: .15em;
                  text-transform: uppercase; color: #6b7280; }
.inst-minerd    { font-size: 6pt; font-weight: 700; letter-spacing: .1em;
                  text-transform: uppercase; color: #9ca3af; margin-bottom: 3px; }
.inst-nombre    { font-size: 13pt; font-weight: 900; color: #1e3a6e; line-height: 1.1; }
.inst-nivel     { font-size: 7pt; color: #4b5563; font-weight: 600; margin-top: 1px; }
.hdr-right-cell {
    width: 120px; text-align: center;
    border-left: 1px solid #e5e7eb;
    padding: 6px 8px;
}
.codigo-box { border: 1.5px solid #1e3a6e; border-radius: 4px; padding: 4px 6px; margin-bottom: 4px; }
.codigo-lbl { font-size: 6pt; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #6b7280; display: block; }
.codigo-val { font-size: 9pt; font-weight: 900; color: #1e3a6e; display: block; }
.anio-val   { font-size: 8pt; font-weight: 900; color: #1e3a6e; display: block; }

/* ── Barra de título del acta ── */
.title-bar {
    background: #1e3a6e;
    color: #fff;
    text-align: center;
    font-size: 9pt;
    font-weight: 900;
    letter-spacing: .15em;
    text-transform: uppercase;
    padding: 4px 0 3px;
    margin-bottom: 5px;
}

/* ── Ficha del grupo ── */
.ficha-grupo {
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    margin-bottom: 5px;
    overflow: hidden;
}
.ficha-grupo-header {
    background: #eef3fb;
    border-bottom: 1px solid #c7d6f0;
    padding: 2px 8px;
    font-size: 6pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #1e3a6e;
}
.ficha-grupo-body { padding: 4px 8px; }
.ficha-table-g { width: 100%; border-collapse: collapse; }
.ficha-table-g td { padding: 1px 6px; font-size: 7.5pt; }
.ficha-lbl { font-weight: 700; color: #4b5563; white-space: nowrap; }

/* ═══════════════════════════════════════════════════
   TABLA PRINCIPAL DEL ACTA
═══════════════════════════════════════════════════ */
.acta-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7pt;
    margin-bottom: 6px;
}
.acta-table th {
    background: #1e3a6e;
    color: #fff;
    padding: 3px 4px;
    text-align: center;
    font-weight: 700;
    font-size: 6.5pt;
    letter-spacing: .04em;
    border: 1px solid #1e3a6e;
    vertical-align: middle;
    white-space: nowrap;
}
.acta-table td {
    padding: 2.5px 4px;
    border: 1px solid #cbd5e1;
    vertical-align: middle;
    text-align: center;
}
.acta-table tr:nth-child(even) td { background: #f8faff; }
.acta-table tr:hover td { background: #eef3fb; }

/* columnas fijas */
.col-orden  { width: 22px; font-weight: 700; }
.col-nombre { text-align: left !important; min-width: 120px; max-width: 140px;
              font-weight: 600; white-space: nowrap; overflow: hidden; }
.col-nota   { width: 30px; }
.col-prom   { width: 36px; font-weight: 800; background: #eff6ff !important; }
.col-sit    { width: 44px; font-weight: 800; }

.sit-a { color: #065f46; background: #d1fae5 !important; }
.sit-r { color: #991b1b; background: #fee2e2 !important; }
.sit-p { color: #92400e; background: #fef3c7 !important; }

/* Totales row */
.totales-row td { background: #f0f4ff !important; font-weight: 800; font-size: 7pt; border-top: 2px solid #1e3a6e; }

/* ═══════════════════════════════════════════════════
   RESUMEN Y FIRMAS
═══════════════════════════════════════════════════ */
.resumen-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-size: 7pt;
}
.resumen-table td { padding: 2px 6px; }
.resumen-box {
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 4px 10px;
    text-align: center;
}
.resumen-val { font-size: 13pt; font-weight: 900; line-height: 1; }
.resumen-lbl { font-size: 6.5pt; color: #6b7280; font-weight: 700; text-transform: uppercase; }

.firma-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.firma-table td { text-align: center; padding: 0 8px; vertical-align: bottom; }
.firma-linea { border-top: 1.5px solid #1e3a6e; margin-top: 22px; padding-top: 3px; font-size: 7pt; }
.firma-cargo { font-size: 6.5pt; color: #6b7280; margin-top: 1px; }

.footer-bar {
    border-top: 1px solid #e5e7eb;
    margin-top: 6px;
    padding-top: 4px;
    font-size: 6pt;
    color: #9ca3af;
    text-align: center;
}
</style>
</head>
<body>

{{-- ══════════════════════ ENCABEZADO ══════════════════════ --}}
<div class="hdr-outer">
    <div class="hdr-top">República Dominicana &nbsp;·&nbsp; Ministerio de Educación (MINERD)</div>
    <div class="hdr-body">
        <table class="hdr-table">
            <tr>
                {{-- Logo --}}
                <td class="hdr-logo-cell">
                    @if(!empty($logoPath))
                        <img src="{{ public_path('storage/' . $logoPath) }}" class="logo-img" alt="Logo">
                    @else
                        <div class="logo-abbr-box">{{ strtoupper(substr($instNombre ?? 'P', 0, 2)) }}</div>
                    @endif
                </td>

                {{-- Centro --}}
                <td class="hdr-center-cell">
                    <div class="inst-republica">República Dominicana</div>
                    <div class="inst-minerd">Ministerio de Educación</div>
                    <div class="inst-nombre">{{ $instNombre ?? 'Centro Educativo' }}</div>
                    <div class="inst-nivel">Educación Secundaria</div>
                </td>

                {{-- Código / año --}}
                <td class="hdr-right-cell">
                    <div class="codigo-box">
                        <span class="codigo-lbl">Año Escolar</span>
                        <span class="codigo-val">{{ $schoolYear?->nombre ?? '—' }}</span>
                    </div>
                    <div>
                        <span style="font-size:6pt;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Fecha:</span>
                        <span class="anio-val">{{ now()->format('d/m/Y') }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- Título del acta --}}
<div class="title-bar">Acta de Promoción Final — {{ $grupo->nombre_completo }}</div>

{{-- Ficha del grupo --}}
<div class="ficha-grupo">
    <div class="ficha-grupo-header">Datos del Grupo</div>
    <div class="ficha-grupo-body">
        <table class="ficha-table-g">
            <tr>
                <td class="ficha-lbl">Grupo:</td>
                <td>{{ $grupo->nombre_completo }}</td>
                <td class="ficha-lbl">Ciclo:</td>
                <td>{{ $grupo->grado?->ciclo === 'primer_ciclo' ? 'Primer Ciclo' : 'Segundo Ciclo' }}</td>
                <td class="ficha-lbl">Tutor:</td>
                <td>{{ $grupo->tutor?->name ?? '—' }}</td>
                <td class="ficha-lbl">Total estudiantes:</td>
                <td><strong>{{ count($filas) }}</strong></td>
            </tr>
        </table>
    </div>
</div>

{{-- ══════════════════════ TABLA PRINCIPAL ══════════════════════ --}}
<table class="acta-table">
    <thead>
        <tr>
            <th class="col-orden">#</th>
            <th class="col-nombre" style="text-align:left;">Nombre del Estudiante</th>
            {{-- Columnas de asignaturas --}}
            @foreach($asignaciones as $asi)
            <th class="col-nota" title="{{ $asi->asignatura?->nombre }}">
                {{ \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '—', 10) }}
            </th>
            @endforeach
            <th class="col-prom">Prom.</th>
            <th class="col-sit">Situación</th>
        </tr>
    </thead>
    <tbody>
        @php
            $contAprobados  = 0;
            $contReprobados = 0;
            $contPendientes = 0;
            $sumaPromedios  = 0;
            $conPromedio    = 0;
        @endphp

        @foreach($filas as $fila)
        @php
            $sit = $fila['situacion'];
            if ($sit === 'A') $contAprobados++;
            elseif ($sit === 'R') $contReprobados++;
            else $contPendientes++;

            if ($fila['promedio_final'] !== null) {
                $sumaPromedios += $fila['promedio_final'];
                $conPromedio++;
            }
        @endphp
        <tr>
            <td class="col-orden">{{ $fila['orden'] }}</td>
            <td class="col-nombre">
                {{ $fila['matricula']->estudiante?->apellidos ?? '' }},
                {{ $fila['matricula']->estudiante?->nombres ?? '—' }}
            </td>
            @foreach($asignaciones as $asi)
            <td class="col-nota">
                @php $n = $fila['notas_asignaciones'][$asi->id] ?? null; @endphp
                {{ $n !== null ? number_format($n, 1) : '—' }}
            </td>
            @endforeach
            <td class="col-prom">
                {{ $fila['promedio_final'] !== null ? number_format($fila['promedio_final'], 1) : '—' }}
            </td>
            <td class="col-sit {{ $sit === 'A' ? 'sit-a' : ($sit === 'R' ? 'sit-r' : 'sit-p') }}">
                {{ $sit === 'A' ? 'APROBADO' : ($sit === 'R' ? 'REPROBADO' : 'PENDIENTE') }}
            </td>
        </tr>
        @endforeach

        {{-- Fila de totales --}}
        <tr class="totales-row">
            <td colspan="2" style="text-align:left;">TOTALES</td>
            @foreach($asignaciones as $asi)
            <td>—</td>
            @endforeach
            <td>{{ $conPromedio > 0 ? number_format($sumaPromedios / $conPromedio, 1) : '—' }}</td>
            <td style="font-size:6.5pt;">
                A:{{ $contAprobados }} / R:{{ $contReprobados }}
            </td>
        </tr>
    </tbody>
</table>

{{-- ══════════════════════ RESUMEN ══════════════════════ --}}
<table class="resumen-table">
    <tr>
        <td style="width:25%;">
            <div class="resumen-box">
                <div class="resumen-val" style="color:#1d4ed8;">{{ count($filas) }}</div>
                <div class="resumen-lbl">Total</div>
            </div>
        </td>
        <td style="width:25%;">
            <div class="resumen-box">
                <div class="resumen-val" style="color:#059669;">{{ $contAprobados }}</div>
                <div class="resumen-lbl">Aprobados</div>
            </div>
        </td>
        <td style="width:25%;">
            <div class="resumen-box">
                <div class="resumen-val" style="color:#dc2626;">{{ $contReprobados }}</div>
                <div class="resumen-lbl">Reprobados</div>
            </div>
        </td>
        <td style="width:25%;">
            <div class="resumen-box">
                <div class="resumen-val" style="color:#1d4ed8;">
                    {{ count($filas) > 0 ? number_format(($contAprobados / count($filas)) * 100, 1) : '0.0' }}%
                </div>
                <div class="resumen-lbl">% Aprobación</div>
            </div>
        </td>
    </tr>
</table>

{{-- ══════════════════════ FIRMAS ══════════════════════ --}}
<table class="firma-table">
    <tr>
        <td>
            <div class="firma-linea">{{ $grupo->tutor?->name ?? '___________________________' }}</div>
            <div class="firma-cargo">Maestro(a) Tutor(a)</div>
        </td>
        <td>
            <div class="firma-linea">___________________________</div>
            <div class="firma-cargo">Coordinador(a) Académico(a)</div>
        </td>
        <td>
            <div class="firma-linea">___________________________</div>
            <div class="firma-cargo">Director(a) del Centro</div>
        </td>
        <td>
            <div class="firma-linea">___________________________</div>
            <div class="firma-cargo">Secretaria Docente</div>
        </td>
    </tr>
</table>

{{-- Footer --}}
<div class="footer-bar">
    Generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }} &nbsp;·&nbsp;
    {{ $instNombre ?? 'Centro Educativo' }} &nbsp;·&nbsp;
    Año Escolar {{ $schoolYear?->nombre ?? '—' }}
</div>

</body>
</html>
