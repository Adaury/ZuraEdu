<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0;padding:0;box-sizing:border-box; }
body { font-family:DejaVu Sans,sans-serif;font-size:9.5px;color:#111;line-height:1.45; }

/* ── Cabecera institucional ── */
.inst-header { display:table;width:100%;border-bottom:2px solid #4f46e5;padding-bottom:6px;margin-bottom:6px; }
.inst-logo-cell { display:table-cell;width:52px;vertical-align:middle; }
.inst-logo-cell img { width:44px;height:44px;object-fit:contain; }
.inst-logo-cell .logo-placeholder {
    width:44px;height:44px;border-radius:6px;
    background:#4f46e5;color:#fff;font-size:14px;font-weight:800;
    display:table-cell;text-align:center;vertical-align:middle;
}
.inst-info-cell { display:table-cell;vertical-align:middle;padding-left:8px; }
.inst-name { font-size:11px;font-weight:800;color:#1e293b; }
.inst-sub  { font-size:8px;color:#64748b;margin-top:1px; }
.version-cell { display:table-cell;vertical-align:middle;text-align:right;width:60px; }
.version-badge {
    display:inline-block;border-radius:5px;padding:5px 10px;
    font-size:13px;font-weight:900;letter-spacing:.06em;
}
.version-a { background:#4f46e5;color:#fff; }
.version-b { background:#0891b2;color:#fff; }

/* ── Info del estudiante ── */
.student-bar {
    border:1.5px solid #c7d2fe;border-radius:6px;padding:5px 8px;
    margin-bottom:8px;font-size:8.5px;
}
.student-bar table { width:100%;border:none; }
.student-bar td { border:none;padding:2px 4px;vertical-align:middle; }
.field-line { border-bottom:1px solid #94a3b8;display:inline-block;min-width:80px; }

/* ── Instrucciones ── */
.instrucciones {
    background:#f1f5f9;border-left:3px solid #6366f1;
    padding:5px 8px;margin-bottom:10px;font-size:8px;color:#475569;
    border-radius:0 4px 4px 0;
}

/* ── Secciones ── */
.seccion-title {
    background:#4f46e5;color:#fff;padding:3px 8px;
    font-size:8.5px;font-weight:800;border-radius:4px;
    margin:10px 0 5px;text-transform:uppercase;letter-spacing:.05em;
}

/* ── Preguntas ── */
.pregunta {
    margin-bottom:8px;page-break-inside:avoid;
}
.preg-header {
    display:table;width:100%;margin-bottom:3px;
}
.preg-num {
    display:table-cell;width:18px;vertical-align:top;
    font-weight:800;font-size:9px;color:#4f46e5;padding-top:1px;
}
.preg-text {
    display:table-cell;vertical-align:top;font-size:9px;font-weight:600;
}
.preg-pts {
    display:table-cell;width:38px;text-align:right;vertical-align:top;
    font-size:7.5px;color:#64748b;white-space:nowrap;padding-top:1px;
}

/* Opciones MC */
.opciones-mc { padding-left:18px;margin-top:3px; }
.opcion-mc { margin-bottom:2.5px;font-size:8.5px;display:table;width:100%; }
.opcion-circle {
    display:table-cell;width:12px;vertical-align:middle;
}
.circle-svg { vertical-align:middle; }
.opcion-texto { display:table-cell;vertical-align:middle;padding-left:3px; }
.opcion-letra { font-weight:700;margin-right:3px; }

/* V/F */
.vf-row { padding-left:18px;margin-top:3px;font-size:8.5px; }
.vf-item { display:inline-block;margin-right:20px; }

/* Abierta */
.blank-lines { padding-left:18px;margin-top:4px; }
.blank-line {
    border-bottom:1px solid #94a3b8;height:14px;margin-bottom:2px;
}

/* ── Clave de respuestas ── */
.clave-page { page-break-before:always; }
.clave-header {
    background:#1e293b;color:#fff;text-align:center;
    padding:8px;font-size:11px;font-weight:800;border-radius:6px;
    margin-bottom:10px;letter-spacing:.08em;
}
.clave-table { width:100%;border-collapse:collapse;font-size:8.5px; }
.clave-table th {
    background:#4f46e5;color:#fff;padding:4px 6px;
    font-weight:700;text-align:center;border:1px solid #e2e8f0;
}
.clave-table td {
    padding:4px 6px;border:1px solid #e2e8f0;text-align:center;
    vertical-align:middle;
}
.clave-table tr:nth-child(even) td { background:#f8fafc; }
.resp-mc     { color:#4f46e5;font-weight:800;font-size:10px; }
.resp-vf     { color:#0891b2;font-weight:800; }
.resp-dev    { color:#94a3b8;font-size:7.5px; }
.resp-ok-bg  { background:#dcfce7; }

/* ── Separador de versión ── */
.version-sep { page-break-before:always; }

/* ── Puntaje total ── */
.pts-total {
    text-align:right;font-size:8px;color:#64748b;margin-bottom:6px;
    border-bottom:1px dashed #e2e8f0;padding-bottom:4px;
}
</style>
</head>
<body>

@php
    $letras = ['A','B','C','D','E','F'];
    $asignaturaNombre = $asignacion->asignatura?->nombre ?? '';
    $grupoNombre      = $asignacion->grupo?->nombre ?? '';
    $instNombre       = $tenant?->nombre_institucion ?? config('app.name');
    $logoUrl          = $tenant?->logo_url ?? null;

    // Calcular puntos por sección para cada versión
    $calcSeccion = function($preguntas) {
        $ptsMC   = $preguntas->where('tipo','multiple')->sum('puntos');
        $ptsVF   = $preguntas->where('tipo','verdadero_falso')->sum('puntos');
        $ptsDes  = $preguntas->where('tipo','abierta')->sum('puntos');
        return ['multiple' => $ptsMC, 'verdadero_falso' => $ptsVF, 'abierta' => $ptsDes];
    };
    $seccionesA = $calcSeccion($versionA);
    $seccionesB = $calcSeccion($versionB);
@endphp

{{-- ══════════════════════════════════════════════════════ --}}
{{-- VERSIÓN A                                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
@php
    $preguntas = $versionA;
    $secciones = $seccionesA;
    $verLetra  = 'A';
    $clave     = $claveA;
@endphp
@include('portal.docente.evaluaciones._examen_version')

{{-- ══════════════════════════════════════════════════════ --}}
{{-- VERSIÓN B                                              --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="version-sep"></div>
@php
    $preguntas = $versionB;
    $secciones = $seccionesB;
    $verLetra  = 'B';
    $clave     = $claveB;
@endphp
@include('portal.docente.evaluaciones._examen_version')

{{-- ══════════════════════════════════════════════════════ --}}
{{-- CLAVE DE RESPUESTAS (uso exclusivo del docente)        --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="clave-page">

    <div class="clave-header">CLAVE DE RESPUESTAS — EXCLUSIVO DOCENTE</div>
    <div style="font-size:8px;color:#64748b;margin-bottom:8px;text-align:center;">
        {{ $quiz->titulo }} · {{ $asignaturaNombre }} · {{ $grupoNombre }} ·
        Total: {{ $quiz->puntaje_total }} pts
    </div>

    @php
        // Mismo orden de agrupación que la vista: MC → VF → Abierta
        $groupedA = collect([
            ...$versionA->where('tipo','multiple')->values()->all(),
            ...$versionA->where('tipo','verdadero_falso')->values()->all(),
            ...$versionA->where('tipo','abierta')->values()->all(),
        ]);
        $groupedB = collect([
            ...$versionB->where('tipo','multiple')->values()->all(),
            ...$versionB->where('tipo','verdadero_falso')->values()->all(),
            ...$versionB->where('tipo','abierta')->values()->all(),
        ]);
        $tipoLabel = ['multiple'=>'MC','verdadero_falso'=>'V/F','abierta'=>'Des.'];
    @endphp
    <table style="width:100%;border-collapse:separate;border-spacing:0 0;margin-bottom:12px;">
    <tr>
    <td style="width:50%;vertical-align:top;padding-right:10px;">
        {{-- Clave A --}}
        <div style="font-weight:800;font-size:9px;color:#4f46e5;margin-bottom:4px;border-bottom:1px solid #c7d2fe;padding-bottom:2px;">
            VERSIÓN A
        </div>
        <table class="clave-table" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo</th><th>Pts</th><th>Respuesta</th></tr>
            </thead>
            <tbody>
                @foreach($groupedA as $i => $p)
                @php $num = $i + 1; $resp = $claveA[$num] ?? '?'; @endphp
                <tr @if($resp !== 'Desarrollo') class="resp-ok-bg" @endif>
                    <td>{{ $num }}</td>
                    <td>{{ $tipoLabel[$p->tipo] }}</td>
                    <td>{{ $p->puntos }}</td>
                    <td>
                        @if($p->tipo === 'multiple')
                            <span class="resp-mc">{{ $resp }}</span>
                        @elseif($p->tipo === 'verdadero_falso')
                            <span class="resp-vf">{{ $resp }}</span>
                        @else
                            <span class="resp-dev">Revisar</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight:700;font-size:8px;">TOTAL</td>
                    <td style="font-weight:800;color:#4f46e5;">{{ $quiz->puntaje_total }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </td>
    <td style="width:50%;vertical-align:top;">
        {{-- Clave B --}}
        <div style="font-weight:800;font-size:9px;color:#0891b2;margin-bottom:4px;border-bottom:1px solid #bae6fd;padding-bottom:2px;">
            VERSIÓN B
        </div>
        <table class="clave-table" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo</th><th>Pts</th><th>Respuesta</th></tr>
            </thead>
            <tbody>
                @foreach($groupedB as $i => $p)
                @php $num = $i + 1; $resp = $claveB[$num] ?? '?'; @endphp
                <tr @if($resp !== 'Desarrollo') class="resp-ok-bg" @endif>
                    <td>{{ $num }}</td>
                    <td>{{ $tipoLabel[$p->tipo] }}</td>
                    <td>{{ $p->puntos }}</td>
                    <td>
                        @if($p->tipo === 'multiple')
                            <span class="resp-mc">{{ $resp }}</span>
                        @elseif($p->tipo === 'verdadero_falso')
                            <span class="resp-vf">{{ $resp }}</span>
                        @else
                            <span class="resp-dev">Revisar</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight:700;font-size:8px;">TOTAL</td>
                    <td style="font-weight:800;color:#0891b2;">{{ $quiz->puntaje_total }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </td>
    </tr>
    </table>

    {{-- Instrucciones de corrección de preguntas abiertas --}}
    @if($versionA->where('tipo','abierta')->isNotEmpty())
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:6px 8px;font-size:8px;color:#92400e;margin-top:6px;">
        <strong>Preguntas de desarrollo:</strong> Revisar respuestas manualmente. Se recomienda usar la rúbrica correspondiente para una evaluación objetiva.
    </div>
    @endif

    {{-- Firmas --}}
    <table style="width:100%;margin-top:20px;border-collapse:collapse;">
        <tr>
            <td style="text-align:center;padding:0 10px;border-top:1px solid #475569;padding-top:3px;font-size:7px;color:#64748b;width:33%;">Elaborado por el Docente</td>
            <td style="width:34%;"></td>
            <td style="text-align:center;padding:0 10px;border-top:1px solid #475569;padding-top:3px;font-size:7px;color:#64748b;width:33%;">Revisado — Coordinación Académica</td>
        </tr>
    </table>
</div>

</body>
</html>
