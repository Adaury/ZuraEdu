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
    .mat-blk { margin-bottom:12px; border:1px solid #e5e7eb; border-radius:4px; }
    .mat-hd  { background:#7c3aed; color:#fff; padding:5px 8px; font-size:9pt; font-weight:bold; }
    .mat-hd small { font-size:7pt; font-weight:400; opacity:.85; }
    .plan-row { padding:5px 8px; border-bottom:1px solid #f1f5f9; }
    .plan-row:last-child { border-bottom:none; }
    .badge { display:inline-block; border-radius:20px; padding:.12rem .45rem; font-size:6.5pt; font-weight:700; }
    .badge-ra  { background:#dbeafe; color:#1d4ed8; }
    .badge-act { background:#dcfce7; color:#15803d; }
    .plan-title { font-weight:700; font-size:8.5pt; }
    .plan-meta  { font-size:7pt; color:#64748b; margin-top:2px; }
    .ra-item { font-size:7.5pt; color:#374151; border-left:3px solid #7c3aed;
               padding:2px 5px; margin-top:3px; background:#f5f3ff; border-radius:0 3px 3px 0; }
    .obj-item { font-size:7.5pt; color:#374151; border-left:3px solid #15803d;
                padding:2px 5px; margin-top:3px; background:#f0fdf4; border-radius:0 3px 3px 0; }
    .footer { margin-top:14px; font-size:7pt; color:#9ca3af; text-align:center;
              border-top:1px solid #e5e7eb; padding-top:6px; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $inst }} — Planificaciones Publicadas</h1>
    <p>
        {{ $estudiante->nombre_completo ?? '' }} &nbsp;·&nbsp;
        {{ $matricula?->grupo?->nombre_completo ?? '' }} &nbsp;·&nbsp;
        {{ now()->format('d/m/Y') }}
        @if($schoolYear) &nbsp;·&nbsp; {{ $schoolYear->nombre }} @endif
    </p>
</div>

@if($planificaciones->isEmpty())
<p style="text-align:center;color:#9ca3af;margin-top:20px;">Sin planificaciones publicadas.</p>
@else

@foreach($planificaciones as $asignacionId => $planes)
@php $primera = $planes->first(); $asignatura = $primera?->asignacion?->asignatura; $docente = $primera?->asignacion?->docente; @endphp
<div class="mat-blk">
    <div class="mat-hd">
        {{ $asignatura?->nombre ?? '—' }}
        @if($docente) <small>&nbsp;·&nbsp; {{ $docente->nombre_completo }}</small> @endif
    </div>
    @foreach($planes as $plan)
    <div class="plan-row">
        <div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">
            @if($plan->tipo === 'ra')
                <span class="badge badge-ra">Por RA</span>
            @else
                <span class="badge badge-act">Por Actividad</span>
            @endif
        </div>
        <div class="plan-title">
            {{ $plan->modulo_nombre ?? $asignatura?->nombre }}
            @if($plan->mf_codigo)<span style="font-size:7pt;font-weight:400;color:#64748b;font-family:monospace;"> · {{ $plan->mf_codigo }}</span>@endif
        </div>
        <div class="plan-meta">
            @if($plan->sesion)Sesión: {{ $plan->sesion }}@endif
            @if($plan->fecha_inicio && $plan->fecha_fin)
            &nbsp;·&nbsp; {{ $plan->fecha_inicio->format('d/m/Y') }} — {{ $plan->fecha_fin->format('d/m/Y') }}
            @endif
            @if($plan->horas) &nbsp;·&nbsp; {{ $plan->horas }}h @endif
        </div>
        @if($plan->tipo === 'ra' && $plan->raItems->isNotEmpty())
            @foreach($plan->raItems as $ra)
            <div class="ra-item">
                @if($ra->ra_codigo)<strong>{{ $ra->ra_codigo }}:</strong> @endif
                {{ \Illuminate\Support\Str::limit($ra->ra_descripcion, 150) }}
            </div>
            @endforeach
        @elseif($plan->tipo === 'actividad')
            @php $act = $plan->actividades->first(); @endphp
            @if($act?->objetivo)
            <div class="obj-item"><strong>Objetivo:</strong> {{ \Illuminate\Support\Str::limit($act->objetivo, 150) }}</div>
            @endif
        @endif
    </div>
    @endforeach
</div>
@endforeach

@endif

<div class="footer">
    {{ $inst }} &nbsp;·&nbsp; {{ $estudiante->nombre_completo ?? '' }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    &nbsp;·&nbsp; Total asignaturas: {{ $planificaciones->count() }}
</div>
</body>
</html>
