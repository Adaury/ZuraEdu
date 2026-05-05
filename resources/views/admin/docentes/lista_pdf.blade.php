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
    .badge-act { background:#d1fae5; color:#065f46; padding:1px 5px; border-radius:4px; font-size:7px; }
    .badge-ina { background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:4px; font-size:7px; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $inst }} — Lista de Docentes</h2>
    <p>Año Escolar: {{ $sy?->nombre ?? '—' }} &nbsp;|&nbsp; Total: {{ $docentes->count() }} docentes &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Apellidos</th>
            <th>Nombres</th>
            <th>Cédula</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Asignaturas</th>
            <th>Grupos</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($docentes as $i => $docente)
        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $docente->apellidos }}</td>
            <td>{{ $docente->nombres }}</td>
            <td>{{ $docente->cedula ?? '—' }}</td>
            <td>{{ $docente->telefono ?? '—' }}</td>
            <td>{{ $docente->user?->email ?? '—' }}</td>
            <td>
                @foreach($docente->asignaciones->unique('asignatura_id')->take(3) as $a)
                    {{ $a->asignatura?->nombre }}<br>
                @endforeach
                @if($docente->asignaciones->unique('asignatura_id')->count() > 3)
                    <em>+{{ $docente->asignaciones->unique('asignatura_id')->count() - 3 }} más</em>
                @endif
            </td>
            <td>
                @foreach($docente->asignaciones->unique('grupo_id')->take(3) as $a)
                    {{ $a->grupo?->nombre }}<br>
                @endforeach
                @if($docente->asignaciones->unique('grupo_id')->count() > 3)
                    <em>+{{ $docente->asignaciones->unique('grupo_id')->count() - 3 }} más</em>
                @endif
            </td>
            <td>
                @if($docente->activo)
                    <span class="badge-act">Activo</span>
                @else
                    <span class="badge-ina">Inactivo</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="footer">{{ config('app.name') }} &mdash; {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
