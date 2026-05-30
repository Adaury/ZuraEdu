<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Registro Académico — {{ $grupo->grado->nombre }} {{ $grupo->seccion->nombre }}</title>
<style>
/* ── Reset ── */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:7pt; color:#1a202c; background:#fff; }

/* ── Encabezado institucional ── */
.doc-header { width:100%; border-bottom:2.5px solid #1e3a6e; padding-bottom:6px; margin-bottom:8px; }
.doc-header table { width:100%; border-collapse:collapse; }
.inst-logo-cell { width:60px; text-align:center; vertical-align:middle; }
.inst-logo { width:50px; height:50px; border:2px solid #1e3a6e; border-radius:4px;
    display:inline-block; line-height:50px; text-align:center;
    font-weight:900; font-size:14pt; color:#1e3a6e; background:#e8edf8; }
.inst-info { padding-left:10px; vertical-align:middle; }
.inst-name { font-size:11pt; font-weight:900; color:#1e3a6e; }
.inst-sub  { font-size:7.5pt; color:#4a5568; margin-top:2px; }
.doc-title-cell { text-align:right; padding-right:4px; vertical-align:middle; }
.doc-title-main { font-size:10pt; font-weight:900; color:#1e3a6e; }
.doc-title-sub  { font-size:7pt; color:#4a5568; margin-top:2px; }

/* ── Ficha de metadatos ── */
.ficha { width:100%; border-collapse:collapse; margin-bottom:6px;
    background:#f0f4ff; border:1px solid #c7d2fe; }
.ficha td { padding:3px 7px; font-size:7pt; vertical-align:top; }
.ficha .lbl { font-weight:700; color:#1e3a6e; font-size:6pt; text-transform:uppercase; letter-spacing:.04em; }
.ficha .val { color:#1a202c; font-weight:600; }
.ficha .sep { width:1px; background:#c7d2fe; padding:0; }

/* ── Leyenda escala ── */
.leyenda-wrap { margin-bottom:5px; font-size:6.5pt; }
.ley-box { display:inline-block; width:13px; height:13px; border-radius:2px;
    text-align:center; line-height:13px; font-weight:900; font-size:7pt;
    margin-right:2px; border:0.5px solid; }
.ley-lbl { font-size:6.5pt; color:#374151; margin-right:8px; }
.b1 { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
.b2 { background:#fef9c3; color:#854d0e; border-color:#fde047; }
.b3 { background:#dbeafe; color:#1e40af; border-color:#93c5fd; }
.b4 { background:#dcfce7; color:#15803d; border-color:#86efac; }

/* ── Tabla principal ── */
.reg-tbl { width:100%; border-collapse:collapse; margin-bottom:8px; }
.reg-tbl th, .reg-tbl td {
    border:0.5px solid #cbd5e1; padding:1.5px 2px; text-align:center;
    vertical-align:middle;
}

/* Cabecera fija (# y nombre) */
.th-fixed { background:#1e3a6e; color:#fff; font-weight:800; font-size:7pt; padding:3px 4px; }
/* Cabecera por materia */
.th-mat  { background:#2d5aa0; color:#fff; font-weight:700; font-size:6pt; padding:2px 3px; }
/* Cabecera período */
.th-per  { background:#e8edf8; color:#1e3a6e; font-weight:700; font-size:5.5pt; padding:2px 2px; }
/* Cabecera promedio materia */
.th-pmat { background:#f0fdf4; color:#15803d; font-weight:800; font-size:5.5pt; padding:2px 2px; }
/* Cabecera promedio general / situación */
.th-gen  { background:#111827; color:#fff; font-weight:800; font-size:6.5pt; padding:3px 3px; }
/* Cabecera asistencia */
.th-asist { background:#7c3aed; color:#fff; font-weight:700; font-size:6pt; padding:2px 2px; }

/* Celdas de datos */
.td-num  { background:#f8fafc; font-weight:700; font-size:7pt; width:20px; }
.td-name { text-align:left; padding-left:4px; font-size:6.5pt; font-weight:600;
    white-space:nowrap; max-width:115px; overflow:hidden; }
.td-val  { font-size:6.5pt; font-weight:700; min-width:16px; }
.td-pmat { font-weight:800; font-size:6.5pt; }
.td-gen  { font-weight:900; font-size:7pt; }
.td-sit  { font-weight:800; font-size:6pt; }
.td-asist{ font-size:6.5pt; font-weight:700; }

/* ── Colores escala cualitativa (1-4) primer ciclo ── */
.q1 { background:#fee2e2; color:#991b1b; }   /* Inicial */
.q2 { background:#fef9c3; color:#854d0e; }   /* En proceso */
.q3 { background:#dbeafe; color:#1e40af; }   /* Logrado */
.q4 { background:#dcfce7; color:#15803d; }   /* Avanzado */
.q-e{ background:#f9fafb; color:#d1d5db; }   /* vacío */

/* ── Colores numéricos segundo ciclo ── */
.n-a { background:#d1fae5; color:#065f46; }  /* ≥90 */
.n-b { background:#dcfce7; color:#15803d; }  /* 65-89 */
.n-c { background:#fef9c3; color:#854d0e; }  /* 50-64 */
.n-d { background:#fee2e2; color:#991b1b; }  /* <50 */
.n-e { background:#f9fafb; color:#d1d5db; }  /* vacío */

/* ── Situación ── */
.sit-prom  { background:#d1fae5; color:#065f46; font-size:5.5pt; }
.sit-cond  { background:#fef9c3; color:#854d0e; font-size:5.5pt; }
.sit-rep   { background:#fee2e2; color:#991b1b; font-size:5.5pt; }
.sit-pend  { background:#f1f5f9; color:#94a3b8; font-size:5.5pt; }

/* ── Asistencia ── */
.asist-ok   { background:#d1fae5; color:#065f46; }
.asist-warn { background:#fef9c3; color:#854d0e; }
.asist-bad  { background:#fee2e2; color:#991b1b; }
.asist-na   { background:#f1f5f9; color:#94a3b8; }

/* Fila promedio grupal */
.tr-avg td { background:#fffbeb; font-weight:800; font-size:6.5pt;
    border-top:2px solid #f59e0b; color:#92400e; }
.tr-avg .td-name { font-weight:900; text-transform:uppercase; letter-spacing:.04em; }

/* Filas alternas */
.tr-even .td-name, .tr-even .td-num { background:#f8fafc; }

/* ── Pie de página ── */
.doc-footer { border-top:1.5px solid #1e3a6e; padding-top:6px; margin-top:4px; }
</style>
</head>
<body>

@php
    use App\Services\RegistroAcademicoService;

    $periodoIds   = $periodos->pluck('id')->all();
    $numPeriodos  = $periodos->count();
    $umbral       = $ciclo === 'primer_ciclo' ? 2.5 : RegistroAcademicoService::NOTA_APROBATORIA;

    // ─── Helper: promedio de una materia en un período concreto ───────────────
    $materiaPromPer = function(array $materia, int $periodoId): ?float {
        $vals = [];
        foreach ($materia['competencias'] as $ceRow) {
            if (!empty($ceRow['indicadores'])) {
                foreach ($ceRow['indicadores'] as $ilRow) {
                    $v = $ilRow['periodos'][$periodoId] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
            } else {
                $v = $ceRow['periodos'][$periodoId] ?? null;
                if ($v !== null) $vals[] = (float)$v;
            }
        }
        return count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
    };

    // ─── Helper: clase CSS de un valor según ciclo ────────────────────────────
    $valClass = function($v) use ($ciclo): string {
        if ($v === null || $v === '') return $ciclo === 'primer_ciclo' ? 'q-e' : 'n-e';
        $f = (float)$v;
        if ($ciclo === 'primer_ciclo') {
            return match(true) {
                $f >= 3.5 => 'q4',
                $f >= 2.5 => 'q3',
                $f >= 1.5 => 'q2',
                default   => 'q1',
            };
        }
        return match(true) {
            $f >= 90 => 'n-a',
            $f >= 65 => 'n-b',
            $f >= 50 => 'n-c',
            default  => 'n-d',
        };
    };

    // ─── Helper: formatear valor para celda ───────────────────────────────────
    $fmt = function($v) use ($ciclo): string {
        if ($v === null) return '—';
        return $ciclo === 'primer_ciclo'
            ? number_format((float)$v, 1)
            : number_format((float)$v, 0);
    };

    // ─── Pre-calcular promedios por período por materia para cada estudiante ──
    // $grid[$matriculaId][$asigIndex][$periodoId] = promedio
    $grid = [];
    foreach ($registro as $row) {
        $mId = $row['matricula']->id;
        foreach ($row['materias'] as $idx => $materia) {
            foreach ($periodoIds as $pId) {
                $grid[$mId][$idx][$pId] = $materiaPromPer($materia, $pId);
            }
        }
    }

    // ─── Promedio grupal por materia por período ──────────────────────────────
    $grupoProms = [];
    foreach ($asignaciones as $idx => $asig) {
        foreach ($periodoIds as $pId) {
            $vals = [];
            foreach ($registro as $row) {
                $v = $grid[$row['matricula']->id][$idx][$pId] ?? null;
                if ($v !== null) $vals[] = $v;
            }
            $grupoProms[$idx][$pId] = count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
        }
        // Promedio general de la materia
        $all = [];
        foreach ($registro as $row) {
            $v = $row['materias'][$idx]['promedio'] ?? null;
            if ($v !== null) $all[] = $v;
        }
        $grupoProms[$idx]['general'] = count($all) ? round(array_sum($all)/count($all), 2) : null;
    }
@endphp

{{-- ═══════════════════════════════════════════
     ENCABEZADO INSTITUCIONAL
═══════════════════════════════════════════ --}}
<div class="doc-header">
    <table>
        <tr>
            <td class="inst-logo-cell">
                <div class="inst-logo">SGE</div>
            </td>
            <td class="inst-info">
                <div class="inst-name">PSAC — Sistema de Gestión Escolar</div>
                <div class="inst-sub">
                    Registro Académico &nbsp;|&nbsp; Año Escolar: {{ $schoolYear->nombre }}
                </div>
            </td>
            <td class="doc-title-cell">
                <div class="doc-title-main">REGISTRO ACADÉMICO MINERD</div>
                <div class="doc-title-sub">
                    {{ $ciclo === 'primer_ciclo' ? 'Primer Ciclo — Escala Cualitativa 1–4' : 'Segundo Ciclo — Escala Numérica 0–100' }}
                    &nbsp;|&nbsp; {{ now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════════════════════════════════
     FICHA DE METADATOS
═══════════════════════════════════════════ --}}
<table class="ficha">
    <tr>
        <td style="width:16%"><div class="lbl">Grado</div><div class="val">{{ $grupo->grado->nombre }}</div></td>
        <td class="sep"></td>
        <td style="width:10%"><div class="lbl">Sección</div><div class="val">{{ $grupo->seccion->nombre }}</div></td>
        <td class="sep"></td>
        <td style="width:10%"><div class="lbl">Ciclo</div><div class="val">{{ $ciclo === 'primer_ciclo' ? '1.° Ciclo' : '2.° Ciclo' }}</div></td>
        <td class="sep"></td>
        <td style="width:12%"><div class="lbl">Matriculados</div><div class="val">{{ count($registro) }}</div></td>
        <td class="sep"></td>
        <td style="width:18%"><div class="lbl">Año Escolar</div><div class="val">{{ $schoolYear->nombre }}</div></td>
        <td class="sep"></td>
        <td style="width:12%"><div class="lbl">Períodos</div><div class="val">{{ $periodos->map(fn($p)=>$p->nombre)->implode(', ') }}</div></td>
        <td class="sep"></td>
        <td><div class="lbl">Generado</div><div class="val">{{ now()->format('d/m/Y H:i') }}</div></td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════
     LEYENDA
═══════════════════════════════════════════ --}}
<div class="leyenda-wrap">
    @if($ciclo === 'primer_ciclo')
        <strong style="color:#374151;">Escala MINERD:&nbsp;</strong>
        <span class="ley-box b1">1</span><span class="ley-lbl">Inicial</span>
        <span class="ley-box b2">2</span><span class="ley-lbl">En proceso</span>
        <span class="ley-box b3">3</span><span class="ley-lbl">Logrado</span>
        <span class="ley-box b4">4</span><span class="ley-lbl">Avanzado</span>
        <span style="margin-left:12px;color:#6b7280;">Aprobatorio ≥ 2.5</span>
    @else
        <strong style="color:#374151;">Escala numérica:&nbsp;</strong>
        <span style="background:#d1fae5;color:#065f46;padding:1px 5px;border-radius:2px;font-weight:700;">≥90 A</span>&nbsp;
        <span style="background:#dcfce7;color:#15803d;padding:1px 5px;border-radius:2px;font-weight:700;">65–89 B</span>&nbsp;
        <span style="background:#fef9c3;color:#854d0e;padding:1px 5px;border-radius:2px;font-weight:700;">50–64 C</span>&nbsp;
        <span style="background:#fee2e2;color:#991b1b;padding:1px 5px;border-radius:2px;font-weight:700;">&lt;50 F</span>
        <span style="margin-left:12px;color:#6b7280;">Aprobatorio ≥ 65</span>
    @endif
</div>

{{-- ═══════════════════════════════════════════
     TABLA PRINCIPAL
═══════════════════════════════════════════ --}}
<table class="reg-tbl">
    <thead>

    {{-- ── Fila 1: columnas fijas + nombre de materia ── --}}
    <tr>
        <th class="th-fixed" rowspan="2" style="width:20px;">#</th>
        <th class="th-fixed" rowspan="2" style="text-align:left;padding-left:4px;width:115px;">Apellidos, Nombres</th>

        @foreach($asignaciones as $asig)
            {{-- colspan = nPeriodos + 1 (promedio materia) --}}
            <th class="th-mat" colspan="{{ $numPeriodos + 1 }}">
                {{ \Illuminate\Support\Str::limit($asig->asignatura?->nombre ?? '—', 22) }}
            </th>
        @endforeach

        <th class="th-asist" rowspan="2" style="min-width:24px;">Asist.<br>%</th>
        <th class="th-gen"   rowspan="2" style="min-width:22px;">Prom.<br>Gral</th>
        <th class="th-gen"   rowspan="2" style="min-width:30px;font-size:5.5pt;">Situación</th>
    </tr>

    {{-- ── Fila 2: encabezados de período + prom por materia ── --}}
    <tr>
        @foreach($asignaciones as $asig)
            @foreach($periodos as $p)
                <th class="th-per">P{{ $p->numero }}</th>
            @endforeach
            <th class="th-pmat">Prom</th>
        @endforeach
    </tr>

    </thead>
    <tbody>

    {{-- ── Filas de estudiantes ── --}}
    @php
        $grupoAllProms = [];
        $grupoAsistencia = [];
    @endphp

    @foreach($registro as $i => $row)
        @php
            $mId       = $row['matricula']->id;
            $promGral  = $row['promedio_general'];
            $pctAsist  = $row['pct_asistencia'];
            $trClass   = $i % 2 === 0 ? 'tr-even' : 'tr-odd';

            if ($promGral !== null) $grupoAllProms[] = $promGral;
            if ($pctAsist !== null) $grupoAsistencia[] = $pctAsist;

            // Situación
            $sitClass = 'sit-pend'; $sitLabel = 'Pendiente';
            if ($promGral !== null) {
                if ($ciclo === 'primer_ciclo') {
                    $sitClass = $promGral >= 2.5 ? 'sit-prom' : 'sit-rep';
                    $sitLabel = $promGral >= 2.5 ? 'Promovido' : 'No promovido';
                } else {
                    $reprobadas = collect($row['materias'])->filter(fn($m) => $m['aprobada'] === false)->count();
                    if ($promGral >= 65) {
                        $sitClass = 'sit-prom'; $sitLabel = 'Promovido';
                    } elseif ($reprobadas <= 2) {
                        $sitClass = 'sit-cond'; $sitLabel = 'Condicionado';
                    } else {
                        $sitClass = 'sit-rep'; $sitLabel = 'No promovido';
                    }
                }
            }

            $asistClass = 'asist-na';
            if ($pctAsist !== null) {
                $asistClass = $pctAsist >= 75 ? 'asist-ok' : ($pctAsist >= 65 ? 'asist-warn' : 'asist-bad');
            }
        @endphp

        <tr class="{{ $trClass }}">
            <td class="td-num">{{ $i + 1 }}</td>
            <td class="td-name">
                {{ $row['matricula']->estudiante?->apellidos }}, {{ $row['matricula']->estudiante?->nombres }}
            </td>

            {{-- Materias --}}
            @foreach($row['materias'] as $idx => $materia)
                @foreach($periodos as $p)
                    @php $v = $grid[$mId][$idx][$p->id] ?? null; @endphp
                    <td class="td-val {{ $valClass($v) }}">{{ $fmt($v) }}</td>
                @endforeach
                @php $pm = $materia['promedio']; @endphp
                <td class="td-pmat {{ $valClass($pm) }}">{{ $fmt($pm) }}</td>
            @endforeach

            {{-- Asistencia --}}
            <td class="td-asist {{ $asistClass }}">
                {{ $pctAsist !== null ? number_format($pctAsist, 0).'%' : '—' }}
            </td>

            {{-- Promedio general --}}
            <td class="td-gen {{ $valClass($promGral) }}">{{ $fmt($promGral) }}</td>

            {{-- Situación --}}
            <td class="td-sit {{ $sitClass }}">{{ $sitLabel }}</td>
        </tr>
    @endforeach

    {{-- ── Fila promedio grupal ── --}}
    <tr class="tr-avg">
        <td colspan="2" class="td-name">Promedio del grupo</td>

        @foreach($asignaciones as $idx => $asig)
            @foreach($periodos as $p)
                @php $v = $grupoProms[$idx][$p->id] ?? null; @endphp
                <td class="{{ $valClass($v) }}">{{ $fmt($v) }}</td>
            @endforeach
            @php $pg = $grupoProms[$idx]['general'] ?? null; @endphp
            <td class="{{ $valClass($pg) }}">{{ $fmt($pg) }}</td>
        @endforeach

        @php
            $promedioGrupoAsist = count($grupoAsistencia) ? round(array_sum($grupoAsistencia)/count($grupoAsistencia), 1) : null;
            $promedioGrupoGral  = count($grupoAllProms)   ? round(array_sum($grupoAllProms)/count($grupoAllProms), 2)   : null;
        @endphp
        <td class="{{ $promedioGrupoAsist !== null ? ($promedioGrupoAsist >= 75 ? 'asist-ok' : 'asist-warn') : 'asist-na' }}">
            {{ $promedioGrupoAsist !== null ? number_format($promedioGrupoAsist, 0).'%' : '—' }}
        </td>
        <td class="{{ $valClass($promedioGrupoGral) }}">{{ $fmt($promedioGrupoGral) }}</td>
        <td>—</td>
    </tr>

    </tbody>
</table>

{{-- ═══════════════════════════════════════════
     ESTADÍSTICAS DE SITUACIÓN
═══════════════════════════════════════════ --}}
@php
    $promovidos   = collect($registro)->filter(function($row) use ($ciclo) {
        $p = $row['promedio_general'];
        if ($p === null) return false;
        return $ciclo === 'primer_ciclo' ? $p >= 2.5 : $p >= 65;
    })->count();
    $pendientes   = collect($registro)->filter(fn($r) => $r['promedio_general'] === null)->count();
    $noPromovidos = count($registro) - $promovidos - $pendientes;
@endphp
<table style="width:100%;border-collapse:collapse;margin-bottom:8px;font-size:6.5pt;">
    <tr>
        <td style="background:#d1fae5;color:#065f46;font-weight:700;padding:3px 8px;border-radius:3px;text-align:center;width:22%;">
            Promovidos: {{ $promovidos }}
        </td>
        <td style="width:3%;"></td>
        <td style="background:#fee2e2;color:#991b1b;font-weight:700;padding:3px 8px;border-radius:3px;text-align:center;width:22%;">
            No promovidos: {{ $noPromovidos }}
        </td>
        <td style="width:3%;"></td>
        <td style="background:#f1f5f9;color:#64748b;font-weight:700;padding:3px 8px;border-radius:3px;text-align:center;width:22%;">
            Pendientes: {{ $pendientes }}
        </td>
        <td style="width:3%;"></td>
        <td style="background:#f0f4ff;color:#1e3a6e;font-weight:700;padding:3px 8px;border-radius:3px;text-align:center;width:25%;">
            Total matriculados: {{ count($registro) }}
        </td>
    </tr>
</table>

{{-- ═══════════════════════════════════════════
     PIE DE PÁGINA / FIRMAS
═══════════════════════════════════════════ --}}
<div class="doc-footer">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="width:30%;text-align:center;padding-top:26px;">
                <div style="border-top:1px solid #374151;width:150px;margin:0 auto;"></div>
                <div style="font-size:6pt;color:#374151;margin-top:2px;">Encargado/a de Registro</div>
                <div style="font-size:5.5pt;color:#94a3b8;margin-top:1px;">&nbsp;</div>
            </td>
            <td style="width:30%;text-align:center;padding-top:26px;">
                <div style="border-top:1px solid #374151;width:150px;margin:0 auto;"></div>
                <div style="font-size:6pt;color:#374151;margin-top:2px;">Director/a del Centro</div>
                <div style="font-size:5.5pt;color:#94a3b8;margin-top:1px;">&nbsp;</div>
            </td>
            <td style="width:40%;text-align:right;vertical-align:bottom;padding-bottom:2px;">
                <div style="font-size:5.5pt;color:#94a3b8;">
                    SGE — PSAC &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}<br>
                    {{ $grupo->grado->nombre }} — Sección {{ $grupo->seccion->nombre }}
                    &nbsp;|&nbsp; Año Escolar: {{ $schoolYear->nombre }}
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
