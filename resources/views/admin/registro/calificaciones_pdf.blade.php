<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Registro de Calificaciones</title>
<style>
/* ── Reset ── */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:7.5pt; color:#1a202c; background:#fff; }

/* ── Encabezado institucional ── */
.doc-header { width:100%; border-bottom:2.5px solid #1e3a6e; padding-bottom:6px; margin-bottom:8px; }
.doc-header table { width:100%; border-collapse:collapse; }
.inst-logo-cell { width:60px; text-align:center; }
.inst-logo { width:50px; height:50px; border:2px solid #1e3a6e; border-radius:4px;
    display:inline-block; line-height:50px; text-align:center;
    font-weight:900; font-size:14pt; color:#1e3a6e; background:#e8edf8; }
.inst-info { padding-left:10px; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#4a5568; margin-top:2px; }
.doc-title-cell { text-align:right; padding-right:4px; }
.doc-title-main { font-size:10pt; font-weight:900; color:#1e3a6e; }
.doc-title-sub  { font-size:7.5pt; color:#4a5568; margin-top:2px; }

/* ── Ficha de datos ── */
.ficha { width:100%; border-collapse:collapse; margin-bottom:7px;
    background:#f0f4ff; border:1px solid #c7d2fe; border-radius:4px; }
.ficha td { padding:3px 8px; font-size:7.5pt; }
.ficha .lbl { font-weight:700; color:#1e3a6e; font-size:6.5pt; text-transform:uppercase;
    letter-spacing:.04em; }
.ficha .val { color:#1a202c; font-weight:600; }
.ficha .sep { width:1px; background:#c7d2fe; padding:0; }

/* ── Leyenda ── */
.leyenda { width:100%; margin-bottom:7px; }
.leyenda td { padding:2px 4px; font-size:6.5pt; text-align:center; font-weight:700; }
.ley-box { display:inline-block; width:14px; height:14px; border-radius:3px;
    text-align:center; line-height:14px; font-weight:900; font-size:7pt; margin-right:2px;
    border:1px solid; }
.ley-lbl { font-size:6.5pt; color:#374151; }
.b1 { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
.b2 { background:#fef9c3; color:#854d0e; border-color:#fde047; }
.b3 { background:#dbeafe; color:#1e40af; border-color:#93c5fd; }
.b4 { background:#dcfce7; color:#15803d; border-color:#86efac; }

/* ── Tabla principal ── */
.reg-tbl { width:100%; border-collapse:collapse; margin-bottom:10px; }
.reg-tbl th, .reg-tbl td { border:0.5px solid #cbd5e1; padding:2px 3px; text-align:center; }

/* Cabecera fila 1: info cols + CEs */
.th-info  { background:#1e3a6e; color:#fff; font-weight:800; font-size:7pt;
    padding:3px 4px; }
.th-ce    { background:#2d5aa0; color:#fff; font-weight:700; font-size:6pt;
    padding:2px 3px; }
.th-il    { background:#e8edf8; color:#1e3a6e; font-weight:700; font-size:5.5pt;
    padding:2px 2px; }
.th-prom  { background:#f0fdf4; color:#15803d; font-weight:800; font-size:6pt; }
.th-total { background:#1a202c; color:#fff; font-weight:800; font-size:6.5pt; }
.th-per   { background:#f8fafc; color:#374151; font-size:6pt; font-weight:700; }

/* Celdas de datos */
.td-num  { background:#f8fafc; font-weight:700; font-size:7pt; width:22px; }
.td-name { text-align:left; padding-left:5px; font-size:7pt; font-weight:600;
    white-space:nowrap; max-width:130px; overflow:hidden; }
.td-val  { font-size:7pt; font-weight:800; min-width:18px; }
.v1 { background:#fee2e2; color:#991b1b; }
.v2 { background:#fef9c3; color:#854d0e; }
.v3 { background:#dbeafe; color:#1e40af; }
.v4 { background:#dcfce7; color:#15803d; }
.v-empty { background:#fafafa; color:#cbd5e1; }

.td-prom { font-weight:800; font-size:6.5pt; }
.td-total { font-weight:900; font-size:7.5pt; }
.p-v1 { background:#ffd7d7; color:#7f1d1d; }
.p-v2 { background:#fef08a; color:#713f12; }
.p-v3 { background:#bfdbfe; color:#1e3a8a; }
.p-v4 { background:#bbf7d0; color:#14532d; }
.p-empty { background:#f9fafb; color:#9ca3af; }

/* Fila promedio grupal */
.tr-group-avg td { background:#fffbeb; font-weight:800; font-size:6.5pt;
    border-top:2px solid #f59e0b; color:#92400e; }
.tr-group-avg .td-name { font-weight:900; text-transform:uppercase; letter-spacing:.04em; }

/* Filas alternadas */
.tr-even td.td-name, .tr-even td.td-num { background:#f8fafc; }
.tr-odd  td.td-name, .tr-odd  td.td-num { background:#fff; }
.tr-even td.td-val.v-empty  { background:#f1f5f9; }
.tr-odd  td.td-val.v-empty  { background:#fafafa; }

/* ── Pie de página ── */
.doc-footer { border-top:1.5px solid #1e3a6e; padding-top:8px; margin-top:4px; }
.firma-line { border-top:1px solid #374151; width:160px; display:inline-block;
    margin-top:28px; margin-right:40px; }
.firma-label { font-size:6pt; color:#374151; text-align:center; margin-top:2px; }
</style>
</head>
<body>

@php
    $ces = $asignacion->asignatura->competenciasActivas ?? collect();

    // Total columnas IL por CE
    $ilCountPerCe = $ces->map(fn($ce) => max(1, ($ce->indicadoresActivos ?? collect())->count()));
    $totalILCols  = $ilCountPerCe->sum();

    // Calcular promedio ponderado para un estudiante (todas las evaluaciones)
    $calcProm = function(int $mId) use ($ces, $evalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            if ($ils->isNotEmpty()) {
                foreach ($ils as $il) {
                    $v = $evalMap[$mId]["il_{$il->id}"][$periodo->id] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
            } else {
                $v = $evalMap[$mId]["ce_{$ce->id}"][$periodo->id] ?? null;
                if ($v !== null) $vals[] = (float)$v;
            }
        }
        return count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
    };

    $calcAllProm = function(int $mId) use ($ces, $periodos, $evalMap): ?float {
        $vals = [];
        foreach ($ces as $ce) {
            $ils = $ce->indicadoresActivos ?? collect();
            foreach ($periodos as $p) {
                if ($ils->isNotEmpty()) {
                    foreach ($ils as $il) {
                        $v = $evalMap[$mId]["il_{$il->id}"][$p->id] ?? null;
                        if ($v !== null) $vals[] = (float)$v;
                    }
                } else {
                    $v = $evalMap[$mId]["ce_{$ce->id}"][$p->id] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
            }
        }
        return count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
    };

    $colorClass = function(?float $v): string {
        if ($v === null) return 'p-empty';
        if ($v >= 3.5)  return 'p-v4';
        if ($v >= 2.5)  return 'p-v3';
        if ($v >= 1.5)  return 'p-v2';
        return 'p-v1';
    };

    $valClass = function($v): string {
        if ($v === null || $v === '') return 'v-empty';
        return 'v' . (int)$v;
    };

    $escalaLabel = function(?float $v): string {
        if ($v === null) return '—';
        if ($v >= 3.5)  return number_format($v, 1) . ' ✓';
        return number_format($v, 1);
    };
@endphp

{{-- ════════════════════════════════════════════════
     ENCABEZADO INSTITUCIONAL
════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table>
        <tr>
            <td class="inst-logo-cell">
                <div class="inst-logo">SGE</div>
            </td>
            <td class="inst-info">
                <div class="inst-name">PSAC — Sistema de Gestión Escolar</div>
                <div class="inst-sub">
                    Año Escolar: {{ $schoolYear->nombre }} &nbsp;|&nbsp;
                    {{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }}
                </div>
            </td>
            <td class="doc-title-cell">
                <div class="doc-title-main">REGISTRO DE CALIFICACIONES</div>
                <div class="doc-title-sub">
                    Formato MINERD — Primer Ciclo &nbsp;|&nbsp; {{ now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ════════════════════════════════════════════════
     FICHA DE DATOS
════════════════════════════════════════════════ --}}
<table class="ficha">
    <tr>
        <td style="width:12%"><div class="lbl">Materia</div><div class="val">{{ $asignacion->asignatura->nombre }}</div></td>
        <td class="sep"></td>
        <td style="width:14%"><div class="lbl">Docente</div><div class="val">{{ $asignacion->docente?->nombre_completo ?? '—' }}</div></td>
        <td class="sep"></td>
        <td style="width:14%"><div class="lbl">Período</div><div class="val">{{ $periodo->nombre }}{{ $periodo->cerrado ? ' (Cerrado)' : '' }}</div></td>
        <td class="sep"></td>
        <td style="width:12%"><div class="lbl">Grado</div><div class="val">{{ $grupo->grado->nombre }}</div></td>
        <td class="sep"></td>
        <td style="width:10%"><div class="lbl">Sección</div><div class="val">{{ $grupo->seccion->nombre }}</div></td>
        <td class="sep"></td>
        <td style="width:10%"><div class="lbl">Matriculados</div><div class="val">{{ $matriculas->count() }}</div></td>
        <td class="sep"></td>
        <td style="width:14%"><div class="lbl">Año Escolar</div><div class="val">{{ $schoolYear->nombre }}</div></td>
    </tr>
</table>

{{-- ════════════════════════════════════════════════
     LEYENDA
════════════════════════════════════════════════ --}}
<table class="leyenda">
    <tr>
        <td style="text-align:left; padding-left:2px;">
            <strong style="font-size:6.5pt;color:#374151;">Escala de evaluación MINERD:&nbsp;&nbsp;</strong>
            <span class="ley-box b1">1</span><span class="ley-lbl">Inicial&nbsp;&nbsp;&nbsp;</span>
            <span class="ley-box b2">2</span><span class="ley-lbl">En proceso&nbsp;&nbsp;&nbsp;</span>
            <span class="ley-box b3">3</span><span class="ley-lbl">Logrado&nbsp;&nbsp;&nbsp;</span>
            <span class="ley-box b4">4</span><span class="ley-lbl">Avanzado</span>
        </td>
    </tr>
</table>

{{-- ════════════════════════════════════════════════
     TABLA DE REGISTRO
════════════════════════════════════════════════ --}}
<table class="reg-tbl">
    {{-- ── Fila 1: columnas fijas + CE (con colspan por ILs) ── --}}
    <thead>
    <tr>
        <th class="th-info" rowspan="2" style="width:22px;">#</th>
        <th class="th-info" rowspan="2" style="text-align:left; padding-left:5px; width:130px;">Nombre del Estudiante</th>
        @foreach($ces as $idx => $ce)
            @php $cols = max(1, ($ce->indicadoresActivos ?? collect())->count()); @endphp
            <th class="th-ce" colspan="{{ $cols }}">
                CE{{ $idx+1 }}: {{ \Illuminate\Support\Str::limit($ce->nombre, 45) }}
            </th>
        @endforeach
        <th class="th-prom" rowspan="2">PROM<br>P{{ $periodo->numero }}</th>
        <th class="th-total" rowspan="2">PROM<br>GRAL</th>
    </tr>
    {{-- ── Fila 2: ILs ── --}}
    <tr>
        @foreach($ces as $ce)
            @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
            @if($ils->isNotEmpty())
                @foreach($ils as $il)
                    <th class="th-il" title="{{ $il->descripcion }}">IL{{ $loop->iteration }}</th>
                @endforeach
            @else
                <th class="th-il">CE</th>
            @endif
        @endforeach
    </tr>
    </thead>

    {{-- ── Filas estudiantes ── --}}
    <tbody>
    @php $allProms = []; @endphp
    @foreach($matriculas as $m)
        @php
            $prom     = $calcProm($m->id);
            $promTodo = $calcAllProm($m->id);
            if ($prom !== null) $allProms[] = $prom;
            $trClass  = $loop->iteration % 2 === 0 ? 'tr-even' : 'tr-odd';
        @endphp
        <tr class="{{ $trClass }}">
            <td class="td-num">{{ $m->numero_orden }}</td>
            <td class="td-name">{{ $m->estudiante?->apellidos }}, {{ $m->estudiante?->nombres }}</td>

            @foreach($ces as $ce)
                @php $ils = $ce->indicadoresActivos ?? collect(); @endphp
                @if($ils->isNotEmpty())
                    @foreach($ils as $il)
                        @php $val = $evalMap[$m->id]["il_{$il->id}"][$periodo->id] ?? null; @endphp
                        <td class="td-val {{ $valClass($val) }}">{{ $val ?? '' }}</td>
                    @endforeach
                @else
                    @php $val = $evalMap[$m->id]["ce_{$ce->id}"][$periodo->id] ?? null; @endphp
                    <td class="td-val {{ $valClass($val) }}">{{ $val ?? '' }}</td>
                @endif
            @endforeach

            <td class="td-prom {{ $colorClass($prom) }}">
                {{ $prom !== null ? number_format($prom, 1) : '—' }}
            </td>
            <td class="td-total {{ $colorClass($promTodo) }}">
                {{ $promTodo !== null ? number_format($promTodo, 1) : '—' }}
            </td>
        </tr>
    @endforeach

    {{-- ── Fila promedio grupal ── --}}
    <tr class="tr-group-avg">
        <td colspan="2" class="td-name">Promedio del grupo</td>
        @php $blankCols = $totalILCols; @endphp
        @for($c = 0; $c < $blankCols; $c++)
            <td>—</td>
        @endfor
        <td class="{{ $colorClass(count($allProms) ? round(array_sum($allProms)/count($allProms),2) : null) }}">
            {{ count($allProms) ? number_format(array_sum($allProms)/count($allProms), 1) : '—' }}
        </td>
        <td>—</td>
    </tr>
    </tbody>
</table>

{{-- ════════════════════════════════════════════════
     PIE DE PÁGINA / FIRMAS
════════════════════════════════════════════════ --}}
<div class="doc-footer">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:33%; text-align:center; padding-top:30px;">
                <div style="border-top:1px solid #374151; width:160px; margin:0 auto;"></div>
                <div style="font-size:6pt; color:#374151; margin-top:3px;">
                    Firma del/la Docente
                </div>
                <div style="font-size:7pt; color:#1a202c; font-weight:700; margin-top:1px;">
                    {{ $asignacion->docente?->nombre_completo ?? '___________________' }}
                </div>
            </td>
            <td style="width:33%; text-align:center; padding-top:30px;">
                <div style="border-top:1px solid #374151; width:160px; margin:0 auto;"></div>
                <div style="font-size:6pt; color:#374151; margin-top:3px;">
                    Firma del/la Director/a
                </div>
                <div style="font-size:7pt; color:#1a202c; margin-top:1px;">
                    &nbsp;
                </div>
            </td>
            <td style="width:33%; text-align:right; vertical-align:bottom; padding-bottom:2px;">
                <div style="font-size:6pt; color:#94a3b8;">
                    Generado por SGE — PSAC &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }}<br>
                    {{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }} &nbsp;|&nbsp;
                    {{ $asignacion->asignatura->nombre }} &nbsp;|&nbsp; {{ $periodo->nombre }}
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
