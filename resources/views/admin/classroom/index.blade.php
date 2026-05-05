@extends('layouts.admin')
@section('page-title', 'Classroom Virtual')
@section('content')

<x-breadcrumb :items="[
    ['label'=>'Dashboard','url'=>route('admin.dashboard')],
    ['label'=>'Classroom Virtual'],
]"/>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-easel2-fill me-2" style="color:#4f46e5;"></i>Classroom Virtual</h4>
        <p class="text-muted small mb-0">Monitoreo de aulas virtuales del año escolar {{ $schoolYear?->nombre }}</p>
    </div>
    <a href="{{ route('admin.classroom.create') }}" class="btn btn-primary btn-sm" style="border-radius:9px;">
        <i class="bi bi-plus-lg me-1"></i>Nueva Aula
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats globales --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Aulas Activas', $statsGlobal['total_activas'], '#4f46e5', 'bi-easel2-fill'],
        ['Total Entregas', $statsGlobal['total_entregas'], '#10b981', 'bi-send-check-fill'],
        ['Por Calificar', $statsGlobal['por_calificar'], $statsGlobal['por_calificar']>0?'#f59e0b':'#10b981', 'bi-inbox-fill'],
    ] as [$lbl,$val,$clr,$icn])
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:42px;height:42px;background:{{ $clr }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.1rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.2rem;color:{{ $clr }};">{{ $val }}</div>
                    <div class="text-muted small">{{ $lbl }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
<div class="card-body py-3">
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
        <div class="flex-grow-1" style="min-width:200px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                   placeholder="Buscar por aula, asignatura, docente...">
        </div>
        <div>
            <select name="activo" class="form-select form-select-sm" style="min-width:130px;">
                <option value="">Todos los estados</option>
                <option value="1" {{ request('activo')==='1'?'selected':'' }}>Activas</option>
                <option value="0" {{ request('activo')==='0'?'selected':'' }}>Inactivas</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px;"><i class="bi bi-search me-1"></i>Filtrar</button>
        @if(request('q') || request('activo'))
        <a href="{{ route('admin.classroom.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">✕</a>
        @endif
    </form>
</div>
</div>

{{-- Listado --}}
@if($clases->isEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-easel2" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
        <p class="fw-semibold mb-1">No hay aulas virtuales</p>
        <a href="{{ route('admin.classroom.create') }}" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-plus-lg me-1"></i>Crear primera aula
        </a>
    </div>
</div>
@else

{{-- Tabla de aulas con stats --}}
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
<thead style="background:#F8FAFC;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Aula Virtual</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Docente</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Grupo</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Materiales</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Estado</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Acciones</th>
    </tr>
</thead>
<tbody>
@foreach($clases as $clase)
@php $color = $clase->portada_color ?? '#4f46e5'; @endphp
<tr>
    <td class="px-4 py-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:10px;height:36px;background:{{ $color }};border-radius:3px;flex-shrink:0;"></div>
            <div>
                <a href="{{ route('admin.classroom.show', $clase) }}" class="fw-semibold text-decoration-none text-dark">
                    {{ $clase->nombre }}
                </a>
                <div class="text-muted small">{{ $clase->asignacion?->asignatura?->nombre }}</div>
            </div>
        </div>
    </td>
    <td class="py-3">
        <div style="font-size:.875rem;">{{ $clase->asignacion?->docente?->user?->name ?? '—' }}</div>
    </td>
    <td class="py-3">
        <span class="badge rounded-pill" style="background:#EEF2FF;color:#4f46e5;font-size:.75rem;">
            {{ $clase->asignacion?->grupo?->nombre ?? '—' }}
        </span>
    </td>
    <td class="py-3 text-center">
        <span class="fw-semibold">{{ $clase->materiales_count }}</span>
    </td>
    <td class="py-3 text-center">
        @if($clase->activo)
        <span class="badge bg-success">Activa</span>
        @else
        <span class="badge bg-secondary">Inactiva</span>
        @endif
    </td>
    <td class="py-3 text-center">
        <div class="d-flex justify-content-center gap-1">
            <a href="{{ route('admin.classroom.show', $clase) }}"
               class="btn btn-sm btn-outline-primary" style="border-radius:7px;padding:.3rem .6rem;" title="Ver aula">
                <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('admin.classroom.edit', $clase) }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius:7px;padding:.3rem .6rem;" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="{{ route('admin.classroom.destroy', $clase) }}"
                  onsubmit="return confirm('¿Eliminar esta aula virtual?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:7px;padding:.3rem .6rem;" title="Eliminar">
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
@if($clases->hasPages())
<div class="px-4 py-3 border-top">{{ $clases->links() }}</div>
@endif
</div>
@endif
@endsection
