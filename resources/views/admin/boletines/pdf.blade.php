<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Boletín — {{ optional($matricula->estudiante)->nombre_completo }}</title>
<style>
/* ═══════════════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════════════ */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 9pt;
    color: #1a1a2e;
    background: #fff;
    line-height: 1.35;
}
@page {
    size: letter portrait;
    margin: 1.1cm 1.4cm 1.1cm 1.4cm;
}

/* ═══════════════════════════════════════════════════
   ENCABEZADO INSTITUCIONAL
═══════════════════════════════════════════════════ */
.hdr-outer {
    border: 2.5px solid #1e3a6e;
    border-radius: 4px;
    margin-bottom: 0;
    overflow: hidden;
}
.hdr-top {
    background: #1e3a6e;
    color: #fff;
    text-align: center;
    font-size: 7pt;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    padding: 3px 0 2px;
}
.hdr-body {
    background: #fff;
    padding: 0;
}
.hdr-table {
    width: 100%;
    border-collapse: collapse;
}
.hdr-table td {
    padding: 8px 10px;
    vertical-align: middle;
}
.hdr-logo-cell {
    width: 75px;
    text-align: center;
    border-right: 1px solid #e5e7eb;
    padding: 8px;
}
.logo-img {
    height: 58px;
    max-width: 68px;
    object-fit: contain;
}
.logo-abbr-box {
    width: 58px;
    height: 58px;
    border-radius: 6px;
    background: #1e3a6e;
    color: #fff;
    font-size: 14pt;
    font-weight: 900;
    display: inline-block;
    text-align: center;
    line-height: 58px;
    letter-spacing: .03em;
}
.hdr-center-cell {
    text-align: center;
    padding: 7px 12px;
}
.inst-republica {
    font-size: 7pt;
    font-weight: 700;
    letter-spacing: .15em;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 1px;
}
.inst-minerd {
    font-size: 7pt;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: 4px;
}
.inst-nombre {
    font-size: 14pt;
    font-weight: 900;
    color: #1e3a6e;
    line-height: 1.15;
    letter-spacing: .01em;
}
.inst-nivel {
    font-size: 8pt;
    color: #4b5563;
    font-weight: 600;
    margin-top: 2px;
}
.inst-lema {
    font-size: 7.5pt;
    color: #9ca3af;
    font-style: italic;
    margin-top: 2px;
}
.inst-contacto {
    font-size: 7pt;
    color: #6b7280;
    margin-top: 3px;
}
.hdr-right-cell {
    width: 110px;
    text-align: center;
    border-left: 1px solid #e5e7eb;
    padding: 8px 10px;
    vertical-align: middle;
}
.codigo-box {
    border: 1.5px solid #1e3a6e;
    border-radius: 5px;
    padding: 5px 8px;
    margin-bottom: 6px;
}
.codigo-lbl { font-size: 6.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #6b7280; display: block; }
.codigo-val { font-size: 10pt; font-weight: 900; color: #1e3a6e; display: block; }
.anio-lbl   { font-size: 6.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #6b7280; display: block; margin-bottom: 1px; }
.anio-val   { font-size: 9pt; font-weight: 900; color: #1e3a6e; display: block; }

/* ─── Barra roja de título ─── */
.title-bar {
    background: #c0392b;
    color: #fff;
    text-align: center;
    font-size: 10pt;
    font-weight: 900;
    letter-spacing: .18em;
    text-transform: uppercase;
    padding: 5px 0 4px;
    margin-bottom: 7px;
    border-left: 2.5px solid #1e3a6e;
    border-right: 2.5px solid #1e3a6e;
}

/* ═══════════════════════════════════════════════════
   FICHA DEL ESTUDIANTE
═══════════════════════════════════════════════════ */
.ficha-outer {
    border: 1.5px solid #1e3a6e;
    border-radius: 3px;
    margin-bottom: 7px;
    overflow: hidden;
}
.ficha-header {
    background: #eef3fb;
    border-bottom: 1px solid #c7d6f0;
    padding: 3px 9px;
    font-size: 6.5pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #1e3a6e;
}
.ficha-body {
    display: block;
    width: 100%;
}
.ficha-table {
    width: 100%;
    border-collapse: collapse;
}
.ficha-table td {
    padding: 4px 9px;
    vertical-align: top;
    border-bottom: 1px solid #f3f4f6;
}
.ficha-table tr:last-child td { border-bottom: 0; }
.f-label {
    display: block;
    font-size: 6pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: #9ca3af;
    margin-bottom: 1px;
}
.f-value {
    display: block;
    font-size: 9pt;
    font-weight: 700;
    color: #1a1a2e;
}
.foto-cell {
    width: 78px;
    text-align: center;
    border-left: 1px solid #e5e7eb;
    background: #f8faff;
    vertical-align: middle;
    padding: 6px;
}
.foto-img {
    width: 66px;
    height: 80px;
    object-fit: cover;
    border: 1.5px solid #c7d6f0;
    border-radius: 3px;
}
.foto-placeholder {
    width: 66px;
    height: 80px;
    border: 1.5px dashed #d1d5db;
    border-radius: 3px;
    background: #f3f4f6;
    font-size: 6pt;
    color: #9ca3af;
    text-align: center;
    padding-top: 26px;
    line-height: 1.5;
    display: inline-block;
}

/* ═══════════════════════════════════════════════════
   TÍTULOS DE SECCIÓN
═══════════════════════════════════════════════════ */
.sec-title {
    font-size: 7pt;
    font-weight: 900;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #fff;
    background: #1e3a6e;
    padding: 3px 9px;
    margin-bottom: 0;
    margin-top: 6px;
}

/* ═══════════════════════════════════════════════════
   TABLA DE CALIFICACIONES
═══════════════════════════════════════════════════ */
.notas-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
    margin-bottom: 6px;
    border: 1px solid #c7d6f0;
}
.notas-table thead th {
    background: #2a4f96;
    color: #fff;
    font-size: 7.5pt;
    font-weight: 800;
    padding: 5px 5px;
    text-align: center;
    border: 1px solid #1e3a6e;
}
.notas-table thead th.th-mat {
    text-align: left;
    padding-left: 8px;
    min-width: 125px;
}
.notas-table thead th.th-prom { background: #c0392b; }
.notas-table tbody td {
    padding: 4px 5px;
    border: 1px solid #e5e7eb;
    text-align: center;
    vertical-align: middle;
    font-size: 8.5pt;
}
.notas-table tbody td.td-mat {
    text-align: left;
    padding-left: 8px;
    font-weight: 600;
    color: #1a1a2e;
}
.notas-table tbody tr:nth-child(even) td { background: #f9fbff; }

/* Colores de nota */
.g-ex { background: #d1fae5 !important; color: #065f46; font-weight: 800; }
.g-bu { background: #dbeafe !important; color: #1e40af; font-weight: 800; }
.g-pr { background: #fef3c7 !important; color: #92400e; font-weight: 800; }
.g-in { background: #fee2e2 !important; color: #991b1b; font-weight: 800; }
.g-na { color: #d1d5db; }

/* Badge indicador */
.ind { font-size: 7pt; font-weight: 800; padding: 1px 5px; border-radius: 3px; white-space: nowrap; }
.ind-e { background: #d1fae5; color: #065f46; }
.ind-b { background: #dbeafe; color: #1e40af; }
.ind-p { background: #fef3c7; color: #92400e; }
.ind-i { background: #fee2e2; color: #991b1b; }
.ind-v { background: #f3f4f6; color: #9ca3af; }

/* Fila promedio general */
.prom-row td {
    background: #1e3a6e !important;
    color: #fff !important;
    font-weight: 800;
    font-size: 8.5pt;
    border: 1px solid #0f1f3d;
}
.prom-row td.td-mat { color: #c7d6f0 !important; }
.prom-box {
    display: inline-block;
    background: #fff;
    color: #1e3a6e;
    border-radius: 3px;
    padding: 1px 8px;
    font-size: 10pt;
    font-weight: 900;
}

/* ═══════════════════════════════════════════════════
   ASISTENCIA
═══════════════════════════════════════════════════ */
.asist-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8pt;
    margin-bottom: 6px;
    border: 1px solid #c7d6f0;
}
.asist-table th {
    background: #2a4f96;
    color: #fff;
    font-size: 7pt;
    font-weight: 800;
    padding: 4px 5px;
    text-align: center;
    border: 1px solid #1e3a6e;
}
.asist-table th.th-concepto { text-align: left; padding-left: 8px; min-width: 100px; }
.asist-table td {
    padding: 3px 5px;
    text-align: center;
    border: 1px solid #e5e7eb;
    font-weight: 600;
}
.asist-table td.td-concepto {
    text-align: left;
    background: #f8faff;
    font-weight: 700;
    padding-left: 8px;
    color: #374151;
}
.asist-total { background: #eef3fb !important; font-weight: 800 !important; color: #1e3a6e !important; }
.pct-green  { background: #d1fae5 !important; color: #065f46; font-weight: 800; }
.pct-yellow { background: #fef3c7 !important; color: #92400e; font-weight: 800; }
.pct-red    { background: #fee2e2 !important; color: #991b1b; font-weight: 800; }

/* ═══════════════════════════════════════════════════
   OBSERVACIONES
═══════════════════════════════════════════════════ */
.obs-box {
    border: 1px solid #c7d6f0;
    padding: 6px 9px;
    min-height: 38px;
    font-size: 8.5pt;
    color: #374151;
    margin-bottom: 6px;
    background: #fafbff;
}
.obs-tipo-lbl {
    font-size: 6.5pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: #1e3a6e;
    margin-bottom: 2px;
    margin-top: 4px;
}

/* ═══════════════════════════════════════════════════
   ESTADO ACADÉMICO FINAL
═══════════════════════════════════════════════════ */
.estado-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 6px;
    border: 2px solid #1e3a6e;
    border-radius: 4px;
}
.estado-table td { padding: 7px 11px; vertical-align: middle; }
.estado-left {
    width: 45%;
    border-right: 1px solid #c7d6f0;
    text-align: center;
}
.estado-badge-lbl {
    font-size: 6.5pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
    display: block;
    margin-bottom: 2px;
}
.estado-badge-val {
    font-size: 14pt;
    font-weight: 900;
    display: block;
    line-height: 1.1;
}
.estado-dashed {
    border: 1.5px dashed #d1d5db;
    padding: 7px 10px;
    font-size: 8pt;
    color: #9ca3af;
    font-style: italic;
    margin-bottom: 6px;
}

/* ═══════════════════════════════════════════════════
   SECCIÓN DE FIRMAS
═══════════════════════════════════════════════════ */
.firma-section-hdr {
    background: #1e3a6e;
    color: #fff;
    font-size: 6.5pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .14em;
    padding: 3px 9px;
    margin-top: 10px;
    margin-bottom: 0;
}
.firma-outer {
    border: 1.5px solid #1e3a6e;
    border-top: 0;
}
.firma-table {
    width: 100%;
    border-collapse: collapse;
}
.firma-table td {
    text-align: center;
    padding: 4px 8px 6px;
    vertical-align: bottom;
    font-size: 8pt;
    border-right: 1px solid #e5e7eb;
}
.firma-table td:last-child { border-right: 0; }
.firma-space { height: 34px; }
.firma-line {
    border-top: 1.5px solid #374151;
    padding-top: 3px;
    font-weight: 800;
    color: #1a1a2e;
    font-size: 8pt;
}
.firma-titulo {
    font-size: 6.5pt;
    font-weight: 700;
    color: #6b7280;
    margin-top: 1px;
}
.sello-box {
    width: 58px;
    height: 58px;
    border: 1.5px dashed #9ca3af;
    border-radius: 50%;
    font-size: 6pt;
    color: #9ca3af;
    text-align: center;
    display: inline-block;
    padding-top: 17px;
    line-height: 1.5;
    margin-bottom: 3px;
}
.firma-nota {
    font-size: 6.5pt;
    font-weight: 700;
    color: #9ca3af;
    margin-top: 1px;
}

/* ═══════════════════════════════════════════════════
   PIE DE PÁGINA
═══════════════════════════════════════════════════ */
.footer-bar {
    text-align: center;
    font-size: 6.5pt;
    color: #9ca3af;
    border-top: 1px solid #e5e7eb;
    padding-top: 4px;
    margin-top: 8px;
}

/* ─── PHP helpers ─────────────────────────────────── */
</style>
</head>
<body>

@php
    /* ── Datos config ── */
    $inst       = ($boletinConfig && $boletinConfig->nombre_institucion) ? $boletinConfig->nombre_institucion : 'Centro Educativo';
    $codigoCe   = ($boletinConfig && $boletinConfig->codigo) ? $boletinConfig->codigo : '—';
    $nivel      = ($boletinConfig && $boletinConfig->nivel_educativo) ? $boletinConfig->nivel_educativo : 'Nivel Secundario';
    $regional   = ($boletinConfig && $boletinConfig->regional) ? 'Regional '.$boletinConfig->regional : '';
    $distrito   = ($boletinConfig && $boletinConfig->distrito) ? 'Distrito '.$boletinConfig->distrito : '';
    $municipio  = ($boletinConfig && $boletinConfig->municipio) ? $boletinConfig->municipio : '';
    $direccion  = ($boletinConfig && $boletinConfig->direccion) ? $boletinConfig->direccion : '';
    $telefono   = ($boletinConfig && $boletinConfig->telefono) ? 'Tel. '.$boletinConfig->telefono : '';
    $lema       = ($boletinConfig && $boletinConfig->lema) ? $boletinConfig->lema : '';

    /* ── Contacto de 1 línea ── */
    $contactLine = implode('  ·  ', array_filter([$municipio, $telefono, $direccion]));

    /* ── Autoridades ── */
    $directorFull   = $boletinConfig ? $boletinConfig->nombre_director_completo   : 'Director(a)';
    $encargadoFull  = $boletinConfig ? $boletinConfig->nombre_encargado_completo  : 'Encargado(a) Académico';

    /* ── Tutor del grupo ── */
    $tutorNombre = optional(optional($matricula->grupo)->tutor)->nombre_completo ?? 'Docente Guía';

    /* ── Helpers de nota ── */
    $gc = function($n) {
        if ($n === null) return 'g-na';
        if ($n >= 90) return 'g-ex';
        if ($n >= 75) return 'g-bu';
        if ($n >= 60) return 'g-pr';
        return 'g-in';
    };
    $ic = function($i) {
        return match($i) {
            'Excelente'    => 'ind-e',
            'Bueno'        => 'ind-b',
            'En proceso'   => 'ind-p',
            'Insuficiente' => 'ind-i',
            default        => 'ind-v',
        };
    };
    $pi = function($a) {
        if ($a === null) return null;
        if ($a >= 90) return 'Excelente';
        if ($a >= 75) return 'Bueno';
        if ($a >= 60) return 'En proceso';
        return 'Insuficiente';
    };
    $pctCls = function($p) {
        if ($p === null) return '';
        if ($p >= 90) return 'pct-green';
        if ($p >= 75) return 'pct-yellow';
        return 'pct-red';
    };
    $tlabel = function($t) {
        return match($t) {
            'academica'  => 'Académica',
            'conducta'   => 'Conducta',
            'sugerencia' => 'Sugerencia',
            default      => 'General',
        };
    };
@endphp

{{-- ══════════════════════════════════════════════════════════
     1. ENCABEZADO INSTITUCIONAL
══════════════════════════════════════════════════════════ --}}
<div class="hdr-outer">
    <div class="hdr-top">República Dominicana &nbsp;·&nbsp; Ministerio de Educación (MINERD)</div>
    <div class="hdr-body">
        <table class="hdr-table" cellpadding="0" cellspacing="0">
            <tr>
                {{-- Logo --}}
                <td class="hdr-logo-cell">
                    @if($boletinConfig && $boletinConfig->logo)
                        <img class="logo-img"
                             src="{{ public_path('storage/'.$boletinConfig->logo) }}"
                             alt="Logo">
                    @else
                        <div class="logo-abbr-box">
                            {{ strtoupper(substr($inst, 0, 3)) }}
                        </div>
                    @endif
                </td>
                {{-- Centro --}}
                <td class="hdr-center-cell">
                    @if($regional || $distrito)
                    <div class="inst-republica">{{ implode('  |  ', array_filter([$regional, $distrito])) }}</div>
                    @endif
                    <div class="inst-nombre">{{ $inst }}</div>
                    <div class="inst-nivel">{{ $nivel }}</div>
                    @if($lema)
                        <div class="inst-lema">&ldquo;{{ $lema }}&rdquo;</div>
                    @endif
                    @if($contactLine)
                        <div class="inst-contacto">{{ $contactLine }}</div>
                    @endif
                </td>
                {{-- Código / Año --}}
                <td class="hdr-right-cell">
                    <div class="codigo-box">
                        <span class="codigo-lbl">Código</span>
                        <span class="codigo-val">{{ $codigoCe }}</span>
                    </div>
                    <span class="anio-lbl">Año Escolar</span>
                    <span class="anio-val">{{ $schoolYear ? $schoolYear->nombre : '—' }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ── Barra de título ── --}}
<div class="title-bar">
    &#9670;&nbsp; Boletín de Calificaciones &nbsp;·&nbsp; {{ $periodo->nombre }} &nbsp;&#9670;
</div>

{{-- ══════════════════════════════════════════════════════════
     2. FICHA DEL ESTUDIANTE
══════════════════════════════════════════════════════════ --}}
<div class="ficha-outer">
    <div class="ficha-header">&#9998; Datos del Estudiante</div>
    <table class="ficha-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:44%;">
                <span class="f-label">Nombre Completo</span>
                <span class="f-value">{{ optional($matricula->estudiante)->nombre_completo ?? '—' }}</span>
            </td>
            <td style="width:28%;">
                <span class="f-label">No. Matrícula</span>
                <span class="f-value" style="font-family:monospace;">
                    {{ optional($matricula->estudiante)->numero_matricula ?? '#'.$matricula->id }}
                </span>
            </td>
            <td style="width:28%;">
                <span class="f-label">Cédula / Pasaporte</span>
                <span class="f-value" style="font-family:monospace;">{{ optional($matricula->estudiante)->cedula ?? '—' }}</span>
            </td>
            <td class="foto-cell" rowspan="3">
                @if(optional($matricula->estudiante)->foto)
                    <img class="foto-img"
                         src="{{ public_path('storage/'.$matricula->estudiante->foto) }}"
                         alt="Foto">
                @else
                    <div class="foto-placeholder">Foto<br>Estudiante</div>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <span class="f-label">Grado / Sección</span>
                <span class="f-value">{{ optional($matricula->grupo)->nombre_completo ?? '—' }}</span>
            </td>
            <td>
                <span class="f-label">Período Evaluado</span>
                <span class="f-value">{{ $periodo->nombre }}</span>
            </td>
            <td>
                <span class="f-label">Fecha de Nacimiento</span>
                <span class="f-value">
                    @php $fnac = optional($matricula->estudiante)->fecha_nacimiento; @endphp
                    {{ $fnac ? \Carbon\Carbon::parse($fnac)->format('d/m/Y') : '—' }}
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="f-label">Docente Guía</span>
                <span class="f-value">{{ $tutorNombre }}</span>
            </td>
            <td colspan="2">
                <span class="f-label">Encargado(a) Académico(a)</span>
                <span class="f-value">{{ $encargadoFull }}</span>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════════
     3. TABLA DE CALIFICACIONES
══════════════════════════════════════════════════════════ --}}
<div class="sec-title">&#9654; Calificaciones por Período</div>

@if(empty($tablaNotas))
<div style="border:1px solid #e5e7eb;padding:8px 10px;color:#9ca3af;font-size:8pt;font-style:italic;">
    No hay calificaciones registradas para este período.
</div>
@else
<table class="notas-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th class="th-mat">Asignatura</th>
            @foreach($periodos as $p)
                <th style="width:40px;">{{ $p->nombre_corto ?? 'P'.$p->numero }}</th>
            @endforeach
            <th style="width:38px;background:#0f4c81;color:#fff;font-size:6.5pt;font-weight:800;padding:5px 3px;text-align:center;">Prog.</th>
            <th class="th-prom" style="width:56px;">Prom.<br>Anual</th>
            <th class="th-prom" style="width:70px;">Indicador</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tablaNotas as $row)
        @php
            $pAn  = $row['promedio'] ?? null;
            $ind  = $row['indicador'] ?? null;
            $pCls = $gc($pAn);
            $iCls = $ic($ind);
            $pg   = $progreso[$row['asignacion']->id] ?? null;
        @endphp
        <tr>
            <td class="td-mat">{{ $row['asignatura'] }}</td>
            @foreach($periodos as $p)
            @php
                $cal  = $row['periodos'][$p->id] ?? null;
                $nota = $cal ? $cal->nota_final : null;
                $cls  = $gc($nota);
            @endphp
            <td class="{{ $cls }}">{{ $nota !== null ? number_format($nota,1) : '—' }}</td>
            @endforeach
            <td style="text-align:center;font-size:7.5pt;font-weight:800;">
                @if($pg)
                    @if($pg['direccion']==='sube')
                        <span style="color:#15803d;">↑{{ abs($pg['diff']) }}</span>
                    @elseif($pg['direccion']==='baja')
                        <span style="color:#dc2626;">↓{{ abs($pg['diff']) }}</span>
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                @else
                    <span style="color:#d1d5db;">—</span>
                @endif
            </td>
            <td class="{{ $pCls }}" style="font-weight:800;">
                {{ $pAn !== null ? number_format($pAn,1) : '—' }}
            </td>
            <td>
                <span class="ind {{ $iCls }}">{{ $ind ?? '—' }}</span>
            </td>
        </tr>
        @endforeach

        {{-- Fila promedio general --}}
        @php
            $pgLabel = $pi($promedioGeneral);
            $pgCls   = $ic($pgLabel);
        @endphp
        <tr class="prom-row">
            <td class="td-mat" colspan="{{ $periodos->count() + 1 }}"
                style="text-align:right;padding-right:10px;font-size:7.5pt;letter-spacing:.1em;">
                PROMEDIO GENERAL ANUAL
            </td>
            <td style="text-align:center;">
                <span class="prom-box">
                    {{ $promedioGeneral !== null ? number_format($promedioGeneral,1) : '—' }}
                </span>
            </td>
            <td style="text-align:center;">
                @if($pgLabel)
                    <span class="ind {{ $pgCls }}" style="background:rgba(255,255,255,.18);color:#fff;">
                        {{ $pgLabel }}
                    </span>
                @endif
            </td>
        </tr>
    </tbody>
</table>

{{-- Leyenda --}}
<table width="100%" cellpadding="0" cellspacing="0"
       style="font-size:6.5pt;margin-bottom:5px;border:1px solid #e5e7eb;">
    <tr>
        <td style="padding:3px 8px;background:#f9fafb;color:#6b7280;font-weight:600;">
            Escala: &nbsp;
            <span style="background:#d1fae5;color:#065f46;padding:1px 5px;border-radius:2px;font-weight:800;">90–100 Excelente</span>&nbsp;
            <span style="background:#dbeafe;color:#1e40af;padding:1px 5px;border-radius:2px;font-weight:800;">75–89 Bueno</span>&nbsp;
            <span style="background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:2px;font-weight:800;">60–74 En Proceso</span>&nbsp;
            <span style="background:#fee2e2;color:#991b1b;padding:1px 5px;border-radius:2px;font-weight:800;">&lt;60 Insuficiente</span>
        </td>
    </tr>
</table>
@endif

{{-- ══════════════════════════════════════════════════════════
     4. ASISTENCIA
══════════════════════════════════════════════════════════ --}}
@if(!$boletinConfig || $boletinConfig->mostrar_asistencia)
<div class="sec-title">&#9654; Resumen de Asistencia</div>

<table class="asist-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th class="th-concepto">Concepto</th>
            @foreach($periodos as $p)
                <th>{{ $p->nombre_corto ?? 'P'.$p->numero }}</th>
            @endforeach
            <th class="asist-total" style="background:#0f1f3d;color:#fff;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach([
            'total'       => 'Días de Clase',
            'presente'    => 'Asistencias',
            'ausente'     => 'Ausencias',
            'tardanza'    => 'Tardanzas',
            'justificado' => 'Justificados',
        ] as $key => $label)
        <tr>
            <td class="td-concepto">{{ $label }}</td>
            @foreach($periodos as $p)
            @php $ap = $asistenciaPorPeriodo[$p->id] ?? []; @endphp
            <td>{{ $ap[$key] ?? 0 }}</td>
            @endforeach
            <td class="asist-total">{{ $asistenciaTotales[$key] ?? 0 }}</td>
        </tr>
        @endforeach
        {{-- % Asistencia --}}
        <tr>
            <td class="td-concepto" style="font-weight:800;">% Asistencia</td>
            @foreach($periodos as $p)
            @php $pct = ($asistenciaPorPeriodo[$p->id] ?? [])['pct'] ?? null; @endphp
            <td class="{{ $pctCls($pct) }}">{{ $pct !== null ? number_format($pct,1).'%' : '—' }}</td>
            @endforeach
            @php $tpct = $asistenciaTotales['pct'] ?? null; @endphp
            <td class="asist-total {{ $pctCls($tpct) }}">
                {{ $tpct !== null ? number_format($tpct,1).'%' : '—' }}
            </td>
        </tr>
    </tbody>
</table>
@endif

{{-- ══════════════════════════════════════════════════════════
     5. OBSERVACIONES
══════════════════════════════════════════════════════════ --}}
<div class="sec-title">&#9654; Observaciones</div>

@php
    $hayObs1 = isset($boletinObservaciones) && $boletinObservaciones->isNotEmpty();
    $hayObs2 = isset($observacionesList)    && $observacionesList->isNotEmpty();
    $hayObs3 = $boletinConfig && $boletinConfig->observaciones_generales;
@endphp

<div class="obs-box">
    @if($hayObs1)
        @foreach($boletinObservaciones as $tipo => $items)
        <div class="obs-tipo-lbl">{{ $tlabel($tipo) }}</div>
        @foreach($items as $obs)
        <div style="margin-bottom:3px;">
            @if($obs->docente)
                <span style="color:#9ca3af;font-size:7pt;">({{ optional($obs->docente)->nombre_completo }}):</span>
            @endif
            {{ $obs->contenido }}
        </div>
        @endforeach
        @endforeach
    @endif
    @if($hayObs2)
        @foreach($observacionesList as $obs)
        @if($obs->observaciones)
        <div style="margin-bottom:3px;">
            <strong>{{ optional($obs->asignacion->asignatura)->nombre ?? 'Materia' }}:</strong>
            {{ $obs->observaciones }}
        </div>
        @endif
        @endforeach
    @endif
    @if($hayObs3)
        <div style="border-top:1px dashed #d1d5db;padding-top:4px;margin-top:4px;font-style:italic;">
            {{ $boletinConfig->observaciones_generales }}
        </div>
    @endif
    @if(!$hayObs1 && !$hayObs2 && !$hayObs3)
        <div style="height:24px;">&nbsp;</div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════
     6. ESTADO ACADÉMICO FINAL
══════════════════════════════════════════════════════════ --}}
<div class="sec-title">&#9654; Estado Académico Final</div>

@if(isset($promocion) && $promocion)
@php
    $eBg  = $promocion->estado_color;
    $eLbl = strtoupper($promocion->estado_label);
    $eTxt = match($promocion->estado) {
        'promovido'    => '#065f46',
        'no_promovido' => '#991b1b',
        'condicionado' => '#92400e',
        default        => '#374151',
    };
    $eBrd = $eTxt;
@endphp
<table class="estado-table" cellpadding="0" cellspacing="0">
    <tr>
        <td class="estado-left">
            <span class="estado-badge-lbl" style="color:{{ $eBrd }};">Decisión Académica</span>
            <span class="estado-badge-val" style="color:{{ $eTxt }};background:{{ $eBg }};padding:3px 14px;border-radius:4px;display:inline-block;margin-top:2px;">
                {{ $eLbl }}
            </span>
            @if($promocion->promedio_final !== null)
            <div style="font-size:7.5pt;color:{{ $eBrd }};margin-top:4px;font-weight:700;">
                Promedio anual: {{ number_format($promocion->promedio_final, 2) }}
            </div>
            @endif
        </td>
        <td style="font-size:8pt;color:#374151;">
            @if($promocion->materias_reprobadas)
            <div style="margin-bottom:3px;"><strong>Materias reprobadas:</strong> {{ $promocion->materias_reprobadas }}</div>
            @endif
            @if($promocion->pct_asistencia !== null)
            <div style="margin-bottom:3px;"><strong>Asistencia anual:</strong> {{ number_format($promocion->pct_asistencia, 1) }}%</div>
            @endif
            @if($promocion->observacion)
            <div style="font-style:italic;color:#6b7280;">{{ $promocion->observacion }}</div>
            @endif
            @if(!$promocion->materias_reprobadas && $promocion->pct_asistencia === null && !$promocion->observacion)
            <span style="color:#9ca3af;font-style:italic;">Sin observaciones adicionales.</span>
            @endif
        </td>
    </tr>
</table>
@else
<div class="estado-dashed">
    Estado de promoción pendiente de evaluación por la dirección académica.
</div>
@endif

{{-- ══════════════════════════════════════════════════════════
     7. SECCIÓN DE FIRMAS
══════════════════════════════════════════════════════════ --}}
<div class="firma-section-hdr">&#9998; Certificamos la veracidad de la información</div>
<div class="firma-outer">
    <table class="firma-table" cellpadding="0" cellspacing="0">
        <tr>
            {{-- Director/a --}}
            <td style="width:25%;">
                <div class="firma-space"></div>
                <div class="firma-line">{{ $directorFull }}</div>
                <div class="firma-titulo">Director(a) del Centro</div>
                <div class="firma-nota">Firma y No. Cédula</div>
            </td>
            {{-- Encargado Académico --}}
            <td style="width:25%;">
                <div class="firma-space"></div>
                <div class="firma-line">{{ $encargadoFull }}</div>
                <div class="firma-titulo">Encargado(a) Académico(a)</div>
                <div class="firma-nota">Firma y No. Cédula</div>
            </td>
            {{-- Sello + Docente Guía --}}
            <td style="width:25%;border-right:1px solid #e5e7eb;">
                <div class="firma-space"></div>
                <div class="firma-line">{{ $tutorNombre }}</div>
                <div class="firma-titulo">Docente Guía / Tutor(a)</div>
                <div class="firma-nota">Firma y No. Cédula</div>
            </td>
            {{-- Padre / Madre --}}
            <td style="width:25%;border-right:0;">
                <div style="text-align:center;margin-bottom:4px;">
                    <div class="sello-box">SELLO<br>OFICIAL<br>DEL CENTRO</div>
                </div>
                <div class="firma-titulo" style="text-align:center;">Sello Oficial</div>
            </td>
        </tr>
        {{-- Fila padre/madre --}}
        <tr>
            <td colspan="3"
                style="border-top:1px solid #e5e7eb;padding:4px 8px 6px;font-size:8pt;vertical-align:bottom;">
                <div style="margin-bottom:28px;">&nbsp;</div>
                <div class="firma-line" style="max-width:55%;display:inline-block;">
                    Padre / Madre / Tutor(a) Legal
                </div>
                <div class="firma-titulo">Firma, Nombre y No. Cédula</div>
            </td>
            <td style="border-top:1px solid #e5e7eb;text-align:center;vertical-align:bottom;padding:4px 8px 6px;">
                <div style="font-size:6.5pt;color:#9ca3af;font-style:italic;line-height:1.4;">
                    Fecha de entrega:<br>____/____/________
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════════
     8. PIE DE PÁGINA
══════════════════════════════════════════════════════════ --}}
@php
    $rankP = $rankingGrupo['puesto'] ?? null;
    $rankT = $rankingGrupo['total'] ?? null;
    $verifyCode = strtoupper(substr(md5($matricula->id . $periodo->id . $schoolYear?->id), 0, 10));
@endphp
<div class="footer-bar">
    {{ $inst }}
    @if($boletinConfig && $boletinConfig->codigo)
        &nbsp;·&nbsp; Código CE: {{ $boletinConfig->codigo }}
    @endif
    &nbsp;·&nbsp; Año Escolar: {{ $schoolYear ? $schoolYear->nombre : '—' }}
    @if($rankP) &nbsp;·&nbsp; Puesto {{ $rankP }} de {{ $rankT }} @endif
    &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Verificación: <strong>{{ $verifyCode }}</strong>
    @if($boletinConfig && $boletinConfig->pie_pagina)
        &nbsp;·&nbsp; {{ $boletinConfig->pie_pagina }}
    @endif
</div>

</body>
</html>
