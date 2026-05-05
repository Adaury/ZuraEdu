<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

/* ── Encabezado ───────────────────────────────────────────── */
.header {
    text-align: center;
    border-bottom: 2.5px solid #1e40af;
    padding-bottom: 10px;
    margin-bottom: 14px;
}
.header .inst {
    font-size: 13px;
    font-weight: bold;
    color: #1e40af;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.header .sub {
    font-size: 8.5px;
    color: #64748b;
    margin-top: 2px;
}
.header .doc-label {
    display: inline-block;
    margin-top: 6px;
    background: #dbeafe;
    color: #1e3a8a;
    font-size: 9.5px;
    font-weight: bold;
    padding: 3px 18px;
    border-radius: 4px;
    letter-spacing: .3px;
}

/* ── Ficha del evento ─────────────────────────────────────── */
.event-card {
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    padding: 10px 14px;
    margin-bottom: 14px;
    background: #f8faff;
}
.event-card .ev-title {
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 6px;
}
.event-card .meta-row {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}
.event-card .meta-item .lbl { color: #64748b; font-size: 8px; }
.event-card .meta-item .val { font-weight: 700; color: #1e293b; font-size: 9px; }

/* ── Badge tipo ───────────────────────────────────────────── */
.badge-tipo {
    display: inline-block;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 7.5px;
    font-weight: bold;
    color: #fff;
}
.bg-academico  { background: #1d4ed8; }
.bg-deportivo  { background: #15803d; }
.bg-cultural   { background: #7c3aed; }
.bg-social     { background: #b45309; }
.bg-otro       { background: #4b5563; }

/* ── Estadísticas --*/
.stats-row {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
}
.stat-box {
    flex: 1;
    text-align: center;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    padding: 7px 4px;
    background: #fff;
}
.stat-box .sval { font-size: 16px; font-weight: 800; color: #1e40af; line-height: 1; }
.stat-box .slbl { font-size: 7.5px; color: #64748b; margin-top: 2px; }

/* ── Tabla de inscritos ───────────────────────────────────── */
table { width: 100%; border-collapse: collapse; margin-top: 4px; }
thead tr { background: #1e3a8a; color: #fff; }
thead th { padding: 6px 8px; font-size: 8.5px; font-weight: 700; text-align: left; }
thead th.center { text-align: center; }
tbody tr:nth-child(even) { background: #f0f4ff; }
tbody tr td { padding: 5px 8px; font-size: 8.5px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
tbody tr td.center { text-align: center; }

.asistio-si  { color: #15803d; font-weight: 700; }
.asistio-no  { color: #9ca3af; }
.asistio-box {
    display: inline-block;
    width: 11px; height: 11px;
    border: 1.5px solid #94a3b8;
    border-radius: 2px;
    text-align: center;
    line-height: 9px;
    font-size: 9px;
}
.asistio-box.checked { background: #15803d; border-color: #15803d; color: #fff; }

/* ── Footer ───────────────────────────────────────────────── */
.footer {
    margin-top: 14px;
    border-top: 1px solid #e2e8f0;
    padding-top: 7px;
    display: flex;
    justify-content: space-between;
    font-size: 7.5px;
    color: #94a3b8;
}
</style>
</head>
<body>

{{-- Encabezado institucional --}}
<div class="header">
    <div class="inst">{{ $inst }}</div>
    @if($dir)
    <div class="sub">Director/a: {{ $dir }}</div>
    @endif
    @if($config?->codigo_centro ?? \App\Models\ConfigInstitucional::get('codigo_centro',''))
    <div class="sub">Código: {{ $config?->codigo_centro ?? \App\Models\ConfigInstitucional::get('codigo_centro','') }}</div>
    @endif
    <div><span class="doc-label">LISTA DE INSCRITOS — EVENTO EXTRACURRICULAR</span></div>
</div>

{{-- Ficha del evento --}}
@php
$tipoClass = [
    'academico' => 'bg-academico',
    'deportivo' => 'bg-deportivo',
    'cultural'  => 'bg-cultural',
    'social'    => 'bg-social',
    'otro'      => 'bg-otro',
];
$tipoLabel = [
    'academico' => 'Académico',
    'deportivo' => 'Deportivo',
    'cultural'  => 'Cultural',
    'social'    => 'Social',
    'otro'      => 'Otro',
];
$tc = $tipoClass[$evento->tipo] ?? 'bg-otro';
$tl = $tipoLabel[$evento->tipo] ?? ucfirst($evento->tipo);

$totalInscritos   = $inscripciones->count();
$totalAsistieron  = $inscripciones->where('asistio', true)->count();
$pctAsistencia    = $totalInscritos > 0 ? round($totalAsistieron / $totalInscritos * 100) : 0;
@endphp

<div class="event-card">
    <div class="ev-title">
        {{ $evento->nombre }}
        <span class="badge-tipo {{ $tc }}" style="margin-left:6px;vertical-align:middle;">{{ $tl }}</span>
    </div>
    <div class="meta-row">
        <div class="meta-item">
            <div class="lbl">Fecha inicio</div>
            <div class="val">{{ $evento->fecha_inicio->format('d/m/Y') }}</div>
        </div>
        @if($evento->fecha_fin)
        <div class="meta-item">
            <div class="lbl">Fecha fin</div>
            <div class="val">{{ $evento->fecha_fin->format('d/m/Y') }}</div>
        </div>
        @endif
        @if($evento->lugar)
        <div class="meta-item">
            <div class="lbl">Lugar</div>
            <div class="val">{{ $evento->lugar }}</div>
        </div>
        @endif
        @if($evento->cupo_maximo)
        <div class="meta-item">
            <div class="lbl">Cupo máximo</div>
            <div class="val">{{ $evento->cupo_maximo }}</div>
        </div>
        @endif
        @if($evento->descripcion)
        <div class="meta-item" style="flex:2;">
            <div class="lbl">Descripción</div>
            <div class="val" style="font-weight:400;">{{ \Illuminate\Support\Str::limit($evento->descripcion, 120) }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Estadísticas --}}
<div class="stats-row">
    <div class="stat-box">
        <div class="sval">{{ $totalInscritos }}</div>
        <div class="slbl">Inscritos</div>
    </div>
    <div class="stat-box">
        <div class="sval" style="color:#15803d;">{{ $totalAsistieron }}</div>
        <div class="slbl">Asistieron</div>
    </div>
    <div class="stat-box">
        <div class="sval" style="color:#b45309;">{{ $totalInscritos - $totalAsistieron }}</div>
        <div class="slbl">Ausentes</div>
    </div>
    <div class="stat-box">
        <div class="sval" style="color:#1d4ed8;">{{ $pctAsistencia }}%</div>
        <div class="slbl">% Asistencia</div>
    </div>
</div>

{{-- Tabla de inscritos --}}
@if($inscripciones->isEmpty())
    <p style="text-align:center;color:#94a3b8;margin-top:20px;font-size:9px;">
        No hay estudiantes inscritos en este evento.
    </p>
@else
<table>
    <thead>
        <tr>
            <th style="width:28px;" class="center">#</th>
            <th style="width:200px;">Nombre Completo</th>
            <th style="width:90px;">Matrícula</th>
            <th>Grupo / Grado</th>
            <th style="width:80px;">F. Inscripción</th>
            <th style="width:65px;" class="center">Asistió</th>
        </tr>
    </thead>
    <tbody>
    @foreach($inscripciones as $i => $insc)
    @php
        $est = $insc->estudiante;
        $matricula = $est?->matriculas->first();
    @endphp
    <tr>
        <td class="center" style="color:#94a3b8;">{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $est?->nombre_completo ?? '—' }}</td>
        <td style="color:#475569;">{{ $est?->numero_matricula ?? '—' }}</td>
        <td>
            @if($matricula?->grupo)
                {{ $matricula->grupo->grado->nombre ?? '' }}
                {{ $matricula->grupo->seccion->nombre ?? '' }}
            @else
                <span style="color:#9ca3af;">—</span>
            @endif
        </td>
        <td>{{ $insc->fecha_inscripcion?->format('d/m/Y') ?? '—' }}</td>
        <td class="center">
            @if($insc->asistio)
                <span class="asistio-box checked">&#10003;</span>
            @else
                <span class="asistio-box"></span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

{{-- Firma --}}
<div style="margin-top:30px;display:flex;gap:40px;">
    <div style="flex:1;text-align:center;border-top:1px solid #94a3b8;padding-top:5px;font-size:8px;color:#475569;margin-top:30px;">
        <strong>{{ $dir ?: 'Director/a del Centro' }}</strong><br>
        Director/a
    </div>
    <div style="flex:1;text-align:center;margin-top:30px;">
        <div style="width:70px;height:70px;border:2px dashed #94a3b8;border-radius:50%;
                    display:inline-block;line-height:70px;color:#cbd5e1;font-size:7.5px;">SELLO</div>
    </div>
    <div style="flex:1;text-align:center;border-top:1px solid #94a3b8;padding-top:5px;font-size:8px;color:#475569;margin-top:30px;">
        Responsable del Evento: _______________________<br>
        <br>Fecha: ____________________________
    </div>
</div>

<div class="footer">
    <span>{{ $inst }} — Lista de Inscritos / Evento Extracurricular</span>
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
    <span>Asistencia: {{ $pctAsistencia }}% ({{ $totalAsistieron }}/{{ $totalInscritos }})</span>
</div>

</body>
</html>
