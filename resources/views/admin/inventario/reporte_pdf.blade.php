<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

/* ── Encabezado ─────────────────────────────────────────────────────── */
.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; letter-spacing: .04em; }
.header .label { font-size: 9px; font-weight: bold; color: #0f172a; margin-top: 6px;
                 background: #dbeafe; padding: 3px 14px; border-radius: 4px; display: inline-block; }
.header .sub   { font-size: 8px; color: #64748b; margin-top: 3px; }

/* ── Chips resumen ----------------------------------------------------------*/
.chips-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
.chip { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px;
        border-radius: 20px; font-size: 7.5px; font-weight: 700; }

/* ── Tarjetas stats ─────────────────────────────────────────────────────── */
.stats-row { display: flex; gap: 8px; margin-bottom: 12px; }
.stat-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;
            padding: 6px 10px; text-align: center; }
.stat-val  { font-size: 14px; font-weight: 800; color: #1e293b; line-height: 1.1; }
.stat-lbl  { font-size: 7px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }

/* ── Sección por categoría ──────────────────────────────────────────────── */
.cat-title { font-size: 9px; font-weight: 800; padding: 5px 10px; margin-top: 10px;
             border-radius: 5px 5px 0 0; }

/* ── Tabla ──────────────────────────────────────────────────────────────── */
table { width: 100%; border-collapse: collapse; }
thead th { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
           color: #fff; background: #1e3a6e; padding: 5px 7px; border: 1px solid #1e3a6e; }
tbody td { font-size: 8px; padding: 4px 7px; border: 1px solid #e5e7eb; vertical-align: middle; }
tbody tr:nth-child(even) td { background: #f8fafc; }

.badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 7px; font-weight: 700; }

/* Barra de disponibilidad */
.qty-wrap { display: flex; align-items: center; gap: 4px; }
.qty-track { width: 50px; height: 5px; border-radius: 3px; background: #e5e7eb; display: inline-block; overflow: hidden; }
.qty-fill  { height: 100%; border-radius: 3px; display: block; }

/* ── Footer ─────────────────────────────────────────────────────────────── */
.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 6px;
          display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; }
</style>
</head>
<body>

{{-- Encabezado --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div><span class="label">REPORTE DE INVENTARIO ESCOLAR</span></div>
    <div class="sub">Generado el {{ now()->translatedFormat('d \d\e F \d\e Y') }} a las {{ now()->format('H:i') }}</div>
</div>

{{-- Tarjetas stats --}}
@php
    $totalArticulos   = $articulos->count();
    $totalDisponibles = $articulos->sum('cantidad_disponible');
    $totalUnidades    = $articulos->sum('cantidad_total');
    $enMalEstado      = $articulos->where('estado', 'malo')->count();
    $valorTotal       = $articulos->sum(fn($a) => ($a->costo_unitario ?? 0) * $a->cantidad_total);

    $categorias = \App\Models\ArticuloInventario::CATEGORIAS;
    $estados    = \App\Models\ArticuloInventario::ESTADOS;
@endphp

<div class="stats-row">
    <div class="stat-box">
        <div class="stat-val">{{ $totalArticulos }}</div>
        <div class="stat-lbl">Total Artículos</div>
    </div>
    <div class="stat-box">
        <div class="stat-val">{{ $porCategoria->count() }}</div>
        <div class="stat-lbl">Categorías</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:#065f46;">{{ $totalDisponibles }}</div>
        <div class="stat-lbl">Disponibles</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:#1d4ed8;">{{ $totalUnidades }}</div>
        <div class="stat-lbl">Totales</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:#991b1b;">{{ $enMalEstado }}</div>
        <div class="stat-lbl">Mal Estado</div>
    </div>
    @if($valorTotal > 0)
    <div class="stat-box" style="background:#1e3a6e; border-color:#1e3a6e;">
        <div class="stat-val" style="color:#fff; font-size:10px;">RD${{ number_format($valorTotal, 2) }}</div>
        <div class="stat-lbl" style="color:rgba(255,255,255,.7);">Valor Total</div>
    </div>
    @endif
</div>

{{-- Chips por categoría --}}
@if($porCategoria->isNotEmpty())
<div class="chips-row">
    @foreach($categorias as $key => $cat)
    @if(isset($porCategoria[$key]))
    <span class="chip" style="background:{{ $cat['color'] }}; color:{{ $cat['text'] }};">
        {{ $cat['label'] }}: {{ $porCategoria[$key] }}
    </span>
    @endif
    @endforeach
</div>
@endif

{{-- Tabla de artículos agrupada por categoría --}}
@php $articulosPorCat = $articulos->groupBy('categoria'); @endphp

@foreach($articulosPorCat as $catKey => $items)
@php $catInfo = $categorias[$catKey] ?? $categorias['otro']; @endphp

<div class="cat-title" style="background:{{ $catInfo['color'] }}; color:{{ $catInfo['text'] }};">
    {{ $catInfo['label'] }} ({{ $items->count() }} artículo{{ $items->count() !== 1 ? 's' : '' }})
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th style="width:180px;">Artículo</th>
            <th style="width:72px;">Estado</th>
            <th style="width:100px;">Disponible / Total</th>
            <th style="width:80px;">Costo U.</th>
            <th style="width:85px;">Valor Total</th>
            <th style="width:90px;">Ubicación</th>
            <th>Descripción</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $art)
        @php
            $estInfo  = $estados[$art->estado] ?? $estados['bueno'];
            $pct      = $art->cantidad_total > 0
                ? round(($art->cantidad_disponible / $art->cantidad_total) * 100)
                : 0;
            $barColor = $pct > 60 ? '#10b981' : ($pct > 30 ? '#f59e0b' : '#ef4444');
        @endphp
        <tr>
            <td style="color:#9ca3af; text-align:center;">{{ $i + 1 }}</td>
            <td style="font-weight:700; color:#111827;">{{ $art->nombre }}</td>
            <td>
                <span class="badge" style="background:{{ $estInfo['color'] }}; color:{{ $estInfo['text'] }};">
                    {{ $estInfo['label'] }}
                </span>
            </td>
            <td>
                <div class="qty-wrap">
                    <strong>{{ $art->cantidad_disponible }}</strong>
                    <span class="qty-track">
                        <span class="qty-fill" style="width:{{ $pct }}%; background:{{ $barColor }};"></span>
                    </span>
                    <span style="color:#9ca3af;">/ {{ $art->cantidad_total }}</span>
                </div>
            </td>
            <td style="color:#374151; text-align:right;">
                {{ $art->costo_unitario ? 'RD$' . number_format($art->costo_unitario, 2) : '—' }}
            </td>
            <td style="font-weight:700; color:#1e3a6e; text-align:right;">
                {{ $art->costo_unitario ? 'RD$' . number_format($art->valor_total, 2) : '—' }}
            </td>
            <td style="color:#6b7280;">{{ $art->ubicacion ?? '—' }}</td>
            <td style="color:#374151;">{{ $art->descripcion ? \Illuminate\Support\Str::limit($art->descripcion, 60) : '—' }}</td>
        </tr>
        @endforeach
        @if($items->sum('costo_unitario') > 0)
        <tr>
            <td colspan="5" style="text-align:right; font-weight:800; color:#1e3a6e; font-size:8.5px;">Subtotal categoría:</td>
            <td style="font-weight:800; color:#1e3a6e; text-align:right;">
                RD${{ number_format($items->sum(fn($a) => ($a->costo_unitario ?? 0) * $a->cantidad_total), 2) }}
            </td>
            <td colspan="2"></td>
        </tr>
        @endif
    </tbody>
</table>
@endforeach

@if($valorTotal > 0)
<div style="margin-top:12px; background:#1e3a6e; border-radius:6px; padding:8px 14px; display:flex; justify-content:space-between; align-items:center;">
    <span style="font-size:9px; font-weight:700; color:rgba(255,255,255,.8); text-transform:uppercase; letter-spacing:.04em;">Valor Total del Inventario</span>
    <span style="font-size:14px; font-weight:800; color:#fff;">RD${{ number_format($valorTotal, 2) }}</span>
</div>
@endif

@if($totalMalo > 0)
<div style="margin-top:8px; background:#fff1f2; border:1px solid #fecdd3; border-radius:6px; padding:7px 12px; font-size:8px; color:#991b1b;">
    <strong>Atención:</strong> {{ $totalMalo }} artículo{{ $totalMalo !== 1 ? 's' : '' }} se encuentran en mal estado y pueden requerir reposición o mantenimiento.
</div>
@endif

<div class="footer">
    <span>{{ $inst }} — Inventario Escolar</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
