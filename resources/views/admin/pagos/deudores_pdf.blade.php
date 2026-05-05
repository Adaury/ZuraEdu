<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Deudores</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.2cm 1.4cm; }

.hdr { border:2px solid #1e3a6e; border-radius:4px; margin-bottom:1rem; overflow:hidden; }
.hdr-top { background:#1e3a6e; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:8px 12px; display:flex; align-items:center; gap:12px; }
.inst-name { font-size:13pt; font-weight:900; color:#1e3a6e; line-height:1.2; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:2px; }

.report-title { font-size:12pt; font-weight:800; color:#991b1b; margin-bottom:.35rem; }
.report-meta  { font-size:8pt; color:#6b7280; margin-bottom:.75rem; border-bottom:1px solid #e5e7eb; padding-bottom:.4rem; }

.alerta { background:#fee2e2; border:1px solid #fca5a5; border-radius:5px; padding:6px 10px;
          font-size:8.5pt; font-weight:700; color:#991b1b; margin-bottom:.75rem; }

table { width:100%; border-collapse:collapse; margin-top:.25rem; }
th { background:#1e3a6e; color:#fff; font-size:7.5pt; font-weight:700; padding:5px 8px;
     text-align:left; letter-spacing:.03em; }
td { font-size:8pt; padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tr:nth-child(even) td { background:#fef2f2; }
.text-right { text-align:right; }
.text-center { text-align:center; }
.fw-bold { font-weight:700; }
.text-danger { color:#991b1b; }
.total-row td { background:#fee2e2 !important; font-weight:800; border-top:2px solid #fca5a5; }

.footer { margin-top:1rem; border-top:1px solid #e5e7eb; padding-top:.4rem;
          display:flex; justify-content:space-between; font-size:7.5pt; color:#9ca3af; }
</style>
</head>
<body>

{{-- Encabezado institucional --}}
@php $si = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));  @endphp
<div class="hdr">
    <div class="hdr-top">Sistema de Gestión Escolar</div>
    <div class="hdr-body">
        <div>
            <div class="inst-name">{{ $si }}</div>
            <div class="inst-sub">Reporte Financiero · {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

<div class="report-title">&#9888; Reporte de Deudores — Pagos Vencidos</div>
<div class="report-meta">
    Año escolar: <strong>{{ $schoolYear->nombre ?? '—' }}</strong>
    &nbsp;·&nbsp; Total estudiantes con mora: <strong>{{ $matriculas->count() }}</strong>
    &nbsp;·&nbsp; Deuda total: <strong>RD$ {{ number_format($totalDeuda, 2) }}</strong>
</div>

@if($matriculas->isNotEmpty())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Estudiante</th>
            <th>Matrícula</th>
            <th>Grupo</th>
            <th class="text-center">Cuotas</th>
            <th class="text-right">Deuda (RD$)</th>
            <th>Primera mora</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td class="fw-bold">{{ $mat->estudiante->apellido ?? $mat->estudiante->apellidos ?? '' }}, {{ $mat->estudiante->nombre ?? $mat->estudiante->nombres ?? '' }}</td>
            <td>{{ $mat->estudiante->matricula ?? '—' }}</td>
            <td>{{ $mat->grupo->grado->nombre ?? '—' }} {{ $mat->grupo->seccion->nombre ?? '' }}</td>
            <td class="text-center text-danger fw-bold">{{ $mat->cuotas_vencidas }}</td>
            <td class="text-right text-danger fw-bold">{{ number_format($mat->total_vencido, 2) }}</td>
            <td>{{ $mat->primera_mora ? \Carbon\Carbon::parse($mat->primera_mora)->format('d/m/Y') : '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="4" class="text-right">TOTAL DEUDA ACUMULADA:</td>
            <td class="text-center">{{ $matriculas->sum('cuotas_vencidas') }}</td>
            <td class="text-right text-danger">RD$ {{ number_format($totalDeuda, 2) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
@else
<p style="text-align:center;color:#6b7280;padding:1.5rem;">Sin deudores registrados.</p>
@endif

<div class="footer">
    <span>Generado por SGE PSAC · {{ now()->format('d/m/Y') }}</span>
    <span>Confidencial — uso interno</span>
</div>

</body>
</html>
