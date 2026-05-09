<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Préstamos — Biblioteca</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1e293b; }

    .header { background: linear-gradient(135deg, #1d4ed8, #3b82f6); color: #fff; padding: 16px 22px; border-radius: 8px; margin-bottom: 14px; }
    .header h1 { font-size: 17px; font-weight: 700; }
    .header p  { font-size: 10px; opacity: .85; margin-top: 3px; }

    .chips { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .chip  { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; font-size: 10px; color: #475569; }
    .chip strong { display: block; font-size: 15px; color: #1e293b; font-weight: 800; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead th { background: #1d4ed8; color: #fff; padding: 7px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 9px; font-weight: 700; }
    .b-activo   { background: #dbeafe; color: #1d4ed8; }
    .b-vencido  { background: #fee2e2; color: #dc2626; }
    .b-devuelto { background: #d1fae5; color: #065f46; }
    .text-danger { color: #dc2626; font-weight: 700; }

    .footer { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>

<div class="header">
    <h1>📚 Reporte de Préstamos — Biblioteca Escolar</h1>
    @if($config)
    <p>{{ $config->nombre_centro ?? '' }}</p>
    @endif
    <p>Generado: {{ now()->format('d/m/Y H:i') }}
       @if($estado !== 'todos') — Filtro: {{ ucfirst($estado) }} @endif
    </p>
</div>

<div class="chips">
    <div class="chip">
        <strong>{{ $prestamos->count() }}</strong>
        Total Registros
    </div>
    <div class="chip">
        <strong>{{ $totalActivos }}</strong>
        Activos
    </div>
    <div class="chip">
        <strong>{{ $totalVencidos }}</strong>
        Vencidos
    </div>
    <div class="chip">
        <strong>{{ $totalDevueltos }}</strong>
        Devueltos
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:25%;">Estudiante</th>
            <th style="width:30%;">Libro</th>
            <th style="width:12%;text-align:center;">Préstamo</th>
            <th style="width:12%;text-align:center;">Vencimiento</th>
            <th style="width:12%;text-align:center;">Devolución</th>
            <th style="width:9%;text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @forelse($prestamos as $prestamo)
    <tr>
        <td><strong>{{ $prestamo->estudiante?->apellidos }}</strong>, {{ $prestamo->estudiante?->nombres }}</td>
        <td>
            {{ $prestamo->libro?->titulo ?? '—' }}<br>
            <span style="color:#64748b;">{{ $prestamo->libro?->autor }}</span>
        </td>
        <td style="text-align:center;">{{ $prestamo->fecha_prestamo?->format('d/m/Y') }}</td>
        <td style="text-align:center;" class="{{ $prestamo->estado !== 'devuelto' && $prestamo->fecha_vencimiento < now() ? 'text-danger' : '' }}">
            {{ $prestamo->fecha_vencimiento?->format('d/m/Y') }}
        </td>
        <td style="text-align:center;">{{ $prestamo->fecha_devolucion?->format('d/m/Y') ?? '—' }}</td>
        <td style="text-align:center;">
            <span class="badge b-{{ $prestamo->estado }}">{{ ucfirst($prestamo->estado) }}</span>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">Sin préstamos registrados.</td>
    </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    <span>ZuraEdu — Sistema de Gestión Escolar</span>
    <span>Página 1</span>
</div>

</body>
</html>
