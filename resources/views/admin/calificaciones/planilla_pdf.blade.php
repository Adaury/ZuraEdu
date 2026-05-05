<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla Académica — {{ $asignacion->asignatura->nombre }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 7pt; }
        .header { text-align: center; margin-bottom: 8pt; }
        .header h2 { font-size: 10pt; }
        .header p { font-size: 8pt; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 1pt 2pt; text-align: center; font-size: 6.5pt; }
        th { background: #1e3a8a; color: white; font-weight: bold; }
        .th-c1 { background: #1e40af; }
        .th-c2 { background: #1d4ed8; }
        .th-c3 { background: #2563eb; }
        .th-c4 { background: #3b82f6; }
        .th-pc { background: #0e7490; }
        .th-fin { background: #0f766e; }
        .th-compl { background: #b45309; }
        .th-extra { background: #9f1239; }
        .th-eval { background: #6d28d9; }
        .th-sit { background: #374151; }
        .th-asist { background: #166534; }
        .td-nom { text-align: left; padding-left: 3pt; min-width: 100pt; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .sit-a { color: green; font-weight: bold; }
        .sit-r { color: red; font-weight: bold; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4 landscape; margin: 8mm; }
        }
    </style>
</head>
<body>
<div class="header">
    <h2>POLITÉCNICO SALESIANO ARQUIDES CALDERÓN (PSAC)</h2>
    <p>Planilla de Calificaciones — Área Académica &nbsp;|&nbsp; {{ $schoolYear->nombre }}</p>
    <p>
        <strong>Asignatura:</strong> {{ $asignacion->asignatura->nombre }} &nbsp;|&nbsp;
        <strong>Grupo:</strong> {{ $asignacion->grupo->nombre_completo }} &nbsp;|&nbsp;
        <strong>Docente:</strong> {{ $asignacion->docente?->nombre_completo ?? '—' }}
    </p>
</div>

<table>
<thead>
<tr>
    <th rowspan="2">#</th>
    <th rowspan="2" class="td-nom" style="color:white;text-align:left;">Estudiante</th>
    <th colspan="4" class="th-c1">COMP. 1</th>
    <th colspan="4" class="th-c2">COMP. 2</th>
    <th colspan="4" class="th-c3">COMP. 3</th>
    <th colspan="4" class="th-c4">COMP. 4</th>
    <th colspan="4" class="th-pc">PROM. COMP.</th>
    <th rowspan="2" class="th-fin">CAL.<br>FINAL</th>
    <th colspan="4" class="th-compl">COMPLETIVAS</th>
    <th colspan="3" class="th-extra">EXTRAORDINARIAS</th>
    <th colspan="2" class="th-eval">EVAL.<br>ESP.</th>
    <th colspan="2" class="th-sit">SITUACIÓN</th>
    <th colspan="5" class="th-asist">ASISTENCIA</th>
</tr>
<tr>
    @foreach([1,2,3,4] as $c)
        <th class="th-c{{ $c }}">P1</th>
        <th class="th-c{{ $c }}">P2</th>
        <th class="th-c{{ $c }}">P3</th>
        <th class="th-c{{ $c }}">P4</th>
    @endforeach
    <th class="th-pc">PC1</th><th class="th-pc">PC2</th><th class="th-pc">PC3</th><th class="th-pc">PC4</th>
    <th class="th-compl">CC</th><th class="th-compl">50%CC</th><th class="th-compl">CE</th><th class="th-compl">50%CE</th>
    <th class="th-extra">Compl.</th><th class="th-extra">Extraord.</th><th class="th-extra">70%EX</th>
    <th class="th-eval">CF</th><th class="th-eval">CE</th>
    <th class="th-sit">A</th><th class="th-sit">R</th>
    <th class="th-asist">P1</th><th class="th-asist">P2</th><th class="th-asist">P3</th><th class="th-asist">P4</th><th class="th-asist">%</th>
</tr>
</thead>
<tbody>
@php $n = 1; @endphp
@foreach($matriculas as $m)
@php $r = $registros[$m->id] ?? null; @endphp
<tr>
    <td>{{ $n++ }}</td>
    <td class="td-nom">{{ $m->estudiante->nombre_completo }}</td>
    @foreach([1,2,3,4] as $c)
        @foreach([1,2,3,4] as $p)
            <td>{{ $r?->{"comp{$c}_p{$p}"} ?? '' }}</td>
        @endforeach
    @endforeach
    @foreach([1,2,3,4] as $i)
        <td>{{ $r?->{"prom_comp{$i}"} !== null ? number_format($r->{"prom_comp{$i}"}, 1) : '' }}</td>
    @endforeach
    <td>{{ $r?->nota_final !== null ? number_format($r->nota_final, 1) : '' }}</td>
    <td>{{ $r?->nota_cc !== null ? number_format($r->nota_cc, 1) : '' }}</td>
    <td>{{ $r?->nota_cc !== null ? number_format($r->nota_cc * 0.5, 1) : '' }}</td>
    <td>{{ $r?->nota_ce !== null ? number_format($r->nota_ce, 1) : '' }}</td>
    <td>{{ $r?->nota_ce !== null ? number_format($r->nota_ce * 0.5, 1) : '' }}</td>
    <td>{{ $r?->nota_completiva !== null ? number_format($r->nota_completiva, 1) : '' }}</td>
    <td>{{ $r?->nota_extraordinaria !== null ? number_format($r->nota_extraordinaria, 1) : '' }}</td>
    <td>{{ $r?->nota_extraordinaria !== null ? number_format($r->nota_extraordinaria * 0.7, 1) : '' }}</td>
    <td>{{ $r?->eval_cf ?? '' }}</td>
    <td>{{ $r?->eval_ce ?? '' }}</td>
    <td class="sit-a">{{ $r?->situacion === 'A' ? '✓' : '' }}</td>
    <td class="sit-r">{{ $r?->situacion === 'R' ? '✗' : '' }}</td>
    @foreach([1,2,3,4] as $p)
        <td>{{ $r?->{"asist_p{$p}"} ?? '' }}</td>
    @endforeach
    <td>{{ $r?->pct_asistencia !== null ? number_format($r->pct_asistencia, 1) . '%' : '' }}</td>
</tr>
@endforeach
</tbody>
</table>

<div style="margin-top:20pt;font-size:7pt;text-align:right;color:#555;">
    Generado: {{ now()->format('d/m/Y H:i') }} | PSAC SGE
</div>
</body>
</html>
