@extends('layouts.admin')
@section('page-title', 'Sin Grupo Asignado')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-0" style="font-size:1.3rem;font-weight:800;color:var(--primary);">
            <i class="bi bi-person-fill-x me-2" style="color:#ef4444;"></i>Estudiantes Sin Grupo
        </h1>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            Activos sin matrícula en
            @if($schoolYear) <strong>{{ $schoolYear->nombre }}</strong>
            @else <span class="text-warning">sin año escolar activo</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.registro-academico.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <a href="{{ route('admin.matriculas.create') }}" class="btn btn-sm" style="background:var(--secondary);color:#fff;border-radius:8px;font-weight:600;">
            <i class="bi bi-plus-lg me-1"></i>Nueva Matrícula
        </a>
    </div>
</div>

@if($estudiantes->isEmpty())
<div class="text-center py-5" style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;">
    <i class="bi bi-check-circle-fill" style="font-size:3rem;color:#10b981;"></i>
    <p class="mt-3 mb-0 fw-bold">¡Todos los estudiantes tienen grupo asignado!</p>
</div>
@else
<div style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden;">
    <div style="padding:.85rem 1.25rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--primary);">
            <i class="bi bi-people me-1"></i>{{ $estudiantes->total() }} estudiantes sin grupo
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.84rem;">
            <thead style="background:#f8faff;">
                <tr>
                    <th class="ps-4">Estudiante</th>
                    <th>Cédula</th>
                    <th>Fecha Registro</th>
                    <th class="text-end pe-4">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estudiantes as $est)
                <tr>
                    <td class="ps-4">
                        <div style="font-weight:600;">{{ $est->apellidos }}, {{ $est->nombres }}</div>
                        <div style="font-size:.75rem;color:#9ca3af;">{{ $est->numero_matricula }}</div>
                    </td>
                    <td>{{ $est->cedula ?? '—' }}</td>
                    <td>{{ $est->created_at->format('d/m/Y') }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.matriculas.create', ['estudiante_id' => $est->id]) }}"
                           class="btn btn-sm btn-outline-primary" style="border-radius:6px;font-size:.75rem;">
                            Matricular
                        </a>
                        <a href="{{ route('admin.perfiles.estudiante', $est) }}"
                           class="btn btn-sm btn-outline-secondary ms-1" style="border-radius:6px;font-size:.75rem;">
                            Perfil
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($estudiantes->hasPages())
    <div class="px-4 py-3 border-top">{{ $estudiantes->links() }}</div>
    @endif
</div>
@endif
@endsection
