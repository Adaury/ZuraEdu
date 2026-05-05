<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.border-page { border: 3px solid #1e3a6e; padding: 16px; }
.inner { border: 1px solid #bfdbfe; padding: 14px; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e3a6e; padding-bottom: 12px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #1e3a6e; text-transform: uppercase; }
.header .titulo{ font-size: 14px; font-weight: 900; color: #0f172a; margin-top: 8px; }
.header .sub   { font-size: 8.5px; color: #6b7280; margin-top: 4px; }

.kpis { display: flex; gap: 8px; margin-bottom: 14px; }
.kpi  { flex: 1; text-align: center; padding: 8px 5px; border-radius: 6px; border: 1px solid #e2e8f0; }
.kpi .num { font-size: 18px; font-weight: 900; }
.kpi .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
.k1 { background:#dbeafe; } .k1 .num { color:#1d4ed8; }
.k2 { background:#dcfce7; } .k2 .num { color:#15803d; }
.k3 { background:#ede9fe; } .k3 .num { color:#7c3aed; }
.k4 { background:#fef9c3; } .k4 .num { color:#d97706; }
.k5 { background:#fee2e2; } .k5 .num { color:#dc2626; }

.section-title { font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #1e3a6e;
                 border-bottom: 1px solid #bfdbfe; padding-bottom: 3px; margin: 12px 0 8px; letter-spacing:.04em; }

.row2 { display: flex; gap: 12px; }
.col-half { flex: 1; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e3a6e; color: #fff; }
thead th { padding: 4px 6px; font-size: 8px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 4px 6px; border: 1px solid #bfdbfe; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.bar-wrap { background: #e2e8f0; border-radius: 3px; height: 7px; width: 70px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 7px; border-radius: 3px; display: block; }

.stat-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.stat-box { flex: 1; min-width: 70px; background: #f8faff; border: 1px solid #dbeafe; border-radius: 5px; padding: 6px 8px; text-align: center; }
.stat-box .num { font-size: 14px; font-weight: 800; color: #1d4ed8; }
.stat-box .lbl { font-size: 7px; color: #6b7280; margin-top: 1px; }

.pago-row { display: flex; gap: 8px; }
.pago-box { flex: 1; text-align: center; padding: 6px; border-radius: 5px; border: 1px solid #e2e8f0; }
.pago-box .num { font-size: 13px; font-weight: 800; }
.pago-box .lbl { font-size: 7px; color: #6b7280; margin-top: 1px; }
.pb-cobr { background: #dcfce7; } .pb-cobr .num { color: #15803d; }
.pb-pend { background: #fef9c3; } .pb-pend .num { color: #d97706; }
.pb-deud { background: #fee2e2; } .pb-deud .num { color: #dc2626; }

.firma-row { display: flex; gap: 24px; margin-top: 20px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #1e3a6e; padding-top: 5px; font-size: 8px; color: #374151; margin-top: 26px; }

.footer { border-top: 1px solid #bfdbfe; padding-top: 7px; margin-top: 14px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
<div class="border-page">
<div class="inner">

<div class="header">
    <div class="inst">{{ $inst }}</div>
    @if($dir)<div style="font-size:8.5px;color:#475569;margin-top:2px;">Director/a: {{ $dir }}</div>@endif
    <div class="titulo">RESUMEN ANUAL DEL AÑO ESCOLAR</div>
    <div class="sub">{{ $sy->nombre }} &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</div>
</div>

<div class="kpis">
    <div class="kpi k1"><div class="num">{{ $totalMat }}</div><div class="lbl">Matriculados</div></div>
    <div class="kpi k2"><div class="num">{{ $totalDoc }}</div><div class="lbl">Docentes</div></div>
    <div class="kpi k3"><div class="num">{{ $totalGrupos }}</div><div class="lbl">Grupos</div></div>
    <div class="kpi k4"><div class="num">{{ $promGlobal ?? '—' }}</div><div class="lbl">Promedio Global</div></div>
    <div class="kpi k5"><div class="num">{{ $tasaApro !== null ? $tasaApro . '%' : '—' }}</div><div class="lbl">Tasa Aprobación</div></div>
</div>

<div class="row2">
    <div class="col-half">
        <div class="section-title">Matrícula por Grado</div>
        <table>
            <thead><tr><th class="left">Grado</th><th style="width:55px;">Estudiantes</th><th style="width:80px;"></th></tr></thead>
            <tbody>
                @foreach($porGrado as $grado => $total)
                @php $pct = $totalMat > 0 ? round($total/$totalMat*100) : 0; @endphp
                <tr>
                    <td class="left">{{ $grado }}</td>
                    <td style="font-weight:700;">{{ $total }}</td>
                    <td><span class="bar-wrap"><span class="bar-fill" style="width:{{ $pct }}%;background:#1d4ed8;"></span></span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-half">
        <div class="section-title">Calificaciones y Asistencia</div>
        <div class="stat-row">
            <div class="stat-box" style="background:#dcfce7;border-color:#bbf7d0;"><div class="num" style="color:#15803d;">{{ $aprobados }}</div><div class="lbl">Aprobados</div></div>
            <div class="stat-box" style="background:#fee2e2;border-color:#fca5a5;"><div class="num" style="color:#dc2626;">{{ $reprobados }}</div><div class="lbl">Reprobados</div></div>
            <div class="stat-box"><div class="num">{{ $asistGlobal ? round($asistGlobal,1).'%' : '—' }}</div><div class="lbl">Asistencia Prom.</div></div>
        </div>

        <div class="section-title">Actividad Docente</div>
        <div class="stat-row">
            <div class="stat-box"><div class="num">{{ $planificaciones }}</div><div class="lbl">Planificaciones</div></div>
            <div class="stat-box"><div class="num">{{ $planesClase }}</div><div class="lbl">Planes Clase</div></div>
            <div class="stat-box"><div class="num">{{ $observaciones }}</div><div class="lbl">Observaciones</div></div>
            <div class="stat-box"><div class="num">{{ $comunicados }}</div><div class="lbl">Comunicados</div></div>
        </div>
    </div>
</div>

<div class="row2">
    <div class="col-half">
        <div class="section-title">Top 5 Grupos — Mayor Promedio</div>
        <table>
            <thead><tr><th style="width:20px;">Pos.</th><th class="left">Grupo</th><th style="width:55px;">Promedio</th></tr></thead>
            <tbody>
                @foreach($topGrupos as $i => $r)
                <tr>
                    <td style="font-weight:700;color:#15803d;">{{ $i+1 }}</td>
                    <td class="left">{{ $r->grupo?->nombre_completo ?? '—' }}</td>
                    <td style="font-weight:800;color:#15803d;">{{ $r->promedio_grupo ? number_format($r->promedio_grupo,1) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="col-half">
        <div class="section-title">Bottom 5 Grupos — Menor Promedio</div>
        <table>
            <thead><tr><th style="width:20px;">Pos.</th><th class="left">Grupo</th><th style="width:55px;">Promedio</th></tr></thead>
            <tbody>
                @foreach($bottomGrupos as $i => $r)
                <tr>
                    <td style="color:#dc2626;font-weight:700;">{{ $i+1 }}</td>
                    <td class="left">{{ $r->grupo?->nombre_completo ?? '—' }}</td>
                    <td style="font-weight:800;color:#dc2626;">{{ $r->promedio_grupo ? number_format($r->promedio_grupo,1) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($moduloPagos && $pagos)
<div class="section-title">Situación Financiera</div>
<div class="pago-row">
    <div class="pago-box pb-cobr"><div class="num">RD$ {{ number_format($pagos['cobrado'],0) }}</div><div class="lbl">Cobrado</div></div>
    <div class="pago-box pb-pend"><div class="num">RD$ {{ number_format($pagos['pendiente'],0) }}</div><div class="lbl">Pendiente</div></div>
    <div class="pago-box pb-deud"><div class="num">{{ $pagos['deudores'] }}</div><div class="lbl">Deudores</div></div>
</div>
@endif

<div class="firma-row">
    <div class="firma-box">{{ $dir ?: 'Director/a del Centro' }}</div>
    <div style="flex:1;text-align:center;margin-top:0;">
        <div style="width:65px;height:65px;border:2px dashed #1e3a6e;border-radius:50%;display:inline-block;margin-top:6px;line-height:65px;font-size:7.5px;color:#93c5fd;">SELLO</div>
    </div>
    <div class="firma-box">Secretaria/o Académica/o</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Resumen Anual {{ $sy->nombre }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</div>
</div>
</body>
</html>
