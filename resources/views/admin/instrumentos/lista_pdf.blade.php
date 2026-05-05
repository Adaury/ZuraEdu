<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:9px; color:#1e293b; }
    .header { text-align:center; margin-bottom:12px; border-bottom:2px solid #1e3a6e; padding-bottom:8px; }
    .header h2 { font-size:13px; color:#1e3a6e; font-weight:700; }
    .header p  { font-size:8px; color:#64748b; margin-top:2px; }
    table { width:100%; border-collapse:collapse; }
    th { background:#1e3a6e; color:#fff; padding:5px 4px; font-size:8px; text-align:left; }
    td { padding:4px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    tr.alt { background:#f0f6ff; }
    .badge { padding:1px 6px; border-radius:4px; font-size:7px; font-weight:600; }
    .badge-lista  { background:#dbeafe; color:#1e40af; }
    .badge-rubrica { background:#ede9fe; color:#6d28d9; }
    .badge-escala  { background:#d1fae5; color:#065f46; }
    .badge-pub { background:#d1fae5; color:#065f46; }
    .badge-nopub { background:#f3f4f6; color:#6b7280; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Instrumentos de Evaluación</h2>
    <p>Año Escolar: {{ $schoolYear?->nombre ?? '—' }} &nbsp;|&nbsp; Total: {{ $instrumentos->count() }} instrumentos &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Asignatura</th>
            <th>Grupo</th>
            <th>Docente</th>
            <th>Estado</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @php $tipos = \App\Models\InstrumentoEvaluacion::$tiposLabels; @endphp
        @foreach($instrumentos as $i => $instr)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $instr->titulo }}</td>
            <td>
                @php $tipo = $instr->tipo; @endphp
                @if($tipo === 'lista_cotejo')
                    <span class="badge badge-lista">Lista Cotejo</span>
                @elseif($tipo === 'rubrica')
                    <span class="badge badge-rubrica">Rúbrica</span>
                @else
                    <span class="badge badge-escala">Escala</span>
                @endif
            </td>
            <td>{{ $instr->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>{{ $instr->asignacion?->grupo?->nombre ?? '—' }}</td>
            <td>{{ $instr->docente?->apellidos ?? '—' }}, {{ $instr->docente?->nombres ?? '' }}</td>
            <td>
                @if($instr->publicado)
                    <span class="badge badge-pub">Publicado</span>
                @else
                    <span class="badge badge-nopub">Borrador</span>
                @endif
            </td>
            <td>{{ $instr->created_at?->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
