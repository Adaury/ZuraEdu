<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }

    /* ── Cabecera ─────────────────────────────────────────── */
    .header {
        background: #1e3a6e;
        color: #fff;
        padding: 13px 18px 11px;
        margin-bottom: 14px;
    }
    .header h1 { font-size: 14pt; font-weight: bold; margin-bottom: 2px; }
    .header .sub { font-size: 8.5pt; opacity: .85; }

    /* ── Sección titulo ───────────────────────────────────── */
    .section-title {
        font-size: 8pt;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #1e3a6e;
        border-bottom: 1.5px solid #1e3a6e;
        padding-bottom: 4px;
        margin-bottom: 10px;
        margin-top: 14px;
    }

    /* ── Tabla info ───────────────────────────────────────── */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #f8fafc;
        margin-bottom: 4px;
    }
    .info-table td {
        padding: 6px 11px;
        vertical-align: top;
        border-bottom: 1px solid #e5e7eb;
    }
    .info-table tr:last-child td { border-bottom: none; }
    .label {
        font-size: 7.5pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6b7280;
        width: 130px;
    }
    .value { color: #1e293b; }

    /* ── Badge riesgo/estado ──────────────────────────────── */
    .badge {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 20px;
        font-size: 7.5pt;
        font-weight: 700;
    }
    .badge-bajo     { background: #dcfce7; color: #166534; }
    .badge-medio    { background: #fef9c3; color: #854d0e; }
    .badge-alto     { background: #ffedd5; color: #9a3412; }
    .badge-critico  { background: #fee2e2; color: #991b1b; }
    .badge-abierto  { background: #dbeafe; color: #1e40af; }
    .badge-seguimiento { background: #e0e7ff; color: #3730a3; }
    .badge-cerrado  { background: #f3f4f6; color: #374151; }

    /* ── Descripción ──────────────────────────────────────── */
    .descripcion-box {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #f8fafc;
        padding: 8px 11px;
        font-size: 8.5pt;
        color: #374151;
        white-space: pre-line;
        line-height: 1.5;
    }

    /* ── Timeline ─────────────────────────────────────────── */
    .intervencion-block {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 10px;
        page-break-inside: avoid;
    }
    .intervencion-header {
        background: #f1f5f9;
        padding: 6px 11px;
        border-bottom: 1px solid #e5e7eb;
    }
    .num-badge {
        display: inline-block;
        background: #1e3a6e;
        color: #fff;
        border-radius: 3px;
        padding: 1px 6px;
        font-size: 7.5pt;
        font-weight: 700;
        margin-right: 6px;
    }
    .tipo-chip {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 20px;
        font-size: 7.5pt;
        font-weight: 700;
    }
    .tipo-reunion    { background: #dbeafe; color: #1d4ed8; }
    .tipo-llamada    { background: #dcfce7; color: #15803d; }
    .tipo-visita     { background: #fef3c7; color: #b45309; }
    .tipo-derivacion { background: #ede9fe; color: #6d28d9; }
    .tipo-otro       { background: #f3f4f6; color: #374151; }

    .int-fecha { font-size: 7.5pt; color: #6b7280; font-weight: 600; float: right; }
    .int-body  { padding: 7px 11px; }
    .field-label {
        font-size: 7pt; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: #9ca3af; margin-bottom: 2px; margin-top: 6px;
    }
    .field-value { font-size: 8pt; color: #374151; white-space: pre-line; line-height: 1.4; }
    .field-next  { font-size: 8pt; color: #3730a3; white-space: pre-line; line-height: 1.4; }

    /* ── Sin intervenciones ───────────────────────────────── */
    .no-int {
        text-align: center; padding: 20px; color: #9ca3af;
        font-size: 9pt; border: 1px dashed #d1d5db; border-radius: 6px;
    }

    /* ── Firmas ───────────────────────────────────────────── */
    .firmas-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
    }
    .firma-cell {
        width: 33%;
        text-align: center;
        padding: 0 14px;
        vertical-align: bottom;
    }
    .firma-linea {
        border-top: 1px solid #374151;
        margin-top: 36px;
        padding-top: 5px;
        font-size: 8pt;
        color: #374151;
        font-weight: 700;
    }
    .firma-cargo {
        font-size: 7.5pt;
        color: #6b7280;
        margin-top: 1px;
    }

    /* ── Pie ──────────────────────────────────────────────── */
    .footer {
        margin-top: 20px;
        font-size: 7pt;
        color: #9ca3af;
        text-align: center;
        border-top: 1px solid #e5e7eb;
        padding-top: 8px;
    }
</style>
</head>
<body>

{{-- ── CABECERA ─────────────────────────────────────────────── --}}
<div class="header">
    <h1>{{ $inst }}</h1>
    <div class="sub">
        Informe de Seguimiento Social — Caso #{{ $caso->id }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- ── DATOS DEL ESTUDIANTE ──────────────────────────────────── --}}
<div class="section-title">Datos del Estudiante</div>

<table class="info-table">
    <tr>
        <td class="label">Nombre Completo</td>
        <td class="value" style="font-weight:700;">{{ $caso->estudiante->nombre_completo ?? '—' }}</td>
        <td class="label">Matrícula</td>
        <td class="value">{{ $caso->estudiante->numero_matricula ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Cédula</td>
        <td class="value">{{ $caso->estudiante->cedula ?? '—' }}</td>
        <td class="label">Grupo</td>
        <td class="value">{{ $caso->estudiante->matriculaActiva?->grupo?->nombre_completo ?? '—' }}</td>
    </tr>
    @if($caso->estudiante->tutor_nombre)
    <tr>
        <td class="label">Tutor / Representante</td>
        <td class="value">{{ $caso->estudiante->tutor_nombre }}</td>
        <td class="label">Tel. Tutor</td>
        <td class="value">{{ $caso->estudiante->tutor_telefono ?? '—' }}</td>
    </tr>
    @endif
    @if($caso->estudiante->direccion)
    <tr>
        <td class="label">Dirección</td>
        <td class="value" colspan="3">{{ $caso->estudiante->direccion }}</td>
    </tr>
    @endif
</table>

{{-- ── DATOS DEL CASO ─────────────────────────────────────────── --}}
<div class="section-title">Datos del Caso</div>

@php
    $nivelClass  = 'badge-' . $caso->nivel_riesgo;
    $estadoClass = match($caso->estado) {
        'abierto'        => 'badge-abierto',
        'en_seguimiento' => 'badge-seguimiento',
        'cerrado'        => 'badge-cerrado',
        default          => 'badge-cerrado',
    };
@endphp

<table class="info-table">
    <tr>
        <td class="label">Tipo de Caso</td>
        <td class="value">{{ $caso->tipo_label }}</td>
        <td class="label">Nivel de Riesgo</td>
        <td class="value">
            <span class="badge {{ $nivelClass }}">{{ $caso->nivel_riesgo_info['label'] }}</span>
        </td>
    </tr>
    <tr>
        <td class="label">Estado</td>
        <td class="value">
            <span class="badge {{ $estadoClass }}">{{ $caso->estado_info['label'] }}</span>
        </td>
        <td class="label">Responsable</td>
        <td class="value">{{ $caso->responsable->nombre_completo ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Fecha Apertura</td>
        <td class="value">{{ $caso->fecha_apertura?->format('d/m/Y') ?? '—' }}</td>
        <td class="label">Fecha Cierre</td>
        <td class="value">{{ $caso->fecha_cierre?->format('d/m/Y') ?? '—' }}</td>
    </tr>
</table>

<div style="margin-top:8px;">
    <div class="field-label" style="margin-bottom:4px;">Descripción del Caso</div>
    <div class="descripcion-box">{{ $caso->descripcion }}</div>
</div>

{{-- ── INTERVENCIONES ──────────────────────────────────────────── --}}
<div class="section-title">
    Registro de Intervenciones
    <span style="font-size:8pt;font-weight:normal;color:#6b7280;text-transform:none;letter-spacing:0;">
        ({{ $caso->intervenciones->count() }} intervención(es))
    </span>
</div>

@if($caso->intervenciones->isEmpty())
    <div class="no-int">No hay intervenciones registradas para este caso.</div>
@else
    @foreach($caso->intervenciones as $i => $intervencion)
    @php
        $tipoClass = 'tipo-' . $intervencion->tipo_intervencion;
    @endphp
    <div class="intervencion-block">
        <div class="intervencion-header">
            <span class="num-badge">{{ $i + 1 }}</span>
            <span class="tipo-chip {{ $tipoClass }}">{{ $intervencion->tipo_label }}</span>
            <span class="int-fecha">{{ $intervencion->fecha?->format('d/m/Y') }}</span>
        </div>
        <div class="int-body">
            <div class="field-label">Descripción</div>
            <div class="field-value">{{ $intervencion->descripcion }}</div>

            @if($intervencion->resultado)
                <div class="field-label">Resultado</div>
                <div class="field-value">{{ $intervencion->resultado }}</div>
            @endif

            @if($intervencion->siguiente_accion)
                <div class="field-label">Siguiente Acción</div>
                <div class="field-next">{{ $intervencion->siguiente_accion }}</div>
            @endif
        </div>
    </div>
    @endforeach
@endif

{{-- ── FIRMAS ──────────────────────────────────────────────────── --}}
<table class="firmas-table">
    <tr>
        <td class="firma-cell">
            <div class="firma-linea">{{ $caso->responsable->nombre_completo ?? '______________________' }}</div>
            <div class="firma-cargo">Responsable del Caso</div>
        </td>
        <td class="firma-cell">
            <div class="firma-linea">______________________</div>
            <div class="firma-cargo">Director(a) / Coordinador(a)</div>
        </td>
        <td class="firma-cell">
            <div class="firma-linea">{{ $caso->estudiante->tutor_nombre ?? '______________________' }}</div>
            <div class="firma-cargo">Tutor / Representante</div>
        </td>
    </tr>
</table>

{{-- ── PIE ────────────────────────────────────────────────────── --}}
<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Informe de Seguimiento Social — Caso #{{ $caso->id }}
    &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>

</body>
</html>
