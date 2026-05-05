<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #7c3aed; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #7c3aed; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.chips { display: flex; gap: 10px; margin-bottom: 12px; }
.chip { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.chip .num { font-size: 16px; font-weight: 800; }
.chip .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
.c-total  { background: #f5f3ff; } .c-total .num { color: #7c3aed; }
.c-pub    { background: #dcfce7; } .c-pub .num   { color: #15803d; }
.c-nopub  { background: #fef9c3; } .c-nopub .num { color: #92400e; }
.c-notas  { background: #fee2e2; } .c-notas .num { color: #dc2626; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #7c3aed; color: #fff; }
thead th { padding: 5px 6px; font-size: 8px; border: 1px solid #6d28d9; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #f5f3ff; }
tbody td { padding: 5px 6px; border: 1px solid #e0d9f7; font-size: 8.5px; text-align: center; }
tbody td.left { text-align: left; }

.badge { padding: 2px 6px; border-radius: 10px; font-weight: 700; font-size: 7.5px; }
.b-notas  { background: #fee2e2; color: #991b1b; }
.b-nopub  { background: #fef9c3; color: #92400e; }

.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">INFORME DE REZAGADOS — CALIFICACIONES PENDIENTES</div>
    <div class="sub">
        Año Escolar: {{ $schoolYear->nombre }}
        @if($periodo) &nbsp;·&nbsp; Período: {{ $periodo->nombre }} @endif
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<div class="chips">
    <div class="chip c-total">
        <div class="num">{{ $resumen['total'] }}</div>
        <div class="lbl">Total Asignaciones</div>
    </div>
    <div class="chip c-pub">
        <div class="num">{{ $resumen['publicados'] }}</div>
        <div class="lbl">Publicadas</div>
    </div>
    <div class="chip c-nopub">
        <div class="num">{{ $resumen['sin_publicar'] }}</div>
        <div class="lbl">Sin Publicar</div>
    </div>
    <div class="chip c-notas">
        <div class="num">{{ $resumen['sin_notas'] }}</div>
        <div class="lbl">Sin Notas</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th class="left" style="width:140px;">Docente</th>
            <th class="left" style="width:120px;">Asignatura</th>
            <th style="width:90px;">Grupo</th>
            <th style="width:55px;">Área</th>
            <th style="width:80px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rezagados as $i => $item)
        @php $asig = $item['asignacion']; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="left">{{ $asig->docente?->nombre_completo ?? 'Sin docente' }}</td>
            <td class="left">{{ $asig->asignatura?->nombre ?? '—' }}</td>
            <td>{{ ($asig->grupo?->grado->nombre ?? '') . ' ' . ($asig->grupo?->seccion->nombre ?? '') }}</td>
            <td style="text-transform:capitalize;">{{ $asig->area }}</td>
            <td>
                @if($item['estado'] === 'sin_notas')
                    <span class="badge b-notas">Sin notas</span>
                @else
                    <span class="badge b-nopub">Sin publicar</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;color:#94a3b8;font-style:italic;padding:10px;">
                ¡Todos los docentes están al día con sus calificaciones!
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — Informe de Rezagados</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
