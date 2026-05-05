<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }

/* ── Encabezado ─────────────────────────────────────────────── */
.header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1e40af; padding-bottom: 12px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .label { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 8px;
                 background: #dbeafe; padding: 4px 20px; border-radius: 4px; display: inline-block; }

/* ── Meta chips ────────────────────────────────────────────── */
.meta-row { display: flex; justify-content: space-between; margin-bottom: 12px;
            background: #f8faff; border: 1px solid #dbeafe; border-radius: 5px; padding: 8px 12px; }
.meta-cell .lbl { font-size: 8px; color: #6b7280; display: block; }
.meta-cell .val { font-size: 9.5px; font-weight: 700; color: #1e293b; }

/* ── Sección títulos ────────────────────────────────────────── */
.section-title { font-size: 9.5px; font-weight: bold; text-transform: uppercase;
                 letter-spacing: .06em; color: #1e40af; border-bottom: 1px solid #bfdbfe;
                 padding-bottom: 3px; margin: 14px 0 6px; }

/* ── Texto contenido ────────────────────────────────────────── */
.content-block { font-size: 10px; line-height: 1.6; color: #1e293b;
                 white-space: pre-wrap; text-align: justify; }

/* ── Tabla de acuerdos ──────────────────────────────────────── */
.acuerdos-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
.acuerdos-table th {
    background: #1e3a8a; color: #fff; font-size: 8.5px;
    padding: 5px 7px; text-align: left; font-weight: 700;
}
.acuerdos-table td {
    font-size: 9px; padding: 5px 7px; vertical-align: top;
    border-bottom: 1px solid #e2e8f0;
}
.acuerdos-table tr:nth-child(even) td { background: #f0f6ff; }
.badge-cumplido   { background: #dcfce7; color: #166534; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 700; }
.badge-pendiente  { background: #fef9c3; color: #854d0e; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 700; }
.badge-vencido    { background: #fee2e2; color: #991b1b; padding: 1px 6px; border-radius: 10px; font-size: 8px; font-weight: 700; }

/* ── Firmas ─────────────────────────────────────────────────── */
.firma-area { display: flex; gap: 24px; margin-top: 40px; }
.firma-box  { flex: 1; text-align: center; }
.firma-line { border-top: 1px solid #94a3b8; padding-top: 6px; margin-top: 32px;
              font-size: 8.5px; color: #475569; }

/* ── Footer ─────────────────────────────────────────────────── */
.footer { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 6px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }

.no-acuerdos { font-size: 9px; color: #94a3b8; text-align: center; padding: 14px; font-style: italic; }
</style>
</head>
<body>

{{-- Encabezado institucional --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    @if($dir)
    <div class="sub">Director/a: {{ $dir }}</div>
    @endif
    <div><span class="label">ACTA DE REUNIÓN</span></div>
</div>

{{-- Meta información --}}
<div class="meta-row">
    <div class="meta-cell">
        <span class="lbl">Tipo de reunión</span>
        <span class="val">{{ $reunion->tipoLabel() }}</span>
    </div>
    <div class="meta-cell">
        <span class="lbl">Fecha y hora</span>
        <span class="val">{{ $reunion->fecha->format('d/m/Y') }} — {{ $reunion->fecha->format('H:i') }}</span>
    </div>
    <div class="meta-cell">
        <span class="lbl">Lugar</span>
        <span class="val">{{ $reunion->lugar ?: '—' }}</span>
    </div>
    <div class="meta-cell">
        <span class="lbl">Estado</span>
        <span class="val">{{ $reunion->estadoLabel() }}</span>
    </div>
    <div class="meta-cell">
        <span class="lbl">Convocante</span>
        <span class="val">{{ $reunion->convocante?->name ?? '—' }}</span>
    </div>
</div>

{{-- Título --}}
<div style="font-size:14px;font-weight:800;color:#0f172a;margin-bottom:10px;">
    {{ $reunion->titulo }}
</div>

{{-- Agenda --}}
@if($reunion->agenda)
<div class="section-title">Agenda</div>
<div class="content-block">{{ $reunion->agenda }}</div>
@endif

{{-- Participantes --}}
@if($reunion->participantes)
<div class="section-title">Participantes</div>
<div class="content-block">{{ $reunion->participantes }}</div>
@endif

{{-- Acuerdos --}}
<div class="section-title">Acuerdos adoptados</div>

@if($reunion->acuerdos->isNotEmpty())
<table class="acuerdos-table">
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:46%;">Descripción del acuerdo</th>
            <th style="width:22%;">Responsable</th>
            <th style="width:14%;">Fecha límite</th>
            <th style="width:14%;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reunion->acuerdos as $i => $acuerdo)
    <tr>
        <td style="text-align:center;font-weight:700;">{{ $i + 1 }}</td>
        <td>{{ $acuerdo->descripcion }}</td>
        <td>{{ $acuerdo->responsable ?: '—' }}</td>
        <td style="text-align:center;">
            {{ $acuerdo->fecha_limite ? $acuerdo->fecha_limite->format('d/m/Y') : '—' }}
        </td>
        <td style="text-align:center;">
            @if($acuerdo->cumplido)
                <span class="badge-cumplido">Cumplido</span>
            @elseif($acuerdo->fecha_limite && now()->gt($acuerdo->fecha_limite))
                <span class="badge-vencido">Vencido</span>
            @else
                <span class="badge-pendiente">Pendiente</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="no-acuerdos">No se registraron acuerdos en esta reunión.</div>
@endif

{{-- Espacio de firmas --}}
<div class="firma-area">
    <div class="firma-box">
        <div class="firma-line">
            <strong>{{ $dir ?: 'Director/a del Centro' }}</strong><br>
            Director/a
        </div>
    </div>
    <div class="firma-box">
        <div class="firma-line">
            <strong>{{ $reunion->convocante?->name ?? 'Secretario/a' }}</strong><br>
            Convocante / Secretario/a
        </div>
    </div>
    <div class="firma-box">
        <div class="firma-line">
            _____________________________<br>
            Representante de los Participantes
        </div>
    </div>
</div>

{{-- Fecha de emisión --}}
<div style="text-align:right;font-size:8.5px;color:#475569;margin-top:14px;">
    {{ $inst }} — {{ now()->translatedFormat('d \d\e F \d\e Y') }}
</div>

<div class="footer">
    <span>{{ $inst }} — Acta de Reunión Oficial</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
