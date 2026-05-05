<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1e293b; margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 12px; }
    .header .inst { font-size: 11pt; font-weight: bold; color: #1e3a6e; }
    .header .title { font-size: 9pt; color: #475569; margin-top: 2px; }
    .header .sub { font-size: 7.5pt; color: #64748b; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th { background: #1e3a6e; color: #fff; font-size: 7.5pt; padding: 4px 5px; text-align: left; }
    td { font-size: 7.5pt; padding: 3px 5px; border-bottom: 1px solid #e2e8f0; }
    tr.even td { background: #f0f6ff; }
    .badge { border-radius: 3px; padding: 1px 4px; font-size: 6.5pt; font-weight: bold; }
    .footer { margin-top: 10px; text-align: right; font-size: 7pt; color: #94a3b8; }
    .accion { max-width: 140px; overflow: hidden; }
    .detalles { max-width: 200px; overflow: hidden; word-break: break-all; }
</style>
</head>
<body>
<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="title">Log de Actividad del Sistema</div>
    <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }} — {{ $logs->count() }} registros (máx. 500)</div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha / Hora</th>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Módulo</th>
            <th>IP</th>
            <th>Detalles</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $i => $log)
        @php
            $accion = strtolower($log->accion ?? '');
            $color = match(true) {
                str_contains($accion, 'login')   => '#dcfce7',
                str_contains($accion, 'delete') || str_contains($accion, 'eliminar') => '#fee2e2',
                str_contains($accion, 'create') || str_contains($accion, 'crear')    => '#dbeafe',
                str_contains($accion, 'update') || str_contains($accion, 'editar')   => '#fef9c3',
                default => ($i % 2 === 1 ? '#f0f6ff' : '#ffffff'),
            };
        @endphp
        <tr style="background:{{ $color }};">
            <td>{{ $i + 1 }}</td>
            <td style="white-space:nowrap;">{{ $log->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $log->user?->name ?? 'Sistema' }}</td>
            <td class="accion">{{ $log->accion ?? '—' }}</td>
            <td>{{ $log->modulo ?? '—' }}</td>
            <td style="white-space:nowrap;">{{ $log->ip ?? '—' }}</td>
            <td class="detalles">{{ Str::limit($log->detalles ?? '', 60) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">{{ $inst }} — {{ now()->format('d/m/Y') }}</div>
</body>
</html>
