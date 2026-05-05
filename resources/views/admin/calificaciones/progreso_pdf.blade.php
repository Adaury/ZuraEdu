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
    tbody td.nombre-col { text-align:left; font-weight:600; }
    .ap  { color:#065f46; font-weight:700; }
    .re  { color:#991b1b; font-weight:700; }
    .gen { background:#e0f2fe; font-weight:800; }
    .footer { margin-top:12px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Progreso de Calificaciones por Período</h1>
    <p>{{ $grupo->nombre_completo }} &nbsp;·&nbsp; {{ $schoolYear->nombre }} &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th class="nombre-col" style="min-width:130px;text-align:left;">Asignatura</th>
            @foreach($periodos as $p)
            <th style="width:60px;">Período {{ $p->numero }}</th>
            @endforeach
            <th class="gen" style="width:65px;">Promedio</th>
        </tr>
    </thead>
    <tbody>
    @foreach($asignaciones as $asig)
    @php $gen = $promedios[$asig->id]['general'] ?? null; @endphp
    <tr>
        <td class="nombre-col">{{ $asig->asignatura?->nombre ?? '—' }}</td>
        @foreach($periodos as $p)
        @php $nota = $promedios[$asig->id][$p->id] ?? null; @endphp
        <td class="{{ $nota !== null ? ($nota >= 65 ? 'ap' : 're') : '' }}">
            {{ $nota !== null ? number_format($nota, 1) : '—' }}
        </td>
        @endforeach
        <td class="gen {{ $gen !== null ? ($gen >= 65 ? 'ap' : 're') : '' }}">
            {{ $gen !== null ? number_format($gen, 1) : '—' }}
        </td>
    </tr>
    @endforeach
    @php
        $promsGen = collect($asignaciones)->map(fn($a) => $promedios[$a->id]['general'] ?? null)->filter();
        $promTotal = $promsGen->count() ? round($promsGen->avg(), 1) : null;
    @endphp
    <tr style="background:#dbeafe;font-weight:800;">
        <td class="nombre-col" style="font-weight:800;">Promedio General</td>
        @foreach($periodos as $p)
        @php
            $notas = collect($asignaciones)->map(fn($a) => $promedios[$a->id][$p->id] ?? null)->filter();
            $pAvg  = $notas->count() ? round($notas->avg(), 1) : null;
        @endphp
        <td class="{{ $pAvg !== null ? ($pAvg >= 65 ? 'ap' : 're') : '' }}">
            {{ $pAvg !== null ? number_format($pAvg, 1) : '—' }}
        </td>
        @endforeach
        <td class="gen {{ $promTotal !== null ? ($promTotal >= 65 ? 'ap' : 're') : '' }}">
            {{ $promTotal !== null ? number_format($promTotal, 1) : '—' }}
        </td>
    </tr>
    </tbody>
</table>

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Progreso P1–P4 — {{ $grupo->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Aprobado ≥ 65
</div>
</body>
</html>
