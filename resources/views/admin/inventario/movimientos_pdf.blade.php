<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size:10px; color:#1e293b; background:#fff; }

.header { background:linear-gradient(135deg,#1e3a6e 0%,#3b82f6 100%); color:#fff; padding:18px 24px 14px; margin-bottom:18px; }
.header h1 { font-size:15px; font-weight:700; margin-bottom:3px; }
.header p { font-size:9px; opacity:.85; }

.meta { display:flex; gap:12px; margin-bottom:14px; padding:10px 16px; background:#f8fafc; border-radius:6px; border:1px solid #e5e7eb; }
.meta-item { flex:1; }
.meta-label { font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:2px; }
.meta-value { font-size:11px; font-weight:700; color:#111827; }

.summary { display:flex; gap:10px; margin-bottom:14px; }
.sum-box { flex:1; padding:8px 12px; border-radius:6px; text-align:center; border:1px solid #e5e7eb; }
.sum-num { font-size:14px; font-weight:800; }
.sum-lbl { font-size:8px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }

table { width:100%; border-collapse:collapse; font-size:9px; }
thead th { background:#f1f5f9; padding:7px 9px; text-align:left; font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; border-bottom:2px solid #e2e8f0; }
tbody td { padding:6px 9px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
tbody tr:nth-child(even) td { background:#f8fafc; }
tbody tr:last-child td { border-bottom:none; }

.chip { display:inline-block; padding:2px 7px; border-radius:10px; font-size:8px; font-weight:700; }
.chip-entrada { background:#d1fae5; color:#065f46; }
.chip-salida  { background:#fee2e2; color:#991b1b; }
.chip-ajuste  { background:#fef3c7; color:#92400e; }

.footer { margin-top:18px; padding-top:8px; border-top:1px solid #e5e7eb; display:flex; justify-content:space-between; font-size:8px; color:#9ca3af; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Movimientos de Inventario</h1>
    <p>Artículo: {{ $articulo->nombre }} &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

{{-- Datos del artículo --}}
@php
    $catInfo = $articulo->categoria_info;
    $estInfo = $articulo->estado_info;
    $entradas = $movimientos->where('tipo','entrada')->sum('cantidad');
    $salidas  = $movimientos->where('tipo','salida')->sum('cantidad');
    $ajustes  = $movimientos->where('tipo','ajuste')->count();
@endphp

<div class="meta">
    <div class="meta-item">
        <div class="meta-label">Categoría</div>
        <div class="meta-value">{{ $catInfo['label'] }}</div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Estado</div>
        <div class="meta-value">{{ $estInfo['label'] }}</div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Disponible / Total</div>
        <div class="meta-value">{{ $articulo->cantidad_disponible }} / {{ $articulo->cantidad_total }}</div>
    </div>
    @if($articulo->ubicacion)
    <div class="meta-item">
        <div class="meta-label">Ubicación</div>
        <div class="meta-value">{{ $articulo->ubicacion }}</div>
    </div>
    @endif
</div>

<div class="summary">
    <div class="sum-box" style="background:#d1fae5; border-color:#6ee7b7;">
        <div class="sum-num" style="color:#065f46;">+{{ $entradas }}</div>
        <div class="sum-lbl">Entradas</div>
    </div>
    <div class="sum-box" style="background:#fee2e2; border-color:#fca5a5;">
        <div class="sum-num" style="color:#991b1b;">-{{ $salidas }}</div>
        <div class="sum-lbl">Salidas</div>
    </div>
    <div class="sum-box" style="background:#fef3c7; border-color:#fde68a;">
        <div class="sum-num" style="color:#92400e;">{{ $ajustes }}</div>
        <div class="sum-lbl">Ajustes</div>
    </div>
    <div class="sum-box" style="background:#f0f4ff; border-color:#c7d2fe;">
        <div class="sum-num" style="color:#3730a3;">{{ $movimientos->count() }}</div>
        <div class="sum-lbl">Total registros</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>Motivo</th>
            <th>Usuario</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movimientos as $i => $mov)
        <tr>
            <td style="color:#9ca3af;">{{ $i + 1 }}</td>
            <td style="white-space:nowrap;">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
            <td>
                <span class="chip chip-{{ $mov->tipo }}">{{ ucfirst($mov->tipo) }}</span>
            </td>
            <td style="font-weight:800; color:{{ $mov->tipo === 'entrada' ? '#065f46' : ($mov->tipo === 'salida' ? '#991b1b' : '#92400e') }};">
                {{ $mov->tipo_info['sign'] }}{{ $mov->cantidad }}
            </td>
            <td>{{ $mov->motivo }}</td>
            <td style="color:#6b7280;">{{ $mov->usuario?->name ?? 'Sistema' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center; padding:20px; color:#9ca3af;">
                Sin movimientos registrados para este artículo.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }}</span>
    <span>Reporte generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}</span>
</div>

</body>
</html>
