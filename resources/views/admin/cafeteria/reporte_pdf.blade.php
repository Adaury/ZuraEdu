<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 6px; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }

.resumen-row { display: flex; gap: 10px; margin-bottom: 14px; }
.chip { flex: 1; text-align: center; padding: 8px 6px; border-radius: 6px; border: 1px solid #e2e8f0; }
.chip .num { font-size: 15px; font-weight: 800; }
.chip .lbl { font-size: 7px; color: #64748b; margin-top: 2px; }
.chip-ventas   { background: #fee2e2; } .chip-ventas .num   { color: #dc2626; }
.chip-recargas { background: #dcfce7; } .chip-recargas .num { color: #15803d; }
.chip-total    { background: #eff6ff; } .chip-total .num    { color: #1d4ed8; }

.section-title { font-size: 10px; font-weight: bold; color: #1e40af; margin: 12px 0 6px; border-bottom: 1px solid #bfdbfe; padding-bottom: 3px; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 7px; text-align: center; font-size: 8px; border: 1px solid #1e40af; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f8faff; }
tbody td { padding: 5px 7px; border: 1px solid #e2e8f0; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.badge { padding: 2px 6px; border-radius: 10px; font-weight: 700; font-size: 7.5px; }
.badge-venta   { background: #fee2e2; color: #991b1b; }
.badge-recarga { background: #dcfce7; color: #065f46; }
.badge-ajuste  { background: #fef9c3; color: #854d0e; }

.footer { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">REPORTE DIARIO DE CAFETERÍA</div>
    <div class="sub">
        Fecha: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
        &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- Resumen --}}
@php
    $cantVentas   = $ventas->where('tipo', 'venta')->count();
    $cantRecargas = $ventas->where('tipo', 'recarga')->count();
@endphp
<div class="resumen-row">
    <div class="chip chip-ventas">
        <div class="num">RD$ {{ number_format($totalVentas, 2) }}</div>
        <div class="lbl">Total Ventas ({{ $cantVentas }})</div>
    </div>
    <div class="chip chip-recargas">
        <div class="num">RD$ {{ number_format($totalRecargas, 2) }}</div>
        <div class="lbl">Total Recargas ({{ $cantRecargas }})</div>
    </div>
    <div class="chip chip-total">
        <div class="num">{{ $ventas->count() }}</div>
        <div class="lbl">Movimientos Totales</div>
    </div>
</div>

{{-- Ventas por categoría --}}
@if($porCategoria->isNotEmpty())
<div class="section-title">Resumen por Categoría de Producto</div>
<table>
    <thead>
        <tr>
            <th class="left">Categoría</th>
            <th>Cantidad</th>
            <th>Total (RD$)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($porCategoria as $cat => $data)
        <tr>
            <td class="left">{{ \App\Models\ProductoCafeteria::CATEGORIAS[$cat] ?? ucfirst($cat) }}</td>
            <td>{{ $data['count'] }}</td>
            <td style="font-weight:700;">{{ number_format($data['total'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Detalle de movimientos --}}
<div class="section-title">Detalle de Movimientos del Día</div>
<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th style="width:45px;">Hora</th>
            <th class="left">Estudiante</th>
            <th style="width:40px;">Tipo</th>
            <th class="left">Producto / Descripción</th>
            <th style="width:70px;">Monto (RD$)</th>
            <th style="width:70px;">Saldo Nuevo</th>
            <th style="width:70px;">Operador</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventas as $i => $v)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $v->created_at->format('H:i') }}</td>
            <td class="left">{{ $v->estudiante?->nombre_completo ?? '—' }}</td>
            <td>
                <span class="badge badge-{{ $v->tipo }}">
                    {{ ucfirst($v->tipo) }}
                </span>
            </td>
            <td class="left">{{ $v->producto?->nombre ?? $v->descripcion ?? '—' }}</td>
            <td style="font-weight:700;
                       color: {{ $v->tipo === 'venta' ? '#dc2626' : '#15803d' }}">
                {{ $v->tipo === 'venta' ? '-' : '+' }}{{ number_format($v->monto, 2) }}
            </td>
            <td>{{ number_format($v->saldo_nuevo, 2) }}</td>
            <td style="color:#6b7280;font-size:8px;">{{ $v->creadoPor?->name ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;color:#94a3b8;font-style:italic;">
                Sin movimientos en esta fecha
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Totales finales --}}
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;padding:6px 10px;font-size:8.5px;color:#1e40af;margin-bottom:10px;text-align:right;">
    <strong>Total vendido: RD$ {{ number_format($totalVentas, 2) }}</strong>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>Total recargado: RD$ {{ number_format($totalRecargas, 2) }}</strong>
</div>

<div class="footer">
    <span>{{ $inst }} — Sistema SGE | Documento generado automáticamente</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
