<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Becados</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }
@page { size:letter portrait; margin:1.2cm 1.4cm; }

/* ── Encabezado ── */
.hdr { border:2px solid #4338ca; border-radius:4px; margin-bottom:1rem; overflow:hidden; }
.hdr-top { background:#4338ca; color:#fff; text-align:center; font-size:7pt; font-weight:700;
           letter-spacing:.15em; text-transform:uppercase; padding:3px 0; }
.hdr-body { background:#fff; padding:8px 12px; }
.inst-name { font-size:13pt; font-weight:900; color:#4338ca; line-height:1.2; }
.inst-sub  { font-size:7.5pt; color:#374151; margin-top:2px; }

/* ── Títulos de sección ── */
.report-title { font-size:12pt; font-weight:800; color:#4338ca; margin-bottom:.3rem; }
.report-meta  { font-size:8pt; color:#6b7280; margin-bottom:.75rem;
                border-bottom:1px solid #e5e7eb; padding-bottom:.4rem; }

/* ── Resumen de totales ── */
.resumen-box { background:#f0f0ff; border:1px solid #c7d2fe; border-radius:5px;
               padding:8px 12px; margin-bottom:.9rem; display:flex; gap:30px; }
.resumen-item { text-align:center; }
.resumen-val  { font-size:13pt; font-weight:900; color:#4338ca; }
.resumen-lbl  { font-size:7pt; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; }

/* ── Tipo section ── */
.tipo-header { background:#4338ca; color:#fff; font-size:8pt; font-weight:700;
               text-transform:uppercase; letter-spacing:.08em;
               padding:4px 8px; border-radius:3px 3px 0 0; margin-top:.9rem; }
.tipo-badge-porcentaje { background:#e0e7ff; color:#3730a3; }
.tipo-badge-monto { background:#d1fae5; color:#065f46; }
.tipo-sub { background:#eef2ff; padding:4px 8px 3px; font-size:7.5pt; color:#374151;
            border:1px solid #c7d2fe; border-top:none; margin-bottom:.25rem; }

/* ── Tabla ── */
table { width:100%; border-collapse:collapse; }
th { background:#4338ca; color:#fff; font-size:7.5pt; font-weight:700;
     padding:5px 8px; text-align:left; letter-spacing:.03em; }
td { font-size:8pt; padding:4px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tr:nth-child(even) td { background:#f5f3ff; }
.text-right  { text-align:right; }
.text-center { text-align:center; }
.fw-bold { font-weight:700; }
.text-indigo { color:#4338ca; }
.text-emerald { color:#065f46; }

.subtotal-row td { background:#e0e7ff !important; font-weight:700;
                   border-top:1.5px solid #c7d2fe; font-size:8pt; }
.total-row td { background:#4338ca !important; color:#fff !important;
                font-weight:800; border-top:2px solid #312e81; }

/* ── Footer ── */
.footer { margin-top:1rem; border-top:1px solid #e5e7eb; padding-top:.4rem;
          display:flex; justify-content:space-between; font-size:7.5pt; color:#9ca3af; }
</style>
</head>
<body>

{{-- Encabezado institucional --}}
<div class="hdr">
    <div class="hdr-top">Sistema de Gestión Escolar — Módulo de Becas y Descuentos</div>
    <div class="hdr-body">
        <div class="inst-name">{{ $inst }}</div>
        <div class="inst-sub">Reporte de Becados · Año Escolar: {{ $syActual?->nombre ?? '—' }} · Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<div class="report-title">Reporte General de Becas y Descuentos</div>
<div class="report-meta">
    Total becados activos: <strong>{{ $totalBecados }}</strong>
    &nbsp;·&nbsp; Moneda: <strong>{{ $mon }}</strong>
    @if($montoCuota > 0)
        &nbsp;·&nbsp; Cuota base referencial: <strong>{{ $mon }} {{ number_format($montoCuota, 2) }}</strong>
    @endif
</div>

{{-- Resumen general --}}
<div class="resumen-box">
    <div class="resumen-item">
        <div class="resumen-val">{{ $totalBecados }}</div>
        <div class="resumen-lbl">Becados</div>
    </div>
    @if($montoCuota > 0)
    <div class="resumen-item">
        <div class="resumen-val">{{ $mon }} {{ number_format($totalMensual, 2) }}</div>
        <div class="resumen-lbl">Descuento mensual est.</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val">{{ $mon }} {{ number_format($totalAnual, 2) }}</div>
        <div class="resumen-lbl">Descuento anual est.</div>
    </div>
    @endif
    <div class="resumen-item">
        <div class="resumen-val">{{ count($becados) }}</div>
        <div class="resumen-lbl">Tipos de beca</div>
    </div>
</div>

{{-- Agrupado por tipo --}}
@foreach($becados as $tipo => $grupo)
@php
    $etiquetaTipo = $tipo === 'porcentaje' ? 'Becas por Porcentaje' : 'Becas por Monto Fijo';
    $res = $resumen[$tipo] ?? [];
@endphp

<div class="tipo-header">{{ $etiquetaTipo }} — {{ $grupo->count() }} becado(s)</div>
<div class="tipo-sub">
    @if($montoCuota > 0)
        Descuento mensual estimado: <strong>{{ $mon }} {{ number_format($res['desc_mensual'] ?? 0, 2) }}</strong>
        &nbsp;·&nbsp;
        Descuento anual estimado: <strong>{{ $mon }} {{ number_format($res['desc_anual'] ?? 0, 2) }}</strong>
    @else
        Agrupa {{ $grupo->count() }} estudiante(s) con beca de tipo {{ $tipo === 'porcentaje' ? 'porcentaje' : 'monto fijo' }}.
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Estudiante</th>
            <th>Grupo</th>
            <th>Beca</th>
            <th class="text-center">Valor</th>
            @if($montoCuota > 0)
            <th class="text-right">Desc. mensual</th>
            @endif
            <th class="text-center">Desde</th>
            <th class="text-center">Hasta</th>
            <th>Notas</th>
        </tr>
    </thead>
    <tbody>
        @foreach($grupo as $i => $be)
        @php $est = $be->matricula?->estudiante; $grp = $be->matricula?->grupo; @endphp
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td class="fw-bold">{{ $est?->apellidos ?? $est?->apellido ?? '—' }}, {{ $est?->nombres ?? $est?->nombre ?? '—' }}</td>
            <td>{{ $grp?->grado?->nombre ?? '—' }} {{ $grp?->seccion?->nombre ?? '' }}</td>
            <td>{{ $be->beca?->nombre ?? '—' }}</td>
            <td class="text-center {{ $tipo === 'porcentaje' ? 'text-indigo' : 'text-emerald' }} fw-bold">
                {{ $tipo === 'porcentaje' ? $be->beca?->valor . '%' : ($mon . ' ' . number_format($be->beca?->valor ?? 0, 2)) }}
            </td>
            @if($montoCuota > 0)
            <td class="text-right fw-bold">{{ $mon }} {{ number_format($be->beca?->calcularDescuento($montoCuota) ?? 0, 2) }}</td>
            @endif
            <td class="text-center">{{ $be->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
            <td class="text-center">{{ $be->fecha_fin?->format('d/m/Y') ?? 'Indefinida' }}</td>
            <td style="font-size:7.5pt;color:#6b7280">{{ $be->notas ? \Illuminate\Support\Str::limit($be->notas, 40) : '—' }}</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="{{ $montoCuota > 0 ? 5 : 4 }}" class="text-right">Subtotal ({{ $etiquetaTipo }}):</td>
            <td class="text-center text-indigo">{{ $grupo->count() }} becado(s)</td>
            @if($montoCuota > 0)
            <td class="text-right text-indigo">{{ $mon }} {{ number_format($res['desc_mensual'] ?? 0, 2) }}</td>
            @endif
            <td colspan="2"></td>
        </tr>
    </tbody>
</table>

@endforeach

@if($becados->isEmpty())
<p style="text-align:center;color:#6b7280;padding:2rem;">No hay becados activos en el año escolar actual.</p>
@endif

{{-- Fila gran total --}}
@if($becados->isNotEmpty() && $montoCuota > 0)
<table style="margin-top:.5rem;">
    <tbody>
        <tr class="total-row">
            <td colspan="5" class="text-right" style="padding:6px 8px;">TOTAL GENERAL — {{ $totalBecados }} becado(s)</td>
            <td class="text-right" style="padding:6px 8px;">{{ $mon }} {{ number_format($totalMensual, 2) }}/mes</td>
            <td colspan="3" style="padding:6px 8px;">Anual: {{ $mon }} {{ number_format($totalAnual, 2) }}</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">
    <span>Generado por SGE PSAC · {{ now()->format('d/m/Y H:i') }}</span>
    <span>Confidencial — uso interno</span>
</div>

</body>
</html>
