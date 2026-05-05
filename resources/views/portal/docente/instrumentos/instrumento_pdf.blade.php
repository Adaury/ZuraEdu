<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #4f46e5; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.meta-row { display: flex; gap: 10px; margin-bottom: 10px; }
.meta-box  { background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 6px 9px; }
.meta-box .lbl { font-size: 7px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
.meta-box .val { font-size: 9px; font-weight: 700; color: #1e293b; }

.criterios-box { margin-bottom: 10px; background: #f8faff; border: 1px solid #e0e7ff; border-radius: 4px; padding: 7px 10px; }
.criterios-box .titulo { font-weight: 700; font-size: 8.5px; color: #4f46e5; margin-bottom: 5px; }
.criterio-item { display: flex; justify-content: space-between; padding: 2px 0; border-bottom: 1px dotted #e2e8f0; font-size: 8px; }
.criterio-item:last-child { border-bottom: none; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #4f46e5; color: #fff; }
thead th { padding: 5px 5px; font-size: 7.5px; border: 1px solid #4338ca; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f4ff; }
tbody td { padding: 4px 5px; border: 1px solid #e0e7ff; font-size: 8px; text-align: center; vertical-align: middle; }
tbody td.name { text-align: left; max-width: 130px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
tbody td.nota-cell { font-weight: 700; }
.nota-ap { color: #15803d; }
.nota-rep { color: #dc2626; }

.footer { margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 6px;
          display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; }
.firma-row { display: flex; gap: 24px; margin-top: 18px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 5px; font-size: 7.5px; color: #475569; margin-top: 22px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">INSTRUMENTO DE EVALUACIÓN — {{ strtoupper($instrumento->titulo) }}</div>
    <div class="sub">
        {{ $asignacion->asignatura->nombre ?? '' }}
        &nbsp;·&nbsp; {{ $asignacion->grupo->nombre_completo ?? '' }}
        &nbsp;·&nbsp; Docente: {{ $docente->nombre_completo ?? '' }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

{{-- Meta info --}}
<div class="meta-row">
    <div class="meta-box">
        <div class="lbl">Tipo</div>
        <div class="val">{{ $instrumento->tipo_label ?? ucfirst($instrumento->tipo) }}</div>
    </div>
    @if($instrumento->competencia)
    <div class="meta-box" style="flex:2;">
        <div class="lbl">Competencia</div>
        <div class="val">{{ $instrumento->competencia }}</div>
    </div>
    @endif
    @if($instrumento->indicadores_logro)
    <div class="meta-box" style="flex:3;">
        <div class="lbl">Indicadores de Logro</div>
        <div class="val">{{ $instrumento->indicadores_logro }}</div>
    </div>
    @endif
    <div class="meta-box">
        <div class="lbl">Total puntos</div>
        <div class="val">{{ $instrumento->criterios->sum('peso_max') }}</div>
    </div>
</div>

{{-- Criterios --}}
@if($instrumento->criterios->isNotEmpty())
<div class="criterios-box">
    <div class="titulo"><i>Criterios de evaluación</i></div>
    @foreach($instrumento->criterios->sortBy('orden') as $crit)
    <div class="criterio-item">
        <span>{{ $loop->iteration }}. {{ $crit->nombre }}@if($crit->descripcion) — <span style="color:#6b7280;font-style:italic;">{{ $crit->descripcion }}</span>@endif</span>
        <span style="font-weight:700;color:#4f46e5;">{{ $crit->peso_max }} pts</span>
    </div>
    @endforeach
</div>
@endif

{{-- Tabla de evaluaciones --}}
@php
    $criterios = $instrumento->criterios->sortBy('orden');
    $totalMax  = $criterios->sum('peso_max');
@endphp
<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th class="left" style="width:130px;">Estudiante</th>
            @foreach($criterios as $crit)
            <th style="width:{{ max(35, intval(380 / max($criterios->count(),1))) }}px;" title="{{ $crit->nombre }}">
                {{ mb_substr($crit->nombre, 0, 10) }}..<br>({{ $crit->peso_max }})
            </th>
            @endforeach
            <th style="width:45px;">Total<br>/{{ $totalMax }}</th>
            <th style="width:35px;">%</th>
            @if($instrumento->tipo === 'rubrica')<th style="width:60px;">Nivel</th>@endif
            <th class="left" style="width:80px;">Observación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matriculas as $i => $mat)
        @php
            $ev    = $evaluaciones[$mat->id] ?? null;
            $puntajes = $ev ? ($ev->puntajes ?? []) : [];
            $total = 0;
            foreach ($criterios as $crit) {
                $total += (float)($puntajes[$crit->id] ?? 0);
            }
            $pct = $totalMax > 0 ? round($total / $totalMax * 100, 1) : null;
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="name">{{ $mat->estudiante->nombre_completo ?? ($mat->estudiante->nombres . ' ' . $mat->estudiante->apellidos) }}</td>
            @foreach($criterios as $crit)
            <td>{{ $puntajes[$crit->id] ?? '—' }}</td>
            @endforeach
            <td class="nota-cell {{ $ev && $total >= ($totalMax * 0.6) ? 'nota-ap' : 'nota-rep' }}">
                {{ $ev ? number_format($total, 1) : '—' }}
            </td>
            <td style="color:{{ $pct !== null ? ($pct >= 60 ? '#15803d' : '#dc2626') : '#94a3b8' }};">
                {{ $pct !== null ? $pct . '%' : '—' }}
            </td>
            @if($instrumento->tipo === 'rubrica')
            <td style="font-size:7px;">{{ $ev?->nivel_desempeno ?? '—' }}</td>
            @endif
            <td class="left" style="font-size:7px;color:#6b7280;">{{ $ev?->observacion ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="firma-row">
    <div class="firma-box">Docente: {{ $docente->nombre_completo ?? '' }}</div>
    <div class="firma-box">Coordinador/a Académico</div>
    <div class="firma-box">Sello del Centro</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Instrumento: {{ $instrumento->titulo }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
