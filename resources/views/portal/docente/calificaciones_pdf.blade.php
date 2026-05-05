<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:8.5px; color:#1e293b; }
    .header { text-align:center; margin-bottom:10px; border-bottom:2px solid #1e3a6e; padding-bottom:7px; }
    .header h2 { font-size:12px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:8px; color:#64748b; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:4px 3px; font-size:8px; text-align:center; }
    th.left { text-align:left; }
    td { padding:3px; border-bottom:1px solid #e2e8f0; text-align:center; }
    td.left { text-align:left; }
    tr.alt { background:#f0f6ff; }
    .nota-ok  { color:#15803d; font-weight:700; }
    .nota-bad { color:#dc2626; font-weight:700; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Calificaciones: {{ $asignacion->asignatura?->nombre }}</h2>
    <p>
        {{ $asignacion->grupo?->grado?->nombre }} {{ $asignacion->grupo?->seccion?->nombre }}
        &nbsp;·&nbsp; Docente: {{ $docente->nombre_completo }}
        &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th class="left">#</th>
            <th class="left">Estudiante</th>
            @foreach($periodos as $p)
                @if($esTecnica)
                    <th>P{{ $p->numero }}</th>
                @else
                    <th>P{{ $p->numero }} C1</th>
                    <th>P{{ $p->numero }} C2</th>
                    <th>P{{ $p->numero }} C3</th>
                    <th>P{{ $p->numero }} C4</th>
                    <th>P{{ $p->numero }} Prom</th>
                @endif
            @endforeach
            <th>Promedio</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $est = $mat->estudiante;
            $notasFinales = [];
            if ($esTecnica) {
                foreach ($periodos as $p) {
                    $nota = $calMap[$mat->id . '_' . $p->id]?->first()?->nota_final ?? null;
                    if ($nota !== null) $notasFinales[] = $nota;
                }
            } else {
                $cal = $calMap[$mat->id] ?? null;
                foreach ($periodos as $p) {
                    $nota = $cal ? ($cal->{'p' . $p->numero . '_prom'} ?? null) : null;
                    if ($nota !== null) $notasFinales[] = $nota;
                }
            }
            $promFinal = count($notasFinales) ? round(array_sum($notasFinales) / count($notasFinales), 1) : null;
        @endphp
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td class="left">{{ $i + 1 }}</td>
            <td class="left">{{ $est?->apellidos }}, {{ $est?->nombres }}</td>
            @foreach($periodos as $p)
                @if($esTecnica)
                    @php $nota = $calMap[$mat->id . '_' . $p->id]?->first()?->nota_final ?? null; @endphp
                    <td class="{{ $nota !== null && $nota < 70 ? 'nota-bad' : ($nota !== null && $nota >= 90 ? 'nota-ok' : '') }}">
                        {{ $nota ?? '—' }}
                    </td>
                @else
                    @php $cal = $calMap[$mat->id] ?? null; $pn = $p->numero; @endphp
                    <td>{{ $cal?->{"p{$pn}_c1"} ?? '—' }}</td>
                    <td>{{ $cal?->{"p{$pn}_c2"} ?? '—' }}</td>
                    <td>{{ $cal?->{"p{$pn}_c3"} ?? '—' }}</td>
                    <td>{{ $cal?->{"p{$pn}_c4"} ?? '—' }}</td>
                    @php $pr = $cal?->{"p{$pn}_prom"} ?? null; @endphp
                    <td class="{{ $pr !== null && $pr < 70 ? 'nota-bad' : ($pr !== null && $pr >= 90 ? 'nota-ok' : '') }}">
                        {{ $pr ?? '—' }}
                    </td>
                @endif
            @endforeach
            <td class="{{ $promFinal !== null && $promFinal < 70 ? 'nota-bad' : ($promFinal !== null && $promFinal >= 90 ? 'nota-ok' : '') }}">
                {{ $promFinal ?? '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
