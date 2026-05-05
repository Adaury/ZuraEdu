@extends('layouts.admin')
@section('title', 'Ver Planificación')

@push('styles')
<style>
.plan-header-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.plan-header-table td { padding:.35rem .6rem; border:1px solid #dee2e6; vertical-align:top; }
.plan-header-table td.lbl { background:#f8f9fa; font-weight:700; width:170px; color:#374151; white-space:nowrap; }
.plan-ra-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.plan-ra-table th { background:#198754; color:#fff; padding:.45rem .55rem; border:1px solid #157347; font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; vertical-align:middle; }
.plan-ra-table td { padding:.55rem .55rem; border:1px solid #dee2e6; vertical-align:top; }
.plan-ra-table tr:nth-child(even) td { background:#f8f9fa; }
.section-hdr { background:#0d6efd; color:#fff; padding:.5rem 1rem; font-weight:700; font-size:.8rem; text-transform:uppercase; letter-spacing:.08em; border-radius:6px; margin-bottom:.75rem; }
.act-badge-inicio    { background:#cfe2ff; color:#0a58ca; }
.act-badge-desarrollo{ background:#d1e7dd; color:#146c43; }
.act-badge-cierre    { background:#fff3cd; color:#664d03; }
.act-section { border-left:4px solid; padding:.6rem .85rem; margin-bottom:.75rem; background:#fcfcfc; border-radius:0 6px 6px 0; white-space:pre-line; font-size:.84rem; line-height:1.55; }
.act-section.inicio      { border-color:#0d6efd; }
.act-section.desarrollo  { border-color:#198754; }
.act-section.cierre      { border-color:#ffc107; }
.act-section.estrategia  { border-color:#6f42c1; }
.act-section.recursos    { border-color:#0dcaf0; }
.act-section.instrumentos{ border-color:#dc3545; }
@media print {
    .no-print { display:none !important; }
    body { font-size:11pt; }
    .card { box-shadow:none !important; border:1px solid #dee2e6 !important; }
    .plan-header-table td.lbl { background:#e9ecef !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .plan-ra-table th { background:#198754 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-3" style="max-width:1100px;">

{{-- Header --}}
<div class="d-flex align-items-center gap-2 mb-3 no-print">
    <a href="{{ route('admin.planificacion.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Planificación
            @if($planificacion->tipo === 'ra')
                <span class="badge bg-primary fs-6">Por RA</span>
            @else
                <span class="badge bg-success fs-6">Por Actividad</span>
            @endif
        </h4>
        <small class="text-muted">{{ $planificacion->asignacion?->asignatura?->nombre }} — {{ $planificacion->schoolYear?->nombre }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.planificacion.pdf', $planificacion) }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="{{ route('admin.planificacion.edit', $planificacion) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <form method="POST" action="{{ route('admin.planificacion.toggle-publicado', $planificacion) }}" class="d-inline">
            @csrf @method('PATCH')
            <button class="btn btn-sm {{ $planificacion->publicado ? 'btn-warning' : 'btn-success' }}">
                <i class="bi bi-{{ $planificacion->publicado ? 'eye-slash' : 'eye' }} me-1"></i>
                {{ $planificacion->publicado ? 'Despublicar' : 'Publicar' }}
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 no-print" role="alert">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── CABECERA INSTITUCIONAL ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div style="background:#1a365d;color:#fff;padding:.85rem 1.2rem;border-radius:8px 8px 0 0;">
            <div class="text-center">
                <div style="font-weight:800;font-size:1rem;letter-spacing:.05em;">POLITÉCNICO SALESIANO ARQUIDES CALDERÓN</div>
                <div style="font-size:.8rem;font-style:italic;">"Formando Honrados Ciudadanos y Buenos Cristianos"</div>
                <div style="margin-top:.4rem;font-weight:700;font-size:.9rem;text-transform:uppercase;">
                    @if($planificacion->tipo === 'ra')
                        Matriz de Planificación por Resultados de Aprendizaje
                    @else
                        Matriz de Planificación por Actividad de Aprendizaje
                    @endif
                </div>
            </div>
        </div>
        <div class="p-3">
            <table class="plan-header-table">
                <tr>
                    <td class="lbl">Familia Profesional</td>
                    <td colspan="3">{{ $planificacion->familia_profesional ?? '—' }}</td>
                    <td class="lbl">Denominación</td>
                    <td colspan="3">{{ $planificacion->denominacion ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Módulo</td>
                    <td colspan="5">{{ $planificacion->modulo_nombre ?? $planificacion->asignacion?->asignatura?->nombre ?? '—' }}</td>
                    <td class="lbl">Sesión</td>
                    <td>{{ $planificacion->sesion ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Docente</td>
                    <td colspan="3">{{ $planificacion->asignacion?->docente?->nombre_completo ?? '—' }}</td>
                    <td class="lbl">Código</td>
                    <td>{{ $planificacion->mf_codigo ?? '—' }}</td>
                    <td class="lbl">Horas</td>
                    <td>{{ $planificacion->horas ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Fecha de Inicio</td>
                    <td colspan="3">{{ $planificacion->fecha_inicio?->format('d \d\e F \d\e Y') ?? '—' }}</td>
                    <td class="lbl">Fecha Final</td>
                    <td colspan="3">{{ $planificacion->fecha_fin?->format('d \d\e F \d\e Y') ?? '—' }}</td>
                </tr>
                @if($planificacion->tipo === 'ra' && $planificacion->uc_codigo)
                <tr>
                    <td class="lbl">Unidad de Competencia</td>
                    <td colspan="7">{{ $planificacion->uc_codigo }}</td>
                </tr>
                @endif
                @if($planificacion->tipo === 'actividad')
                <tr>
                    <td class="lbl">Nivel</td>
                    <td>{{ $planificacion->nivel ?? '—' }}</td>
                    <td class="lbl">UC / MF</td>
                    <td colspan="5">{{ $planificacion->uc_codigo ?? '—' }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>
</div>

@if($planificacion->tipo === 'ra')
{{-- ═══════════════════ VISTA POR RA ════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        @if($planificacion->raItems->isEmpty())
            <div class="text-center py-4 text-muted">Sin RA registrados.</div>
        @else
        <div style="overflow-x:auto;">
            <table class="plan-ra-table">
                <thead>
                    <tr>
                        <th style="min-width:170px;">Resultados de Aprendizaje</th>
                        <th style="min-width:200px;">Elementos de Capacidad</th>
                        <th style="min-width:130px;">Fechas</th>
                        <th style="min-width:220px;">Actividades de Enseñanza-Aprendizaje</th>
                        <th style="min-width:150px;">Instrumento de Evaluación</th>
                        <th style="min-width:180px;">Contenidos</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($planificacion->raItems as $item)
                <tr>
                    <td>
                        @if($item->ra_codigo)
                        <strong class="text-success">{{ $item->ra_codigo }}:</strong><br>
                        @endif
                        <span style="white-space:pre-line;">{{ $item->ra_descripcion }}</span>
                        @if($item->nivel_taxonomico)
                        <br><em class="text-muted" style="font-size:.78rem;">({{ $item->nivel_taxonomico }})</em>
                        @endif
                    </td>
                    <td>
                        @if(!empty($item->elementos_capacidad))
                            @foreach($item->elementos_capacidad as $idx => $ec)
                            <div style="margin-bottom:.3rem;">{{ $idx + 1 }}- {{ $ec['descripcion'] ?? $ec }}</div>
                            @endforeach
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($item->fechas))
                            @foreach($item->fechas as $f)
                            <div style="white-space:nowrap;margin-bottom:.2rem;">
                                <small>Desde: <strong>{{ $f['desde'] ? \Carbon\Carbon::parse($f['desde'])->format('d/m/Y') : '—' }}</strong></small><br>
                                <small>Hasta: <strong>{{ $f['hasta'] ? \Carbon\Carbon::parse($f['hasta'])->format('d/m/Y') : '—' }}</strong></small>
                            </div>
                            @endforeach
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="white-space:pre-line;">{{ $item->actividades ?? '—' }}</td>
                    <td style="white-space:pre-line;">{{ $item->instrumentos_evaluacion ?? '—' }}</td>
                    <td style="white-space:pre-line;">{{ $item->contenidos ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@else
{{-- ═══════════════════ VISTA POR ACTIVIDAD ═════════════════════════════════ --}}
@php $act = $planificacion->actividades->first(); @endphp
@if($act)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">

        {{-- RA y Actividad --}}
        <div class="section-hdr"><i class="bi bi-bookmark-check me-2"></i>Recurso de Aprendizaje (RA)</div>
        <table class="plan-header-table mb-3">
            <tr>
                <td class="lbl" style="width:120px;">Código RA</td>
                <td style="width:80px;font-weight:700;">{{ $act->ra_codigo ?? '—' }}</td>
                <td class="lbl" style="width:130px;">Nº Actividad</td>
                <td><strong class="text-primary">#{{ $act->actividad_numero ?? '—' }}</strong></td>
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

        {{-- Descripción de la actividad --}}
        <div class="section-hdr"><i class="bi bi-layout-text-window me-2"></i>Descripción de la Actividad</div>

        @if($act->act_inicio)
        <div class="mb-2">
            <div class="fw-bold mb-1" style="font-size:.8rem;color:#0a58ca;">
                <span class="badge act-badge-inicio me-1">INICIO</span> Actividad de Inicio
            </div>
            <div class="act-section inicio">{{ $act->act_inicio }}</div>
        </div>
        @endif

        @if($act->act_desarrollo)
        <div class="mb-2">
            <div class="fw-bold mb-1" style="font-size:.8rem;color:#146c43;">
                <span class="badge act-badge-desarrollo me-1">DESARROLLO</span>
                Actividad de Desarrollo <span class="fw-normal text-muted">(conceptual / procedimental y/o actitudinal)</span>
            </div>
            <div class="act-section desarrollo">{{ $act->act_desarrollo }}</div>
        </div>
        @endif

        @if($act->act_cierre)
        <div class="mb-2">
            <div class="fw-bold mb-1" style="font-size:.8rem;color:#664d03;">
                <span class="badge act-badge-cierre me-1">CIERRE</span> Actividad de Generalización o Cierre
            </div>
            <div class="act-section cierre">{{ $act->act_cierre }}</div>
        </div>
        @endif

        {{-- Estrategias, Recursos, Instrumentos --}}
        @if($act->estrategias || $act->recursos || $act->instrumentos_evaluacion)
        <div class="section-hdr mt-3"><i class="bi bi-tools me-2"></i>Estrategias, Recursos e Instrumentos</div>
        <div class="row g-3">
            @if($act->estrategias)
            <div class="col-md-6">
                <div class="fw-bold mb-1" style="font-size:.8rem;color:#6f42c1;">
                    <i class="bi bi-diagram-3 me-1"></i>Estrategias:
                </div>
                <div class="act-section estrategia">{{ $act->estrategias }}</div>
            </div>
            @endif
            @if($act->recursos)
            <div class="col-md-6">
                <div class="fw-bold mb-1" style="font-size:.8rem;color:#0dcaf0;">
                    <i class="bi bi-box me-1"></i>Recursos:
                </div>
                <div class="act-section recursos">{{ $act->recursos }}</div>
            </div>
            @endif
            @if($act->instrumentos_evaluacion)
            <div class="col-12">
                <div class="fw-bold mb-1" style="font-size:.8rem;color:#dc3545;">
                    <i class="bi bi-clipboard-check me-1"></i>Instrumentos de Evaluación:
                </div>
                <div class="act-section instrumentos">{{ $act->instrumentos_evaluacion }}</div>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>
@else
<div class="alert alert-warning">Sin datos de actividad registrados.</div>
@endif
@endif

{{-- Firma --}}
<div style="margin-top:2rem;display:grid;grid-template-columns:1fr 1fr;gap:2rem;text-align:center;font-size:.82rem;">
    <div>
        <div style="border-top:1px solid #6c757d;padding-top:.5rem;">
            <strong>{{ $planificacion->asignacion?->docente?->nombre_completo ?? '________________________' }}</strong>
            <div class="text-muted">Docente</div>
        </div>
    </div>
    <div>
        <div style="border-top:1px solid #6c757d;padding-top:.5rem;">
            <strong>________________________</strong>
            <div class="text-muted">Coordinador Técnico Pedagógico</div>
        </div>
    </div>
</div>

</div>
@endsection
