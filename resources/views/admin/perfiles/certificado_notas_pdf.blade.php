<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }

.border-page { border: 3px solid #1e40af; padding: 18px; min-height: 680px; position: relative; }
.inner-border { border: 1px solid #bfdbfe; padding: 14px; min-height: 654px; }

.header { text-align: center; margin-bottom: 16px; }
.header .logo-area { margin-bottom: 8px; }
.header .inst  { font-size: 14px; font-weight: bold; color: #1e40af; text-transform: uppercase; letter-spacing:.04em; }
.header .inst2 { font-size: 9px; color: #475569; margin-top: 2px; }
.header .sep   { border: none; border-top: 2px solid #1e40af; margin: 8px 0; }
.header .titulo{ font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 6px; letter-spacing:.03em; }
.header .subtitulo { font-size: 9.5px; color: #1e40af; font-weight: 600; margin-top: 3px; }

.estudiante-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;
                   padding: 10px 14px; margin-bottom: 14px; }
.est-row { display: flex; gap: 20px; flex-wrap: wrap; }
.est-col { flex: 1; }
.est-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px; }
.est-val { font-size: 10.5px; font-weight: 700; color: #1e293b; }

.certifica { text-align: center; margin: 12px 0 10px; font-size: 11px; color: #374151;
             line-height: 1.6; }
.certifica strong { color: #1e40af; font-size: 13px; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 8px; font-size: 8.5px; border: 1px solid #1e3a8a; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 5px 8px; border: 1px solid #bfdbfe; font-size: 9px; text-align: center; }
tbody td.left { text-align: left; }

.nota-ap  { color: #15803d; font-weight: 800; }
.nota-rep { color: #dc2626; font-weight: 800; }

.resumen-row { display: flex; gap: 12px; margin-bottom: 14px; }
.resumen-chip { flex: 1; text-align: center; padding: 7px; border-radius: 5px; border: 1px solid #e2e8f0; }
.resumen-chip .num { font-size: 15px; font-weight: 800; }
.resumen-chip .lbl { font-size: 7.5px; color: #6b7280; margin-top: 1px; }
.chip-prom   { background: #eff6ff; } .chip-prom .num { color: #1d4ed8; }
.chip-apro   { background: #dcfce7; } .chip-apro .num { color: #15803d; }
.chip-repr   { background: #fee2e2; } .chip-repr .num { color: #dc2626; }

.certifica-text { font-size: 9.5px; color: #374151; line-height: 1.6; margin: 10px 0; text-align: justify; }

.firmas { display: flex; gap: 24px; margin-top: 24px; }
.firma  { flex: 1; text-align: center; }
.firma-line { border-top: 1px solid #1e40af; padding-top: 5px; font-size: 8.5px; color: #374151; margin-top: 28px; }
.firma-name { font-weight: 700; font-size: 9px; }

.footer { position: absolute; bottom: 18px; left: 18px; right: 18px;
          border-top: 1px solid #bfdbfe; padding-top: 6px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
<div class="border-page">
<div class="inner-border">

{{-- Encabezado --}}
<div class="header">
    <div class="inst">{{ $si }}</div>
    @if($cod)<div class="inst2">Código del Centro: {{ $cod }}</div>@endif
    <div class="inst2">{{ $config?->director ? 'Director/a: ' . $config->director : ($dir ? 'Director/a: ' . $dir : '') }}</div>
    <hr class="sep">
    <div class="titulo">CERTIFICADO OFICIAL DE CALIFICACIONES</div>
    <div class="subtitulo">Año Escolar: {{ $schoolYear->nombre }}</div>
</div>

{{-- Datos del estudiante --}}
<div class="estudiante-info">
    <div class="est-row">
        <div class="est-col">
            <div class="est-lbl">Nombre del Estudiante</div>
            <div class="est-val">{{ $estudiante->nombre_completo }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">No. de Matrícula</div>
            <div class="est-val">{{ $estudiante->matricula ?? '—' }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">Cédula / RNE</div>
            <div class="est-val">{{ $estudiante->cedula ?? '—' }}</div>
        </div>
    </div>
    <div class="est-row" style="margin-top:6px;">
        <div class="est-col">
            <div class="est-lbl">Grado y Sección</div>
            <div class="est-val">{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">Modalidad</div>
            <div class="est-val">{{ $config?->nivel_educativo ?? 'Secundaria' }}</div>
        </div>
        <div class="est-col">
            <div class="est-lbl">Fecha de Expedición</div>
            <div class="est-val">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

{{-- Párrafo de certificación --}}
<div class="certifica-text">
    Por medio del presente documento, <strong>{{ $si }}</strong> certifica que el/la estudiante
    <strong>{{ strtoupper($estudiante->nombre_completo) }}</strong>, con número de matrícula
    <strong>{{ $estudiante->matricula ?? '—' }}</strong>, obtuvo las siguientes calificaciones durante
    el Año Escolar <strong>{{ $schoolYear->nombre }}</strong>:
</div>

{{-- Tabla de calificaciones --}}
<table>
    <thead>
        <tr>
            <th class="left" style="width:180px;">Asignatura</th>
            <th style="width:55px;">P1</th>
            <th style="width:55px;">P2</th>
            <th style="width:55px;">P3</th>
            <th style="width:55px;">P4</th>
            <th style="width:65px;">Cal. Final</th>
            <th style="width:60px;">Situación</th>
        </tr>
    </thead>
    <tbody>
        @forelse($calAcad as $cal)
        <tr>
            <td class="left">{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $cal->comp1_p1 ?? '—' }}</td>
            <td>{{ $cal->comp1_p2 ?? '—' }}</td>
            <td>{{ $cal->comp1_p3 ?? '—' }}</td>
            <td>{{ $cal->comp1_p4 ?? '—' }}</td>
            <td class="{{ $cal->nota_final !== null ? ($cal->nota_final >= 60 ? 'nota-ap' : 'nota-rep') : '' }}">
                {{ $cal->nota_final !== null ? number_format($cal->nota_final, 1) : '—' }}
            </td>
            <td>
                @if($cal->situacion === 'A')
                    <span style="color:#15803d;font-weight:700;">Aprobado</span>
                @elseif($cal->situacion === 'R')
                    <span style="color:#dc2626;font-weight:700;">Reprobado</span>
                @else
                    <span style="color:#94a3b8;">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#94a3b8;font-style:italic;">Sin calificaciones registradas.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Resumen --}}
<div class="resumen-row">
    <div class="resumen-chip chip-prom">
        <div class="num">{{ $promedio ? number_format($promedio, 1) : '—' }}</div>
        <div class="lbl">Promedio General</div>
    </div>
    <div class="resumen-chip chip-apro">
        <div class="num">{{ $aprobadas }}</div>
        <div class="lbl">Asignaturas Aprobadas</div>
    </div>
    <div class="resumen-chip chip-repr">
        <div class="num">{{ $reprobadas }}</div>
        <div class="lbl">Asignaturas Reprobadas</div>
    </div>
</div>

{{-- Firmas --}}
<div class="firmas">
    <div class="firma">
        <div class="firma-line">
            <div class="firma-name">{{ $dir ?: 'Director/a del Centro' }}</div>
            Director/a
        </div>
    </div>
    <div class="firma" style="text-align:center;margin-top:0;">
        <div style="width:72px;height:72px;border:2px dashed #1e40af;border-radius:50%;display:inline-block;margin-top:6px;line-height:72px;font-size:8px;color:#bfdbfe;">SELLO</div>
    </div>
    <div class="firma">
        <div class="firma-line">
            Encargado/a de Docencia
        </div>
    </div>
</div>

<div class="footer">
    <span>{{ $si }} — Certificado emitido el {{ now()->format('d/m/Y') }}</span>
    <span>Documento de carácter oficial — No válido sin sello y firma</span>
</div>

</div>
</div>
</body>
</html>
