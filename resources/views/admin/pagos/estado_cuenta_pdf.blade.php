<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 6px; }

.info-grid { display: flex; gap: 16px; margin-bottom: 14px; }
.info-box  { flex: 1; background: #f8faff; border: 1px solid #dbeafe; border-radius: 5px; padding: 8px 10px; }
.info-box .label { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 3px; }
.info-box .value { font-size: 9.5px; font-weight: 700; color: #1e293b; }

.resumen-row { display: flex; gap: 10px; margin-bottom: 14px; }
.resumen-chip { flex: 1; text-align: center; padding: 8px 6px; border-radius: 6px; border: 1px solid #e2e8f0; }
.resumen-chip .num { font-size: 15px; font-weight: 800; }
.resumen-chip .lbl { font-size: 7px; color: #64748b; margin-top: 2px; }
.chip-pagado   { background: #dcfce7; }
.chip-pagado   .num { color: #15803d; }
.chip-pendiente{ background: #fef9c3; }
.chip-pendiente .num { color: #92400e; }
.chip-vencido  { background: #fee2e2; }
.chip-vencido  .num { color: #dc2626; }
.chip-total    { background: #eff6ff; }
.chip-total    .num { color: #1d4ed8; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 7px; text-align: center; font-size: 8px; border: 1px solid #1e40af; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody td { padding: 5px 7px; border: 1px solid #e2e8f0; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.badge { padding: 2px 6px; border-radius: 10px; font-weight: 700; font-size: 7.5px; }
.badge-pagado    { background: #dcfce7; color: #065f46; }
.badge-pendiente { background: #fef3c7; color: #92400e; }
.badge-vencido   { background: #fee2e2; color: #991b1b; }
.badge-cancelado { background: #f3f4f6; color: #6b7280; }

.footer { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }

.firmas { display: flex; gap: 30px; margin-top: 20px; }
.firma  { flex: 1; text-align: center; }
.firma-line { border-top: 1px solid #94a3b8; padding-top: 5px; font-size: 7.5px; color: #475569; margin-top: 24px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="sub">{{ $config?->director ? 'Director/a: ' . $config->director : '' }}</div>
    <div class="titulo">ESTADO DE CUENTA — COLEGIATURAS Y PAGOS</div>
    <div class="sub" style="margin-top:4px;">
        Año Escolar: {{ $sy?->nombre ?? '—' }}
        &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- Info estudiante --}}
@php
    $est = $matricula->estudiante;
    $rep = $est->representantes->first();
@endphp
<div class="info-grid">
    <div class="info-box">
        <div class="label">Estudiante</div>
        <div class="value">{{ $est->nombre_completo }}</div>
    </div>
    <div class="info-box">
        <div class="label">No. Matrícula</div>
        <div class="value">{{ $est->matricula ?? '—' }}</div>
    </div>
    <div class="info-box">
        <div class="label">Grupo</div>
        <div class="value">{{ ($matricula->grupo->grado->nombre ?? '') . ' ' . ($matricula->grupo->seccion->nombre ?? '') }}</div>
    </div>
    <div class="info-box">
        <div class="label">Representante</div>
        <div class="value">{{ $rep ? $rep->nombre_completo : '—' }}</div>
    </div>
</div>

{{-- Resumen --}}
@php
    $cVencido = $matricula->pagos->where('estado', 'vencido')->count();
@endphp
<div class="resumen-row">
    <div class="resumen-chip chip-total">
        <div class="num">{{ $mon }} {{ number_format($totales['total'], 2) }}</div>
        <div class="lbl">Total Facturado</div>
    </div>
    <div class="resumen-chip chip-pagado">
        <div class="num">{{ $mon }} {{ number_format($totales['pagado'], 2) }}</div>
        <div class="lbl">Total Pagado</div>
    </div>
    <div class="resumen-chip chip-pendiente">
        <div class="num">{{ $mon }} {{ number_format($totales['pendiente'], 2) }}</div>
        <div class="lbl">Saldo Pendiente</div>
    </div>
    @if($cVencido > 0)
    <div class="resumen-chip chip-vencido">
        <div class="num">{{ $cVencido }}</div>
        <div class="lbl">Cuota(s) Vencida(s)</div>
    </div>
    @endif
</div>

{{-- Detalle de pagos --}}
<table>
    <thead>
        <tr>
            <th class="left" style="width:160px;">Concepto</th>
            <th style="width:70px;">Vencimiento</th>
            <th style="width:70px;">Fecha Pago</th>
            <th style="width:80px;">Monto ({{ $mon }})</th>
            <th style="width:50px;">Método</th>
            <th style="width:65px;">Estado</th>
            <th class="left">Referencia</th>
        </tr>
    </thead>
    <tbody>
        @forelse($matricula->pagos as $pago)
        <tr>
            <td class="left">{{ $pago->concepto }}</td>
            <td>{{ $pago->fecha_vencimiento ? \Carbon\Carbon::parse($pago->fecha_vencimiento)->format('d/m/Y') : '—' }}</td>
            <td>{{ $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '—' }}</td>
            <td style="font-weight:700;">{{ number_format($pago->monto, 2) }}</td>
            <td>{{ $pago->metodo_pago ? ucfirst($pago->metodo_pago) : '—' }}</td>
            <td>
                <span class="badge badge-{{ $pago->estado }}">
                    {{ ucfirst($pago->estado) }}
                </span>
            </td>
            <td class="left" style="color:#6b7280;font-size:8px;">{{ $pago->referencia ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#94a3b8;font-style:italic;">Sin registros de pago</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Total --}}
@if($totales['pendiente'] > 0)
<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:4px;padding:6px 10px;font-size:8px;color:#854d0e;margin-bottom:12px;">
    <strong>Saldo pendiente: {{ $mon }} {{ number_format($totales['pendiente'], 2) }}</strong>
    — Por favor, regularice este saldo en la administración del centro.
</div>
@else
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:4px;padding:6px 10px;font-size:8px;color:#15803d;margin-bottom:12px;">
    <strong>Cuenta al día.</strong> No hay saldo pendiente. Gracias por su puntualidad.
</div>
@endif

{{-- Firmas --}}
<div class="firmas">
    <div class="firma">
        <div class="firma-line">Representante / Tutor</div>
    </div>
    <div class="firma">
        <div class="firma-line">Administración / Dirección</div>
    </div>
    <div class="firma">
        <div class="firma-line">Sello del Centro</div>
    </div>
</div>

<div class="footer">
    <span>{{ $inst }} — Sistema SGE | Documento generado automáticamente</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
