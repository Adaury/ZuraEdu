<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }

.header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
.header .inst  { font-size: 12px; font-weight: bold; color: #dc2626; text-transform: uppercase; }
.header .titulo{ font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 5px; }
.header .sub   { font-size: 8px; color: #6b7280; margin-top: 3px; }

.chips { display: flex; gap: 10px; margin-bottom: 12px; }
.chip { flex: 1; text-align: center; padding: 7px 5px; border-radius: 5px; border: 1px solid #e2e8f0; }
.chip .num { font-size: 16px; font-weight: 800; }
.chip .lbl { font-size: 7px; color: #6b7280; margin-top: 2px; }
.c-total { background: #fee2e2; } .c-total .num { color: #991b1b; }
.c-1     { background: #fef9c3; } .c-1 .num     { color: #92400e; }
.c-2     { background: #fef2f2; } .c-2 .num     { color: #dc2626; }
.c-3     { background: #7f1d1d; } .c-3 .num     { color: #fff; } .c-3 .lbl { color: #fca5a5; }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #dc2626; color: #fff; }
thead th { padding: 5px 6px; font-size: 8px; border: 1px solid #b91c1c; text-align: center; }
thead th.left { text-align: left; }
tbody tr:nth-child(even) { background: #fff5f5; }
tbody td { padding: 5px 6px; border: 1px solid #fca5a5; font-size: 8.5px; text-align: center; vertical-align: middle; }
tbody td.left { text-align: left; }

.mat-chip { display:inline-block; background:#fee2e2; color:#991b1b; border-radius:4px;
            padding:1px 5px; font-size:7.5px; font-weight:600; margin:1px; }
.nivel-alto   { background:#7f1d1d; color:#fff; font-weight:700; padding:2px 6px; border-radius:10px; font-size:7.5px; }
.nivel-medio  { background:#dc2626; color:#fff; font-weight:700; padding:2px 6px; border-radius:10px; font-size:7.5px; }
.nivel-bajo   { background:#fef9c3; color:#92400e; font-weight:700; padding:2px 6px; border-radius:10px; font-size:7.5px; }

.footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 7px;
          display: flex; justify-content: space-between; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">INFORME DE ESTUDIANTES EN RECUPERACIÓN</div>
    <div class="sub">
        Año Escolar: {{ $schoolYear->nombre }}
        @if($grupo) &nbsp;·&nbsp; Grupo: {{ $grupo->grado->nombre ?? '' }} {{ $grupo->seccion->nombre ?? '' }} @endif
        &nbsp;·&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<div class="chips">
    <div class="chip c-total">
        <div class="num">{{ $resumen['total_reprobados'] }}</div>
        <div class="lbl">Total en Recuperación</div>
    </div>
    <div class="chip c-1">
        <div class="num">{{ $resumen['reprueba_1'] }}</div>
        <div class="lbl">1 Materia</div>
    </div>
    <div class="chip c-2">
        <div class="num">{{ $resumen['reprueba_2'] }}</div>
        <div class="lbl">2 Materias</div>
    </div>
    <div class="chip c-3">
        <div class="num">{{ $resumen['reprueba_3plus'] }}</div>
        <div class="lbl">3 o más</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th class="left" style="width:140px;">Estudiante</th>
            <th style="width:90px;">Grupo</th>
            <th class="left">Materias Reprobadas</th>
            <th style="width:45px;">Total</th>
            <th style="width:60px;">Nota Mín.</th>
            <th style="width:60px;">Nivel</th>
        </tr>
    </thead>
    <tbody>
        @forelse($estudiantesRiesgo as $i => $item)
        @php
            $nivel = $item['total_repr'] >= 3 ? 'alto' : ($item['total_repr'] >= 2 ? 'medio' : 'bajo');
            $grp   = $item['matricula']?->grupo;
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td class="left" style="font-weight:{{ $nivel === 'alto' ? '700' : '400' }};">
                {{ $item['estudiante']?->nombre_completo ?? ($item['estudiante']?->nombres . ' ' . $item['estudiante']?->apellidos) }}
            </td>
            <td>{{ $grp ? ($grp->grado->nombre ?? '') . ' ' . ($grp->seccion->nombre ?? '') : '—' }}</td>
            <td class="left">
                @foreach($item['materias'] as $mat)
                <span class="mat-chip">{{ $mat }}</span>
                @endforeach
            </td>
            <td style="font-weight:800;color:#dc2626;">{{ $item['total_repr'] }}</td>
            <td style="color:{{ ($item['nota_minima'] ?? 100) < 60 ? '#dc2626' : '#d97706' }};font-weight:700;">
                {{ $item['nota_minima'] !== null ? number_format($item['nota_minima'], 1) : '—' }}
            </td>
            <td>
                <span class="nivel-{{ $nivel }}">{{ ucfirst($nivel) }}</span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#94a3b8;font-style:italic;padding:12px;">
                ¡Sin estudiantes en recuperación!
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>{{ $inst }} — Informe de Recuperaciones</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
