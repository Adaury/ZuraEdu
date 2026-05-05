@extends('layouts.admin')
@section('page-title', 'Indicadores de Aprendizaje')

@push('styles')
<style>
    .table th {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8faff;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
        font-size: .87rem;
    }
    .section-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--primary);
        padding-bottom: .4rem;
        border-bottom: 2px solid var(--primary);
        margin-bottom: 1.25rem;
        display: inline-block;
    }
    .badge-periodo {
        font-size: .72rem;
        font-weight: 600;
        padding: .28em .7em;
        border-radius: 20px;
        background: #dbeafe;
        color: #1d4ed8;
        white-space: nowrap;
    }
    .badge-activo {
        font-size: .75rem;
        font-weight: 600;
        padding: .3em .75em;
        border-radius: 20px;
        background: #dcfce7;
        color: #15803d;
        white-space: nowrap;
    }
    .badge-inactivo {
        font-size: .75rem;
        font-weight: 600;
        padding: .3em .75em;
        border-radius: 20px;
        background: #f1f5f9;
        color: #64748b;
        white-space: nowrap;
    }
    .indicador-desc {
        max-width: 320px;
        line-height: 1.4;
        color: #1e293b;
    }
    .filter-card {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
    }
    .table-card {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
    }

    [data-theme="dark"] .badge-periodo { background: #0c1f3f; color: #93c5fd; }
    [data-theme="dark"] .badge-activo { background: #052e16; color: #4ade80; }
    [data-theme="dark"] .badge-inactivo { background: #1e293b; color: #64748b; }
    [data-theme="dark"] .indicador-desc { color: #cbd5e1; }
    [data-theme="dark"] .filter-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .table-card { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--primary);">
            <i class="bi bi-list-check me-2"></i>Indicadores de Aprendizaje
        </h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Gestión de indicadores por asignatura, grado y período.
            <span class="badge ms-1" style="background:#e0e7ff;color:#3730a3;font-size:.75rem;font-weight:600;border-radius:20px;padding:.28em .75em;">
                {{ $indicadores->count() }} indicador{{ $indicadores->count() !== 1 ? 'es' : '' }}
            </span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.indicadores.lista-pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.indicadores.lista-excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <button type="button" class="btn btn-primary px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="bi bi-plus-circle me-2"></i>Nuevo Indicador
        </button>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2" style="font-size:.85rem;border-radius:10px;">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2" style="font-size:.85rem;border-radius:10px;">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filter bar --}}
<div class="filter-card shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.indicadores.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="asignatura_id" class="form-select form-select-sm">
                    <option value="">— Todas las asignaturas —</option>
                    @foreach($asignaturas as $asig)
                    <option value="{{ $asig->id }}" {{ request('asignatura_id') == $asig->id ? 'selected' : '' }}>
                        {{ $asig->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="grado_id" class="form-select form-select-sm">
                    <option value="">— Todos los grados —</option>
                    @foreach($grados as $grado)
                    <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                        {{ $grado->nombre }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="periodo" class="form-select form-select-sm">
                    <option value="">— Período —</option>
                    @foreach([1,2,3,4] as $p)
                    <option value="{{ $p }}" {{ request('periodo') == $p ? 'selected' : '' }}>
                        Período {{ $p }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary px-3">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                @if(request()->hasAny(['asignatura_id','grado_id','periodo']))
                <a href="{{ route('admin.indicadores.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x me-1"></i>Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table card --}}
<div class="table-card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:48px;">#</th>
                        <th>Grado</th>
                        <th>Asignatura</th>
                        <th class="text-center">Período</th>
                        <th>Descripción</th>
                        <th class="text-center" style="width:68px;">Orden</th>
                        <th class="text-center" style="width:100px;">Estado</th>
                        <th class="text-center pe-3" style="width:100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($indicadores as $ind)
                    <tr>
                        <td class="ps-3 text-muted" style="font-size:.8rem;">{{ $ind->id }}</td>
                        <td>
                            <span class="fw-semibold" style="font-size:.88rem;">
                                {{ $ind->grado?->nombre ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:.88rem;">{{ $ind->asignatura?->nombre ?? '—' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge-periodo">
                                Período {{ $ind->periodo_numero }}
                            </span>
                        </td>
                        <td>
                            <div class="indicador-desc">{{ $ind->descripcion }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border" style="font-size:.78rem;">
                                {{ $ind->orden }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($ind->activo)
                            <span class="badge-activo">
                                <i class="bi bi-check-circle-fill me-1"></i>Activo
                            </span>
                            @else
                            <span class="badge-inactivo">
                                <i class="bi bi-dash-circle me-1"></i>Inactivo
                            </span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary px-2"
                                        title="Editar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEdit-{{ $ind->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('admin.indicadores.destroy', $ind) }}"
                                      onsubmit="return confirm('¿Eliminar este indicador?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-list-check" style="font-size:3rem;opacity:.3;"></i>
                            <p class="mt-3 mb-0 fw-semibold">No hay indicadores registrados.</p>
                            <p class="mb-0" style="font-size:.82rem;">
                                @if(request()->hasAny(['asignatura_id','grado_id','periodo']))
                                    Intenta cambiar los filtros o
                                    <a href="{{ route('admin.indicadores.index') }}">limpiar la búsqueda</a>.
                                @else
                                    Crea el primer indicador con el botón <strong>Nuevo Indicador</strong>.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ================================================================
     MODAL CREAR
     ================================================================ --}}
<div class="modal fade" id="modalCrear" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border:1px solid #e5e7eb;">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title fw-bold" id="modalCrearLabel" style="color:var(--primary);font-size:1rem;">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Indicador de Aprendizaje
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.indicadores.store') }}">
                @csrf
                <div class="modal-body px-4 py-3">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Asignatura <span class="text-danger">*</span></label>
                        <select name="asignatura_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar asignatura —</option>
                            @foreach($asignaturas as $asig)
                            <option value="{{ $asig->id }}" {{ old('asignatura_id') == $asig->id ? 'selected' : '' }}>
                                {{ $asig->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Grado <span class="text-danger">*</span></label>
                        <select name="grado_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar grado —</option>
                            @foreach($grados as $grado)
                            <option value="{{ $grado->id }}" {{ old('grado_id') == $grado->id ? 'selected' : '' }}>
                                {{ $grado->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Período <span class="text-danger">*</span></label>
                        <select name="periodo_numero" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar período —</option>
                            @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ old('periodo_numero') == $p ? 'selected' : '' }}>
                                Período {{ $p }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción <span class="text-danger">*</span></label>
                        <textarea name="descripcion"
                                  class="form-control form-control-sm"
                                  rows="2"
                                  required
                                  placeholder="Describe el indicador de aprendizaje...">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                        <input type="number"
                               name="orden"
                               class="form-control form-control-sm"
                               style="width:100px;"
                               min="1" max="99"
                               value="{{ old('orden', 1) }}">
                    </div>

                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
                        <i class="bi bi-floppy me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================================================================
     MODALES EDITAR (one per indicator)
     ================================================================ --}}
@foreach($indicadores as $ind)
<div class="modal fade" id="modalEdit-{{ $ind->id }}" tabindex="-1"
     aria-labelledby="modalEditLabel-{{ $ind->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border:1px solid #e5e7eb;">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title fw-bold" id="modalEditLabel-{{ $ind->id }}"
                    style="color:var(--primary);font-size:1rem;">
                    <i class="bi bi-pencil me-2"></i>Editar Indicador #{{ $ind->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.indicadores.update', $ind) }}">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-3">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Asignatura <span class="text-danger">*</span></label>
                        <select name="asignatura_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar asignatura —</option>
                            @foreach($asignaturas as $asig)
                            <option value="{{ $asig->id }}" {{ $ind->asignatura_id == $asig->id ? 'selected' : '' }}>
                                {{ $asig->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Grado <span class="text-danger">*</span></label>
                        <select name="grado_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar grado —</option>
                            @foreach($grados as $grado)
                            <option value="{{ $grado->id }}" {{ $ind->grado_id == $grado->id ? 'selected' : '' }}>
                                {{ $grado->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Período <span class="text-danger">*</span></label>
                        <select name="periodo_numero" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar período —</option>
                            @foreach([1,2,3,4] as $p)
                            <option value="{{ $p }}" {{ $ind->periodo_numero == $p ? 'selected' : '' }}>
                                Período {{ $p }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Descripción <span class="text-danger">*</span></label>
                        <textarea name="descripcion"
                                  class="form-control form-control-sm"
                                  rows="2"
                                  required>{{ $ind->descripcion }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.83rem;">Orden</label>
                        <input type="number"
                               name="orden"
                               class="form-control form-control-sm"
                               style="width:100px;"
                               min="1" max="99"
                               value="{{ $ind->orden }}">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="activo" value="1"
                               id="activo-{{ $ind->id }}"
                               {{ $ind->activo ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="activo-{{ $ind->id }}"
                               style="font-size:.83rem;">
                            Activo
                        </label>
                    </div>

                </div>
                <div class="modal-footer border-top pt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
                        <i class="bi bi-floppy me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
