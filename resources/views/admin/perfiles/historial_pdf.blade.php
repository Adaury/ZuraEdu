<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial Académico — {{ $estudiante->nombre_completo }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5pt; color: #1a1a2e; }
@page { size: letter portrait; margin: 1.2cm 1.5cm; }

/* ── Borde doble de página ─────────────────────────── */
.page-border-outer {
    border: 3px solid #1e3a6e;
    border-radius: 4px;
    padding: 10px;
    min-height: 96vh;
}
.page-border-inner {
    border: 1px solid #1e3a6e;
    border-radius: 2px;
    padding: 14px 18px;
    min-height: calc(96vh - 24px);
    display: flex;
    flex-direction: column;
}

/* ── Encabezado institucional ──────────────────────── */
.hdr { margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid #1e3a6e; }
.hdr-top {
    background: #1e3a6e; color: #fff;
    text-align: center; font-size: 6.5pt; font-weight: 700;
    letter-spacing: .2em; text-transform: uppercase;
    padding: 3px 0; border-radius: 3px; margin-bottom: 7px;
}
.hdr-body { display: flex; align-items: center; gap: 12px; }
.logo-box {
    width: 52px; height: 52px; border-radius: 7px;
    background: #1e3a6e; color: #fff;
    font-size: 12pt; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.logo-img  { height: 50px; max-width: 54px; object-fit: contain; }
.inst-name { font-size: 11pt; font-weight: 900; color: #1e3a6e; }
.inst-sub  { font-size: 7pt; color: #374151; margin-top: 2px; }
.inst-cod  { font-size: 6.5pt; color: #6b7280; margin-top: 2px; }
.hdr-right { margin-left: auto; text-align: right; }
.hdr-right .doc-num { font-size: 7pt; color: #6b7280; }
.hdr-right .doc-date { font-size: 7pt; color: #374151; font-weight: 700; margin-top: 2px; }

/* ── Título del documento ──────────────────────────── */
.doc-title {
    text-align: center; font-size: 12pt; font-weight: 900;
    color: #1e3a6e; text-transform: uppercase;
    letter-spacing: .08em; margin: 10px 0 3px;
}
.doc-sub {
    text-align: center; font-size: 7.5pt; color: #6b7280; margin-bottom: 10px;
}
.sep-line {
    border: none; border-top: 1.5px solid #1e3a6e;
    margin: 8px 0;
}

/* ── Ficha del estudiante ──────────────────────────── */
.est-box {
    border: 1.5px solid #1e3a6e; border-radius: 5px;
    background: #f8faff; padding: 8px 12px; margin-bottom: 12px;
}
.est-name {
    font-size: 11pt; font-weight: 900; color: #1e3a6e;
    text-transform: uppercase; margin-bottom: 4px;
}
.est-grid { display: flex; flex-wrap: wrap; gap: 0 20px; }
.est-item { min-width: 120px; }
.est-lbl { font-size: 6.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; }
.est-val { font-size: 8pt; color: #1e3a6e; font-weight: 700; }

/* ── Resumen KPIs ──────────────────────────────────── */
.kpis-row { display: flex; gap: 10px; margin-bottom: 12px; }
.kpi {
    flex: 1; text-align: center; border-radius: 5px;
    padding: 6px 4px; border: 1px solid #e5e7eb;
}
.kpi .num { font-size: 14pt; font-weight: 900; }
.kpi .lbl { font-size: 6.5pt; color: #6b7280; margin-top: 1px; text-transform: uppercase; letter-spacing: .05em; }
.kpi-years  { background: #eff6ff; } .kpi-years .num  { color: #1d4ed8; }
.kpi-prom   { background: #f0fdf4; } .kpi-prom .num   { color: #15803d; }
.kpi-apro   { background: #dcfce7; } .kpi-apro .num   { color: #15803d; }
.kpi-repr   { background: #fee2e2; } .kpi-repr .num   { color: #dc2626; }

/* ── Bloque por año ────────────────────────────────── */
.year-block { margin-bottom: 13px; page-break-inside: avoid; }
.year-hdr {
    background: #1e3a6e; color: #fff;
    padding: 5px 10px; border-radius: 4px 4px 0 0;
    display: flex; justify-content: space-between; align-items: center;
}
.year-hdr .yn   { font-size: 9pt; font-weight: 800; }
.year-hdr .yg   { font-size: 7.5pt; opacity: .85; }
.year-hdr .yp {
    background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3);
    border-radius: 12px; padding: 2px 10px; font-size: 8pt; font-weight: 700;
}
.year-body {
    border: 1px solid #c7d2e2; border-top: none;
    border-radius: 0 0 4px 4px; overflow: hidden;
}

.year-stats {
    background: #f8faff; padding: 5px 10px;
    display: flex; gap: 14px; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap;
}
.year-stat { font-size: 7.5pt; color: #374151; }
.year-stat b { color: #1e3a6e; }

/* ── Tabla de asignaturas ──────────────────────────── */
table { width: 100%; border-collapse: collapse; }
.asig-hdr th {
    background: #e0e7ff; color: #374151;
    font-size: 7pt; font-weight: 700; text-align: left;
    padding: 4px 7px; border-bottom: 1.5px solid #c7d2fe;
    text-transform: uppercase; letter-spacing: .04em;
}
.asig-hdr th.tc { text-align: center; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody td {
    font-size: 7.5pt; padding: 3px 7px;
    border-bottom: 1px solid #e5e7eb; vertical-align: middle;
}
.tc { text-align: center; }
.ap { color: #065f46; font-weight: 700; }
.rp { color: #991b1b; font-weight: 700; }
.nota-ok  { color: #065f46; font-weight: 700; }
.nota-mid { color: #92400e; font-weight: 700; }
.nota-bad { color: #991b1b; font-weight: 700; }

tfoot td {
    background: #f1f5f9; font-size: 7.5pt;
    padding: 4px 7px; border-top: 1.5px solid #cbd5e1;
}
.sit-chip-a {
    display: inline-block; background: #d1fae5; color: #065f46;
    border-radius: 3px; padding: 1px 6px; font-size: 7pt; font-weight: 700;
}
.sit-chip-r {
    display: inline-block; background: #fee2e2; color: #991b1b;
    border-radius: 3px; padding: 1px 6px; font-size: 7pt; font-weight: 700;
}

/* ── Sección de firma ──────────────────────────────── */
.firmas {
    margin-top: auto; padding-top: 20px;
    display: flex; justify-content: space-around; gap: 20px;
}
.firma-blk { text-align: center; flex: 1; }
.firma-line { border-top: 1px solid #374151; margin-bottom: 4px; }
.firma-name { font-size: 7.5pt; font-weight: 700; color: #1e3a6e; }
.firma-cargo { font-size: 6.5pt; color: #6b7280; }

/* ── Pie de página ─────────────────────────────────── */
.footer {
    margin-top: 10px; padding-top: 5px;
    border-top: 1px solid #e5e7eb;
    display: flex; justify-content: space-between;
    font-size: 6.5pt; color: #9ca3af;
}
</style>
</head>
<body>

@php
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
    $rep      = $estudiante->representantes->first();
    $totalAnios = $historial->count();
    $promediosValidos = $historial->pluck('promedio')->filter(fn($p) => $p !== null);
    $promedioGlobal   = $promediosValidos->count() > 0 ? round($promediosValidos->avg(), 1) : null;
    $totalAprobadas   = $historial->sum('aprobadas');
    $totalReprobadas  = $historial->sum('reprobadas');
@endphp

<div class="page-border-outer">
<div class="page-border-inner">

    {{-- Encabezado institucional --}}
    <div class="hdr">
        <div class="hdr-top">República Dominicana &bull; Ministerio de Educación &bull; MINERD</div>
        <div class="hdr-body">
            @if($logoPath && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" class="logo-img">
            @else
                <div class="logo-box">{{ strtoupper(substr($si, 0, 2)) }}</div>
            @endif
            <div>
                <div class="inst-name">{{ $si }}</div>
                <div class="inst-sub">{{ $nivel }}</div>
                @if($cod)
                <div class="inst-cod">Código: {{ $cod }}</div>
                @endif
            </div>
            <div class="hdr-right">
                <div class="doc-num">Doc. N.° HIST-{{ str_pad($estudiante->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="doc-date">{{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Título --}}
    <div class="doc-title">Historial Académico Oficial</div>
    <div class="doc-sub">Registro completo de trayectoria escolar &bull; Documento oficial de uso institucional</div>
    <hr class="sep-line">

    {{-- Ficha del estudiante --}}
    <div class="est-box">
        <div class="est-name">{{ strtoupper($estudiante->nombre_completo) }}</div>
        <div class="est-grid">
            @if($estudiante->cedula)
            <div class="est-item">
                <div class="est-lbl">Cédula / ID</div>
                <div class="est-val">{{ $estudiante->cedula }}</div>
            </div>
            @endif
            @if($estudiante->numero_matricula)
            <div class="est-item">
                <div class="est-lbl">N.° Matrícula</div>
                <div class="est-val">{{ $estudiante->numero_matricula }}</div>
            </div>
            @endif
            @if($estudiante->fecha_nacimiento)
            <div class="est-item">
                <div class="est-lbl">Fecha Nac.</div>
                <div class="est-val">{{ $estudiante->fecha_nacimiento->format('d/m/Y') }}</div>
            </div>
            @endif
            @if($estudiante->sexo)
            <div class="est-item">
                <div class="est-lbl">Sexo</div>
                <div class="est-val">{{ $estudiante->sexo === 'M' ? 'Masculino' : 'Femenino' }}</div>
            </div>
            @endif
            @if($rep)
            <div class="est-item">
                <div class="est-lbl">Representante</div>
                <div class="est-val">{{ $rep->nombres }} {{ $rep->apellidos }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- KPIs de resumen --}}
    <div class="kpis-row">
        <div class="kpi kpi-years">
            <div class="num">{{ $totalAnios }}</div>
            <div class="lbl">Años Cursados</div>
        </div>
        <div class="kpi kpi-prom">
            <div class="num">{{ $promedioGlobal !== null ? $promedioGlobal : '—' }}</div>
            <div class="lbl">Prom. Histórico</div>
        </div>
        <div class="kpi kpi-apro">
            <div class="num">{{ $totalAprobadas }}</div>
            <div class="lbl">Asig. Aprobadas</div>
        </div>
        <div class="kpi kpi-repr">
            <div class="num">{{ $totalReprobadas }}</div>
            <div class="lbl">Asig. Reprobadas</div>
        </div>
    </div>

    {{-- Historial por año --}}
    @forelse($historial as $h)
    <div class="year-block">
        <div class="year-hdr">
            <div>
                <div class="yn">{{ $h['schoolYear']->nombre ?? '—' }}</div>
                <div class="yg">{{ $h['grado'] }} {{ $h['seccion'] }} &bull; {{ $h['grupo'] }}</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                @if($h['promedio'] !== null)
                <span class="yp">Prom. {{ number_format($h['promedio'], 1) }}</span>
                @endif
                @if($h['situacion_general'] === 'Promovido')
                    <span style="background:#22c55e;color:#052e16;border-radius:10px;padding:2px 9px;font-size:7.5pt;font-weight:700;">Promovido</span>
                @elseif($h['situacion_general'] === 'Reprobado')
                    <span style="background:#ef4444;color:#fff;border-radius:10px;padding:2px 9px;font-size:7.5pt;font-weight:700;">Reprobado</span>
                @else
                    <span style="background:rgba(255,255,255,.18);color:#fff;border-radius:10px;padding:2px 9px;font-size:7.5pt;">—</span>
                @endif
            </div>
        </div>
        <div class="year-body">
            <div class="year-stats">
                <span class="year-stat">Asignaturas: <b>{{ $h['total_asig'] }}</b></span>
                <span class="year-stat">Aprobadas: <b class="ap">{{ $h['aprobadas'] }}</b></span>
                @if($h['reprobadas'] > 0)
                <span class="year-stat">Reprobadas: <b class="rp">{{ $h['reprobadas'] }}</b></span>
                @endif
                @if($h['asistencia'] !== null)
                <span class="year-stat">Asistencia: <b>{{ $h['asistencia'] }}%</b></span>
                @endif
            </div>

            @if($h['califs']->isNotEmpty())
            <table>
                <thead class="asig-hdr">
                    <tr>
                        <th style="width:26px;">#</th>
                        <th>Asignatura</th>
                        <th class="tc" style="width:38px;">P-1</th>
                        <th class="tc" style="width:38px;">P-2</th>
                        <th class="tc" style="width:38px;">P-3</th>
                        <th class="tc" style="width:38px;">P-4</th>
                        <th class="tc" style="width:55px;">Nota Final</th>
                        <th class="tc" style="width:65px;">Situación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($h['califs'] as $i => $cal)
                    @php
                        $nf = $cal->nota_final;
                        $nfClass = $nf === null ? '' : ($nf >= 80 ? 'nota-ok' : ($nf >= 70 ? 'nota-mid' : 'nota-bad'));
                    @endphp
                    <tr>
                        <td class="tc" style="color:#9ca3af;font-size:6.5pt;">{{ $i + 1 }}</td>
                        <td>{{ $cal->asignacion?->asignatura?->nombre ?? '—' }}</td>
                        <td class="tc">{{ $cal->avg_comp1_p1 !== null ? number_format($cal->avg_comp1_p1, 0) : '—' }}</td>
                        <td class="tc">{{ $cal->avg_comp1_p2 !== null ? number_format($cal->avg_comp1_p2, 0) : '—' }}</td>
                        <td class="tc">{{ $cal->avg_comp1_p3 !== null ? number_format($cal->avg_comp1_p3, 0) : '—' }}</td>
                        <td class="tc">{{ $cal->avg_comp1_p4 !== null ? number_format($cal->avg_comp1_p4, 0) : '—' }}</td>
                        <td class="tc {{ $nfClass }}">{{ $nf !== null ? number_format($nf, 1) : '—' }}</td>
                        <td class="tc">
                            @if($cal->situacion === 'A')
                                <span class="sit-chip-a">Aprobado</span>
                            @elseif($cal->situacion === 'R')
                                <span class="sit-chip-r">Reprobado</span>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($h['promedio'] !== null)
                <tfoot>
                    <tr>
                        <td colspan="6" style="text-align:right;font-weight:700;color:#374151;font-size:7pt;">
                            PROMEDIO GENERAL DEL AÑO:
                        </td>
                        <td class="tc" style="font-weight:900;font-size:9pt;color:{{ $h['promedio'] >= 70 ? '#065f46' : '#991b1b' }};">
                            {{ number_format($h['promedio'], 2) }}
                        </td>
                        <td class="tc">
                            @if($h['situacion_general'] === 'Promovido')
                                <span class="sit-chip-a">Promovido</span>
                            @elseif($h['situacion_general'] === 'Reprobado')
                                <span class="sit-chip-r">Reprobado</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @else
            <div style="padding:6px 10px;font-size:7.5pt;color:#9ca3af;">Sin calificaciones registradas.</div>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:2rem;color:#9ca3af;font-size:9pt;">
        No se encontró historial académico para este estudiante.
    </div>
    @endforelse

    {{-- Firmas --}}
    <div class="firmas">
        <div class="firma-blk">
            <div class="firma-line"></div>
            <div class="firma-name">{{ $dir ?: 'Director(a)' }}</div>
            <div class="firma-cargo">Director(a) del Centro</div>
        </div>
        <div class="firma-blk">
            <div class="firma-line"></div>
            <div class="firma-name">Secretaría Académica</div>
            <div class="firma-cargo">Sello y firma</div>
        </div>
    </div>

    {{-- Pie de página --}}
    <div class="footer">
        <span>{{ $si }} &bull; Sistema SGE &bull; Generado: {{ now()->format('d/m/Y H:i') }}</span>
        <span>Documento oficial &bull; Historial completo al {{ now()->format('d/m/Y') }}</span>
    </div>

</div>
</div>

</body>
</html>
