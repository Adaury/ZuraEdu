<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#1e3a6e; color:#fff; font-size:7pt; font-weight:bold;
               padding:4px 5px; text-align:center; border:1px solid #2a4f96; }
    thead th.left { text-align:left; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:3.5px 5px; font-size:7.5pt; border:1px solid #e5e7eb;
               text-align:center; vertical-align:middle; }
    tbody td.left { text-align:left; }
    .badge { display:inline-block; border-radius:20px; padding:.15rem .55rem; font-size:7pt; font-weight:700; }
    .badge-critica { background:#fee2e2; color:#991b1b; }
    .badge-alta    { background:#ffedd5; color:#9a3412; }
    .badge-media   { background:#fef9c3; color:#854d0e; }
    .badge-baja    { background:#f3f4f6; color:#374151; }
    .footer { margin-top:12px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Alertas del Sistema</h1>
    <p>Total: {{ $alertas->count() }} alerta(s) vigente(s) &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}</p>
</div>

@if($alertas->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">No hay alertas vigentes.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th class="left" style="min-width:120px;">Título</th>
            <th class="left" style="min-width:150px;">Mensaje</th>
            <th style="width:65px;">Tipo</th>
            <th style="width:55px;">Nivel</th>
            <th style="width:80px;">Destinatario</th>
            <th style="width:55px;">Fecha</th>
        </tr>
    </thead>
    <tbody>
    @foreach($alertas as $i => $alerta)
    @php
        $nivelClass = match($alerta->nivel ?? 'baja') {
            'critica' => 'badge-critica',
            'alta'    => 'badge-alta',
            'media'   => 'badge-media',
            default   => 'badge-baja',
        };
        $nivelLabel = match($alerta->nivel ?? 'baja') {
            'critica' => 'Crítica',
            'alta'    => 'Alta',
            'media'   => 'Media',
            default   => 'Baja',
        };
    @endphp
    <tr>
        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
        <td class="left" style="font-weight:600;">{{ $alerta->titulo ?? '—' }}</td>
        <td class="left">{{ \Illuminate\Support\Str::limit($alerta->mensaje ?? '', 80) }}</td>
        <td>{{ $alerta->tipo ?? '—' }}</td>
        <td><span class="badge {{ $nivelClass }}">{{ $nivelLabel }}</span></td>
        <td style="font-size:7pt;">{{ $alerta->destinatario?->name ?? ($alerta->destinatario_rol ?? '—') }}</td>
        <td>{{ $alerta->created_at?->format('d/m/Y') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Alertas del Sistema &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
</div>
</body>
</html>
