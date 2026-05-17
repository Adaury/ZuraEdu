<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1e293b; background: #fff; }

/* ── Header ─────────────────────────────────────────────── */
.header { background: #1e293b; color: #fff; padding: 10px 14px; margin-bottom: 10px; }
.header-title { font-size: 12pt; font-weight: bold; }
.header-sub   { font-size: 7.5pt; opacity: .8; margin-top: 2px; }
.header-meta  { font-size: 7pt; opacity: .65; margin-top: 4px; }

/* ── KPIs ────────────────────────────────────────────────── */
.kpi-row { display: table; width: 100%; margin-bottom: 10px; border-collapse: collapse; }
.kpi-cell { display: table-cell; text-align: center; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 4px; width: 16.6%; }
.kpi-num { font-size: 13pt; font-weight: bold; line-height: 1.1; }
.kpi-lbl { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-top: 1px; }

/* ── Top Mejoras / Descensos ─────────────────────────────── */
.two-col { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.two-col td { vertical-align: top; width: 50%; padding: 0 4px; }
.two-col td:first-child { padding-left: 0; }
.two-col td:last-child  { padding-right: 0; }

.top-card { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
.top-card-header { padding: 5px 8px; font-size: 7.5pt; font-weight: bold; }
.top-card-header.mejoras  { background: #dcfce7; color: #15803d; }
.top-card-header.descensos { background: #fee2e2; color: #dc2626; }
.top-row { display: table; width: 100%; padding: 4px 8px; border-top: 1px solid #f1f5f9; }
.top-name { display: table-cell; font-size: 7.5pt; }
.top-badge { display: table-cell; text-align: right; font-size: 7pt; font-weight: bold; white-space: nowrap; }

/* ── Tabla principal ─────────────────────────────────────── */
.tabla { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.tabla th {
    background: #1e293b; color: #fff;
    padding: 4px 5px; font-size: 7pt; font-weight: bold;
    text-transform: uppercase; letter-spacing: .04em;
    text-align: center; white-space: nowrap;
}
.tabla th.left { text-align: left; }
.tabla td { padding: 3.5px 5px; border-bottom: 1px solid #f1f5f9; font-size: 7.5pt; vertical-align: middle; text-align: center; }
.tabla td.left { text-align: left; }
.tabla tr:nth-child(even) td { background: #f8fafc; }
.tabla tr.riesgo td { background: #fff5f5; }

/* Nota badges */
.nb { display: inline-block; min-width: 28px; text-align: center; border-radius: 4px; padding: 1px 4px; font-weight: bold; font-size: 7.5pt; }
.nb-ex  { background: #dbeafe; color: #1d4ed8; }
.nb-ok  { background: #dcfce7; color: #15803d; }
.nb-med { background: #fef9c3; color: #92400e; }
.nb-low { background: #fee2e2; color: #dc2626; }
.nb-nil { background: #f1f5f9; color: #94a3b8; }

/* Tendencia */
.tend-up      { color: #15803d; font-weight: bold; }
.tend-down    { color: #dc2626; font-weight: bold; }
.tend-neutral { color: #94a3b8; }

/* Fila de promedio grupal */
.fila-prom td { background: #f0f9ff !important; font-weight: bold; border-top: 2px solid #93c5fd; }

/* Footer */
.footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 6.5pt; color: #94a3b8; }
.firma-row { width: 100%; border-collapse: collapse; margin-top: 24px; }
.firma-row td { width: 33%; text-align: center; padding: 0 10px; vertical-align: top; }
.firma-line { border-top: 1px solid #475569; margin-top: 30px; padding-top: 3px; font-size: 7pt; }
</style>
</head>
<body>

{{-- ── Encabezado ───────────────────────────────────────────────────── --}}
<div class="header">
    <div class="header-title">
        <span style="opacity:.6; font-size:9pt;">&#9632;</span>
        Comparativa de Rendimiento — {{ $asignacion->asignatura?->nombre ?? '—' }}
    </div>
    <div class="header-sub">
        {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }}
        &nbsp;·&nbsp; Docente: {{ $docente->apellidos ?? '' }}, {{ $docente->nombres ?? '' }}
        @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
    </div>
    <div class="header-meta">
        Generado: {{ now()->format('d/m/Y H:i') }}
        &nbsp;·&nbsp; Períodos: {{ $periodos->count() }}
        &nbsp;·&nbsp; Estudiantes: {{ $filas->count() }}
    </div>
</div>

{{-- ── KPIs ──────────────────────────────────────────────────────────── --}}
<table class="kpi-row">
    <tr>
        @foreach($periodos as $p)
        @php $prom = $promediosPeriodo[$p->numero] ?? null; @endphp
        <td class="kpi-cell">
            <div class="kpi-num" style="color:{{ $prom === null ? '#94a3b8' : ($prom >= 70 ? '#15803d' : ($prom >= 65 ? '#92400e' : '#dc2626')) }};">
                {{ $prom !== null ? number_format($prom, 1) : '—' }}
            </div>
            <div class="kpi-lbl">{{ $p->nombre }}</div>
        </td>
        @endforeach
        <td class="kpi-cell" style="border-color:#fca5a5;">
            <div class="kpi-num" style="color:#d97706;">{{ $enRiesgoCount }}</div>
            <div class="kpi-lbl">En riesgo</div>
        </td>
        <td class="kpi-cell" style="border-color:#86efac;">
            <div class="kpi-num" style="color:#059669;">{{ $mejorandoCount }}</div>
            <div class="kpi-lbl">Mejorando</div>
        </td>
        <td class="kpi-cell" style="border-color:#fca5a5;">
            <div class="kpi-num" style="color:#dc2626;">{{ $declinandoCount }}</div>
            <div class="kpi-lbl">Declinando</div>
        </td>
        <td class="kpi-cell" style="border-color:#93c5fd;">
            <div class="kpi-num" style="color:#1d4ed8;">{{ $promedioFinal !== null ? number_format($promedioFinal, 1) : '—' }}</div>
            <div class="kpi-lbl">Prom. Final</div>
        </td>
    </tr>
</table>

{{-- ── Top Mejoras / Descensos ──────────────────────────────────────── --}}
@php
$mejoras  = $topMejoras->filter(fn($f) => $f['diff'] !== null && $f['diff'] > 0);
$descensos = $topDescensos->filter(fn($f) => $f['diff'] !== null && $f['diff'] < 0);
@endphp
@if($mejoras->isNotEmpty() || $descensos->isNotEmpty())
<table class="two-col">
    <tr>
        <td>
            <div class="top-card">
                <div class="top-card-header mejoras">&#9650; Top Mejoras (P1 → último período)</div>
                @foreach($mejoras as $f)
                <div class="top-row">
                    <span class="top-name">{{ $f['matricula']->estudiante?->apellidos }}, {{ $f['matricula']->estudiante?->nombres }}</span>
                    <span class="top-badge" style="color:#15803d;">+{{ $f['diff'] }} pts</span>
                </div>
                @endforeach
                @if($mejoras->isEmpty())
                <div style="padding:5px 8px; font-size:7pt; color:#94a3b8;">Sin mejoras registradas</div>
                @endif
            </div>
        </td>
        <td>
            <div class="top-card">
                <div class="top-card-header descensos">&#9660; Top Descensos (P1 → último período)</div>
                @foreach($descensos as $f)
                <div class="top-row">
                    <span class="top-name">{{ $f['matricula']->estudiante?->apellidos }}, {{ $f['matricula']->estudiante?->nombres }}</span>
                    <span class="top-badge" style="color:#dc2626;">{{ $f['diff'] }} pts</span>
                </div>
                @endforeach
                @if($descensos->isEmpty())
                <div style="padding:5px 8px; font-size:7pt; color:#94a3b8;">Sin descensos registrados</div>
                @endif
            </div>
        </td>
    </tr>
</table>
@endif

{{-- ── Tabla de estudiantes ─────────────────────────────────────────── --}}
<table class="tabla">
    <thead>
        <tr>
            <th class="left" style="min-width:160px;"># Estudiante</th>
            @foreach($periodos as $p)
            <th>{{ $p->nombre }}</th>
            @endforeach
            <th>Nota Final</th>
            <th>Tendencia</th>
            <th>Var. P1→Ult.</th>
        </tr>
    </thead>
    <tbody>
    @foreach($filas as $i => $fila)
    @php
        $nf   = $fila['notaFinal'];
        $tend = $fila['tendencia'];
        $diff = $fila['diff'];
        $nfClass = 'nb-nil';
        if ($nf !== null) {
            if ($nf >= 90)     $nfClass = 'nb-ex';
            elseif ($nf >= 70) $nfClass = 'nb-ok';
            elseif ($nf >= 65) $nfClass = 'nb-med';
            else               $nfClass = 'nb-low';
        }
    @endphp
    <tr class="{{ $fila['enRiesgo'] ? 'riesgo' : '' }}">
        <td class="left">
            <strong>{{ $fila['matricula']->numero_orden ?? ($i + 1) }}.</strong>
            {{ $fila['matricula']->estudiante?->apellidos }}, {{ $fila['matricula']->estudiante?->nombres }}
        </td>
        @foreach($periodos as $p)
        @php
            $nota = $fila['notasPeriodo'][$p->numero] ?? null;
            $pClass = 'nb-nil';
            if ($nota !== null) {
                if ($nota >= 70)     $pClass = 'nb-ok';
                elseif ($nota >= 65) $pClass = 'nb-med';
                else                 $pClass = 'nb-low';
            }
        @endphp
        <td><span class="nb {{ $pClass }}">{{ $nota !== null ? number_format($nota, 1) : '—' }}</span></td>
        @endforeach
        <td><span class="nb {{ $nfClass }}">{{ $nf !== null ? number_format($nf, 1) : '—' }}</span></td>
        <td class="tend-{{ $tend }}">
            @if($tend === 'up') ▲ Mejora
            @elseif($tend === 'down') ▼ Descenso
            @else — Estable
            @endif
        </td>
        <td>
            @if($diff !== null)
                <span style="color:{{ $diff > 0 ? '#15803d' : ($diff < 0 ? '#dc2626' : '#94a3b8') }};font-weight:bold;">
                    {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                </span>
            @else —
            @endif
        </td>
    </tr>
    @endforeach
    {{-- Fila de promedios del grupo --}}
    <tr class="fila-prom">
        <td class="left" style="font-size:7pt;text-transform:uppercase;letter-spacing:.04em;color:#1d4ed8;">
            Promedio del Grupo
        </td>
        @foreach($periodos as $p)
        @php $prom = $promediosPeriodo[$p->numero] ?? null; @endphp
        <td><span class="nb nb-ex">{{ $prom !== null ? number_format($prom, 1) : '—' }}</span></td>
        @endforeach
        <td><span class="nb nb-ex">{{ $promedioFinal !== null ? number_format($promedioFinal, 1) : '—' }}</span></td>
        <td colspan="2"></td>
    </tr>
    </tbody>
</table>

{{-- ── Firmas ─────────────────────────────────────────────────────────── --}}
<table class="firma-row">
    <tr>
        <td>
            <div class="firma-line">
                Docente: {{ $docente->apellidos ?? '' }}, {{ $docente->nombres ?? '' }}
            </div>
        </td>
        <td>
            <div class="firma-line">Coordinador Académico</div>
        </td>
        <td>
            <div class="firma-line">Director(a)</div>
        </td>
    </tr>
</table>

<div class="footer">
    Documento generado automáticamente · {{ config('app.name') }} · {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
