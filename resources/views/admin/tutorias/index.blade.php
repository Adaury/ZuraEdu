@extends('layouts.admin')

@section('page-title', 'Tutorías')

@push('styles')
<style>
    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(30,58,110,.05);
    }
    .table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: #6b7280;
        padding: .75rem 1rem;
        white-space: nowrap;
    }
    .table tbody td {
        padding: .75rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        font-size: .84rem;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover td { background: #fafbff; }
    .docente-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), #3b82f6);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .68rem;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }
    .grupo-chip {
        background: #eef2ff;
        color: var(--primary);
        border-radius: 6px;
        padding: .2rem .6rem;
        font-size: .76rem;
        font-weight: 700;
    }
    .badge-activo   { background: #d1fae5; color: #065f46; border-radius: 20px; padding: .15rem .6rem; font-size: .73rem; font-weight: 700; }
    .badge-inactivo { background: #f3f4f6; color: #6b7280; border-radius: 20px; padding: .15rem .6rem; font-size: .73rem; font-weight: 700; }
    .empty-state { text-align:center; padding: 3.5rem 2rem; color: #9ca3af; }
    .empty-state i { font-size: 2.5rem; display:block; margin-bottom: .75rem; color: #d1d5db; }
    [data-theme="dark"] .table-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .table thead th { background: #1e293b; border-color: #334155; color: #94a3b8; }
    [data-theme="dark"] .table tbody td { border-color: #1e293b; color: #e2e8f0; }
    [data-theme="dark"] .table tbody tr:hover td { background: #273548; }
    [data-theme="dark"] .grupo-chip { background: #1e3a6e; color: #93c5fd; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-person-hearts me-2"></i>Tutorías
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
            @if($schoolYear)
                Año escolar: <strong>{{ $schoolYear->nombre }}</strong> &mdash;
            @endif
            {{ $tutorias->count() }} tutor{{ $tutorias->count() !== 1 ? 'es' : '' }} asignado{{ $tutorias->count() !== 1 ? 's' : '' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.tutorias.create') }}"
           class="btn btn-sm fw-semibold"
           style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1rem;">
            <i class="bi bi-plus-lg me-1"></i>Asignar Tutor
        </a>
    </div>
</div>

{{-- Alertas --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filtro por año escolar --}}
@if($years->count() > 1)
<form method="GET" action="{{ route('admin.tutorias.index') }}" class="d-flex align-items-center gap-2 mb-3">
    <label class="text-muted fw-semibold" style="font-size:.8rem;white-space:nowrap;">Año escolar:</label>
    <select name="year_id" onchange="this.form.submit()"
            class="form-select form-select-sm" style="max-width:200px;border-radius:8px;font-size:.82rem;">
        @foreach($years as $y)
            <option value="{{ $y->id }}" @selected($y->id == $yearId)>{{ $y->nombre }}</option>
        @endforeach
    </select>
</form>
@endif

{{-- Tabla --}}
@if($tutorias->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <i class="bi bi-person-hearts"></i>
        <h6 class="fw-semibold mb-1" style="color:#6b7280;">No hay tutores asignados</h6>
        <p class="mb-3" style="font-size:.83rem;">Asigna un docente como tutor de cada grupo para gestionar el seguimiento estudiantil.</p>
        <a href="{{ route('admin.tutorias.create') }}" class="btn btn-sm fw-semibold"
           style="background:var(--primary);color:#fff;border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Asignar primer tutor
        </a>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Docente Tutor</th>
                    <th>Grupo</th>
                    <th>Sesiones</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tutorias as $tutoria)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="docente-avatar">
                                {{ strtoupper(substr($tutoria->docente->apellidos ?? 'D', 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold" style="color:#1e293b;font-size:.84rem;line-height:1.2;">
                                    {{ $tutoria->docente->nombre_completo ?? '—' }}
                                </div>
                                @if($tutoria->docente?->especialidad)
                                    <div style="font-size:.71rem;color:#9ca3af;">{{ $tutoria->docente->especialidad }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="grupo-chip">
                            {{ $tutoria->grupo->nombre_completo ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-size:.82rem;font-weight:600;color:#374151;">
                            {{ $tutoria->sesiones->count() }}
                        </span>
                        <span style="font-size:.73rem;color:#9ca3af;"> sesión(es)</span>
                    </td>
                    <td style="max-width:200px;">
                        <span style="font-size:.8rem;color:#6b7280;">
                            {{ $tutoria->descripcion ? \Str::limit($tutoria->descripcion, 60) : '—' }}
                        </span>
                    </td>
                    <td>
                        @if($tutoria->activo)
                            <span class="badge-activo"><i class="bi bi-check-circle-fill me-1"></i>Activa</span>
                        @else
                            <span class="badge-inactivo">Inactiva</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('admin.tutorias.sesiones', $tutoria) }}"
                               class="btn btn-sm"
                               style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:6px;font-size:.75rem;padding:.3rem .6rem;"
                               title="Ver sesiones">
                                <i class="bi bi-calendar-check"></i>
                            </a>
                            <a href="{{ route('admin.tutorias.informe-pdf', $tutoria) }}"
                               class="btn btn-sm"
                               style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;font-size:.75rem;padding:.3rem .6rem;"
                               title="Informe PDF" target="_blank">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <form action="{{ route('admin.tutorias.destroy', $tutoria) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar la tutoría del grupo {{ $tutoria->grupo->nombre_completo ?? '' }}? Se eliminarán todas las sesiones registradas.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm"
                                        style="background:#fff0f0;color:#dc2626;border:1px solid #fecaca;border-radius:6px;font-size:.75rem;padding:.3rem .6rem;"
                                        title="Eliminar">
                                    <i class="bi bi-trash3"></i>
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
@endif

@endsection
