<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family: DejaVu Sans, sans-serif; font-size:8px; color:#1e293b; }
@page { size:letter portrait; margin:.9cm 1.1cm; }

.header { text-align:center; margin-bottom:12px; border-bottom:2px solid #1e3a6e; padding-bottom:7px; }
.header .inst  { font-size:12px; font-weight:bold; color:#1e3a6e; text-transform:uppercase; }
.header .titulo{ font-size:10px; font-weight:bold; color:#0f172a; margin-top:4px; }
.header .sub   { font-size:7.5px; color:#6b7280; margin-top:3px; }

table { width:100%; border-collapse:collapse; }
thead tr th { background:#1e3a6e; color:#fff; font-size:7.5px; font-weight:700; padding:4px 5px; text-align:left; }
tbody tr td  { padding:5px; font-size:7.5px; border-bottom:1px solid #f0f4f8; vertical-align:top; }
tbody tr:nth-child(even) { background:#f8faff; }

.badge { display:inline-block; padding:1px 5px; border-radius:8px; font-size:6.5px; font-weight:700; }
.badge-felicitacion { background:#d1fae5; color:#065f46; }
.badge-llamada_atencion { background:#fee2e2; color:#991b1b; }
.badge-inasistencia { background:#fef3c7; color:#92400e; }
.badge-conducta { background:#ede9fe; color:#5b21b6; }
.badge-academica { background:#dbeafe; color:#1d4ed8; }
.badge-otro { background:#f1f5f9; color:#475569; }
.badge-privada { background:#fee2e2; color:#991b1b; }

.footer { margin-top:10px; border-top:1px solid #e2e8f0; padding-top:5px;
          display:table; width:100%; font-size:7px; color:#94a3b8; }
.footer-l { display:table-cell; }
.footer-r { display:table-cell; text-align:right; }
</style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $inst }}</div>
    <div class="titulo">REPORTE DE OBSERVACIONES ESTUDIANTILES</div>
    <div class="sub">
        @if($schoolYear) Año Escolar: {{ $schoolYear->nombre }} — @endif
        Total: {{ $obs->count() }} observación(es) — Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:16px;">#</th>
            <th style="width:55px;">Fecha</th>
            <th style="width:110px;">Estudiante</th>
            <th style="width:90px;">Docente</th>
            <th style="width:80px;">Asignatura</th>
            <th style="width:55px;">Tipo</th>
            <th>Observación</th>
            <th style="width:30px;">Privada</th>
        </tr>
    </thead>
    <tbody>
        @forelse($obs as $i => $o)
        @php
            $tipo = $o->tipo ?? 'otro';
            $cls  = 'badge-' . str_replace(' ', '_', $tipo);
            $info = $o->tipo_info ?? ['label' => ucfirst($tipo), 'color' => '#6b7280'];
        @endphp
        <tr>
            <td style="text-align:center;color:#9ca3af;">{{ $i + 1 }}</td>
            <td>{{ $o->created_at?->format('d/m/Y') }}</td>
            <td style="font-weight:600;">{{ $o->estudiante?->nombre_completo ?? '—' }}</td>
            <td style="font-size:7px;">{{ $o->docente?->nombre_completo ?? '—' }}</td>
            <td style="font-size:7px;">{{ $o->asignacion?->asignatura?->nombre ?? '—' }}</td>
            <td>
                <span class="badge {{ $cls }}">{{ $info['label'] ?? ucfirst($tipo) }}</span>
            </td>
            <td style="font-size:7px;color:#374151;line-height:1.45;">{{ $o->texto }}</td>
            <td style="text-align:center;">
                @if($o->privada)
                <span class="badge badge-privada">Sí</span>
                @else
                <span style="color:#9ca3af;font-size:7px;">No</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;padding:1rem;color:#9ca3af;">Sin observaciones registradas.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <div class="footer-l">{{ $inst }} — Observaciones Estudiantiles</div>
    <div class="footer-r">{{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
