<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0;padding:0;box-sizing:border-box; }
    body { font-family:DejaVu Sans,sans-serif;font-size:9px;color:#1e293b; }
    .header { background:#4f46e5;color:#fff;padding:10px 14px;border-radius:6px 6px 0 0;margin-bottom:10px; }
    .header h1 { font-size:13px;font-weight:700; }
    .header .sub { font-size:8px;opacity:.85;margin-top:2px; }
    .kpi-row { display:flex;gap:8px;margin-bottom:10px; }
    .kpi { flex:1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:5px;padding:6px 8px;text-align:center; }
    .kpi .val { font-size:13px;font-weight:800;color:#4f46e5; }
    .kpi .lbl { font-size:7px;color:#64748b;margin-top:1px; }
    table { width:100%;border-collapse:collapse;font-size:8px; }
    th { background:#f1f5f9;font-weight:700;padding:4px 6px;border:1px solid #e2e8f0;color:#475569;text-align:left; }
    td { padding:4px 6px;border:1px solid #e2e8f0;vertical-align:middle; }
    tr:nth-child(even) td { background:#fafbfc; }
    .badge { display:inline-block;padding:1px 5px;border-radius:99px;font-size:7px;font-weight:700;color:#fff; }
    .bg-ok  { background:#10b981; }
    .bg-no  { background:#ef4444; }
    .bg-na  { background:#94a3b8; }
    .section { font-size:9px;font-weight:700;color:#4f46e5;margin:10px 0 5px;text-transform:uppercase;letter-spacing:.05em; }
    .pbar-bg { background:#e2e8f0;border-radius:99px;height:4px;width:60px;display:inline-block;vertical-align:middle; }
    .pbar-fill { height:4px;border-radius:99px; }
    .footer { margin-top:14px;display:flex;justify-content:space-between; }
    .sig { border-top:1px solid #475569;padding-top:3px;width:28%;font-size:7px;color:#64748b;text-align:center; }
    .pregunta-row td { font-size:7.5px; }
</style>
</head>
<body>

<div class="header">
    <h1>Resultados: {{ $quiz->titulo }}</h1>
    <div class="sub">
        {{ $asignacion->asignatura?->nombre ?? '' }} ·
        {{ $asignacion->grupo?->nombre ?? '' }} ·
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi">
        <div class="val">{{ $stats['completaron'] }}</div>
        <div class="lbl">Completaron</div>
    </div>
    <div class="kpi">
        <div class="val" style="color:#94a3b8;">{{ $stats['pendientes'] }}</div>
        <div class="lbl">Pendientes</div>
    </div>
    <div class="kpi">
        <div class="val" style="color:#10b981;">{{ $stats['promedio'] ?? '—' }}%</div>
        <div class="lbl">Promedio</div>
    </div>
    <div class="kpi">
        <div class="val" style="color:#f59e0b;">{{ $stats['aprobados'] }}</div>
        <div class="lbl">Aprobados (≥60%)</div>
    </div>
    <div class="kpi">
        <div class="val" style="color:#475569;">{{ $puntajeTotal }}</div>
        <div class="lbl">Pts. total</div>
    </div>
    <div class="kpi">
        @php $tasa = $stats['completaron'] > 0 ? round($stats['aprobados'] / $stats['completaron'] * 100) : 0; @endphp
        <div class="val" style="color:{{ $tasa >= 60 ? '#10b981' : '#ef4444' }};">{{ $tasa }}%</div>
        <div class="lbl">Tasa de aprobación</div>
    </div>
</div>

{{-- Tabla de estudiantes --}}
<div class="section">Resultados por Estudiante</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Estudiante</th>
            <th>Puntaje</th>
            <th>Porcentaje</th>
            <th>Estado</th>
            <th>Duración</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $idx => $m)
        @php $intento = $mejores->get($m->id); @endphp
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td style="font-weight:600;">{{ $m->estudiante?->nombre_completo ?? '—' }}</td>
            @if($intento)
            <td><strong style="color:#4f46e5;">{{ $intento->puntuacion }}</strong> / {{ $puntajeTotal }}</td>
            <td>
                <div class="pbar-bg" style="display:inline-block;vertical-align:middle;">
                    <div class="pbar-fill" style="width:{{ min($intento->porcentaje, 100) }}%;background:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};"></div>
                </div>
                <strong style="color:{{ $intento->porcentaje >= 60 ? '#10b981' : '#ef4444' }};margin-left:3px;">{{ $intento->porcentaje }}%</strong>
            </td>
            <td>
                @if($intento->porcentaje >= 60)
                    <span class="badge bg-ok">Aprobado</span>
                @else
                    <span class="badge bg-no">No aprobado</span>
                @endif
            </td>
            <td>{{ $intento->duracion ?? '—' }}</td>
            @else
            <td colspan="4" style="color:#94a3b8;font-style:italic;">Sin responder</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Análisis por pregunta --}}
@if($analisisPregunta->isNotEmpty())
<div class="section" style="margin-top:14px;">Análisis por Pregunta</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Enunciado</th>
            <th>Tipo</th>
            <th>Puntos</th>
            <th>Respondieron bien</th>
            <th>%</th>
        </tr>
    </thead>
    <tbody>
        @foreach($analisisPregunta as $i => $ap)
        <tr class="pregunta-row">
            <td>{{ $i + 1 }}</td>
            <td>{{ Str::limit($ap['pregunta']->enunciado, 70) }}</td>
            <td>{{ ['multiple'=>'Múltiple','verdadero_falso'=>'V/F','abierta'=>'Abierta'][$ap['pregunta']->tipo] ?? $ap['pregunta']->tipo }}</td>
            <td>{{ $ap['pregunta']->puntos }}</td>
            <td>{{ $ap['correctas'] }}/{{ $ap['total'] }}</td>
            <td>
                @if($ap['pct'] !== null)
                <strong style="color:{{ $ap['pct'] >= 60 ? '#10b981' : '#ef4444' }};">{{ $ap['pct'] }}%</strong>
                @else —
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Firmas --}}
<div class="footer">
    <div class="sig">Docente</div>
    <div class="sig">Director(a)</div>
    <div class="sig">Coordinador(a)</div>
</div>

</body>
</html>
