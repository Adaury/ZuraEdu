<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ranking Gamificación</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

    .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; padding: 18px 24px; border-radius: 10px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; font-weight: 700; }
    .header p  { font-size: 10px; opacity: .85; margin-top: 3px; }
    .inst      { font-size: 10px; opacity: .9; margin-top: 6px; }

    .chips { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .chip  { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; font-size: 10px; color: #475569; }
    .chip strong { display: block; font-size: 15px; color: #1e293b; font-weight: 800; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead th { background: #6366f1; color: #fff; padding: 8px 10px; text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: .04em; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr:first-child td { font-weight: 700; background: #fef9c3; }
    tbody tr:nth-child(2) td { font-weight: 700; background: #f1f5f9; }
    tbody tr:nth-child(3) td { font-weight: 600; background: #fff7ed; }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

    .pos    { font-size: 14px; text-align: center; }
    .badge  { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 700; }
    .b-indigo  { background: #e0e7ff; color: #4338ca; }
    .b-pink    { background: #fce7f3; color: #be185d; }
    .b-purple  { background: #ede9fe; color: #7c3aed; }
    .b-gray    { background: #f1f5f9; color: #64748b; }

    .insignia-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 2px; background: #f59e0b; }

    .footer { margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>

<div class="header">
    <h1>🎮 Ranking de Gamificación</h1>
    @if($grupo)
    <p>{{ $grupo->grado?->nombre }} {{ $grupo->seccion?->nombre }} — {{ $grupo->nombre }}</p>
    @endif
    @if($config)
    <p class="inst">{{ $config->nombre_centro ?? '' }}</p>
    @endif
    @if($schoolYear)
    <p class="inst">Año Escolar {{ $schoolYear->nombre ?? $schoolYear->anio_inicio.'-'.$schoolYear->anio_fin }}</p>
    @endif
</div>

{{-- Chips resumen --}}
<div class="chips">
    <div class="chip">
        <strong>{{ $ranking->count() }}</strong>
        Estudiantes
    </div>
    <div class="chip">
        <strong>{{ number_format($ranking->sum('total')) }}</strong>
        Total Puntos
    </div>
    <div class="chip">
        <strong>{{ number_format($ranking->sum('insignias')) }}</strong>
        Insignias
    </div>
    @if($ranking->isNotEmpty())
    <div class="chip">
        <strong>{{ number_format($ranking->first()['total']) }}</strong>
        Líder ({{ $ranking->first()['matricula']->estudiante?->apellidos ?? '' }})
    </div>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th style="width:40px;text-align:center;">#</th>
            <th>Estudiante</th>
            <th style="width:90px;text-align:center;">Puntos</th>
            <th style="width:80px;text-align:center;">Insignias</th>
            <th style="width:110px;text-align:center;">Nivel</th>
        </tr>
    </thead>
    <tbody>
    @forelse($ranking as $pos => $item)
    @php
        $medallas = ['🥇','🥈','🥉'];
        $m = $medallas[$pos] ?? ($pos + 1);
        $total = $item['total'];
        if ($total >= 500) {
            $nivel = 'Diamante'; $clase = 'b-pink';
        } elseif ($total >= 200) {
            $nivel = 'Platino'; $clase = 'b-purple';
        } elseif ($total >= 100) {
            $nivel = 'Oro'; $clase = 'b-indigo';
        } elseif ($total > 0) {
            $nivel = 'Plata'; $clase = 'b-indigo';
        } else {
            $nivel = 'Sin puntos'; $clase = 'b-gray';
        }
    @endphp
    <tr>
        <td class="pos">{{ $m }}</td>
        <td>
            <strong>{{ $item['matricula']->estudiante?->apellidos }}</strong>,
            {{ $item['matricula']->estudiante?->nombres }}
        </td>
        <td style="text-align:center;font-weight:700;color:#6366f1;font-size:12px;">
            {{ number_format($total) }}
        </td>
        <td style="text-align:center;">
            @if($item['insignias'] > 0)
            @for($i = 0; $i < min($item['insignias'], 5); $i++)
            <span class="insignia-dot"></span>
            @endfor
            {{ $item['insignias'] }}
            @else
            <span style="color:#cbd5e1;">—</span>
            @endif
        </td>
        <td style="text-align:center;">
            <span class="badge {{ $clase }}">{{ $nivel }}</span>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="5" style="text-align:center;padding:20px;color:#94a3b8;">
            Sin datos de ranking para este grupo.
        </td>
    </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    <span>Generado: {{ now()->format('d/m/Y H:i') }}</span>
    <span>ZuraEdu — Sistema de Gestión Escolar</span>
</div>

</body>
</html>
