@extends('layouts.admin')
@section('page-title', 'Actas de Reuniones')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-journal-text me-2"></i>Actas de Reuniones
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Gestión de reuniones institucionales y sus acuerdos
        </p>
    </div>
    <a href="{{ route('admin.reuniones.create') }}" class="btn btn-primary" style="border-radius:8px;">
        <i class="bi bi-plus-lg me-1"></i>Nueva Reunión
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('admin.reuniones.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:600;">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       class="form-control form-control-sm" placeholder="Título, lugar…">
            </div>
            <div class="col-sm-3">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:600;">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $val => $lbl)
                        <option value="{{ $val }}" @selected(request('tipo') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:600;">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $val => $lbl)
                        <option value="{{ $val }}" @selected(request('estado') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('admin.reuniones.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead>
                    <tr style="background:var(--primary);color:#fff;">
                        <th class="ps-3 py-2">Título</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Lugar</th>
                        <th>Convocante</th>
                        <th>Acuerdos</th>
                        <th>Estado</th>
                        <th class="pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reuniones as $r)
                <tr>
                    <td class="ps-3 fw-semibold py-2">{{ $r->titulo }}</td>
                    <td>
                        <span class="badge" style="
                            background:{{ match($r->tipo) {
                                'consejo_directivo' => '#1e3a8a',
                                'reunion_padres'    => '#065f46',
                                'reunion_docentes'  => '#5b21b6',
                                'comite'            => '#92400e',
                                default             => '#374151',
                            } }};color:#fff;font-size:.7rem;">
                            {{ $r->tipoLabel() }}
                        </span>
                    </td>
                    <td class="text-nowrap">{{ $r->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->lugar ?: '—' }}</td>
                    <td>{{ $r->convocante?->name ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary" style="font-size:.72rem;">
                            {{ $r->acuerdos_count ?? $r->acuerdos->count() }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $r->estadoBadgeClass() }}" style="font-size:.72rem;">
                            {{ $r->estadoLabel() }}
                        </span>
                    </td>
                    <td class="pe-3">
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.reuniones.show', $r) }}"
                               class="btn btn-outline-primary btn-sm py-0 px-2" title="Ver / Acuerdos">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <a href="{{ route('admin.reuniones.edit', $r) }}"
                               class="btn btn-outline-secondary btn-sm py-0 px-2" title="Editar">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <a href="{{ route('admin.reuniones.acta_pdf', $r) }}" target="_blank"
                               class="btn btn-outline-danger btn-sm py-0 px-2" title="PDF Acta">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.reuniones.destroy', $r) }}"
                                  onsubmit="return confirm('¿Eliminar esta reunión y todos sus acuerdos?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm py-0 px-2" title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                        No hay reuniones registradas.
                        <a href="{{ route('admin.reuniones.create') }}">Crear la primera</a>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($reuniones->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $reuniones->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
