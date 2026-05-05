<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #0f766e; padding-bottom: 12px; }
.header .inst  { font-size: 13px; font-weight: bold; color: #0f766e; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8.5px; color: #6b7280; margin-top: 3px; }

.resumen { display: flex; gap: 10px; margin-bottom: 14px; }
.res-chip { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.res-chip .num { font-size: 16px; font-weight: 800; color: #0f766e; }
.res-chip .lbl { font-size: 7.5px; color: #6b7280; margin-top: 2px; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #0f766e; color: #fff; }
thead th { padding: 5px 7px; font-size: 8px; border: 1px solid #0f5f5a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0fdf4; }
tbody td { padding: 5px 7px; border: 1px solid #d1fae5; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.tipo-badge { padding: 2px 7px; border-radius: 10px; font-weight: 700; font-size: 7.5px; }
.aplica-badge { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 10px; font-size: 7.5px; }

.color-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; vertical-align: middle; margin-right: 4px; }

.footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">CALENDARIO ACADÉMICO INSTITUCIONAL</div>
    <div class="sub">
        Año Escolar: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; Total eventos: {{ $eventos->count() }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y') }}
    </div>
</div>

@php
$proximos = $eventos->filter(fn($e) => $e->fecha_inicio >= now()->toDateString())->count();
$pasados  = $eventos->filter(fn($e) => $e->fecha_inicio < now()->toDateString())->count();
$tiposCount = $eventos->groupBy('tipo')->map->count();
@endphp

<div class="resumen">
    <div class="res-chip">
        <div class="num">{{ $eventos->count() }}</div>
        <div class="lbl">Total Eventos</div>
    </div>
    <div class="res-chip">
        <div class="num" style="color:#15803d;">{{ $proximos }}</div>
        <div class="lbl">Próximos</div>
    </div>
    <div class="res-chip">
        <div class="num" style="color:#6b7280;">{{ $pasados }}</div>
        <div class="lbl">Realizados</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th class="left" style="width:180px;">Evento</th>
            <th style="width:75px;">Inicio</th>
            <th style="width:75px;">Fin</th>
            <th style="width:75px;">Tipo</th>
            <th style="width:90px;">Aplica a</th>
            <th class="left">Descripción</th>
        </tr>
    </thead>
    <tbody>
        @forelse($eventos as $i => $ev)
        @php
            $esPasado = $ev->fecha_inicio < now()->toDateString();
        @endphp
        <tr style="{{ $esPasado ? 'opacity:.65;' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td class="left">
                <span class="color-dot" style="background:{{ $ev->color ?? '#6b7280' }};"></span>
                <strong>{{ $ev->titulo }}</strong>
            </td>
            <td>{{ $ev->fecha_inicio ? \Carbon\Carbon::parse($ev->fecha_inicio)->format('d/m/Y') : '—' }}</td>
            <td>{{ $ev->fecha_fin ? \Carbon\Carbon::parse($ev->fecha_fin)->format('d/m/Y') : '—' }}</td>
            <td>
                <span class="tipo-badge" style="background:{{ $ev->color ?? '#e2e8f0' }}22;color:{{ $ev->color ?? '#6b7280' }};">
                    {{ \App\Models\CalendarioAcademico::tiposLabels()[$ev->tipo] ?? $ev->tipo }}
                </span>
            </td>
            <td><span class="aplica-badge">{{ ucfirst($ev->aplica_a ?? 'todos') }}</span></td>
            <td class="left" style="font-size:8px;color:#6b7280;">{{ $ev->descripcion ? \Illuminate\Support\Str::limit($ev->descripcion, 60) : '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#94a3b8;font-style:italic;padding:12px;">Sin eventos en el calendario.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — Calendario Académico {{ $schoolYear?->nombre }}</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
