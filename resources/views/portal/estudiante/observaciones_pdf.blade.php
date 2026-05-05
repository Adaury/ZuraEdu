<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1e293b; }
    .header { background:#1e3a6e; color:#fff; padding:10px 14px; margin-bottom:10px; }
    .header h1 { font-size:11pt; font-weight:bold; margin-bottom:2px; }
    .header p  { font-size:7.5pt; opacity:.85; }
    .obs-item { border:1px solid #e5e7eb; border-radius:4px; padding:7px 9px; margin-bottom:6px; }
    .obs-tipo { display:inline-block; border-radius:20px; padding:.15rem .5rem; font-size:7pt; font-weight:700; }
    .tipo-academica  { background:#dbeafe; color:#1d4ed8; }
    .tipo-conductual { background:#fee2e2; color:#dc2626; }
    .tipo-positiva   { background:#dcfce7; color:#15803d; }
    .tipo-general    { background:#f3f4f6; color:#374151; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Observaciones del Estudiante</h1>
    <p>
        {{ $estudiante->nombre_completo }} &nbsp;·&nbsp;
        {{ $matricula?->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        {{ now()->format('d/m/Y') }}
        @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
    </p>
</div>

@if($observaciones->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">Sin observaciones registradas.</p>
@else

@foreach($observaciones as $obs)
@php
    $tipoCls = match($obs->tipo) {
        'academica'  => 'tipo-academica',
        'conductual' => 'tipo-conductual',
        'positiva'   => 'tipo-positiva',
        default      => 'tipo-general',
    };
@endphp
<div class="obs-item">
    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
        <span class="obs-tipo {{ $tipoCls }}">{{ ucfirst($obs->tipo ?? 'general') }}</span>
        <span style="font-size:7.5pt;font-weight:700;color:#1e3a6e;">{{ $obs->asignacion?->asignatura?->nombre ?? 'General' }}</span>
        <span style="font-size:7pt;color:#64748b;margin-left:auto;">{{ $obs->created_at?->format('d/m/Y') }}</span>
    </div>
    <div style="font-size:8pt;color:#374151;line-height:1.4;">{{ $obs->descripcion ?? $obs->observacion ?? '' }}</div>
    @if($obs->docente)
    <div style="font-size:7pt;color:#94a3b8;margin-top:3px;">Docente: {{ $obs->docente->nombre_completo }}</div>
    @endif
</div>
@endforeach

@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; Observaciones — {{ $estudiante->nombre_completo }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Total: {{ $observaciones->count() }} observación(es)
</div>
</body>
</html>
