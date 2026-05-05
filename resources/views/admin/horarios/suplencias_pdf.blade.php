<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #1e40af; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.resumen { display: flex; gap: 10px; margin-bottom: 12px; }
.chip { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.chip .num { font-size: 16px; font-weight: 800; }
.chip .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
.chip-total   { background: #eff6ff; } .chip-total .num   { color: #1d4ed8; }
.chip-cubierta{ background: #dcfce7; } .chip-cubierta .num{ color: #15803d; }
.chip-sin     { background: #fee2e2; } .chip-sin .num     { color: #dc2626; }
.chip-pend    { background: #fef9c3; } .chip-pend .num    { color: #92400e; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #1e40af; color: #fff; }
thead th { padding: 5px 6px; font-size: 8px; border: 1px solid #1e3a8a; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f0f7ff; }
tbody td { padding: 5px 6px; border: 1px solid #bfdbfe; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.badge { padding: 2px 6px; border-radius: 10px; font-weight: 700; font-size: 7.5px; }
.b-cubierta  { background: #dcfce7; color: #065f46; }
.b-pendiente { background: #fef9c3; color: #92400e; }
.b-sin       { background: #fee2e2; color: #991b1b; }
.b-cancelada { background: #f3f4f6; color: #6b7280; }

.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">REPORTE DE SUPLENCIAS DOCENTES</div>
    <div class="sub">
        Año Escolar: {{ $sy?->nombre ?? '—' }}
        &nbsp;·&nbsp; Total registros: {{ $suplencias->count() }}
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

@php
    $cubierta  = $suplencias->where('estado', 'cubierta')->count();
    $sinCubrir = $suplencias->where('estado', 'sin_cubrir')->count();
    $pendiente = $suplencias->where('estado', 'pendiente')->count();
    $cancelada = $suplencias->where('estado', 'cancelada')->count();
@endphp
<div class="resumen">
    <div class="chip chip-total">
        <div class="num">{{ $suplencias->count() }}</div>
        <div class="lbl">Total</div>
    </div>
    <div class="chip chip-cubierta">
        <div class="num">{{ $cubierta }}</div>
        <div class="lbl">Cubierta</div>
    </div>
    <div class="chip chip-pend">
        <div class="num">{{ $pendiente }}</div>
        <div class="lbl">Pendiente</div>
    </div>
    <div class="chip chip-sin">
        <div class="num">{{ $sinCubrir }}</div>
        <div class="lbl">Sin Cubrir</div>
    </div>
    <div class="chip" style="background:#f8fafc;">
        <div class="num" style="color:#6b7280;">{{ $cancelada }}</div>
        <div class="lbl">Cancelada</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th style="width:65px;">Fecha</th>
            <th class="left" style="width:120px;">Docente Original</th>
            <th class="left" style="width:120px;">Docente Suplente</th>
            <th class="left" style="width:110px;">Asignatura</th>
            <th style="width:90px;">Grupo</th>
            <th style="width:70px;">Estado</th>
            <th class="left">Motivo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($suplencias as $i => $s)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $s->fecha ? \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') : '—' }}</td>
            <td class="left">{{ $s->docenteOriginal?->nombre_completo ?? '—' }}</td>
            <td class="left" style="{{ !$s->docenteSuplente ? 'color:#94a3b8;font-style:italic;' : '' }}">
                {{ $s->docenteSuplente?->nombre_completo ?? 'Sin asignar' }}
            </td>
            <td class="left">{{ $s->detalle?->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>
                @php $g = $s->detalle?->asignacion?->grupo; @endphp
                {{ $g ? ($g->grado->nombre ?? '') . ' ' . ($g->seccion->nombre ?? '') : '—' }}
            </td>
            <td>
                @php
                    $estado = $s->estado ?? 'pendiente';
                    $cls = match($estado) {
                        'cubierta'   => 'b-cubierta',
                        'sin_cubrir' => 'b-sin',
                        'cancelada'  => 'b-cancelada',
                        default      => 'b-pendiente',
                    };
                @endphp
                <span class="badge {{ $cls }}">{{ ucfirst($estado) }}</span>
            </td>
            <td class="left" style="font-size:8px;color:#6b7280;">{{ $s->motivo ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;color:#94a3b8;font-style:italic;padding:12px;">
                Sin registros de suplencias.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — Reporte de Suplencias</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
