<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7pt; font-weight:bold;
               padding:4px 5px; text-align:center; border:1px solid #2a4f96; }
    thead th.nombre-col { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:3.5px 5px; font-size:7.5pt; border:1px solid #e5e7eb;
               text-align:center; vertical-align:middle; }
    tbody td.nombre-col { text-align:left; font-weight:600; font-size:7.5pt; }
    .nota-ap { color:#065f46; font-weight:700; }
    .nota-re { color:#991b1b; font-weight:700; }
    .footer { margin-top:12px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Calificaciones</h1>
    <p>{{ $grupo->nombre_completo }} &nbsp;·&nbsp; {{ $sy?->nombre ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th class="nombre-col" style="min-width:130px;text-align:left;">Estudiante</th>
            @foreach($asignaciones as $asig)
            <th style="max-width:55px;">{{ \Illuminate\Support\Str::limit($asig->asignatura?->nombre ?? '—', 10) }}</th>
            @endforeach
            <th style="width:50px;">Prom.</th>
        </tr>
    </thead>
    <tbody>
    @foreach($matriculas as $i => $mat)
    @php
        $misCalifs = $calAcad[$mat->id] ?? collect();
        $notas = [];
    @endphp
    <tr>
        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td class="nombre-col">{{ ($mat->estudiante?->apellidos ?? '') . ', ' . ($mat->estudiante?->nombres ?? '') }}</td>
        @foreach($asignaciones as $asig)
        @php
            $cal  = $misCalifs->firstWhere('asignacion_id', $asig->id);
            $nota = $cal?->nota_final;
            if ($nota !== null) $notas[] = $nota;
        @endphp
        <td class="{{ $nota !== null ? ($nota >= 65 ? 'nota-ap' : 'nota-re') : '' }}">
            {{ $nota !== null ? number_format($nota, 1) : '—' }}
        </td>
        @endforeach
        @php $prom = count($notas) ? round(array_sum($notas)/count($notas), 1) : null; @endphp
        <td style="font-weight:700;{{ $prom !== null ? ($prom >= 65 ? 'color:#065f46' : 'color:#991b1b') : '' }}">
            {{ $prom !== null ? number_format($prom, 1) : '—' }}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Notas — {{ $grupo->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Aprobado ≥ 65
</div>
</body>
</html>
