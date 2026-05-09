<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo de Biblioteca</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1e293b; }

    .header { background: linear-gradient(135deg, #7c3aed, #6366f1); color: #fff; padding: 16px 22px; border-radius: 8px; margin-bottom: 14px; }
    .header h1 { font-size: 17px; font-weight: 700; }
    .header p  { font-size: 10px; opacity: .85; margin-top: 3px; }

    .chips { display: flex; gap: 10px; margin-bottom: 14px; }
    .chip  { background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 8px; padding: 6px 12px; font-size: 10px; color: #5b21b6; }
    .chip strong { display: block; font-size: 15px; color: #1e293b; font-weight: 800; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead th { background: #7c3aed; color: #fff; padding: 7px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
    tbody tr:nth-child(even) { background: #faf5ff; }
    .cat-row td { background: #ede9fe; color: #5b21b6; font-weight: 700; font-size: 10px; padding: 5px 8px; }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #f3f0ff; vertical-align: middle; }

    .badge-disp  { display: inline-block; padding: 2px 6px; border-radius: 20px; font-size: 9px; font-weight: 700; }
    .ok  { background: #d1fae5; color: #065f46; }
    .low { background: #fef3c7; color: #92400e; }
    .out { background: #fee2e2; color: #dc2626; }

    .footer { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>

<div class="header">
    <h1>📚 Catálogo de Biblioteca Escolar</h1>
    @if($config)
    <p>{{ $config->nombre_centro ?? '' }}</p>
    @endif
    <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="chips">
    <div class="chip">
        <strong>{{ $totalLibros }}</strong>
        Títulos
    </div>
    <div class="chip">
        <strong>{{ $totalEjemplares }}</strong>
        Ejemplares
    </div>
    <div class="chip">
        <strong>{{ $totalDisponibles }}</strong>
        Disponibles
    </div>
    <div class="chip">
        <strong>{{ $totalEjemplares - $totalDisponibles }}</strong>
        Prestados
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:35%;">Título</th>
            <th style="width:25%;">Autor</th>
            <th style="width:15%;">ISBN</th>
            <th style="width:8%;text-align:center;">Total</th>
            <th style="width:8%;text-align:center;">Disp.</th>
            <th style="width:9%;text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
    @php $categoriaActual = null; @endphp
    @forelse($libros as $libro)
    @if($libro->categoria !== $categoriaActual)
    @php $categoriaActual = $libro->categoria; @endphp
    <tr class="cat-row">
        <td colspan="6">{{ $libro->categoria }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>{{ $libro->titulo }}</strong></td>
        <td>{{ $libro->autor }}</td>
        <td style="font-family:monospace;">{{ $libro->isbn ?? '—' }}</td>
        <td style="text-align:center;">{{ $libro->cantidad_total }}</td>
        <td style="text-align:center;font-weight:700;">{{ $libro->cantidad_disponible }}</td>
        <td style="text-align:center;">
            @if($libro->cantidad_disponible <= 0)
            <span class="badge-disp out">Agotado</span>
            @elseif($libro->cantidad_disponible <= 2)
            <span class="badge-disp low">Poco stock</span>
            @else
            <span class="badge-disp ok">Disponible</span>
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">Sin libros registrados.</td>
    </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    <span>ZuraEdu — Sistema de Gestión Escolar</span>
    <span>Generado: {{ now()->format('d/m/Y') }}</span>
</div>

</body>
</html>
