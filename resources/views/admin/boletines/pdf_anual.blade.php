@php
$colorPrimario = ($boletinConfig && $boletinConfig->color_primario)  ? $boletinConfig->color_primario  : '#1e3a6e';
$colorSecund   = ($boletinConfig && $boletinConfig->color_secundario) ? $boletinConfig->color_secundario : '#c0392b';
$logoAncho     = ($boletinConfig && $boletinConfig->logo_ancho)   ? (int)$boletinConfig->logo_ancho   : 52;
$logoAlto      = ($boletinConfig && $boletinConfig->logo_alto)    ? (int)$boletinConfig->logo_alto    : 52;
$tamanoFuente  = ($boletinConfig && $boletinConfig->tamano_fuente)? $boletinConfig->tamano_fuente     : '9pt';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Boletín Anual — {{ $matricula->estudiante?->nombres }} {{ $matricula->estudiante?->apellidos }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:{{ $tamanoFuente }}; color:#1a1a2e; background:#fff; line-height:1.3; }
@page { size:letter landscape; margin:.9cm 1.2cm; }

/* ── Encabezado ── */
.hdr { border:2.5px solid {{ $colorPrimario }}; margin-bottom:6px; }
.hdr-top { background:{{ $colorPrimario }};color:#fff;text-align:center;font-size:6.5pt;font-weight:700;letter-spacing:.15em;text-transform:uppercase;padding:2.5px 0; }
.hdr-body { width:100%;border-collapse:collapse; }
.hdr-body td { padding:6px 10px;vertical-align:middle; }
.hdr-logo { width:70px;text-align:center;border-right:1px solid #e5e7eb; }
.logo-abbr { width:{{ $logoAncho }}px;height:{{ $logoAlto }}px;background:{{ $colorPrimario }};color:#fff;border-radius:5px;font-size:13pt;font-weight:900;display:inline-block;text-align:center;line-height:{{ $logoAlto }}px; }
.hdr-center { text-align:center; }
.inst-name { font-size:13pt;font-weight:900;color:{{ $colorPrimario }};line-height:1.15; }
.inst-sub { font-size:7pt;color:#4b5563;margin-top:1px; }
.hdr-right { width:160px;border-left:1px solid #e5e7eb;text-align:right; }

/* ── Barra roja ── */
.title-bar { background:{{ $colorSecund }};color:#fff;text-align:center;font-size:9pt;font-weight:900;letter-spacing:.16em;text-transform:uppercase;padding:4px 0;margin-bottom:6px; }

/* ── Ficha estudiante ── */
.ficha { border:1.5px solid {{ $colorPrimario }};margin-bottom:6px; }
.ficha-hd { background:#eef3fb;border-bottom:1px solid #c7d6f0;padding:2.5px 8px;font-size:6pt;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:{{ $colorPrimario }}; }
.ficha-body { width:100%;border-collapse:collapse; }
.ficha-body td { padding:3.5px 8px;border-right:1px dashed #e5e7eb;vertical-align:top; }
.ficha-body td:last-child { border-right:0; }
.f-lbl { display:block;font-size:5.5pt;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:1px; }
.f-val { display:block;font-size:8.5pt;font-weight:700;color:#1a1a2e; }

/* ── Tabla anual ── */
.anual-table { width:100%;border-collapse:collapse;font-size:8pt;margin-bottom:6px;border:1px solid #c7d6f0; }
.anual-table thead th { background:{{ $colorPrimario }};color:#fff;padding:4px 5px;text-align:center;font-size:7pt;font-weight:800;border:1px solid rgba(255,255,255,.2); }
.anual-table thead th.th-mat { text-align:left;padding-left:8px;min-width:130px; }
.anual-table thead th.th-final { background:{{ $colorSecund }}; }
.anual-table tbody td { padding:3.5px 5px;border:1px solid #e5e7eb;text-align:center;vertical-align:middle; }
.anual-table tbody td.td-mat { text-align:left;padding-left:8px;font-weight:600; }
.anual-table tbody tr:nth-child(even) td { background:#f9fbff; }
.g-ex { background:#d1fae5!important;color:#065f46;font-weight:800; }
.g-bu { background:#dbeafe!important;color:#1e40af;font-weight:800; }
.g-pr { background:#fef3c7!important;color:#92400e;font-weight:800; }
.g-in { background:#fee2e2!important;color:#991b1b;font-weight:800; }
.g-na { color:#d1d5db; }
.ind { font-size:6.5pt;font-weight:800;padding:1px 5px;border-radius:3px;white-space:nowrap; }
.ind-e { background:#d1fae5;color:#065f46; }
.ind-b { background:#dbeafe;color:#1e40af; }
.ind-p { background:#fef3c7;color:#92400e; }
.ind-i { background:#fee2e2;color:#991b1b; }
.ind-v { background:#f3f4f6;color:#9ca3af; }
.prom-row td { background:{{ $colorPrimario }}!important;color:#fff!important;font-weight:800;font-size:8pt;border:1px solid #0f1f3d; }
.prom-row td.td-mat { color:#c7d6f0!important; }
.prom-box { display:inline-block;background:#fff;color:{{ $colorPrimario }};border-radius:3px;padding:1px 8px;font-size:10pt;font-weight:900; }

/* ── Sección estado ── */
.estado-outer { width:100%;border-collapse:collapse;margin-bottom:6px;border:2px solid {{ $colorPrimario }}; }
.estado-outer td { padding:7px 12px;vertical-align:middle; }
.estado-left { width:42%;border-right:1px solid #c7d6f0;text-align:center; }
.estado-val { font-size:16pt;font-weight:900;display:block;line-height:1.1; }
.estado-lbl { font-size:6pt;font-weight:800;text-transform:uppercase;letter-spacing:.12em;display:block;margin-bottom:2px; }

/* ── Firmas ── */
.firmas { width:100%;border-collapse:collapse;border:1.5px solid {{ $colorPrimario }};margin-top:6px; }
.firmas td { text-align:center;padding:4px 8px 6px;vertical-align:bottom;font-size:7.5pt;border-right:1px solid #e5e7eb; }
.firmas td:last-child { border-right:0; }
.firma-space { height:32px; }
.firma-line { border-top:1.5px solid #374151;padding-top:3px;font-weight:800;color:#1a1a2e;font-size:7.5pt; }
.firma-rol { font-size:6pt;color:#6b7280;margin-top:1px; }
.sello-box { width:52px;height:52px;border:1.5px dashed #9ca3af;border-radius:50%;font-size:5.5pt;color:#9ca3af;text-align:center;display:inline-block;padding-top:14px;line-height:1.5; }

/* ── Pie ── */
.footer-bar { text-align:center;font-size:6pt;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:3px;margin-top:6px; }

/* ── Ranking badge ── */
.ranking-badge { display:inline-block;background:{{ $colorPrimario }};color:#fff;border-radius:4px;padding:2px 8px;font-size:7.5pt;font-weight:800; }
</style>
</head>
<body>

@php
$inst         = $boletinConfig?->nombre_institucion ?: env('SCHOOL_NAME','Centro Educativo');
$codigoCe     = $boletinConfig?->codigo ?: '—';
$nivel        = $boletinConfig?->nivel_educativo ?: 'Nivel Secundario';
$directorFull = $boletinConfig ? $boletinConfig->nombre_director_completo : 'Director(a)';
$encargado    = $boletinConfig ? $boletinConfig->nombre_encargado_completo : 'Encargado(a) Académico';
$tutorNombre  = optional($matricula->grupo?->tutor)->nombre_completo ?? 'Docente Guía';
$est          = $matricula->estudiante;
$rankP = $rankingGrupo['puesto'] ?? null;
$rankT = $rankingGrupo['total'] ?? null;
$gc = fn($n) => $n===null?'g-na':($n>=90?'g-ex':($n>=75?'g-bu':($n>=60?'g-pr':'g-in')));
$ic = fn($i) => match($i){'Excelente'=>'ind-e','Bueno'=>'ind-b','En proceso'=>'ind-p','Insuficiente'=>'ind-i',default=>'ind-v'};
$verifyCode = strtoupper(substr(md5($matricula->id . ($periodos->last()?->id ?? 0) . $schoolYear?->id), 0, 10));
@endphp

{{-- ══ ENCABEZADO ══ --}}
<div class="hdr">
    <div class="hdr-top">República Dominicana &nbsp;·&nbsp; Ministerio de Educación (MINERD)</div>
    <table class="hdr-body">
        <tr>
            <td class="hdr-logo">
                @if($boletinConfig?->logo)
                    <img src="{{ asset('storage/'.$boletinConfig->logo) }}" style="max-width:{{ $logoAncho }}px;max-height:{{ $logoAlto }}px;object-fit:contain;">
                @else
                    <div class="logo-abbr">PSA</div>
                @endif
            </td>
            <td class="hdr-center">
                <div class="inst-name">{{ $inst }}</div>
                <div class="inst-sub">{{ $nivel }} · {{ implode(' · ', array_filter([$boletinConfig?->regional ? 'Regional '.$boletinConfig->regional : null, $boletinConfig?->distrito ? 'Distrito '.$boletinConfig->distrito : null, $boletinConfig?->municipio])) }}</div>
                @if($boletinConfig?->lema)<div style="font-size:6.5pt;color:#9ca3af;font-style:italic;margin-top:1px;">"{{ $boletinConfig->lema }}"</div>@endif
            </td>
            <td class="hdr-right">
                <div><span style="font-size:6pt;color:#6b7280;font-weight:700;text-transform:uppercase;">Código CE</span><br><strong>{{ $codigoCe }}</strong></div>
                <div style="margin-top:4px;"><span style="font-size:6pt;color:#6b7280;font-weight:700;text-transform:uppercase;">Año Escolar</span><br><strong>{{ $schoolYear?->nombre ?? '—' }}</strong></div>
            </td>
        </tr>
    </table>
</div>

<div class="title-bar">✦ &nbsp; BOLETÍN ANUAL DE CALIFICACIONES &nbsp; ✦</div>

{{-- ══ FICHA ESTUDIANTE ══ --}}
<div class="ficha">
    <div class="ficha-hd">Datos del Estudiante</div>
    <table class="ficha-body">
        <tr>
            <td>
                <span class="f-lbl">Nombre Completo</span>
                <span class="f-val">{{ $est?->nombres }} {{ $est?->apellidos }}</span>
            </td>
            <td>
                <span class="f-lbl">No. Matrícula</span>
                <span class="f-val" style="font-family:monospace;">{{ $est?->numero_matricula ?? '#'.$matricula->id }}</span>
            </td>
            <td>
                <span class="f-lbl">Cédula</span>
                <span class="f-val" style="font-family:monospace;">{{ $est?->cedula ?? '—' }}</span>
            </td>
            <td>
                <span class="f-lbl">Grado / Sección</span>
                <span class="f-val">{{ $matricula->grupo?->nombre_completo ?? '—' }}</span>
            </td>
            <td>
                <span class="f-lbl">Docente Guía</span>
                <span class="f-val">{{ $tutorNombre }}</span>
            </td>
            @if($rankP)
            <td style="text-align:center;">
                <span class="f-lbl">Posición en el Grupo</span>
                <span class="ranking-badge">Puesto {{ $rankP }} / {{ $rankT }}</span>
            </td>
            @endif
        </tr>
    </table>
</div>

{{-- ══ TABLA ANUAL ══ --}}
<table class="anual-table">
    <thead>
        <tr>
            <th class="th-mat">Asignatura</th>
            @foreach($periodos as $p)
            <th style="width:50px;">{{ $p->nombre_corto ?? 'P'.$p->numero }}</th>
            @endforeach
            <th class="th-final" style="width:60px;">Prom.<br>Final</th>
            <th class="th-final" style="width:75px;">Indicador</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tablaAnual as $row)
        @php
            $pCls = $gc($row['final']);
            $iCls = $ic($row['indicador']);
        @endphp
        <tr>
            <td class="td-mat">{{ $row['asignatura'] }}</td>
            @foreach($periodos as $p)
            @php $nota = $row['periodos'][$p->id] ?? null; @endphp
            <td class="{{ $gc($nota) }}">{{ $nota !== null ? number_format($nota,1) : '—' }}</td>
            @endforeach
            <td class="{{ $pCls }}" style="font-weight:800;">{{ $row['final'] !== null ? number_format($row['final'],1) : '—' }}</td>
            <td><span class="ind {{ $iCls }}">{{ $row['indicador'] ?? '—' }}</span></td>
        </tr>
        @endforeach

        {{-- Promedio anual general --}}
        @php
            $pgLabel = $promedioAnual >= 90 ? 'Excelente' : ($promedioAnual >= 75 ? 'Bueno' : ($promedioAnual >= 60 ? 'En proceso' : 'Insuficiente'));
        @endphp
        <tr class="prom-row">
            <td class="td-mat" colspan="{{ $periodos->count() + 1 }}" style="text-align:right;padding-right:10px;font-size:7pt;letter-spacing:.1em;">
                PROMEDIO GENERAL ANUAL
            </td>
            <td style="text-align:center;">
                <span class="prom-box">{{ $promedioAnual !== null ? number_format($promedioAnual,1) : '—' }}</span>
            </td>
            <td style="text-align:center;">
                @if($promedioAnual !== null)
                <span class="ind {{ $ic($pgLabel) }}">{{ $pgLabel }}</span>
                @endif
            </td>
        </tr>
    </tbody>
</table>

{{-- ══ ASISTENCIA RESUMEN ══ --}}
@if(($asistenciaTotales['total'] ?? 0) > 0)
@php
    $pct = $asistenciaTotales['pct'];
    $pctClr = $pct>=90?'#065f46':($pct>=75?'#92400e':'#991b1b');
    $pctBg  = $pct>=90?'#d1fae5':($pct>=75?'#fef3c7':'#fee2e2');
@endphp
<table style="width:100%;border-collapse:collapse;margin-bottom:6px;border:1px solid #c7d6f0;">
    <tr>
        <td style="background:{{ $colorPrimario }};color:#fff;font-size:7pt;font-weight:800;padding:4px 8px;text-transform:uppercase;letter-spacing:.1em;width:120px;">Asistencia Anual</td>
        <td style="padding:4px 8px;border-right:1px solid #e5e7eb;">
            <strong>Días totales:</strong> {{ $asistenciaTotales['total'] }}
        </td>
        <td style="padding:4px 8px;border-right:1px solid #e5e7eb;">
            <strong>Presencias:</strong> {{ $asistenciaTotales['presente'] }}
        </td>
        <td style="padding:4px 8px;border-right:1px solid #e5e7eb;">
            <strong>Ausencias:</strong> {{ $asistenciaTotales['ausente'] }}
        </td>
        <td style="padding:4px 8px;border-right:1px solid #e5e7eb;">
            <strong>Tardanzas:</strong> {{ $asistenciaTotales['tardanza'] }}
        </td>
        <td style="padding:4px 12px;text-align:center;">
            <span style="background:{{ $pctBg }};color:{{ $pctClr }};font-weight:800;font-size:9pt;padding:2px 10px;border-radius:4px;">
                {{ $pct !== null ? $pct.'%' : '—' }}
            </span>
            <span style="font-size:6pt;color:#6b7280;display:block;margin-top:1px;">% asistencia</span>
        </td>
    </tr>
</table>
@endif

{{-- ══ ESTADO DE PROMOCIÓN ══ --}}
@if($promocion)
@php
    $prom = $promocion;
    [$bgCol,$fgCol] = match($prom->estado) {
        'promovido'    => ['#d1fae5','#065f46'],
        'no_promovido' => ['#fee2e2','#991b1b'],
        'condicionado' => ['#fef3c7','#92400e'],
        default        => ['#f3f4f6','#6b7280'],
    };
@endphp
<table class="estado-outer">
    <tr>
        <td class="estado-left" style="background:{{ $bgCol }};">
            <span class="estado-lbl" style="color:{{ $fgCol }};">Decisión Final</span>
            <span class="estado-val" style="color:{{ $fgCol }};">{{ strtoupper($prom->estado_label) }}</span>
        </td>
        <td>
            @if($prom->promedio_final !== null)
            <span style="font-size:7.5pt;"><strong>Promedio final:</strong> {{ number_format($prom->promedio_final,2) }}</span>&nbsp;
            @endif
            @if($prom->materias_reprobadas)
            <span style="font-size:7.5pt;"><strong>Mat. reprobadas:</strong> {{ $prom->materias_reprobadas }}</span>&nbsp;
            @endif
            @if($prom->pct_asistencia !== null)
            <span style="font-size:7.5pt;"><strong>Asistencia:</strong> {{ number_format($prom->pct_asistencia,1) }}%</span>
            @endif
            @if($prom->observacion)
            <div style="font-size:7.5pt;font-style:italic;color:#6b7280;margin-top:3px;">{{ $prom->observacion }}</div>
            @endif
        </td>
    </tr>
</table>
@endif

{{-- ══ OBSERVACIONES GENERALES ══ --}}
@if($boletinObservaciones->isNotEmpty())
<table style="width:100%;border-collapse:collapse;border:1px solid #c7d6f0;margin-bottom:6px;">
    <tr>
        <td style="background:{{ $colorPrimario }};color:#fff;font-size:7pt;font-weight:800;padding:4px 8px;text-transform:uppercase;letter-spacing:.1em;width:120px;vertical-align:top;">Observaciones</td>
        <td style="padding:4px 8px;font-size:7.5pt;color:#374151;line-height:1.6;">
            @foreach($boletinObservaciones as $tipo => $items)
            <strong style="color:{{ $colorPrimario }};">{{ match($tipo){'academica'=>'Académica','conducta'=>'Conducta','sugerencia'=>'Sugerencia',default=>'General'} }}:</strong>
            @foreach($items as $obs){{ $obs->contenido }}@if(!$loop->last), @endif @endforeach&nbsp;
            @endforeach
        </td>
    </tr>
</table>
@endif

{{-- ══ FIRMAS ══ --}}
<div style="background:{{ $colorPrimario }};color:#fff;font-size:6.5pt;font-weight:800;text-transform:uppercase;letter-spacing:.14em;padding:3px 9px;">
    ✎ Certificamos la veracidad de la información
</div>
<table class="firmas">
    <tr>
        <td>
            <div class="firma-space"></div>
            <div class="firma-line">{{ $directorFull }}</div>
            <div class="firma-rol">Director(a) del Centro</div>
        </td>
        <td>
            <div class="sello-box">SELLO<br>OFICIAL</div>
        </td>
        <td>
            <div class="firma-space"></div>
            <div class="firma-line">{{ $encargado }}</div>
            <div class="firma-rol">Encargado(a) Académico</div>
        </td>
        <td>
            <div class="firma-space"></div>
            <div class="firma-line">{{ $tutorNombre }}</div>
            <div class="firma-rol">Docente Guía</div>
        </td>
        <td>
            <div class="firma-space"></div>
            <div class="firma-line">________________________</div>
            <div class="firma-rol">Representante Legal</div>
        </td>
    </tr>
</table>

<div class="footer-bar">
    {{ $inst }} &nbsp;·&nbsp; Código: {{ $codigoCe }} &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
    @if($rankP) &nbsp;·&nbsp; Puesto {{ $rankP }} de {{ $rankT }} en el grupo @endif
    &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }} &nbsp;·&nbsp; Código verificación: <strong>{{ $verifyCode }}</strong>
</div>

</body>
</html>
