<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.border-page { border: 3px solid #1e3a6e; padding: 16px; }
.inner { border: 1px solid #bfdbfe; padding: 14px; }

.header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1e3a6e; padding-bottom: 12px; }
.header .inst  { font-size: 14px; font-weight: bold; color: #1e3a6e; text-transform: uppercase; }
.header .dir   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .titulo{ font-size: 13px; font-weight: 800; color: #0f172a; margin-top: 8px; }
.header .sub   { font-size: 8.5px; color: #6b7280; margin-top: 4px; }

.kpis { display: flex; gap: 10px; margin-bottom: 14px; }
.kpi  { flex: 1; text-align: center; padding: 9px 5px; border-radius: 6px; border: 1px solid #e2e8f0; }
.kpi .num { font-size: 20px; font-weight: 800; }
.kpi .lbl { font-size: 7.5px; color: #6b7280; margin-top: 2px; }
.k1 { background: #dbeafe; } .k1 .num { color: #1d4ed8; }
.k2 { background: #dcfce7; } .k2 .num { color: #15803d; }
.k3 { background: #ede9fe; } .k3 .num { color: #7c3aed; }
.k4 { background: #fef3c7; } .k4 .num { color: #d97706; }

.section { margin-bottom: 14px; }
.section-title { font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #1e3a6e;
                 letter-spacing: .05em; border-bottom: 1px solid #bfdbfe; padding-bottom: 3px;
                 margin-bottom: 7px; }

.row2 { display: flex; gap: 12px; margin-bottom: 14px; }
.col-half { flex: 1; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #1e3a6e; color: #fff; }
thead th { padding: 4px 6px; font-size: 8px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 4px 6px; border: 1px solid #bfdbfe; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.rendimiento-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.r-chip { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.r-chip .num { font-size: 15px; font-weight: 800; }
.r-chip .lbl { font-size: 7px; color: #6b7280; margin-top: 1px; }
.rc-prom { background: #eff6ff; } .rc-prom .num { color: #1d4ed8; }
.rc-apr  { background: #dcfce7; } .rc-apr .num  { color: #15803d; }
.rc-rep  { background: #fee2e2; } .rc-rep .num  { color: #dc2626; }
.rc-asist{ background: #fef9c3; } .rc-asist .num{ color: #92400e; }

.pago-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.pc { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.pc .num { font-size: 14px; font-weight: 800; }
.pc .lbl { font-size: 7px; color: #6b7280; margin-top: 1px; }
.pc-cobr { background: #dcfce7; } .pc-cobr .num { color: #15803d; }
.pc-pend { background: #fef9c3; } .pc-pend .num { color: #92400e; }
.pc-deud { background: #fee2e2; } .pc-deud .num { color: #dc2626; }

.bar-wrap { background: #e2e8f0; border-radius: 3px; height: 7px; width: 70px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 7px; border-radius: 3px; display: block; }

.alerta-box { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 4px; padding: 6px 10px; font-size: 8.5px; color: #991b1b; }

.firma-row { display: flex; gap: 30px; margin-top: 20px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #1e3a6e; padding-top: 5px; font-size: 8px; color: #374151; margin-top: 26px; }

.footer { border-top: 1px solid #e2e8f0; padding-top: 7px; margin-top: 14px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
<div class="border-page">
<div class="inner">

<div class="header">
    <div class="inst">{{ $inst }}</div>
    @if($dir)<div class="dir">Director/a: {{ $dir }}</div>@endif
    <div class="titulo">INFORME EJECUTIVO INSTITUCIONAL</div>
    <div class="sub">
        Año Escolar: {{ $sy?->nombre ?? '—' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
        &nbsp;·&nbsp; Elaborado por: {{ auth()->user()->name ?? 'Sistema' }}
    </div>
</div>

{{-- KPIs principales --}}
<div class="kpis">
    <div class="kpi k1">
        <div class="num">{{ $totalEstudiantes }}</div>
        <div class="lbl">Estudiantes Matriculados</div>
    </div>
    <div class="kpi k2">
        <div class="num">{{ $totalDocentes }}</div>
        <div class="lbl">Docentes Activos</div>
    </div>
    <div class="kpi k3">
        <div class="num">{{ $totalGrupos }}</div>
        <div class="lbl">Grupos / Secciones</div>
    </div>
    <div class="kpi k4">
        <div class="num">{{ $alertas }}</div>
        <div class="lbl">Alertas sin Resolver</div>
    </div>
</div>

<div class="row2">
    {{-- Matrícula por grado --}}
    <div class="col-half">
        <div class="section-title">Matrícula por Grado</div>
        <table>
            <thead>
                <tr>
                    <th class="left">Grado</th>
                    <th style="width:55px;">Estudiantes</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($porGrado as $grado => $total)
                @php $pct = $totalEstudiantes > 0 ? round($total/$totalEstudiantes*100) : 0; @endphp
                <tr>
                    <td class="left">{{ $grado }}</td>
                    <td style="font-weight:700;">{{ $total }}</td>
                    <td>
                        <span class="bar-wrap">
                            <span class="bar-fill" style="width:{{ $pct }}%;background:#1d4ed8;"></span>
                        </span>
                        <span style="font-size:7.5px;color:#6b7280;margin-left:4px;">{{ $pct }}%</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" style="color:#94a3b8;font-style:italic;">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Rendimiento Académico --}}
    <div class="col-half">
        <div class="section-title">Rendimiento Académico Global</div>
        <div class="rendimiento-chips">
            <div class="r-chip rc-prom">
                <div class="num">{{ $promedioGlobal ?? '—' }}</div>
                <div class="lbl">Promedio Global</div>
            </div>
            <div class="r-chip rc-apr">
                <div class="num">{{ $aprobados }}</div>
                <div class="lbl">Registros Aprobados</div>
            </div>
            <div class="r-chip rc-rep">
                <div class="num">{{ $reprobados }}</div>
                <div class="lbl">Registros Reprobados</div>
            </div>
            <div class="r-chip rc-asist">
                <div class="num">{{ $asistGlobal ? round($asistGlobal, 1) . '%' : '—' }}</div>
                <div class="lbl">Asistencia Global Prom.</div>
            </div>
        </div>

        @if($aprobados + $reprobados > 0)
        <div style="margin-top:8px;background:#f8faff;border:1px solid #e0e7ff;border-radius:4px;padding:6px 8px;">
            <div style="font-size:7.5px;font-weight:700;color:#6b7280;margin-bottom:3px;">Tasa de Aprobación</div>
            @php $tasaApro = round($aprobados / ($aprobados + $reprobados) * 100, 1); @endphp
            <div style="display:flex;align-items:center;gap:6px;">
                <div style="flex:1;background:#e2e8f0;border-radius:4px;height:10px;">
                    <div style="width:{{ $tasaApro }}%;background:{{ $tasaApro >= 80 ? '#22c55e' : ($tasaApro >= 60 ? '#f59e0b' : '#ef4444') }};height:10px;border-radius:4px;"></div>
                </div>
                <span style="font-weight:800;font-size:10px;color:{{ $tasaApro >= 80 ? '#15803d' : ($tasaApro >= 60 ? '#d97706' : '#dc2626') }};">{{ $tasaApro }}%</span>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Top grupos --}}
@if($topGrupos->isNotEmpty())
<div class="section">
    <div class="section-title">Ranking de Grupos por Rendimiento</div>
    <table>
        <thead>
            <tr>
                <th style="width:22px;">Pos.</th>
                <th class="left">Grupo</th>
                <th style="width:60px;">Promedio</th>
                <th style="width:80px;"></th>
                <th style="width:50px;">Estudiantes</th>
                <th style="width:55px;">En Riesgo</th>
                <th style="width:55px;">Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topGrupos as $i => $r)
            @php
                $prom = $r->promedio_grupo;
                $barC = $prom >= 70 ? '#22c55e' : ($prom >= 60 ? '#f59e0b' : '#ef4444');
                $dot  = match($r->semaforo) { 'danger'=>'#ef4444','warning'=>'#f59e0b',default=>'#22c55e' };
            @endphp
            <tr>
                <td style="font-weight:700;color:{{ $i < 3 ? '#d97706' : '#6b7280' }};">{{ $i + 1 }}</td>
                <td class="left">{{ $r->grupo?->nombre_completo ?? '—' }}</td>
                <td style="font-weight:800;color:{{ $prom >= 70 ? '#15803d' : '#dc2626' }};">{{ $prom ? number_format($prom,1) : '—' }}</td>
                <td>
                    @if($prom)
                    <span class="bar-wrap"><span class="bar-fill" style="width:{{ min($prom,100) }}%;background:{{ $barC }};"></span></span>
                    @endif
                </td>
                <td>{{ $r->total_estudiantes }}</td>
                <td style="color:{{ $r->total_riesgo > 0 ? '#dc2626' : '#15803d' }};font-weight:700;">{{ $r->total_riesgo }}</td>
                <td><span style="width:10px;height:10px;background:{{ $dot }};border-radius:50%;display:inline-block;vertical-align:middle;"></span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Pagos --}}
@if($moduloPagos)
<div class="section">
    <div class="section-title">Situación Financiera — Colegiaturas</div>
    <div class="pago-chips">
        <div class="pc pc-cobr">
            <div class="num">RD$ {{ number_format($totalCobrado ?? 0, 0) }}</div>
            <div class="lbl">Total Cobrado</div>
        </div>
        <div class="pc pc-pend">
            <div class="num">RD$ {{ number_format($totalPendiente ?? 0, 0) }}</div>
            <div class="lbl">Pendiente / Vencido</div>
        </div>
        <div class="pc pc-deud">
            <div class="num">{{ $totalDeudores ?? 0 }}</div>
            <div class="lbl">Estudiantes Deudores</div>
        </div>
    </div>
</div>
@endif

@if($alertas > 0)
<div class="alerta-box">
    <strong>⚠ {{ $alertas }} alerta(s) sin resolver</strong> — Revisar en el panel de alertas del sistema.
</div>
@endif

<div class="firma-row">
    <div class="firma-box">
        <strong>{{ $dir ?: 'Director/a del Centro' }}</strong><br>
        Director/a del Centro
    </div>
    <div style="flex:1;text-align:center;margin-top:0;">
        <div style="width:70px;height:70px;border:2px dashed #1e3a6e;border-radius:50%;display:inline-block;margin-top:6px;line-height:70px;font-size:8px;color:#93c5fd;">SELLO</div>
    </div>
    <div class="firma-box">
        Encargado/a Administrativo/a
    </div>
</div>

<div class="footer">
    <span>{{ $inst }} — Informe Ejecutivo {{ now()->format('Y') }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</div>
</div>
</body>
</html>
