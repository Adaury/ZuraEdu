@extends('layouts.portal')
@section('page-title', 'Ver Planificación')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planificacion'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planif.
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@push('styles')
<style>
.plan-hdr-tbl { width:100%; border-collapse:collapse; font-size:.78rem; margin-bottom:.5rem; }
.plan-hdr-tbl td { padding:.3rem .5rem; border:1px solid #e2e8f0; vertical-align:top; }
.plan-hdr-tbl td.lbl { background:#f8faff; font-weight:700; color:#1d4ed8; white-space:nowrap; width:130px; }
.plan-ra-tbl { width:100%; border-collapse:collapse; font-size:.78rem; }
.plan-ra-tbl th { background:#15803d; color:#fff; padding:.4rem .5rem; border:1px solid #166534; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; }
.plan-ra-tbl td { padding:.5rem .5rem; border:1px solid #e2e8f0; vertical-align:top; }
.plan-ra-tbl tr:nth-child(even) td { background:#f8faff; }
.act-section { border-left:3px solid; padding:.5rem .75rem; margin-bottom:.6rem;
               background:#f8faff; border-radius:0 6px 6px 0;
               white-space:pre-line; font-size:.8rem; line-height:1.5; }
.act-section.inicio      { border-color:#2563eb; }
.act-section.desarrollo  { border-color:#15803d; }
.act-section.cierre      { border-color:#d97706; }
.act-section.estrategia  { border-color:#7c3aed; }
.act-section.recursos    { border-color:#0ea5e9; }
.act-section.instrumentos{ border-color:#dc2626; }
.sec-ttl { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
           background:#1d4ed8; color:#fff; padding:.35rem .75rem; border-radius:5px; margin-bottom:.6rem; }
[data-theme="dark"] .plan-hdr-tbl td.lbl { background:#1e3a5f; }
[data-theme="dark"] .plan-hdr-tbl td { border-color:#334155; color:var(--prt-text); }
[data-theme="dark"] .plan-ra-tbl td { border-color:#334155; color:var(--prt-text); }
[data-theme="dark"] .plan-ra-tbl tr:nth-child(even) td { background:#1e293b; }
[data-theme="dark"] .act-section { background:#1e293b; }
@media print {
    .prt-sidebar, .prt-bottom-nav, .no-print { display:none !important; }
    .prt-main { margin:0 !important; }
    body { font-size:10pt; }
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;" class="no-print">
    <a href="{{ route('portal.docente.planificacion.index', $asignacion) }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-file-earmark-text" style="color:#5b21b6;"></i>
            Planificación —
            @if($planificacion->tipo === 'ra')
                <span style="color:#1d4ed8;">Por Resultados de Aprendizaje</span>
            @else
                <span style="color:#15803d;">Por Actividad</span>
            @endif
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->asignatura?->nombre }} · {{ $asignacion->grupo?->nombre_completo }}
        </div>
    </div>
    <a href="{{ route('portal.docente.planificacion.edit', [$asignacion, $planificacion]) }}"
       style="background:#1d4ed8;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-pencil-fill"></i>Editar
    </a>
    <form method="POST" action="{{ route('portal.docente.planificacion.toggle-publicado', [$asignacion, $planificacion]) }}" style="display:inline;">
        @csrf @method('PATCH')
        <button style="background:{{ $planificacion->publicado ? '#dcfce7' : '#f1f5f9' }};color:{{ $planificacion->publicado ? '#15803d' : '#374151' }};border:none;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-{{ $planificacion->publicado ? 'eye-fill' : 'eye-slash' }}"></i>
            {{ $planificacion->publicado ? 'Publicado' : 'Borrador' }}
        </button>
    </form>
    <button onclick="window.print()"
            style="background:#f1f5f9;color:#374151;border:none;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-printer"></i>Imprimir
    </button>
</div>

@if(session('success'))
<div style="background:#dcfce7;color:#15803d;border-radius:8px;padding:.6rem 1rem;margin-bottom:.75rem;font-size:.82rem;" class="no-print">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
</div>
@endif

{{-- Cabecera Institucional --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div style="background:#1a365d;color:#fff;padding:.75rem 1rem;border-radius:8px 8px 0 0;text-align:center;">
        <div style="font-weight:800;font-size:.9rem;letter-spacing:.04em;">POLITÉCNICO SALESIANO ARQUIDES CALDERÓN</div>
        <div style="font-size:.72rem;font-style:italic;">"Formando Honrados Ciudadanos y Buenos Cristianos"</div>
        <div style="font-weight:700;font-size:.8rem;margin-top:.3rem;text-transform:uppercase;">
            @if($planificacion->tipo === 'ra')
                Matriz de Planificación por Resultados de Aprendizaje
            @else
                Matriz de Planificación por Actividad de Aprendizaje
            @endif
        </div>
    </div>
    <div style="padding:.75rem;">
        <table class="plan-hdr-tbl">
            <tr>
                <td class="lbl">Familia Profesional</td>
                <td colspan="3">{{ $planificacion->familia_profesional ?? '—' }}</td>
                <td class="lbl">Denominación</td>
                <td colspan="3">{{ $planificacion->denominacion ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Módulo</td>
                <td colspan="5">{{ $planificacion->modulo_nombre ?? $asignacion->asignatura?->nombre ?? '—' }}</td>
                <td class="lbl">Sesión / Nivel</td>
                <td>{{ $planificacion->sesion ?? '—' }} · Nv. {{ $planificacion->nivel ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Docente</td>
                <td colspan="3">{{ $asignacion->docente?->nombre_completo ?? auth()->user()->name }}</td>
                <td class="lbl">Código</td>
                <td>{{ $planificacion->mf_codigo ?? '—' }}</td>
                <td class="lbl">Horas</td>
                <td>{{ $planificacion->horas ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Fecha de Inicio</td>
                <td colspan="3">{{ $planificacion->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                <td class="lbl">Fecha Final</td>
                <td colspan="3">{{ $planificacion->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @if($planificacion->uc_codigo)
            <tr>
                <td class="lbl">UC / Unidad Competencia</td>
                <td colspan="7">{{ $planificacion->uc_codigo }}</td>
            </tr>
            @endif
        </table>
    </div>
</div>

@if($planificacion->tipo === 'ra')
{{-- ══ VISTA POR RA ══════════════════════════════════════════════════════ --}}
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-list-check" style="color:#15803d;font-size:1rem;"></i>
        <h3>Resultados de Aprendizaje</h3>
    </div>
    @if($planificacion->raItems->isEmpty())
        <div style="padding:1.5rem;text-align:center;color:#64748b;font-size:.82rem;">Sin RA registrados.</div>
    @else
    <div style="overflow-x:auto;">
        <table class="plan-ra-tbl">
            <thead>
                <tr>
                    <th style="min-width:150px;">RA</th>
                    <th style="min-width:170px;">Elementos de Capacidad</th>
                    <th style="min-width:110px;">Fechas</th>
                    <th style="min-width:190px;">Actividades E-A</th>
                    <th style="min-width:130px;">Instrumentos</th>
                    <th style="min-width:150px;">Contenidos</th>
                </tr>
            </thead>
            <tbody>
            @foreach($planificacion->raItems as $item)
            <tr>
                <td>
                    @if($item->ra_codigo)<strong style="color:#15803d;">{{ $item->ra_codigo }}:</strong><br>@endif
                    <span style="white-space:pre-line;font-size:.77rem;">{{ $item->ra_descripcion }}</span>
                    @if($item->nivel_taxonomico)<br><em style="font-size:.72rem;color:#64748b;">({{ $item->nivel_taxonomico }})</em>@endif
                </td>
                <td>
                    @foreach($item->elementos_capacidad ?? [] as $i => $ec)
                    <div style="margin-bottom:.25rem;font-size:.77rem;">{{ $i+1 }}- {{ $ec['descripcion'] ?? $ec }}</div>
                    @endforeach
                </td>
                <td>
                    @foreach($item->fechas ?? [] as $f)
                    <div style="white-space:nowrap;font-size:.75rem;margin-bottom:.2rem;">
                        Desde: <strong>{{ $f['desde'] ? \Carbon\Carbon::parse($f['desde'])->format('d/m/Y') : '—' }}</strong><br>
                        Hasta: <strong>{{ $f['hasta'] ? \Carbon\Carbon::parse($f['hasta'])->format('d/m/Y') : '—' }}</strong>
                    </div>
                    @endforeach
                </td>
                <td style="white-space:pre-line;font-size:.77rem;">{{ $item->actividades ?? '—' }}</td>
                <td style="white-space:pre-line;font-size:.77rem;">{{ $item->instrumentos_evaluacion ?? '—' }}</td>
                <td style="white-space:pre-line;font-size:.77rem;">{{ $item->contenidos ?? '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@else
{{-- ══ VISTA POR ACTIVIDAD ═══════════════════════════════════════════════ --}}
@php $act = $planificacion->actividades->first(); @endphp
@if($act)
<div class="prt-card" style="margin-bottom:.75rem;">
    <div class="prt-card-header">
        <i class="bi bi-activity" style="color:#15803d;font-size:1rem;"></i>
        <h3>Actividad de Aprendizaje</h3>
    </div>
    <div style="padding:.85rem;">

        <div class="sec-ttl"><i class="bi bi-bookmark-check me-1"></i>Recurso de Aprendizaje</div>
        <table class="plan-hdr-tbl" style="margin-bottom:.75rem;">
            <tr>
                <td class="lbl" style="width:100px;">Código RA</td>
                <td style="font-weight:700;">{{ $act->ra_codigo ?? '—' }}</td>
                <td class="lbl" style="width:110px;">Nº Actividad</td>
                <td style="font-weight:700;color:#1d4ed8;">#{{ $act->actividad_numero ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Descripción RA</td>
                <td colspan="3">{{ $act->ra_descripcion ?? '—' }}</td>
            </tr>
            @if($act->objetivo)
            <tr>
                <td class="lbl">Objetivo</td>
                <td colspan="3">{{ $act->objetivo }}</td>
            </tr>
            @endif
        </table>

        <div class="sec-ttl" style="background:#15803d;"><i class="bi bi-layout-text-window me-1"></i>Descripción de la Actividad</div>

        @if($act->act_inicio)
        <div style="font-size:.75rem;font-weight:700;color:#1d4ed8;margin-bottom:.3rem;">
            <span style="background:#dbeafe;border-radius:4px;padding:.1rem .4rem;">INICIO</span>
            Actividad de Inicio
        </div>
        <div class="act-section inicio">{{ $act->act_inicio }}</div>
        @endif

        @if($act->act_desarrollo)
        <div style="font-size:.75rem;font-weight:700;color:#15803d;margin-bottom:.3rem;">
            <span style="background:#dcfce7;border-radius:4px;padding:.1rem .4rem;">DESARROLLO</span>
            Actividad de Desarrollo
        </div>
        <div class="act-section desarrollo">{{ $act->act_desarrollo }}</div>
        @endif

        @if($act->act_cierre)
        <div style="font-size:.75rem;font-weight:700;color:#92400e;margin-bottom:.3rem;">
            <span style="background:#fef3c7;border-radius:4px;padding:.1rem .4rem;">CIERRE</span>
            Generalización / Cierre
        </div>
        <div class="act-section cierre">{{ $act->act_cierre }}</div>
        @endif

        @if($act->estrategias || $act->recursos || $act->instrumentos_evaluacion)
        <div class="sec-ttl" style="background:#7c3aed;margin-top:.75rem;">
            <i class="bi bi-tools me-1"></i>Estrategias, Recursos e Instrumentos
        </div>
        @if($act->estrategias)
        <div style="font-size:.75rem;font-weight:700;color:#7c3aed;margin-bottom:.3rem;">Estrategias:</div>
        <div class="act-section estrategia">{{ $act->estrategias }}</div>
        @endif
        @if($act->recursos)
        <div style="font-size:.75rem;font-weight:700;color:#0ea5e9;margin-bottom:.3rem;">Recursos:</div>
        <div class="act-section recursos">{{ $act->recursos }}</div>
        @endif
        @if($act->instrumentos_evaluacion)
        <div style="font-size:.75rem;font-weight:700;color:#dc2626;margin-bottom:.3rem;">Instrumentos de Evaluación:</div>
        <div class="act-section instrumentos">{{ $act->instrumentos_evaluacion }}</div>
        @endif
        @endif

    </div>
</div>
@endif
@endif

{{-- Firma --}}
<div style="margin-top:1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:2rem;text-align:center;font-size:.78rem;">
    <div><div style="border-top:1px solid #cbd5e1;padding-top:.5rem;">
        <strong>{{ $asignacion->docente?->nombre_completo ?? auth()->user()->name }}</strong>
        <div style="color:#64748b;">Docente</div>
    </div></div>
    <div><div style="border-top:1px solid #cbd5e1;padding-top:.5rem;">
        <strong>________________________</strong>
        <div style="color:#64748b;">Coordinador Técnico Pedagógico</div>
    </div></div>
</div>

@endsection
