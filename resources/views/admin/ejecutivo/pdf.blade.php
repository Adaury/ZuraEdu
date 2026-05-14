<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

.header { background: #1e3a6e; color: #fff; padding: 18px 24px; margin-bottom: 20px; }
.header h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
.header p  { font-size: 10px; opacity: .8; }

.kpi-row { display: flex; gap: 12px; margin-bottom: 20px; }
.kpi-box { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
.kpi-box .val { font-size: 20px; font-weight: bold; color: #1e3a6e; }
.kpi-box .lbl { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold; margin-top: 4px; }

.section-title { font-size: 11px; font-weight: bold; color: #1e3a6e; border-bottom: 2px solid #1e3a6e; padding-bottom: 4px; margin-bottom: 10px; }

table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9px; }
th { background: #1e3a6e; color: #fff; padding: 6px 8px; text-align: left; font-size: 8px; text-transform: uppercase; }
td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
tr:nth-child(even) td { background: #f8fafc; }

.row-2 { display: flex; gap: 16px; margin-bottom: 20px; }
.col-half { flex: 1; }

.badge-ok   { color: #16a34a; font-weight: bold; }
.badge-warn { color: #d97706; font-weight: bold; }
.badge-bad  { color: #dc2626; font-weight: bold; }

.footer { margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 8px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>

<div class="header">
    <h1>Dashboard Ejecutivo — {{ $inst }}</h1>
    <p>{{ $schoolYear?->nombre ?? 'Año escolar' }} · {{ now()->format('d/m/Y H:i') }} · {{ $periodoId ? 'Período filtrado' : 'Vista anual' }}</p>
</div>

<div class="kpi-row">
    <div class="kpi-box">
        <div class="val">{{ number_format($totalEstudiantes) }}</div>
        <div class="lbl">Estudiantes</div>
    </div>
    <div class="kpi-box">
        <div class="val">{{ $promedioInstitucional ? number_format($promedioInstitucional, 1) : '—' }}</div>
        <div class="lbl">Promedio Inst.</div>
    </div>
    <div class="kpi-box">
        <div class="val">{{ $tasaAprobacion }}%</div>
        <div class="lbl">Tasa Aprobación</div>
    </div>
    <div class="kpi-box">
        <div class="val">{{ $pctAsistencia !== null ? $pctAsistencia . '%' : '—' }}</div>
        <div class="lbl">Asistencia (mes)</div>
    </div>
    @if($statsPagos)
    <div class="kpi-box">
        <div class="val" style="font-size:13px;">RD${{ number_format($statsPagos['cobrado'], 0) }}</div>
        <div class="lbl">Cobrado</div>
    </div>
    @endif
</div>

{{-- Promedios por grado --}}
<div class="section-title">Promedio Académico por Grado</div>
<table>
    <thead><tr><th>Grado</th><th>Promedio</th><th>Aprobados</th><th>En Riesgo</th><th>Estudiantes</th></tr></thead>
    <tbody>
        @foreach($rendimiento->sortByDesc('promedio_grupo') as $r)
        <tr>
            <td>{{ $r->grupo?->grado?->nombre ?? '—' }} {{ $r->grupo?->seccion?->nombre ?? '' }}</td>
            <td class="{{ $r->promedio_grupo >= 80 ? 'badge-ok' : ($r->promedio_grupo >= 70 ? 'badge-warn' : 'badge-bad') }}">
                {{ number_format($r->promedio_grupo, 1) }}
            </td>
            <td>{{ $r->total_aprobados }}</td>
            <td class="{{ $r->total_riesgo > 0 ? 'badge-bad' : '' }}">{{ $r->total_riesgo }}</td>
            <td>{{ $r->total_estudiantes }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Matrículas por grado --}}
<div class="section-title">Matrículas Activas por Grado</div>
<table>
    <thead><tr><th>Grado</th><th>Total Matriculados</th></tr></thead>
    <tbody>
        @foreach($matriculasPorGrado as $grado => $total)
        <tr><td>{{ $grado }}</td><td>{{ $total }}</td></tr>
        @endforeach
    </tbody>
</table>

{{-- Asistencia mes actual --}}
<div class="section-title">Asistencia — Mes Actual ({{ now()->format('F Y') }})</div>
<table>
    <thead><tr><th>Estado</th><th>Total Registros</th></tr></thead>
    <tbody>
        @foreach(['presente' => 'Presente', 'tardanza' => 'Tardanza', 'ausente' => 'Ausente', 'justificado' => 'Justificado'] as $key => $label)
        @if(($asistenciaMes[$key] ?? 0) > 0)
        <tr><td>{{ $label }}</td><td>{{ number_format($asistenciaMes[$key] ?? 0) }}</td></tr>
        @endif
        @endforeach
    </tbody>
</table>

@if($statsPagos)
<div class="section-title">Estado de Pagos</div>
<table>
    <thead><tr><th>Estado</th><th>Monto (RD$)</th></tr></thead>
    <tbody>
        <tr><td class="badge-ok">Cobrado</td><td>{{ number_format($statsPagos['cobrado'], 2) }}</td></tr>
        <tr><td class="badge-warn">Pendiente</td><td>{{ number_format($statsPagos['pendiente'], 2) }}</td></tr>
        <tr><td class="badge-bad">Vencido</td><td>{{ number_format($statsPagos['vencido'], 2) }}</td></tr>
    </tbody>
</table>
@endif

{{-- Rendimiento por Asignatura --}}
@if($promediosPorAsignatura->isNotEmpty())
<div class="section-title">Rendimiento por Asignatura</div>
<table>
    <thead><tr><th>#</th><th>Asignatura</th><th>Promedio</th><th>Estudiantes</th></tr></thead>
    <tbody>
        @foreach($promediosPorAsignatura as $i => $asig)
        @php $p = (float)$asig->promedio; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $asig->nombre }}</td>
            <td class="{{ $p >= 80 ? 'badge-ok' : ($p >= 70 ? 'badge-warn' : 'badge-bad') }}">{{ number_format($p, 1) }}</td>
            <td>{{ $asig->total_estudiantes }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Riesgo Académico --}}
@if($riesgoData['totalEnRiesgo'] > 0)
<div class="section-title">Riesgo Académico (≥2 materias &lt; 70)</div>
<table>
    <thead><tr><th>Grado</th><th>En Riesgo</th></tr></thead>
    <tbody>
        @foreach($riesgoData['riesgoPorGrado'] as $grado => $count)
        <tr>
            <td>{{ $grado }}</td>
            <td class="badge-bad">{{ $count }}</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;">
            <td>TOTAL</td>
            <td class="badge-bad">{{ $riesgoData['totalEnRiesgo'] }}</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">
    Generado por ZuraEdu SGE · {{ now()->format('d/m/Y H:i') }} · Documento confidencial de uso interno
</div>

</body>
</html>
