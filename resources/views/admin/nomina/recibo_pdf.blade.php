<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo de Nómina — {{ $pago->mes_formateado }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:9pt; color:#1a1a2e; }

/* ── Encabezado institucional ── */
.hdr { text-align:center; border-bottom:2px solid #1e3a6e; padding-bottom:.65rem; margin-bottom:.85rem; }
.hdr h1 { font-size:12pt; font-weight:900; color:#1e3a6e; }
.hdr .sub { font-size:7.5pt; color:#6b7280; margin-top:2px; }

/* ── Título del recibo ── */
.recibo-title {
    text-align:center; background:#1e3a6e; color:#fff;
    border-radius:6px; padding:.5rem 0;
    font-size:10pt; font-weight:800; margin:.6rem 0;
}
.num-recibo { text-align:center; font-size:8pt; color:#6b7280; margin-bottom:.9rem; }

/* ── Secciones ── */
.section { border:1px solid #e5e7eb; border-radius:6px; margin-bottom:.65rem; overflow:hidden; }
.section-title {
    background:#f0f4ff; font-size:7pt; font-weight:800;
    text-transform:uppercase; letter-spacing:.08em;
    color:#374151; padding:.35rem .7rem;
}
.row { display:flex; justify-content:space-between; padding:.3rem .7rem; border-top:1px solid #f3f4f6; font-size:8.5pt; }
.row:first-of-type { border-top:none; }
.label { color:#6b7280; }
.value { font-weight:700; color:#1e293b; }

/* ── Cuadro de montos ── */
.montos-table { width:100%; border-collapse:collapse; margin:.6rem 0; }
.montos-table td { padding:.4rem .7rem; font-size:8.5pt; border-bottom:1px solid #f3f4f6; }
.montos-table .lbl { color:#6b7280; }
.montos-table .val { text-align:right; font-weight:700; color:#1e293b; }
.montos-table tr.deduccion .val { color:#dc2626; }
.montos-table tr.neto { background:#f0f4ff; }
.montos-table tr.neto .lbl { font-weight:800; color:#1e3a6e; font-size:9pt; }
.montos-table tr.neto .val { font-size:13pt; font-weight:900; color:#1e3a6e; }

/* ── Firma ── */
.sigs { display:flex; justify-content:space-around; margin-top:1.4rem; }
.sig-block { text-align:center; width:130px; }
.sig-line { border-top:1px solid #374151; margin-bottom:.2rem; }
.sig-name { font-size:7.5pt; font-weight:700; }
.sig-role { font-size:6.5pt; color:#6b7280; }

/* ── Badges ── */
.badge-pagado { background:#dcfce7; color:#15803d; border-radius:4px; padding:.15rem .5rem; font-size:8pt; font-weight:700; }
.badge-pendiente { background:#fef9c3; color:#92400e; border-radius:4px; padding:.15rem .5rem; font-size:8pt; font-weight:700; }

/* ── Footer ── */
.footer { text-align:center; font-size:6.5pt; color:#9ca3af; margin-top:.75rem;
          border-top:1px solid #e5e7eb; padding-top:.35rem; }

/* ── Corte copia ── */
.corte {
    text-align:center; border-top:2px dashed #d1d5db;
    margin:1.1rem 0 .6rem; font-size:7pt;
    color:#9ca3af; letter-spacing:.08em; padding-top:.4rem;
}
</style>
</head>
<body>

@php
    $user = $nomina->user;
@endphp

{{-- ══════════════ ORIGINAL ══════════════ --}}

<div class="hdr">
    <h1>{{ $inst }}</h1>
    <div class="sub">Recibo de Pago de Nómina</div>
</div>

<div class="recibo-title">RECIBO DE NÓMINA</div>
<div class="num-recibo">
    No. {{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}
    &nbsp;·&nbsp;
    Período: {{ $pago->mes_formateado }}
</div>

{{-- Datos del empleado --}}
<div class="section">
    <div class="section-title">Datos del Empleado</div>
    <div class="row">
        <span class="label">Nombre Completo</span>
        <span class="value">{{ trim(($user?->name ?? '') . ' ' . ($user?->apellidos ?? '')) ?: '—' }}</span>
    </div>
    <div class="row">
        <span class="label">Email</span>
        <span class="value">{{ $user?->email ?? '—' }}</span>
    </div>
    <div class="row">
        <span class="label">Cargo</span>
        <span class="value">{{ $nomina->cargo }}</span>
    </div>
    <div class="row">
        <span class="label">Tipo de Contrato</span>
        <span class="value">{{ $nomina->tipo_contrato_label }}</span>
    </div>
    @if($nomina->tipo_contrato === 'hora' && $nomina->horas_semana)
    <div class="row">
        <span class="label">Horas / Semana</span>
        <span class="value">{{ $nomina->horas_semana }} h</span>
    </div>
    @endif
    <div class="row">
        <span class="label">Fecha de Ingreso</span>
        <span class="value">{{ $nomina->fecha_ingreso?->format('d/m/Y') ?? '—' }}</span>
    </div>
</div>

{{-- Período --}}
<div class="section">
    <div class="section-title">Período de Pago</div>
    <div class="row">
        <span class="label">Mes</span>
        <span class="value">{{ $pago->mes_formateado }}</span>
    </div>
    <div class="row">
        <span class="label">Estado</span>
        <span class="value">
            @if($pago->pagado)
                <span class="badge-pagado">PAGADO</span>
            @else
                <span class="badge-pendiente">PENDIENTE</span>
            @endif
        </span>
    </div>
    @if($pago->pagado && $pago->fecha_pago)
    <div class="row">
        <span class="label">Fecha de Pago</span>
        <span class="value">{{ $pago->fecha_pago->format('d/m/Y') }}</span>
    </div>
    @endif
</div>

{{-- Desglose salarial --}}
<div class="section">
    <div class="section-title">Desglose Salarial</div>
    <table class="montos-table">
        <tr>
            <td class="lbl">Salario Bruto</td>
            <td class="val">{{ $mon }} {{ number_format($pago->salario_bruto, 2) }}</td>
        </tr>
        @if($pago->horas_extra > 0)
        <tr>
            <td class="lbl">Horas Extra</td>
            <td class="val">+ {{ $mon }} {{ number_format($pago->horas_extra, 2) }}</td>
        </tr>
        @endif
        @if($pago->bonificacion > 0)
        <tr>
            <td class="lbl">Bonificación</td>
            <td class="val">+ {{ $mon }} {{ number_format($pago->bonificacion, 2) }}</td>
        </tr>
        @endif
        @if($pago->otros_ingresos > 0)
        <tr>
            <td class="lbl">Otros Ingresos</td>
            <td class="val">+ {{ $mon }} {{ number_format($pago->otros_ingresos, 2) }}</td>
        </tr>
        @endif
        <tr class="deduccion">
            <td class="lbl">SFS/TSS ({{ $nomina->tss_porcentaje }}%)</td>
            <td class="val">- {{ $mon }} {{ number_format($pago->desc_tss, 2) }}</td>
        </tr>
        @if($pago->desc_isr > 0)
        <tr class="deduccion">
            <td class="lbl">ISR</td>
            <td class="val">- {{ $mon }} {{ number_format($pago->desc_isr, 2) }}</td>
        </tr>
        @endif
        @if($pago->desc_otros > 0)
        <tr class="deduccion">
            <td class="lbl">Otras Deducciones{{ $pago->notas_deducciones ? ' ('.$pago->notas_deducciones.')' : '' }}</td>
            <td class="val">- {{ $mon }} {{ number_format($pago->desc_otros, 2) }}</td>
        </tr>
        @endif
        <tr class="neto">
            <td class="lbl">SALARIO NETO A PAGAR</td>
            <td class="val">{{ $mon }} {{ number_format($pago->salario_neto, 2) }}</td>
        </tr>
    </table>
</div>

{{-- Firmas --}}
<div class="sigs">
    <div class="sig-block">
        <div style="height:32px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ $dir ?: '______________________' }}</div>
        <div class="sig-role">Director/a</div>
    </div>
    <div class="sig-block">
        <div style="height:32px;"></div>
        <div class="sig-line"></div>
        <div class="sig-name">{{ trim(($user?->name ?? '') . ' ' . ($user?->apellidos ?? '')) ?: '______________________' }}</div>
        <div class="sig-role">Empleado/a — Firma de Conformidad</div>
    </div>
</div>

<div class="footer">
    Emitido por SGE PSAC · {{ now()->format('d/m/Y H:i') }} · Documento válido con sello institucional
</div>

{{-- ══════════════ COPIA ══════════════ --}}
<div class="corte">✂ — — — — — — — — COPIA — — — — — — — — ✂</div>

<div style="text-align:center;margin-bottom:.4rem;">
    <strong style="font-size:9pt;">{{ $inst }}</strong><br>
    <span style="font-size:7.5pt;color:#6b7280;">RECIBO DE NÓMINA No. {{ str_pad($pago->id,6,'0',STR_PAD_LEFT) }}</span>
</div>
<div style="display:flex;justify-content:space-between;font-size:8pt;padding:0 .5rem;margin-bottom:.2rem;">
    <span><b>{{ trim(($user?->name ?? '') . ' ' . ($user?->apellidos ?? '')) ?: '—' }}</b> — {{ $nomina->cargo }}</span>
    <span style="font-weight:900;color:#1e3a6e;">{{ $mon }} {{ number_format($pago->salario_neto,2) }}</span>
</div>
<div style="font-size:7.5pt;color:#6b7280;padding:0 .5rem;">
    Período: {{ $pago->mes_formateado }}
    &nbsp;·&nbsp;
    Bruto: {{ $mon }} {{ number_format($pago->salario_bruto,2) }}
    &nbsp;·&nbsp;
    Deducciones: {{ $mon }} {{ number_format($pago->deducciones,2) }}
    &nbsp;·&nbsp;
    @if($pago->pagado) PAGADO @else PENDIENTE @endif
</div>

</body>
</html>
