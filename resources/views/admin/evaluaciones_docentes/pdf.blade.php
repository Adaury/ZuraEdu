<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

/* Header */
.header { text-align: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #1e40af; }
.header .inst   { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 6px; }
.header .sub    { font-size: 8px; color: #6b7280; margin-top: 3px; }

/* Docente card */
.docente-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
.doc-row { display: flex; gap: 14px; }
.doc-col { flex: 1; }
.doc-lbl { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px; letter-spacing: .04em; }
.doc-val { font-size: 10px; font-weight: 700; color: #1e293b; }

/* Promedio box */
.promedio-box {
    display: inline-block;
    background: linear-gradient(135deg, #1e40af, #3b82f6);
    color: #fff;
    border-radius: 8px;
    padding: 8px 16px;
    text-align: center;
    margin-bottom: 14px;
    float: right;
}
.prom-num { font-size: 26px; font-weight: 900; line-height: 1; }
.prom-lbl { font-size: 7px; opacity: .8; text-transform: uppercase; letter-spacing: .06em; margin-top: 2px; }
.prom-nivel { font-size: 9px; font-weight: 700; background: rgba(255,255,255,.2); border-radius: 20px; padding: 2px 8px; margin-top: 4px; display: inline-block; }

/* Nivel colors */
.nivel-excelente  { background: #dcfce7; color: #166534; }
.nivel-bueno      { background: #dbeafe; color: #1e40af; }
.nivel-regular    { background: #fef9c3; color: #854d0e; }
.nivel-deficiente { background: #fee2e2; color: #991b1b; }

/* Section title */
.section-title {
    font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: #6b7280;
    border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin: 12px 0 7px;
    letter-spacing: .05em;
}

/* Criterios table */
table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 7px; font-size: 8px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 5px 7px; border: 1px solid #bfdbfe; font-size: 8.5px; vertical-align: middle; }
tbody td.left { text-align: left; }
tbody td.center { text-align: center; }

/* Bar */
.bar-wrap { background: #e2e8f0; border-radius: 3px; height: 8px; width: 80px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 8px; border-radius: 3px; display: block; }

/* Stars */
.stars { font-size: 10px; color: #f59e0b; letter-spacing: 1px; }
.star-empty { color: #d1d5db; }

/* Observaciones */
.obs-box {
    border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px;
    font-size: 8.5px; color: #374151; line-height: 1.6;
    background: #f8faff; margin-top: 4px; white-space: pre-wrap;
}

/* Firmas */
.firma-row { display: flex; gap: 20px; margin-top: 30px; }
.firma-box {
    flex: 1; text-align: center; padding-top: 5px;
    border-top: 1px solid #94a3b8; font-size: 8px; color: #475569;
    margin-top: 22px;
}

/* Footer */
.footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 7px;
    display: flex; justify-content: space-between;
    font-size: 7.5px; color: #94a3b8;
}

/* Clearfix */
.cf::after { content: ''; display: table; clear: both; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">INFORME DE EVALUACIÓN DE DESEMPEÑO DOCENTE</div>
    <div class="sub">Generado: {{ now()->format('d/m/Y H:i') }}</div>
</div>

@php
    $ev    = $evaluacion;
    $nivel = $ev->nivelDesempeno();
    $nClass = 'nivel-' . strtolower($nivel['label']);
    $criteriosLista = [
        ['key' => 'puntualidad',          'label' => 'Puntualidad y Asistencia'],
        ['key' => 'dominio_contenido',     'label' => 'Dominio del Contenido'],
        ['key' => 'metodologia',           'label' => 'Metodología de Enseñanza'],
        ['key' => 'relacion_estudiantes',  'label' => 'Relación con Estudiantes'],
        ['key' => 'planificacion',         'label' => 'Planificación Docente'],
    ];
    $barColores = [5=>'#22c55e', 4=>'#84cc16', 3=>'#f59e0b', 2=>'#f97316', 1=>'#ef4444'];
    $etiquetas  = [1=>'Deficiente', 2=>'Regular', 3=>'Bueno', 4=>'Muy Bueno', 5=>'Excelente'];
@endphp

{{-- Promedio flotante + datos docente --}}
<div class="cf">
    <div class="promedio-box">
        <div class="prom-num">{{ number_format($ev->promedio, 2) }}</div>
        <div class="prom-lbl">Promedio Final</div>
        <div class="prom-nivel">{{ $nivel['label'] }}</div>
    </div>

    <div class="docente-card" style="overflow:hidden;">
        <div class="doc-row">
            <div class="doc-col">
                <div class="doc-lbl">Docente</div>
                <div class="doc-val" style="font-size:11px;">{{ $ev->docente->nombre_completo }}</div>
            </div>
            <div class="doc-col">
                <div class="doc-lbl">Cédula</div>
                <div class="doc-val">{{ $ev->docente->cedula ?? '—' }}</div>
            </div>
            <div class="doc-col">
                <div class="doc-lbl">Especialidad</div>
                <div class="doc-val" style="font-size:9px;">{{ $ev->docente->especialidad ?? '—' }}</div>
            </div>
        </div>
        <div class="doc-row" style="margin-top:6px;">
            <div class="doc-col">
                <div class="doc-lbl">Período Evaluado</div>
                <div class="doc-val">{{ $ev->periodo_evaluado }}</div>
            </div>
            <div class="doc-col">
                <div class="doc-lbl">Evaluador</div>
                <div class="doc-val">{{ $ev->evaluador->name ?? '—' }}</div>
            </div>
            <div class="doc-col">
                <div class="doc-lbl">Fecha de Evaluación</div>
                <div class="doc-val">{{ $ev->created_at->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Criterios de Evaluación --}}
<div class="section-title">Resultados por Criterio de Evaluación</div>
<table>
    <thead>
        <tr>
            <th class="left" style="width:160px;">Criterio</th>
            <th style="width:70px;">Puntaje</th>
            <th style="width:80px;">Nivel</th>
            <th style="width:90px;">Barra</th>
            <th>Estrellas</th>
        </tr>
    </thead>
    <tbody>
        @foreach($criteriosLista as $crit)
        @php
            $val    = $ev->{$crit['key']};
            $pct    = ($val / 5) * 100;
            $color  = $barColores[$val] ?? '#94a3b8';
            $etiq   = $etiquetas[$val] ?? '—';
        @endphp
        <tr>
            <td class="left" style="font-weight:600;">{{ $crit['label'] }}</td>
            <td class="center" style="font-size:12px;font-weight:900;color:{{ $color }};">{{ $val }}/5</td>
            <td class="center">
                <span style="font-size:7.5px;font-weight:700;padding:2px 6px;border-radius:10px;
                             background:{{ $barColores[$val] ?? '#e2e8f0' }}22;color:{{ $color }};">
                    {{ $etiq }}
                </span>
            </td>
            <td class="center">
                <span class="bar-wrap">
                    <span class="bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></span>
                </span>
            </td>
            <td class="center">
                <span class="stars">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $val)&#9733;@else<span class="star-empty">&#9734;</span>@endif
                    @endfor
                </span>
            </td>
        </tr>
        @endforeach

        {{-- Fila de promedio --}}
        <tr style="background:#1e40af;">
            <td class="left" style="font-weight:700;color:#fff;font-size:9px;">PROMEDIO GENERAL</td>
            <td class="center" style="font-size:13px;font-weight:900;color:#fff;">{{ number_format($ev->promedio, 2) }}</td>
            <td class="center" colspan="3">
                <span style="font-size:9px;font-weight:700;padding:3px 10px;border-radius:12px;
                             background:{{ $nivel['color'] }};color:{{ $nivel['text'] }};">
                    {{ $nivel['label'] }}
                </span>
            </td>
        </tr>
    </tbody>
</table>

{{-- Escala de Referencia --}}
<div class="section-title">Escala de Niveles de Desempeño</div>
<table style="margin-bottom:12px;">
    <thead>
        <tr>
            <th>Nivel</th>
            <th>Rango Promedio</th>
            <th>Descripción</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="center"><span style="font-weight:700;color:#166534;background:#dcfce7;padding:2px 7px;border-radius:10px;font-size:8px;">Excelente</span></td>
            <td class="center">4.5 – 5.0</td>
            <td class="left">Desempeño sobresaliente en todos los criterios evaluados.</td>
        </tr>
        <tr>
            <td class="center"><span style="font-weight:700;color:#1e40af;background:#dbeafe;padding:2px 7px;border-radius:10px;font-size:8px;">Bueno</span></td>
            <td class="center">3.5 – 4.4</td>
            <td class="left">Desempeño satisfactorio con oportunidades de mejora puntuales.</td>
        </tr>
        <tr>
            <td class="center"><span style="font-weight:700;color:#854d0e;background:#fef9c3;padding:2px 7px;border-radius:10px;font-size:8px;">Regular</span></td>
            <td class="center">2.5 – 3.4</td>
            <td class="left">Desempeño básico; se requiere acompañamiento y plan de mejora.</td>
        </tr>
        <tr>
            <td class="center"><span style="font-weight:700;color:#991b1b;background:#fee2e2;padding:2px 7px;border-radius:10px;font-size:8px;">Deficiente</span></td>
            <td class="center">1.0 – 2.4</td>
            <td class="left">Desempeño crítico; requiere intervención inmediata.</td>
        </tr>
    </tbody>
</table>

{{-- Observaciones --}}
@if($ev->observaciones)
<div class="section-title">Observaciones del Evaluador</div>
<div class="obs-box">{{ $ev->observaciones }}</div>
@endif

{{-- Firmas --}}
<div class="firma-row">
    <div class="firma-box">
        <div style="font-weight:700;">{{ $ev->docente->nombre_completo }}</div>
        <div style="margin-top:2px;color:#94a3b8;">Docente Evaluado</div>
    </div>
    <div class="firma-box">
        <div style="font-weight:700;">{{ $ev->evaluador->name ?? '________________________________' }}</div>
        <div style="margin-top:2px;color:#94a3b8;">Evaluador / Director Académico</div>
    </div>
    <div class="firma-box">
        <div style="font-weight:700;">________________________________</div>
        <div style="margin-top:2px;color:#94a3b8;">Director/a del Centro</div>
    </div>
</div>

{{-- Footer --}}
<div class="footer">
    <span>{{ $inst }} — Evaluación de Desempeño: {{ $ev->docente->nombre_completo }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>

</body>
</html>
