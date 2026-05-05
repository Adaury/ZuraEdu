<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: DejaVu Sans, sans-serif; font-size:8.5px; color:#1e293b; }
@page { size:letter portrait; margin:.9cm 1.1cm; }

.header { text-align:center; margin-bottom:12px; border-bottom:2px solid #1e3a6e; padding-bottom:7px; }
.header .inst  { font-size:12px; font-weight:bold; color:#1e3a6e; text-transform:uppercase; }
.header .titulo{ font-size:10px; font-weight:bold; color:#0f172a; margin-top:4px; }
.header .sub   { font-size:7.5px; color:#6b7280; margin-top:3px; }

table { width:100%; border-collapse:collapse; }
thead tr th { background:#1e3a6e; color:#fff; font-size:7.5px; font-weight:700; padding:4px 6px; text-align:left; }
tbody tr td { padding:5px 6px; font-size:7.5px; border-bottom:1px solid #f0f4f8; vertical-align:top; }
tbody tr:nth-child(even) { background:#f8faff; }

.titulo-com { font-weight:700; font-size:8px; color:#1e293b; }
.cuerpo-com { color:#374151; margin-top:2px; font-size:7px; line-height:1.5; }
.badge { display:inline-block; padding:1px 5px; border-radius:8px; font-size:6.5px; font-weight:700; }
.badge-todos      { background:#dbeafe; color:#1d4ed8; }
.badge-estudiantes{ background:#dcfce7; color:#166534; }
.badge-docentes   { background:#fef3c7; color:#92400e; }
.badge-padres     { background:#f3e8ff; color:#7c3aed; }

.footer { margin-top:10px; border-top:1px solid #e2e8f0; padding-top:6px;
          display:table; width:100%; font-size:7px; color:#94a3b8; }
.footer-l { display:table-cell; }
.footer-r { display:table-cell; text-align:right; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">LISTADO DE COMUNICADOS INSTITUCIONALES</div>
    <div class="sub">
        @if($sy) Año Escolar: {{ $sy->nombre }} — @endif
        Total: {{ $comunicados->count() }} comunicado(s) — Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th style="width:160px;">Título</th>
            <th>Contenido</th>
            <th style="width:55px;">Destinatarios</th>
            <th style="width:55px;">Fecha</th>
            <th style="width:65px;">Autor</th>
            <th style="width:40px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($comunicados as $i => $com)
        @php
            $tipo = $com->tipo_destinatarios ?? 'todos';
            $cls  = 'badge-' . $tipo;
        @endphp
        <tr>
            <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
            <td class="titulo-com">{{ $com->titulo }}</td>
            <td>
                <div class="cuerpo-com">{{ \Illuminate\Support\Str::limit($com->cuerpo, 120) }}</div>
            </td>
            <td style="text-align:center;">
                <span class="badge {{ $cls }}">{{ ucfirst($tipo) }}</span>
            </td>
            <td>{{ $com->published_at?->format('d/m/Y') }}</td>
            <td style="font-size:7px;">{{ $com->autor?->name ?? 'Admin.' }}</td>
            <td style="text-align:center;">
                @if($com->publicado ?? ($com->estado ?? '') === 'publicado' || $com->published_at?->isPast())
                <span style="color:#166534;font-weight:700;">Pub.</span>
                @else
                <span style="color:#92400e;font-weight:700;">Bor.</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:1rem;color:#9ca3af;">Sin comunicados registrados.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <div class="footer-l">{{ $inst }} — Listado de Comunicados</div>
    <div class="footer-r">{{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
