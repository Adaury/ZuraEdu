<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo de Suscripción #{{ $subscription->id }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }

.hdr { text-align:center; border-bottom:2px solid #1e3a6e; padding-bottom:.65rem; margin-bottom:.85rem; }
.hdr h1 { font-size:12pt; font-weight:900; color:#1e3a6e; }
.hdr .sub { font-size:7.5pt; color:#6b7280; margin-top:2px; }

.recibo-title { text-align:center; background:#1e3a6e; color:#fff; border-radius:6px;
               padding:.5rem 0; font-size:10pt; font-weight:800; margin:.6rem 0; }
.num-recibo   { text-align:center; font-size:8pt; color:#6b7280; margin-bottom:.8rem; }

.section { border:1px solid #e5e7eb; border-radius:6px; margin-bottom:.65rem; overflow:hidden; }
.section-title { background:#f0f4ff; font-size:7pt; font-weight:800; text-transform:uppercase;
                 letter-spacing:.08em; color:#374151; padding:.35rem .7rem; }
.row { display:flex; justify-content:space-between; padding:.3rem .7rem; border-top:1px solid #f3f4f6; font-size:8.5pt; }
.row:first-of-type { border-top:none; }
.label { color:#6b7280; }
.value { font-weight:700; color:#1e293b; }

.monto-box { text-align:center; border:2px solid #1e3a6e; border-radius:8px; padding:.8rem;
             margin:.75rem 0; background:#f0f4ff; }
.monto-box .monto { font-size:18pt; font-weight:900; color:#1e3a6e; }
.monto-box .monto-lbl { font-size:7.5pt; color:#374151; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }

.badge-estado { border-radius:4px; padding:.2rem .55rem; font-size:8pt; font-weight:700; }
.badge-activa { background:#dcfce7; color:#15803d; }
.badge-otro   { background:#f3f4f6; color:#374151; }

.footer { text-align:center; font-size:6.5pt; color:#9ca3af; margin-top:.75rem;
          border-top:1px solid #e5e7eb; padding-top:.35rem; }
</style>
</head>
<body>

@php
    $metodoLabel = match($subscription->metodo_pago) {
        'stripe'        => 'Tarjeta (Stripe)',
        'transferencia' => 'Transferencia bancaria',
        default         => $subscription->metodo_pago ? ucfirst($subscription->metodo_pago) : '—',
    };
@endphp

<div class="hdr">
    <h1>ZuraEdu — Plataforma SaaS</h1>
    <div class="sub">Recibo de suscripción del centro educativo</div>
</div>

<div class="recibo-title">RECIBO DE SUSCRIPCIÓN</div>
<div class="num-recibo">
    No. {{ str_pad($subscription->id, 6, '0', STR_PAD_LEFT) }}
    @if($subscription->referencia_pago) &nbsp;·&nbsp; Ref: {{ Str::limit($subscription->referencia_pago, 30) }} @endif
</div>

<div class="section">
    <div class="section-title">Datos del Centro Educativo</div>
    <div class="row"><span class="label">Institución</span><span class="value">{{ $tenant->nombre_institucion ?? '—' }}</span></div>
    <div class="row"><span class="label">Dominio</span><span class="value">{{ $tenant->dominio ?? '—' }}</span></div>
</div>

<div class="section">
    <div class="section-title">Detalle de la Suscripción</div>
    <div class="row"><span class="label">Plan</span><span class="value">{{ strtoupper($subscription->plan?->nombre ?? '—') }}</span></div>
    <div class="row"><span class="label">Ciclo</span><span class="value">{{ ucfirst($subscription->ciclo ?? '—') }}</span></div>
    <div class="row"><span class="label">Período</span><span class="value">{{ $subscription->fecha_inicio->format('d/m/Y') }} → {{ $subscription->fecha_fin->format('d/m/Y') }}</span></div>
    <div class="row"><span class="label">Método de pago</span><span class="value">{{ $metodoLabel }}</span></div>
    <div class="row"><span class="label">Estado</span>
        <span class="value">
            <span class="badge-estado {{ $subscription->estado === 'activa' ? 'badge-activa' : 'badge-otro' }}">
                {{ ucfirst($subscription->estado) }}
            </span>
        </span>
    </div>
</div>

<div class="monto-box">
    <div class="monto-lbl">Monto Pagado</div>
    <div class="monto">{{ $subscription->moneda ?? 'USD' }} {{ number_format($subscription->monto_pagado, 2) }}</div>
</div>

<div class="footer">
    Emitido por ZuraEdu · {{ now()->format('d/m/Y H:i') }} · Este recibo corresponde a la suscripción de la
    plataforma, no a los pagos de colegiatura de los estudiantes del centro.
</div>

</body>
</html>
