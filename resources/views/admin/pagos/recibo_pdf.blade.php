<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo de Pago #{{ $pago->id }}</title>
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

.badge-pagado { background:#dcfce7; color:#15803d; border-radius:4px; padding:.2rem .55rem;
                font-size:8pt; font-weight:700; }

.sigs { display:flex; justify-content:space-around; margin-top:1.25rem; }
.sig-block { text-align:center; width:120px; }
.sig-line { border-top:1px solid #374151; margin-bottom:.2rem; }
.sig-name { font-size:7.5pt; font-weight:700; }
.sig-role { font-size:6.5pt; color:#6b7280; }

.footer { text-align:center; font-size:6.5pt; color:#9ca3af; margin-top:.75rem;
          border-top:1px solid #e5e7eb; padding-top:.35rem; }
.corte { text-align:center; border-top:2px dashed #d1d5db; margin:1.1rem 0 .6rem;
         font-size:7pt; color:#9ca3af; letter-spacing:.08em; padding-top:.4rem; }
</style>
</head>
<body>

@php
    $est  = $pago->matricula?->estudiante;
    $rep  = $est?->representantes?->first();
    $logoPath = $config?->logo ? public_path('storage/' . $config->logo) : null;
@endphp

{{-- ── Copia Original ── --}}
<div class="hdr">
    <h1>{{ $inst }}</h1>
    <div class="sub">{{ \App\Models\ConfigInstitucional::get('nivel_educativo','') }}</div>
</div>

<div class="recibo-title">RECIBO DE PAGO</div>
<div class="num-recibo">
    No. {{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}
    @if($pago->referencia) &nbsp;·&nbsp; Ref: {{ $pago->referencia }} @endif
</div>

<div class="section">
    <div class="section-title">Datos del Estudiante</div>
    <div class="row"><span class="label">Nombre</span><span class="value">{{ $est?->nombre_completo ?? '—' }}</span></div>
    <div class="row"><span class="label">Matrícula</span><span class="value">{{ $est?->matricula ?? '—' }}</span></div>
    <div class="row"><span class="label">Grado</span><span class="value">{{ $pago->matricula?->grupo?->grado?->nombre ?? '—' }} {{ $pago->matricula?->grupo?->seccion?->nombre ?? '' }}</span></div>
    @if($rep)
    <div class="row"><span class="label">Representante</span><span class="value">{{ $rep->nombres }} {{ $rep->apellidos }}</span></div>
    @endif
</div>

<div class="section">
    <div class="section-title">Detalle del Pago</div>
    <div class="row"><span class="label">Concepto</span><span class="value">{{ $pago->concepto }}</span></div>
    <div class="row"><span class="label">Fecha de pago</span><span class="value">{{ $pago->fecha_pago?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span></div>
    <div class="row"><span class="label">Método</span><span class="value">{{ $pago->metodo_pago ? ucfirst($pago->metodo_pago) : 'Efectivo' }}</span></div>
    <div class="row"><span class="label">Estado</span><span class="value"><span class="badge-pagado">✓ PAGADO</span></span></div>
    @if($pago->registrador)
    <div class="row"><span class="label">Registrado por</span><span class="value">{{ $pago->registrador->name }}</span></div>
    @endif
</div>

<div class="monto-box">
    <div class="monto-lbl">Monto Pagado</div>
    <div class="monto">{{ $mon }} {{ number_format($pago->monto, 2) }}</div>
</div>

<div class="sigs">
    <div class="sig-block">
        <div style="height:30px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $dir ?: '________________________' }}</div>
        <div class="sig-role">Director/a</div>
    </div>
    <div class="sig-block">
        <div style="height:30px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $rep ? $rep->nombres . ' ' . $rep->apellidos : '________________________' }}</div>
        <div class="sig-role">Representante</div>
    </div>
</div>

<div class="footer">
    Emitido por SGE PSAC · {{ now()->format('d/m/Y H:i') }} · Recibo válido con sello del centro
</div>

{{-- ── Corte y copia --}}
<div class="corte">✂ — — — — — — — — COPIA — — — — — — — — ✂</div>

<div style="text-align:center;margin-bottom:.4rem;">
    <strong style="font-size:9pt;">{{ $inst }}</strong><br>
    <span style="font-size:7.5pt;color:#6b7280;">RECIBO No. {{ str_pad($pago->id,6,'0',STR_PAD_LEFT) }}</span>
</div>
<div style="display:flex;justify-content:space-between;font-size:8pt;padding:0 .5rem;">
    <span><b>{{ $est?->nombre_completo ?? '—' }}</b></span>
    <span style="font-weight:900;color:#1e3a6e;">{{ $mon }} {{ number_format($pago->monto,2) }}</span>
</div>
<div style="font-size:7.5pt;color:#6b7280;padding:0 .5rem;">{{ $pago->concepto }} · {{ $pago->fecha_pago?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>

</body>
</html>
