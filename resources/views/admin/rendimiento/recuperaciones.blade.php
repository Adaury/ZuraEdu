@extends('layouts.admin')
@section('page-title', 'Dashboard de Recuperaciones')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.35rem; font-weight:800; color:var(--primary); margin:0; }

.stat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.stat-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:1rem 1.25rem; text-align:center; }
.stat-val { font-size:2rem; font-weight:900; line-height:1; }
.stat-lbl { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-top:.3rem; }

.filter-bar { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:.85rem 1.1rem; margin-bottom:1.25rem; display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; }

.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.84rem; padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }
.table-card tbody tr:hover { background:#fff5f5; }

.mat-chip { display:inline-flex; align-items:center; background:#fee2e2; color:#991b1b; border-radius:6px;
            padding:.18rem .5rem; font-size:.72rem; font-weight:600; margin:.1rem; }
.riesgo-alto  { background:#fee2e2; color:#991b1b; }
.riesgo-medio { background:#fef3c7; color:#92400e; }
.riesgo-bajo  { background:#f3f4f6; color:#374151; }
</style>
@endpush

@section('content')
@if(isset($sinAnio))
<div class="alert alert-warning">Sin año escolar activo.</div>
@else

<div class="page-header">
    <div>
        <h1><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Dashboard de Recuperaciones</h1>
        <span class="text-muted" style="font-size:.85rem;">{{ $schoolYear->nombre }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.rendimiento.recuperaciones.pdf', request()->query()) }}" target="_blank"
           class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.rendimiento.recuperaciones.excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="stat-grid">
    <div class="stat-card" style="border-top:4px solid #ef4444;">
        <div class="stat-val" style="color:#dc2626;">{{ $resumen['total_reprobados'] }}</div>
        <div class="stat-lbl">Total en recuperación</div>
    </div>
    <div class="stat-card" style="border-top:4px solid #f59e0b;">
        <div class="stat-val" style="color:#d97706;">{{ $resumen['reprueba_1'] }}</div>
        <div class="stat-lbl">Reprueba 1 materia</div>
    </div>
    <div class="stat-card" style="border-top:4px solid #ef4444;">
        <div class="stat-val" style="color:#dc2626;">{{ $resumen['reprueba_2'] }}</div>
        <div class="stat-lbl">Reprueba 2 materias</div>
    </div>
    <div class="stat-card" style="border-top:4px solid #7f1d1d;background:#fff5f5;">
        <div class="stat-val" style="color:#7f1d1d;">{{ $resumen['reprueba_3plus'] }}</div>
        <div class="stat-lbl">Reprueba 3 o más</div>
    </div>
</div>

{{-- Filtro --}}
<form method="GET" action="{{ route('admin.rendimiento.recuperaciones') }}">
<div class="filter-bar">
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Grupo</label>
        <select name="grupo_id" class="form-select form-select-sm" style="min-width:200px;">
            <option value="">Todos los grupos</option>
            @foreach($grupos as $g)
                <option value="{{ $g->id }}" {{ $grupoId == $g->id ? 'selected' : '' }}>
                    {{ $g->grado->nombre ?? '' }} {{ $g->seccion->nombre ?? '' }}
                </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">Filtrar</button>
    <a href="{{ route('admin.rendimiento.recuperaciones') }}" class="btn btn-outline-secondary btn-sm" style="height:36px;">Todos</a>
</div>
</form>

@if($estudiantesRiesgo->isEmpty())
<div class="alert alert-success py-3" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>
    <strong>¡Excelente!</strong> No hay estudiantes con materias reprobadas en este grupo.
</div>
@else
<div class="table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Estudiante</th>
                <th>Grupo</th>
                <th>Materias Reprobadas</th>
                <th class="text-center">Total</th>
                <th class="text-center">Nota mín.</th>
                <th class="text-center">Riesgo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantesRiesgo as $i => $item)
            @php
                $nivel = $item['total_repr'] >= 3 ? 'alto' : ($item['total_repr'] >= 2 ? 'medio' : 'bajo');
                $nivelLabel = ['alto' => 'Alto', 'medio' => 'Medio', 'bajo' => 'Bajo'][$nivel];
            @endphp
            <tr>
                <td class="text-muted" style="font-size:.78rem;">{{ $i + 1 }}</td>
                <td>
                    <div class="fw-semibold">{{ $item['estudiante']?->apellidos ?? $item['estudiante']?->apellido ?? '' }}, {{ $item['estudiante']?->nombres ?? $item['estudiante']?->nombre ?? '' }}</div>
                    <div style="font-size:.72rem;color:#6b7280;">Matr. {{ $item['estudiante']?->matricula ?? '—' }}</div>
                </td>
                <td style="font-size:.8rem;">
                    {{ $item['matricula']?->grupo?->grado?->nombre ?? '—' }}
                    {{ $item['matricula']?->grupo?->seccion?->nombre ?? '' }}
                </td>
                <td>
                    @foreach($item['materias'] as $mat)
                        <span class="mat-chip">{{ $mat }}</span>
                    @endforeach
                </td>
                <td class="text-center fw-bold" style="color:#dc2626;">{{ $item['total_repr'] }}</td>
                <td class="text-center fw-bold" style="color:#dc2626;">
                    {{ $item['nota_minima'] !== null ? number_format($item['nota_minima'],1) : '—' }}
                </td>
                <td class="text-center">
                    <span class="badge riesgo-{{ $nivel }}" style="font-size:.72rem;border-radius:20px;padding:.25rem .65rem;">
                        {{ $nivelLabel }}
                    </span>
                </td>
                <td>
                    @if($item['estudiante'])
                    <a href="{{ route('admin.perfiles.estudiante', $item['estudiante']) }}"
                       class="btn btn-sm btn-outline-primary py-1" style="font-size:.75rem;">
                        <i class="bi bi-person"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endif
@endsection
