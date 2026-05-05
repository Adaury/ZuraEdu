@extends('layouts.admin')

@section('page-title', 'Malla Curricular')

@push('styles')
<style>
    .filter-bar { background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:.75rem 1rem; }
    .area-badge-academica { background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe; }
    .area-badge-tecnica   { background:#fee2e2;color:#dc2626;border:1px solid #fca5a5; }

    [data-theme="dark"] .filter-bar { background: #162032; border-color: #334155; }
    [data-theme="dark"] .area-badge-academica { background: #0c1f3f; color: #93c5fd; border-color: #1d4ed8; }
    [data-theme="dark"] .area-badge-tecnica { background: #1c0000; color: #f87171; border-color: #dc2626; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-grid-3x3 me-2"></i>Malla Curricular
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">
            Asignaturas por grado según el currículo MINERD — Nivel Secundario
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.malla.matriz') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-table me-1"></i>Matriz Completa
        </a>
        <a href="{{ route('admin.malla.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Agregar Asignatura
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros --}}
<div class="filter-bar mb-3">
    <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:.82rem;font-weight:600;white-space:nowrap;">Área:</label>
            <select name="area" class="form-select form-select-sm" style="max-width:160px;">
                <option value="">Todas</option>
                <option value="academica" {{ request('area') === 'academica' ? 'selected' : '' }}>Académica</option>
                <option value="tecnica"   {{ request('area') === 'tecnica'   ? 'selected' : '' }}>Técnica</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:.82rem;font-weight:600;white-space:nowrap;">Grado:</label>
            <select name="grado_id" class="form-select form-select-sm" style="max-width:180px;">
                <option value="">Todos</option>
                @foreach($grados as $g)
                <option value="{{ $g->id }}" {{ request('grado_id') == $g->id ? 'selected' : '' }}>
                    {{ $g->nombre }}
                </option>
                @endforeach
            </select>
        </div>
        @if($especialidades->isNotEmpty())
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:.82rem;font-weight:600;white-space:nowrap;">Especialidad:</label>
            <select name="especialidad_id" class="form-select form-select-sm" style="max-width:200px;">
                <option value="">Todas</option>
                @foreach($especialidades as $esp)
                <option value="{{ $esp->id }}" {{ request('especialidad_id') == $esp->id ? 'selected' : '' }}>
                    {{ $esp->nombre }}
                </option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-funnel me-1"></i>Filtrar
        </button>
        <a href="{{ route('admin.malla.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x me-1"></i>Limpiar
        </a>
    </form>
</div>

@if($malla->isEmpty())
<div class="empty-state-enhanced">
    <div class="empty-illustration"><i class="bi bi-grid-3x3"></i></div>
    <div class="empty-title">Malla curricular vacía</div>
    <div class="empty-desc">
        Agrega las asignaturas del currículo dominicano por grado y área.
        Puedes agregar las materias del MINERD para el nivel secundario.
    </div>
    <div class="empty-actions">
        <a href="{{ route('admin.malla.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Agregar Primera Asignatura
        </a>
    </div>
</div>
@else

{{-- Agrupar por grado --}}
@php $porGrado = $malla->groupBy('grado_id'); @endphp

@foreach($porGrado as $gradoId => $items)
@php $grado = $items->first()->grado; @endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;border-radius:8px;background:#1e3a6e;color:#fff;
                        display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;">
                {{ $grado->nivel ?? '?' }}
            </div>
            <span class="fw-bold" style="color:#1e293b;">{{ $grado->nombre ?? 'Grado ' . $gradoId }}</span>
            @php $ciclo = ($grado->nivel ?? 0) <= 3 ? 'Primer Ciclo' : 'Segundo Ciclo'; @endphp
            <span class="badge text-bg-secondary" style="font-size:.65rem;">{{ $ciclo }}</span>
        </div>
        <span class="badge text-bg-light" style="font-size:.7rem;">{{ $items->count() }} asignatura(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="font-size:.75rem;background:#f8fafc;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                    <tr>
                        <th class="px-3 py-2">Asignatura</th>
                        <th class="px-3 py-2 text-center">Área</th>
                        <th class="px-3 py-2 text-center">Horas/Sem.</th>
                        <th class="px-3 py-2">Especialidad</th>
                        <th class="px-3 py-2 text-center">Obligatoria</th>
                        <th class="px-3 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody style="font-size:.84rem;">
                    @foreach($items->sortBy('orden_display') as $entry)
                    <tr>
                        <td class="px-3 py-2 fw-semibold">{{ $entry->asignatura->nombre ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="badge area-badge-{{ $entry->area }}" style="font-size:.68rem;">
                                {{ ucfirst($entry->area) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center fw-bold" style="color:#1e3a6e;">
                            {{ $entry->horas_semanales }}h
                        </td>
                        <td class="px-3 py-2">
                            @if($entry->especialidad)
                            <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.78rem;">
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $entry->especialidad->color }};flex-shrink:0;"></span>
                                {{ $entry->especialidad->nombre }}
                            </span>
                            @else
                            <span class="text-muted" style="font-size:.78rem;">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($entry->es_obligatoria)
                            <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                            <i class="bi bi-dash-circle text-muted"></i>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('admin.malla.edit', $entry) }}"
                                   class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:.15rem .4rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.malla.destroy', $entry) }}"
                                      onsubmit="return confirm('¿Eliminar esta entrada?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger"
                                            style="font-size:.7rem;padding:.15rem .4rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

<p class="text-muted mt-2" style="font-size:.78rem;">
    {{ $malla->count() }} entrada(s) en la malla curricular
    @if(request()->hasAny(['area','grado_id','especialidad_id'])) (filtrada) @endif
</p>
@endif
@endsection
