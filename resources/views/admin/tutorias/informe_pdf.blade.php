<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; }

    /* ── Cabecera ── */
    .header {
        background: #1e3a6e;
        color: #fff;
        padding: 13px 18px 11px;
        margin-bottom: 14px;
    }
    .header h1 { font-size: 14pt; font-weight: bold; margin-bottom: 2px; }
    .header .sub { font-size: 8.5pt; opacity: .85; }

    /* ── Info tutor / grupo ── */
    .info-grid {
        display: table;
        width: 100%;
        margin-bottom: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #f8fafc;
    }
    .info-row { display: table-row; }
    .info-cell {
        display: table-cell;
        padding: 6px 11px;
        font-size: 8.5pt;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    .info-cell:last-child { border-bottom: none; }
    .info-label { color: #6b7280; font-weight: 700; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .05em; }
    .info-value { color: #1e293b; }

    /* ── Estadísticas ── */
    .stats-bar {
        background: #1e3a6e;
        color: #fff;
        padding: 7px 14px;
        margin-bottom: 14px;
        border-radius: 5px;
        font-size: 8pt;
    }
    .stats-bar span { margin-right: 20px; }

    /* ── Sesiones ── */
    .section-title {
        font-size: 8pt;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #1e3a6e;
        border-bottom: 1.5px solid #1e3a6e;
        padding-bottom: 4px;
        margin-bottom: 10px;
    }

    .sesion-block {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 10px;
        page-break-inside: avoid;
    }
    .sesion-header {
        background: #f1f5f9;
        padding: 6px 11px;
        border-bottom: 1px solid #e5e7eb;
    }
    .sesion-num {
        display: inline-block;
        background: #1e3a6e;
        color: #fff;
        border-radius: 3px;
        padding: 1px 6px;
        font-size: 7.5pt;
        font-weight: 700;
        margin-right: 6px;
    }
    .sesion-fecha { font-size: 7.5pt; color: #6b7280; font-weight: 600; }
    .sesion-tema  { font-weight: 700; font-size: 9pt; color: #1e293b; }
    .sesion-body  { padding: 7px 11px; }

    .field-label {
        font-size: 7pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #9ca3af;
        margin-bottom: 2px;
        margin-top: 6px;
    }
    .field-value { font-size: 8pt; color: #374151; white-space: pre-line; }

    .chip-proxima {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 20px;
        padding: 1px 8px;
        font-size: 7.5pt;
        font-weight: 700;
        margin-top: 5px;
    }

    /* ── Sin sesiones ── */
    .no-sesiones {
        text-align: center;
        padding: 24px 0;
        color: #9ca3af;
        font-size: 9pt;
        border: 1px dashed #d1d5db;
        border-radius: 6px;
    }

    /* ── Pie de página ── */
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

{{-- ── CABECERA ─────────────────────────────────────────── --}}
<div class="header">
    <h1>{{ $inst }}</h1>
    <div class="sub">
        Informe de Tutoría &nbsp;·&nbsp;
        {{ $tutoria->schoolYear?->nombre ?? '' }} &nbsp;·&nbsp;
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- ── DATOS DEL TUTOR Y GRUPO ─────────────────────────── --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:8.5pt;border:1px solid #e5e7eb;border-radius:6px;background:#f8fafc;">
    <tr style="border-bottom:1px solid #e5e7eb;">
        <td style="padding:6px 11px;width:50%;border-right:1px solid #e5e7eb;vertical-align:top;">
            <div class="info-label" style="font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:2px;">Docente Tutor</div>
            <div style="font-weight:700;color:#1e293b;font-size:9.5pt;">{{ $tutoria->docente->nombre_completo ?? '—' }}</div>
            @if($tutoria->docente?->especialidad)
                <div style="font-size:7.5pt;color:#6b7280;">{{ $tutoria->docente->especialidad }}</div>
            @endif
            @if($tutoria->docente?->email)
                <div style="font-size:7.5pt;color:#6b7280;">{{ $tutoria->docente->email }}</div>
            @endif
        </td>
        <td style="padding:6px 11px;width:50%;vertical-align:top;">
            <div class="info-label" style="font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:2px;">Grupo Asignado</div>
            <div style="font-weight:700;color:#1e293b;font-size:9.5pt;">{{ $tutoria->grupo->nombre_completo ?? '—' }}</div>
            @if($tutoria->grupo?->aula)
                <div style="font-size:7.5pt;color:#6b7280;">Aula: {{ $tutoria->grupo->aula }}</div>
            @endif
            <div style="font-size:7.5pt;color:#6b7280;">
                {{ $tutoria->grupo->estudiantes->count() }} estudiante(s) matriculado(s)
            </div>
        </td>
    </tr>
    @if($tutoria->descripcion)
    <tr>
        <td colspan="2" style="padding:6px 11px;">
            <div class="info-label" style="font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:2px;">Descripción / Objetivos</div>
            <div style="font-size:8pt;color:#374151;">{{ $tutoria->descripcion }}</div>
        </td>
    </tr>
    @endif
</table>

{{-- ── BARRA DE ESTADÍSTICAS ───────────────────────────── --}}
<div class="stats-bar">
    <span><strong>Total sesiones:</strong> {{ $tutoria->sesionesAsc->count() }}</span>
    @if($tutoria->sesionesAsc->isNotEmpty())
        <span><strong>Primera sesión:</strong> {{ $tutoria->sesionesAsc->first()->fecha?->format('d/m/Y') }}</span>
        <span><strong>Última sesión:</strong> {{ $tutoria->sesionesAsc->last()->fecha?->format('d/m/Y') }}</span>
    @endif
</div>

{{-- ── LISTADO DE SESIONES ─────────────────────────────── --}}
<div class="section-title">Registro de Sesiones</div>

@if($tutoria->sesionesAsc->isEmpty())
    <div class="no-sesiones">No hay sesiones registradas para esta tutoría.</div>
@else
    @foreach($tutoria->sesionesAsc as $i => $sesion)
    <div class="sesion-block">

        {{-- Encabezado de la sesión --}}
        <div class="sesion-header">
            <span class="sesion-num">{{ $i + 1 }}</span>
            <span class="sesion-tema">{{ $sesion->tema }}</span>
            <span class="sesion-fecha" style="float:right;">
                {{ $sesion->fecha?->format('d/m/Y') }}
            </span>
        </div>

        {{-- Cuerpo de la sesión --}}
        <div class="sesion-body">
            @if($sesion->descripcion)
                <div class="field-label">Descripción / Desarrollo</div>
                <div class="field-value">{{ $sesion->descripcion }}</div>
            @endif

            @if($sesion->estudiantes_atendidos)
                <div class="field-label">Estudiantes Atendidos</div>
                <div class="field-value">{{ $sesion->estudiantes_atendidos }}</div>
            @endif

            @if($sesion->acuerdos)
                <div class="field-label">Acuerdos / Compromisos</div>
                <div class="field-value">{{ $sesion->acuerdos }}</div>
            @endif

            @if($sesion->proxima_sesion)
                <div class="chip-proxima">
                    Próxima sesión: {{ $sesion->proxima_sesion->format('d/m/Y') }}
                </div>
            @endif

            @if(!$sesion->descripcion && !$sesion->estudiantes_atendidos && !$sesion->acuerdos && !$sesion->proxima_sesion)
                <div style="color:#9ca3af;font-size:8pt;font-style:italic;">Sin detalles adicionales registrados.</div>
            @endif
        </div>
    </div>
    @endforeach
@endif

{{-- ── PIE ─────────────────────────────────────────────── --}}
<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Informe de Tutoría — {{ $tutoria->grupo->nombre_completo ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>

</body>
</html>
