@extends('layouts.admin')
@section('page-title', 'Evaluaciones Docentes')

@push('styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
    .page-header h1 { font-size:1.45rem; font-weight:800; color:var(--primary); margin:0; }
    .badge-nivel {
        font-size:.72rem; font-weight:700; padding:.28rem .65rem;
        border-radius:20px; letter-spacing:.03em; display:inline-block;
    }
    .badge-excelente  { background:#dcfce7; color:#166534; }
    .badge-bueno      { background:#dbeafe; color:#1e40af; }
    .badge-regular    { background:#fef9c3; color:#854d0e; }
    .badge-deficiente { background:#fee2e2; color:#991b1b; }
    .table-hover tbody tr:hover { background:#f8faff; }
    .btn-action { padding:.25rem .55rem; font-size:.78rem; border-radius:6px; line-height:1.4; }
    .stars { color:#f59e0b; font-size:.9rem; letter-spacing:1px; }
    [data-theme="dark"] .badge-excelente  { background:#052e16; color:#4ade80; }
    [data-theme="dark"] .badge-bueno      { background:#1e3a8a; color:#93c5fd; }
    [data-theme="dark"] .badge-regular    { background:#451a03; color:#fbbf24; }
    [data-theme="dark"] .badge-deficiente { background:#450a0a; color:#f87171; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Header --}}
<div class="page-header">
    <div>
        <h1><i class="bi bi-clipboard2-check me-2" style="color:var(--secondary);"></i>Evaluaciones Docentes</h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Registro y seguimiento del desempeño docente
            @if($evaluaciones->total() > 0)
                &nbsp;·&nbsp; <strong>{{ $evaluaciones->total() }}</strong> registros
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.evaluaciones-docentes.dashboard') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-bar-chart-line me-1"></i>Dashboard
        </a>
        <a href="{{ route('admin.evaluaciones-docentes.lista-pdf', request()->query()) }}"
           class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.evaluaciones-docentes.lista-excel', request()->query()) }}"
           class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.evaluaciones-docentes.create') }}" class="btn btn-sm px-3 py-2 fw-600"
           style="background:var(--primary);color:#fff;border-radius:8px;font-size:.85rem;font-weight:600;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Evaluación
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.evaluaciones-docentes.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;color:#6b7280;">Docente</label>
                <select name="docente_id" class="form-select form-select-sm" style="border-radius:8px;">
                    <option value="">Todos los docentes</option>
                    @foreach($docentes as $d)
                        <option value="{{ $d->id }}" {{ request('docente_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;color:#6b7280;">Período</label>
                <input type="text" name="periodo" value="{{ request('periodo') }}" placeholder="Ej: 2024-2025"
                       class="form-control form-control-sm" style="border-radius:8px;">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm px-3"
                        style="background:var(--primary);color:#fff;border-radius:8px;">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                @if(request()->anyFilled(['docente_id','periodo']))
                    <a href="{{ route('admin.evaluaciones-docentes.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    @if($evaluaciones->isEmpty())
        <div class="text-center py-5" style="color:#9ca3af;">
            <i class="bi bi-clipboard2-x" style="font-size:3rem;opacity:.4;display:block;margin-bottom:1rem;"></i>
            <p class="fw-600 mb-1" style="font-size:1.05rem;color:#374151;">No hay evaluaciones registradas</p>
            <p style="font-size:.88rem;">Comienza registrando la primera evaluación de desempeño.</p>
            <a href="{{ route('admin.evaluaciones-docentes.create') }}" class="btn btn-sm mt-1"
               style="background:var(--primary);color:#fff;border-radius:8px;">
                <i class="bi bi-plus-lg me-1"></i>Nueva Evaluación
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
                <thead style="background:#f8faff;border-bottom:2px solid #e5e7eb;">
                    <tr>
                        <th class="ps-4 py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Docente</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Período</th>
                        <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Promedio</th>
                        <th class="py-3 text-center" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Nivel</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Evaluador</th>
                        <th class="py-3" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Fecha</th>
                        <th class="py-3 pe-4 text-end" style="color:#6b7280;font-weight:600;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluaciones as $ev)
                    @php $nivel = $ev->nivelDesempeno(); @endphp
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="fw-600" style="color:#111827;">{{ $ev->docente->nombre_completo }}</div>
                            <div style="font-size:.75rem;color:#9ca3af;">{{ $ev->docente->especialidad ?? '' }}</div>
                        </td>
                        <td class="py-3" style="color:#374151;">{{ $ev->periodo_evaluado }}</td>
                        <td class="py-3 text-center">
                            <span style="font-size:1.15rem;font-weight:800;color:{{ $nivel['text'] }};">
                                {{ number_format($ev->promedio, 2) }}
                            </span>
                            <div class="stars">
                                @for($i = 1; $i <= 5; $i++)
                                    {!! $i <= round($ev->promedio) ? '&#9733;' : '&#9734;' !!}
                                @endfor
                            </div>
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge-nivel badge-{{ strtolower($nivel['label']) }}">
                                {{ $nivel['label'] }}
                            </span>
                        </td>
                        <td class="py-3" style="color:#374151;font-size:.82rem;">
                            {{ $ev->evaluador->name ?? '—' }}
                        </td>
                        <td class="py-3" style="color:#6b7280;font-size:.82rem;">
                            {{ $ev->created_at->format('d/m/Y') }}
                        </td>
                        <td class="py-3 pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.evaluaciones-docentes.show', $ev) }}"
                                   class="btn btn-action btn-outline-primary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.evaluaciones-docentes.pdf', $ev) }}"
                                   class="btn btn-action btn-outline-danger" title="Descargar PDF" target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <button type="button" class="btn btn-action btn-outline-danger"
                                        title="Eliminar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDel{{ $ev->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Modal eliminar --}}
                    <div class="modal fade" id="modalDel{{ $ev->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
                            <div class="modal-content border-0 shadow" style="border-radius:16px;">
                                <div class="modal-body p-4 text-center">
                                    <div class="mb-3" style="font-size:2.5rem;color:var(--secondary);">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <h5 class="fw-700 mb-2" style="color:#111827;">¿Eliminar evaluación?</h5>
                                    <p class="text-muted mb-4" style="font-size:.88rem;">
                                        Se eliminará la evaluación de <strong>{{ $ev->docente->nombre_completo }}</strong>
                                        correspondiente al período <strong>{{ $ev->periodo_evaluado }}</strong>.
                                    </p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                                        <form method="POST" action="{{ route('admin.evaluaciones-docentes.destroy', $ev) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn px-4"
                                                    style="background:var(--secondary);color:#fff;border-radius:8px;">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($evaluaciones->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="text-muted mb-0" style="font-size:.82rem;">
                    Mostrando {{ $evaluaciones->firstItem() }}–{{ $evaluaciones->lastItem() }} de {{ $evaluaciones->total() }} evaluaciones
                </p>
                {{ $evaluaciones->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
