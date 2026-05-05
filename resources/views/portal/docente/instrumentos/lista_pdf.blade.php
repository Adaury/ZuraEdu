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
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:5px 4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    tr.alt { background:#f0f6ff; }
    .badge { padding:1px 5px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-lc  { background:#dbeafe; color:#1e40af; }
    .badge-rb  { background:#ede9fe; color:#5b21b6; }
    .badge-es  { background:#d1fae5; color:#065f46; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Instrumentos de Evaluación: {{ $asignacion->asignatura?->nombre }}</h2>
    <p>
        {{ $asignacion->grupo?->nombre_completo }}
        &nbsp;·&nbsp; Docente: {{ $docente->nombre_completo }}
        &nbsp;·&nbsp; Año: {{ $schoolYear?->nombre ?? '—' }}
        &nbsp;·&nbsp; {{ $instrumentos->count() }} instrumento(s) &nbsp;|&nbsp; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>

@if($instrumentos->isEmpty())
    <p style="text-align:center;padding:20px;color:#94a3b8;font-style:italic;">No hay instrumentos registrados.</p>
@else
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Criterios</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @foreach($instrumentos as $i => $inst_item)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $inst_item->titulo ?? '—' }}</td>
            <td>
                @if($inst_item->tipo === 'lista_cotejo')
                    <span class="badge badge-lc">Lista de Cotejo</span>
                @elseif($inst_item->tipo === 'rubrica')
                    <span class="badge badge-rb">Rúbrica</span>
                @else
                    <span class="badge badge-es">Escala</span>
                @endif
            </td>
            <td>{{ $inst_item->criterios->count() }}</td>
            <td>{{ $inst_item->created_at?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
