<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 16px; padding-bottom: 12px;
          border-bottom: 2px solid #92400e; }
.header .inst  { font-size: 13px; font-weight: bold; color: #92400e; text-transform: uppercase; }
.header .sub   { font-size: 9px; color: #475569; margin-top: 3px; }
.header .titulo{ font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 6px; letter-spacing:.03em; }
.header .subtitulo { font-size: 9px; color: #6b7280; margin-top: 4px; }

.stars { text-align:center; font-size:18px; margin: 8px 0 14px; color: #f59e0b; letter-spacing:6px; }

.meta { display: flex; justify-content: space-between; margin-bottom: 14px;
        background: #fef3c7; padding: 6px 10px; border-radius: 4px; font-size: 8.5px;
        border: 1px solid #fde68a; }
.meta strong { color: #0f172a; }

/* Podio */
.podio { display: flex; align-items: flex-end; justify-content: center; gap: 12px; margin-bottom: 16px; }
.podio-box { text-align: center; }
.podio-medal { width: 36px; height: 36px; border-radius: 50%; display: flex;
               align-items: center; justify-content: center; font-size: 14px;
               font-weight: 800; margin: 0 auto 4px; border: 2px solid; }
.medal-1 { background: #fef9c3; color: #92400e; border-color: #f59e0b; }
.medal-2 { background: #f1f5f9; color: #475569; border-color: #94a3b8; }
.medal-3 { background: #fef2f2; color: #92400e; border-color: #f97316; }
.podio-name  { font-size: 8px; font-weight: 700; color: #1e293b; max-width: 90px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.podio-prom  { font-size: 11px; font-weight: 800; }
.podio-1 .podio-prom { color: #d97706; }
.podio-2 .podio-prom { color: #64748b; }
.podio-3 .podio-prom { color: #f97316; }
.podio-bar { border-radius: 4px 4px 0 0; width: 60px; margin: 4px auto 0; }
.bar-1 { background: #fde68a; height: 40px; }
.bar-2 { background: #e2e8f0; height: 28px; }
.bar-3 { background: #fed7aa; height: 18px; }

/* Tabla */
table { width: 100%; border-collapse: collapse; }
thead tr { background: #92400e; color: #fff; }
thead th { padding: 5px 7px; font-size: 8px; text-align: center; border: 1px solid #92400e; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #fef9c3; }
tbody td { padding: 5px 7px; border: 1px solid #fde68a; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }
tbody td.rank-medal { font-weight: 800; }

.badge-ex    { background: #d1fae5; color: #065f46; }
.badge-muy-b { background: #dbeafe; color: #1e40af; }
.badge-b     { background: #ede9fe; color: #5b21b6; }
.badge-reg   { background: #f1f5f9; color: #475569; }
.badge-bajo  { background: #fee2e2; color: #991b1b; }

.nota-badge { padding: 2px 6px; border-radius: 10px; font-size: 7.5px; font-weight: 700; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 8px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
.firma-row { display: flex; gap: 30px; margin-top: 20px; }
.firma-box { flex: 1; text-align: center; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 7.5px; color: #475569; margin-top: 24px; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="sub">{{ $boletinConfig?->director ? 'Director/a: ' . $boletinConfig->director : '' }}</div>
    <div class="titulo">CUADRO DE HONOR — RANKING ACADÉMICO</div>
    <div class="subtitulo">
        {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }}
        &nbsp;·&nbsp; Año Escolar: {{ $schoolYear?->nombre ?? '—' }}
        @if($periodo) &nbsp;·&nbsp; Período: {{ $periodo->nombre }} @endif
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

<div class="stars">★ ★ ★</div>

<div class="meta">
    <div><strong>Grupo:</strong> {{ $grupo->nombre_completo ?? $grupo->grado->nombre . ' ' . $grupo->seccion->nombre }}</div>
    <div><strong>Total estudiantes:</strong> {{ $ranking->count() }}</div>
    @if($periodo)
    <div><strong>Período:</strong> {{ $periodo->nombre }}</div>
    @else
    <div><strong>Calificación:</strong> Promedio general del año</div>
    @endif
</div>

{{-- Podio top 3 --}}
@if($ranking->count() >= 3)
@php
    $top1 = $ranking[0] ?? null;
    $top2 = $ranking[1] ?? null;
    $top3 = $ranking[2] ?? null;
    $nombreCorto = fn($est) => mb_substr(($est->nombres ?? $est->nombre ?? ''), 0, 12) . ' ' . mb_substr(($est->apellidos ?? $est->apellido ?? ''), 0, 10);
@endphp
<div class="podio">
    {{-- 2do lugar --}}
    @if($top2)
    <div class="podio-box podio-2">
        <div class="podio-medal medal-2">2</div>
        <div class="podio-name">{{ $nombreCorto($top2['estudiante']) }}</div>
        <div class="podio-prom">{{ $top2['promedio'] ?? '—' }}</div>
        <div class="podio-bar bar-2"></div>
    </div>
    @endif
    {{-- 1er lugar --}}
    @if($top1)
    <div class="podio-box podio-1">
        <div class="podio-medal medal-1">1</div>
        <div class="podio-name">{{ $nombreCorto($top1['estudiante']) }}</div>
        <div class="podio-prom">{{ $top1['promedio'] ?? '—' }}</div>
        <div class="podio-bar bar-1"></div>
    </div>
    @endif
    {{-- 3er lugar --}}
    @if($top3)
    <div class="podio-box podio-3">
        <div class="podio-medal medal-3">3</div>
        <div class="podio-name">{{ $nombreCorto($top3['estudiante']) }}</div>
        <div class="podio-prom">{{ $top3['promedio'] ?? '—' }}</div>
        <div class="podio-bar bar-3"></div>
    </div>
    @endif
</div>
@endif

{{-- Tabla completa --}}
<table>
    <thead>
        <tr>
            <th style="width:28px;">Pos.</th>
            <th class="left">Estudiante</th>
            <th style="width:70px;">Promedio</th>
            <th style="width:55px;">Materias</th>
            <th style="width:80px;">Clasificación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ranking as $i => $row)
        @php
            $prom = $row['promedio'];
            $pos  = $i + 1;
            if ($prom === null) { $cls = 'badge-reg'; $lbl = '—'; }
            elseif ($prom >= 90) { $cls = 'badge-ex';    $lbl = 'Excelente'; }
            elseif ($prom >= 80) { $cls = 'badge-muy-b'; $lbl = 'Muy Bueno'; }
            elseif ($prom >= 70) { $cls = 'badge-b';     $lbl = 'Bueno'; }
            elseif ($prom >= 60) { $cls = 'badge-reg';   $lbl = 'Regular'; }
            else                 { $cls = 'badge-bajo';  $lbl = 'Bajo'; }

            $medallas = [1=>'🥇', 2=>'🥈', 3=>'🥉'];
        @endphp
        <tr>
            <td class="rank-medal" style="color:{{ $pos <= 3 ? '#d97706' : '#6b7280' }};">
                {{ $pos <= 3 ? ($medallas[$pos] ?? $pos) : $pos }}
            </td>
            <td class="left" style="font-weight:{{ $pos <= 3 ? '700' : '400' }};">
                {{ $row['estudiante']?->nombre_completo ?? (($row['estudiante']?->nombres ?? '') . ' ' . ($row['estudiante']?->apellidos ?? '')) }}
            </td>
            <td style="font-weight:800;color:{{ $prom !== null ? ($prom >= 70 ? '#15803d' : '#dc2626') : '#94a3b8' }};">
                {{ $prom !== null ? number_format($prom, 2) : '—' }}
            </td>
            <td style="color:#6b7280;">{{ $row['materias'] }}</td>
            <td>
                @if($prom !== null)
                <span class="nota-badge {{ $cls }}">{{ $lbl }}</span>
                @else
                <span style="color:#94a3b8;font-size:8px;">Sin notas</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="firma-row">
    <div class="firma-box">Director/a del Centro</div>
    <div class="firma-box">Encargado/a de Docencia</div>
    <div class="firma-box">Sello del Centro</div>
</div>

<div class="footer">
    <span>{{ $inst }} — Sistema SGE | Cuadro de Honor {{ now()->format('Y') }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
