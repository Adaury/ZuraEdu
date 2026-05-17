<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:9pt; color:#1e293b; }

.header { display:table; width:100%; margin-bottom:14px; border-bottom:2px solid #3b82f6; padding-bottom:8px; }
.header-logo { display:table-cell; width:60px; vertical-align:middle; }
.header-logo img { max-width:52px; max-height:52px; }
.header-info { display:table-cell; vertical-align:middle; padding-left:10px; }
.inst-name { font-size:12pt; font-weight:700; color:#1e40af; }
.inst-sub  { font-size:8pt; color:#64748b; margin-top:2px; }
.header-right { display:table-cell; text-align:right; vertical-align:top; font-size:7.5pt; color:#64748b; white-space:nowrap; }

.doc-title { font-size:13pt; font-weight:800; color:#1e293b; margin-bottom:4px; }
.doc-meta  { font-size:8pt; color:#475569; margin-bottom:12px; }

.kpis { width:100%; border-collapse:collapse; margin-bottom:14px; }
.kpis td { width:20%; padding:6px 8px; border-radius:8px; text-align:center; }
.kpi-num   { font-size:15pt; font-weight:800; display:block; }
.kpi-label { font-size:7pt; font-weight:600; }

table.main { width:100%; border-collapse:collapse; font-size:8.5pt; }
table.main thead tr { background:#1e40af; color:#fff; }
table.main thead th { padding:5px 7px; text-align:left; font-weight:700; }
table.main tbody tr:nth-child(even) { background:#f8fafc; }
table.main tbody td { padding:5px 7px; border-bottom:1px solid #e2e8f0; vertical-align:top; }

.chip { display:inline-block; padding:1px 7px; border-radius:99px; font-size:7pt; font-weight:700; }
.chip-pendiente  { background:#fef3c7; color:#d97706; }
.chip-entregada  { background:#dbeafe; color:#2563eb; }
.chip-revisada   { background:#d1fae5; color:#059669; }

.nota-bar { width:100%; height:6px; background:#e2e8f0; border-radius:99px; margin-top:2px; }
.nota-bar-fill { height:100%; border-radius:99px; }

.footer { margin-top:18px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>

{{-- Cabecera institucional --}}
@php
    $logoUrl    = $tenant?->logo_url ?? null;
    $nombreInst = $tenant?->nombre_institucion ?? $tenant?->nombre ?? config('app.name', 'Institución');
@endphp
<div class="header">
    <div class="header-logo">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo">
        @else
            <div style="width:48px;height:48px;background:#1e40af;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <span style="color:#fff;font-size:18pt;font-weight:800;">{{ strtoupper(substr($nombreInst,0,1)) }}</span>
            </div>
        @endif
    </div>
    <div class="header-info">
        <div class="inst-name">{{ $nombreInst }}</div>
        <div class="inst-sub">Reporte de Entregas — {{ $asignacion->asignatura?->nombre ?? 'Asignatura' }}</div>
        <div class="inst-sub">{{ $asignacion->grupo?->grado?->nombre ?? '' }} {{ $asignacion->grupo?->seccion?->nombre ?? '' }} &mdash; {{ $asignacion->docente?->nombre_completo ?? '' }}</div>
    </div>
    <div class="header-right">
        Generado: {{ now()->format('d/m/Y H:i') }}<br>
        Año escolar: {{ $asignacion->schoolYear?->nombre ?? date('Y') }}
    </div>
</div>

{{-- Título del documento --}}
<div class="doc-title">
    @php $tipos = \App\Models\Tarea::TIPOS ?? []; @endphp
    {{ $tipos[$tarea->tipo] ?? ucfirst($tarea->tipo) }}: {{ $tarea->titulo }}
</div>
<div class="doc-meta">
    Fecha límite: {{ $tarea->fecha_limite->format('d/m/Y') }}
    @if($tarea->descripcion)
    &nbsp;&mdash;&nbsp;{{ Str::limit($tarea->descripcion, 120) }}
    @endif
    @if($tarea->puntos_valor)
    &nbsp;&mdash;&nbsp;Valor: {{ $tarea->puntos_valor }} pts
    @endif
</div>

{{-- KPIs --}}
<table class="kpis">
    <tr>
        <td style="background:#eff6ff;">
            <span class="kpi-num" style="color:#1d4ed8;">{{ $nTotal }}</span>
            <span class="kpi-label" style="color:#3b82f6;">Total</span>
        </td>
        <td style="background:#fef3c7;">
            <span class="kpi-num" style="color:#d97706;">{{ $nPendientes }}</span>
            <span class="kpi-label" style="color:#d97706;">Pendientes</span>
        </td>
        <td style="background:#dbeafe;">
            <span class="kpi-num" style="color:#2563eb;">{{ $nEntregadas }}</span>
            <span class="kpi-label" style="color:#2563eb;">Entregadas</span>
        </td>
        <td style="background:#d1fae5;">
            <span class="kpi-num" style="color:#059669;">{{ $nRevisadas }}</span>
            <span class="kpi-label" style="color:#059669;">Revisadas</span>
        </td>
        <td style="background:#ede9fe;">
            <span class="kpi-num" style="color:#7c3aed;">{{ $nConFeedback }}</span>
            <span class="kpi-label" style="color:#7c3aed;">Con Feedback</span>
        </td>
    </tr>
</table>

{{-- Tabla de estudiantes --}}
<table class="main">
    <thead>
        <tr>
            <th style="width:28px;">#</th>
            <th>Estudiante</th>
            <th style="width:70px;">Estado</th>
            @if($tarea->puntos_valor)
            <th style="width:70px;text-align:center;">Nota / {{ $tarea->puntos_valor }}</th>
            @endif
            <th style="width:70px;">F. Entrega</th>
            <th>Retroalimentación</th>
        </tr>
    </thead>
    <tbody>
    @php $i = 1; @endphp
    @foreach($matriculas as $m)
    @php
        $est     = $m->estudiante;
        $entrega = $est ? ($entregas->get($est->id) ?? null) : null;
        $estado  = $entrega?->estado ?? 'pendiente';
        $nota    = $entrega?->calificacion;
        $pct     = ($nota !== null && $tarea->puntos_valor)
                    ? min(100, round($nota / $tarea->puntos_valor * 100))
                    : null;
        $barColor = $pct === null ? '#94a3b8'
                  : ($pct >= 70 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444'));
    @endphp
    <tr>
        <td style="color:#94a3b8;font-size:7.5pt;">{{ $i++ }}</td>
        <td style="font-weight:600;">{{ $est?->nombre_completo ?? 'Sin nombre' }}</td>
        <td>
            <span class="chip chip-{{ $estado }}">{{ ucfirst($estado) }}</span>
        </td>
        @if($tarea->puntos_valor)
        <td style="text-align:center;">
            @if($nota !== null)
                <span style="font-weight:700;color:{{ $barColor }};">{{ number_format($nota,1) }}</span>
                <div class="nota-bar">
                    <div class="nota-bar-fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                </div>
            @else
                <span style="color:#94a3b8;">—</span>
            @endif
        </td>
        @endif
        <td style="color:#64748b;font-size:8pt;">
            {{ $entrega?->fecha_entrega?->format('d/m/Y') ?? '—' }}
        </td>
        <td style="font-size:7.5pt;color:#475569;">
            {{ $entrega?->notas_docente ? \Illuminate\Support\Str::limit($entrega->notas_docente, 80) : '' }}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $nombreInst }} &mdash; Reporte generado automáticamente por el sistema &mdash; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
