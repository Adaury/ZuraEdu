@extends('layouts.admin')
@section('page-title', 'Vista Maestra de Horarios')

@push('styles')
<style>
/* ── Selector ──────────────────────────────────────────── */
.vm-selector {
    background:#fff;border:1px solid #e5e7eb;border-radius:12px;
    padding:.8rem 1.1rem;display:flex;align-items:center;gap:.75rem;
    flex-wrap:wrap;margin-bottom:1.25rem;box-shadow:0 1px 4px rgba(30,58,110,.04);
}
.vm-selector label { font-size:.75rem;font-weight:700;color:#6b7280;white-space:nowrap;margin:0; }

/* ── Master table wrapper ──────────────────────────────── */
.vm-wrap { overflow-x:auto;-webkit-overflow-scrolling:touch; }
.vm-table {
    border-collapse:collapse;
    white-space:nowrap;
    font-size:.72rem;
    min-width:700px;
}

/* ── Header rows ───────────────────────────────────────── */
.vm-table thead tr.day-header th {
    background:#1e1b4b;color:#fff;
    text-align:center;padding:.45rem .35rem;
    font-size:.68rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
    border:1px solid #312e81;
}
.vm-table thead tr.day-header th.th-grupo { background:#111827;color:#9ca3af;font-size:.65rem; }
.vm-table thead tr.franja-header th {
    background:#312e81;color:rgba(255,255,255,.75);
    text-align:center;padding:.3rem .2rem;
    font-size:.6rem;font-weight:600;
    border:1px solid #3730a3;
}
.vm-table thead tr.franja-header th.th-grupo-sub { background:#1f2937;color:#6b7280; }

/* ── Body cells ────────────────────────────────────────── */
.vm-table tbody td.td-grupo {
    background:#f8fafc;border-right:2px solid #e2e8f0;
    padding:.35rem .55rem;font-size:.75rem;font-weight:700;
    color:#1e3a6e;white-space:nowrap;vertical-align:middle;
    border-bottom:1px solid #e5e7eb;min-width:80px;
}
.vm-table tbody td.td-cell {
    padding:2px;border:1px solid #f0f2f5;
    vertical-align:middle;text-align:center;
    min-width:46px;width:46px;height:38px;
    background:#fafbff;
}
.vm-table tbody td.td-recreo {
    background:#fef9ec;text-align:center;
    color:#92400e;font-size:.6rem;font-weight:700;
    letter-spacing:.06em;text-transform:uppercase;
    border:1px solid #fde68a;
}
.vm-table tbody tr:hover td { filter:brightness(.97); }

/* ── Chip ──────────────────────────────────────────────── */
.vm-chip {
    display:inline-flex;align-items:center;justify-content:center;
    border-radius:5px;padding:.15rem .25rem;
    font-size:.64rem;font-weight:800;color:#fff;
    min-width:34px;max-width:44px;
    line-height:1.15;text-align:center;
    cursor:default;position:relative;
}
.vm-chip:hover .vm-tooltip { display:block; }
.vm-tooltip {
    display:none;position:absolute;z-index:50;top:calc(100% + 4px);left:50%;
    transform:translateX(-50%);background:#1e293b;color:#fff;
    border-radius:7px;padding:.4rem .7rem;font-size:.65rem;font-weight:500;
    white-space:nowrap;line-height:1.4;box-shadow:0 4px 16px rgba(0,0,0,.2);
    pointer-events:none;
}
.vm-tooltip::before {
    content:'';position:absolute;bottom:100%;left:50%;transform:translateX(-50%);
    border:5px solid transparent;border-bottom-color:#1e293b;
}

/* ── Legend ────────────────────────────────────────────── */
.vm-legend {
    display:flex;flex-wrap:wrap;gap:.5rem;
    margin-top:1rem;padding:.75rem 1rem;
    background:#fff;border:1px solid #e5e7eb;border-radius:10px;
}
.vm-legend-item { display:flex;align-items:center;gap:.35rem;font-size:.73rem;color:#374151; }
.vm-legend-dot { width:12px;height:12px;border-radius:3px;flex-shrink:0; }

/* Dark mode */
[data-theme="dark"] .vm-selector { background:#1e293b;border-color:#334155; }
[data-theme="dark"] .vm-table tbody td.td-grupo { background:#1a2640;border-color:#334155;color:#93c5fd; }
[data-theme="dark"] .vm-table tbody td.td-cell { background:#0f172a;border-color:#1e293b; }
[data-theme="dark"] .vm-table tbody td.td-recreo { background:#1c1200;border-color:#78350f;color:#fbbf24; }
[data-theme="dark"] .vm-legend { background:#1e293b;border-color:#334155; }
[data-theme="dark"] .vm-legend-item { color:#cbd5e1; }
</style>
@endpush

@section('content')

<x-breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Horarios',  'url' => route('admin.horarios.index')],
    ['label' => 'Vista Maestra', 'url' => ''],
]" />

{{-- Selector de horario --}}
<form method="GET" action="{{ route('admin.horarios.vista-maestra') }}" class="vm-selector">
    <label><i class="bi bi-calendar3 me-1"></i>Horario:</label>
    <select name="horario_id" class="form-select form-select-sm" style="max-width:320px;"
            onchange="this.form.submit()">
        <option value="">— Selecciona un horario —</option>
        @foreach($horarios as $h)
        <option value="{{ $h->id }}" @selected($horario?->id === $h->id)>
            {{ $h->nombre ?? 'Horario #'.$h->id }}
            ({{ $h->estado }})
            @if($h->es_activo) ★ @endif
        </option>
        @endforeach
    </select>
    @if($horario)
    <span style="font-size:.75rem;color:#6b7280;">
        <i class="bi bi-info-circle me-1"></i>
        {{ $grupos->count() }} grupos · {{ $franjas->where('es_recreo',false)->count() }} franjas
    </span>
    <a href="{{ route('admin.horarios.vista-maestra.pdf', ['horario_id' => $horario->id]) }}" target="_blank"
       class="btn btn-danger btn-sm" style="border-radius:8px;font-size:.75rem;">
        <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
    </a>
    <a href="{{ route('admin.horarios.vista-maestra.excel', ['horario_id' => $horario->id]) }}"
       class="btn btn-success btn-sm" style="border-radius:8px;font-size:.75rem;">
        <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
    </a>
    <a href="{{ route('admin.horarios.show', $horario) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.75rem;">
        <i class="bi bi-pencil-square me-1"></i>Editar horario
    </a>
    @endif
</form>

@if(!$horario)
<div style="background:#fff;border:2px dashed #e5e7eb;border-radius:16px;text-align:center;padding:4rem 2rem;">
    <div style="font-size:3rem;color:#d1d5db;margin-bottom:1rem;"><i class="bi bi-calendar-week"></i></div>
    <h4 style="font-weight:700;color:#374151;">Selecciona un horario</h4>
    <p style="font-size:.85rem;color:#9ca3af;">Elige un horario del selector para ver la vista maestra de todos los grupos.</p>
</div>
@elseif($grupos->isEmpty())
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:1.5rem;text-align:center;color:#92400e;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Este horario no tiene detalles generados aún.
    <a href="{{ route('admin.horarios.show', $horario) }}" class="btn btn-sm btn-warning ms-2">Ir a editar</a>
</div>
@else

{{-- ── TÍTULO ── --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <div>
        <h2 style="font-size:1.1rem;font-weight:800;color:#1e1b4b;margin:0;">
            <i class="bi bi-grid-3x3-gap-fill me-2" style="color:#4338ca;"></i>
            Vista Maestra — {{ $horario->nombre ?? 'Horario #'.$horario->id }}
        </h2>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $schoolYear?->nombre }} · {{ $grupos->count() }} grupos ·
            <span class="badge bg-{{ $horario->estado === 'publicado' ? 'success' : 'warning' }} text-white" style="font-size:.65rem;">
                {{ ucfirst($horario->estado) }}
            </span>
        </div>
    </div>
    <div style="margin-left:auto;display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('admin.horarios.horario-docente') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.75rem;">
            <i class="bi bi-person-lines-fill me-1"></i>Por Docente
        </a>
        <a href="{{ route('admin.horarios.show', $horario) }}" class="btn btn-sm btn-primary" style="border-radius:8px;font-size:.75rem;">
            <i class="bi bi-pencil-square me-1"></i>Editar
        </a>
    </div>
</div>

{{-- ── TABLA MAESTRA ── --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(30,58,110,.07);margin-bottom:1.25rem;">
<div class="vm-wrap">
<table class="vm-table">
    <thead>
        {{-- Fila 1: días con colspan --}}
        <tr class="day-header">
            <th class="th-grupo" rowspan="2" style="min-width:80px;">Grupo</th>
            @foreach($dias as $dia)
            @php $franjasDia = $franjas->where('es_recreo', false)->count(); @endphp
            <th colspan="{{ $franjasDia }}" style="border-left:2px solid rgba(255,255,255,.15);">
                {{ strtoupper(substr($dia, 0, 3)) }}
            </th>
            @endforeach
        </tr>
        {{-- Fila 2: números de franja --}}
        <tr class="franja-header">
            @foreach($dias as $dia)
            @foreach($franjas->where('es_recreo', false) as $f)
            <th title="{{ $f->hora_inicio }} – {{ $f->hora_fin }}"
                style="@if(!$loop->first && $loop->index === 0) border-left:2px solid rgba(255,255,255,.15); @endif">
                {{ $f->numero }}
            </th>
            @endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
    @foreach($grupos as $grupo)
    <tr>
        <td class="td-grupo">
            {{ $grupo->nombre_completo ?? $grupo->nombre ?? '—' }}
        </td>
        @foreach($dias as $dia)
        @foreach($franjas as $franja)
        @if($franja->es_recreo)
        @continue
        @endif
        @php
            $d = $grid[$grupo->id][$dia][$franja->id] ?? null;
            $asig = $d?->asignacion;
            $asignatura = $asig?->asignatura;
            $color = $asignatura ? ($colores[$asignatura->id] ?? ($asignatura->color ?? '#6b7280')) : null;
            $docente = $asig?->docente;
            $initials = $docente
                ? strtoupper(substr($docente->nombres ?? 'D', 0, 1) . substr($docente->apellidos ?? '', 0, 1))
                : '';
        @endphp
        <td class="td-cell">
            @if($d && $asignatura)
            <div class="vm-chip" style="background:{{ $color }};position:relative;">
                {{ $asignatura->codigo ?? Str::limit($asignatura->nombre, 4, '') }}
                <div class="vm-tooltip">
                    <strong>{{ $asignatura->nombre }}</strong><br>
                    {{ $docente ? ($docente->apellidos.', '.$docente->nombres) : '—' }}<br>
                    {{ $franja->hora_inicio }} – {{ $franja->hora_fin }}
                    @if($d->aula?->nombre)
                    <br><i class="bi bi-door-open"></i> {{ $d->aula->nombre }}
                    @endif
                </div>
            </div>
            @endif
        </td>
        @endforeach
        @endforeach
    </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>

{{-- ── LEYENDA ── --}}
@php
    $asignaturasEnGrid = collect();
    foreach($grupos as $grupo) {
        foreach($dias as $dia) {
            foreach($franjas->where('es_recreo',false) as $franja) {
                $d = $grid[$grupo->id][$dia][$franja->id] ?? null;
                if($d?->asignacion?->asignatura) {
                    $asignaturasEnGrid[$d->asignacion->asignatura->id] = $d->asignacion->asignatura;
                }
            }
        }
    }
@endphp
@if($asignaturasEnGrid->isNotEmpty())
<div class="vm-legend">
    <span style="font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;width:100%;margin-bottom:.25rem;">
        <i class="bi bi-palette me-1"></i>Materias
    </span>
    @foreach($asignaturasEnGrid as $asig)
    @php $c = $colores[$asig->id] ?? ($asig->color ?? '#6b7280'); @endphp
    <div class="vm-legend-item">
        <div class="vm-legend-dot" style="background:{{ $c }};"></div>
        <span>{{ $asig->codigo ?? '' }} — {{ $asig->nombre }}</span>
    </div>
    @endforeach
</div>
@endif

@endif {{-- grupos no vacíos --}}

@endsection
