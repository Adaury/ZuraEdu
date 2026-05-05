<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
    .header { text-align:center; margin-bottom:10px; border-bottom:2px solid #1e3a6e; padding-bottom:7px; }
    .header h2 { font-size:12px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:8px; color:#64748b; margin-top:2px; }
    .sy-block { margin-bottom:12px; }
    .sy-title { background:#1e3a6e; color:#fff; padding:4px 6px; font-size:9px; font-weight:700; margin-bottom:0; }
    table { width:100%; border-collapse:collapse; }
    th { background:#334e8a; color:#fff; padding:4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    tr.alt { background:#f0f6ff; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-act  { background:#d1fae5; color:#065f46; }
    .badge-ina  { background:#f3f4f6; color:#6b7280; }
    .badge-cerr { background:#fee2e2; color:#991b1b; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Períodos Académicos</h2>
    <p>Total años: {{ $schoolYears->count() }} &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

@foreach($schoolYears as $sy)
<div class="sy-block">
    <div class="sy-title">{{ $sy->nombre }} @if($sy->activo) — Activo @endif</div>
    @if($sy->periodos->isEmpty())
        <p style="padding:6px 4px;font-size:8px;color:#94a3b8;font-style:italic;">Sin períodos registrados.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Número</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sy->periodos as $i => $periodo)
            <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $periodo->nombre }}</td>
                <td>P{{ $periodo->numero }}</td>
                <td>{{ $periodo->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $periodo->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    @if($periodo->cerrado)
                        <span class="badge badge-cerr">Cerrado</span>
                    @elseif($periodo->activo)
                        <span class="badge badge-act">Activo</span>
                    @else
                        <span class="badge badge-ina">Inactivo</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endforeach

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
