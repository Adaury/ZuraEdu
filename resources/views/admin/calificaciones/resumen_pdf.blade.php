<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.5pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 10px; }
    .header .inst { font-size: 10pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 8.5pt; font-weight: bold; margin-top: 2px; }
    .header .sub { font-size: 7pt; color: #64748b; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; font-size: 6.5pt; }
    th { background: #1e3a6e; color: #fff; padding: 4px 3px; text-align: center; white-space: nowrap; }
    th.left { text-align: left; }
    td { padding: 3px; border-bottom: 1px solid #e2e8f0; text-align: center; }
    td.left { text-align: left; }
    tr.even td { background: #eff6ff; }
    .nota-baja { color: #dc2626; font-weight: bold; }
    .nota-alta { color: #15803d; font-weight: bold; }
    .footer { margin-top: 10px; text-align: right; font-size: 6.5pt; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Resumen de Calificaciones — {{ $grupo->grado?->nombre }} {{ $grupo->seccion?->nombre }}</div>
    <div class="sub">{{ $schoolYear->nombre }} — Generado el {{ now()->format('d/m/Y H:i') }}</div>
</div>

<table>
    <thead>
        <tr>
            <th class="left">#</th>
            <th class="left">Estudiante</th>
            @foreach($asignaciones as $asi)
                @foreach($periodos as $p)
                <th>{{ \Illuminate\Support\Str::limit($asi->asignatura?->nombre ?? '?', 8) }}<br>P{{ $p->numero }}</th>
                @endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td class="left">{{ $mat->estudiante?->nombre_completo ?? '—' }}</td>
            @foreach($asignaciones as $asi)
                @foreach($periodos as $p)
                @php $cal = $matrix[$mat->id][$asi->id][$p->id] ?? null; $nota = $cal?->nota_final; @endphp
                <td class="{{ $nota !== null && $nota < 70 ? 'nota-baja' : ($nota !== null && $nota >= 90 ? 'nota-alta' : '') }}">
                    {{ $nota !== null ? number_format($nota, 1) : '—' }}
                </td>
                @endforeach
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
