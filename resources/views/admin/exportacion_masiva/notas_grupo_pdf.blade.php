<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7pt; color: #1e293b; }
    .header { background: #1e3a6e; color: #fff; padding: 8px 14px; margin-bottom: 10px; }
    .header h1 { font-size: 11pt; font-weight: bold; margin-bottom: 2px; }
    .header p  { font-size: 7pt; opacity: .85; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #1e3a6e; color: #fff; font-size: 6.5pt; font-weight: bold;
        padding: 4px 3px; text-align: center; white-space: nowrap;
    }
    thead th.left { text-align: left; }
    tbody tr:nth-child(even) td { background: #eff6ff; }
    tbody td { padding: 3px 3px; font-size: 6.5pt; border-bottom: 1px solid #e2e8f0; text-align: center; }
    tbody td.left { text-align: left; font-weight: 600; }
    .nota-baja { color: #dc2626; font-weight: bold; }
    .nota-alta { color: #15803d; font-weight: bold; }
    .prom { font-weight: bold; border-left: 2px solid #93c5fd; }
    .footer { margin-top: 10px; font-size: 6.5pt; color: #94a3b8; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 5px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $inst }} — Acta de Notas</h1>
    <p>
        {{ $grupo->grado?->nombre }} {{ $grupo->seccion?->nombre }}
        &nbsp;·&nbsp; {{ $schoolYear?->nombre }}
        @if($periodo)
            &nbsp;·&nbsp; Período {{ $periodo->numero }}
        @endif
        &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th class="left" style="width:18px;">#</th>
            <th class="left" style="min-width:120px;">Estudiante</th>
            @foreach($asignaciones as $asi)
                @foreach($periodos as $p)
                <th title="{{ $asi->asignatura?->nombre }} — P{{ $p->numero }}">
                    {{ \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '?', 7) }}<br>P{{ $p->numero }}
                </th>
                @endforeach
            @endforeach
            <th class="prom">Prom.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $promediosMateria = [];
            foreach ($asignaciones as $asi) {
                $notasPeriodo = [];
                foreach ($periodos as $p) {
                    $n = ($matrix[$mat->id][$asi->id][$p->id] ?? null)?->nota_final;
                    if ($n !== null) $notasPeriodo[] = (float) $n;
                }
                $promediosMateria[] = count($notasPeriodo) > 0
                    ? round(array_sum($notasPeriodo) / count($notasPeriodo), 1)
                    : null;
            }
            $promValidos = array_filter($promediosMateria, fn($v) => $v !== null);
            $promGeneral = count($promValidos) > 0
                ? round(array_sum($promValidos) / count($promValidos), 1)
                : null;
        @endphp
        <tr>
            <td style="color:#9ca3af;">{{ $i + 1 }}</td>
            <td class="left">{{ $mat->estudiante?->apellidos }}, {{ $mat->estudiante?->nombres }}</td>
            @foreach($asignaciones as $idx => $asi)
                @foreach($periodos as $p)
                @php $nota = ($matrix[$mat->id][$asi->id][$p->id] ?? null)?->nota_final; @endphp
                <td class="{{ $nota !== null && (float)$nota < 70 ? 'nota-baja' : ($nota !== null && (float)$nota >= 90 ? 'nota-alta' : '') }}">
                    {{ $nota !== null ? number_format((float)$nota, 1) : '—' }}
                </td>
                @endforeach
            @endforeach
            <td class="prom {{ $promGeneral !== null && $promGeneral < 70 ? 'nota-baja' : ($promGeneral !== null && $promGeneral >= 90 ? 'nota-alta' : '') }}">
                {{ $promGeneral !== null ? number_format($promGeneral, 1) : '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; {{ $grupo->grado?->nombre }} {{ $grupo->seccion?->nombre }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
