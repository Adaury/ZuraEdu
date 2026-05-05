@extends('layouts.admin')
@section('page-title', 'Auditoría de Calificaciones')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
.page-header h1 { font-size:1.3rem; font-weight:800; color:var(--primary); margin:0; }
.filter-bar { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:.85rem 1.1rem; margin-bottom:1.25rem; display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; }
.filter-bar select, .filter-bar input { font-size:.83rem; padding:.4rem .75rem; border-radius:8px; border:1px solid #d1d5db; background:#f9fafb; height:36px; }
.table-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.table-card table { margin:0; }
.table-card thead th { background:#f8fafc; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
.table-card tbody td { font-size:.82rem; padding:.7rem 1rem; vertical-align:middle; border-bottom:1px solid #f3f4f6; }
.table-card tbody tr:last-child td { border-bottom:none; }
.nota-diff { display:flex; align-items:center; gap:.4rem; }
.nota-old { color:#6b7280; text-decoration:line-through; }
.nota-new { font-weight:800; }
.nota-up   { color:#15803d; }
.nota-down { color:#dc2626; }
.nota-same { color:#92400e; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1><i class="bi bi-clock-history me-2" style="color:var(--primary)"></i>Auditoría de Calificaciones</h1>
</div>

<form method="GET" action="{{ route('admin.calificaciones.auditoria') }}">
<div class="filter-bar">
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Usuario</label>
        <select name="user_id">
            <option value="">Todos los usuarios</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id')==$u->id ? 'selected':'' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Asignatura</label>
        <input type="text" name="asignatura" value="{{ request('asignatura') }}" placeholder="Nombre de materia…">
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Desde</label>
        <input type="date" name="desde" value="{{ request('desde') }}">
    </div>
    <div>
        <label style="font-size:.75rem;font-weight:600;color:#6b7280;display:block;margin-bottom:.2rem;">Hasta</label>
        <input type="date" name="hasta" value="{{ request('hasta') }}">
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="height:36px;">Filtrar</button>
    <a href="{{ route('admin.calificaciones.auditoria') }}" class="btn btn-outline-secondary btn-sm" style="height:36px;">Limpiar</a>
</div>
</form>

<div class="table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Estudiante</th>
                <th>Materia / Grupo</th>
                <th>Campo</th>
                <th>Cambio</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($audits as $a)
            @php
                $anterior = $a->valor_anterior;
                $nuevo    = $a->valor_nuevo;
                $diff     = $anterior !== null && $nuevo !== null ? $nuevo - $anterior : null;
                $cls      = $diff === null ? 'nota-same' : ($diff > 0 ? 'nota-up' : ($diff < 0 ? 'nota-down' : 'nota-same'));
            @endphp
            <tr>
                <td style="white-space:nowrap;color:#6b7280;font-size:.78rem;">
                    {{ $a->created_at->format('d/m/Y') }}<br>
                    <span style="font-size:.72rem;">{{ $a->created_at->format('H:i') }}</span>
                </td>
                <td>
                    <span class="fw-semibold" style="font-size:.82rem;">{{ $a->user?->name ?? '—' }}</span>
                </td>
                <td>
                    <span style="font-size:.82rem;">
                        {{ $a->matricula?->estudiante?->apellidos ?? '' }},
                        {{ $a->matricula?->estudiante?->nombres ?? '—' }}
                    </span>
                </td>
                <td>
                    <div style="font-size:.82rem;font-weight:600;">{{ $a->asignacion?->asignatura?->nombre ?? '—' }}</div>
                    <div style="font-size:.72rem;color:#6b7280;">
                        {{ $a->asignacion?->grupo?->grado?->nombre ?? '' }}
                        {{ $a->asignacion?->grupo?->seccion?->nombre ?? '' }}
                    </div>
                </td>
                <td style="font-size:.78rem;color:#374151;font-family:monospace;">{{ $a->campo }}</td>
                <td>
                    <div class="nota-diff">
                        @if($anterior !== null)
                            <span class="nota-old">{{ number_format($anterior,1) }}</span>
                            <i class="bi bi-arrow-right" style="font-size:.7rem;color:#9ca3af;"></i>
                        @else
                            <span style="color:#9ca3af;font-size:.78rem;">Nuevo</span>
                            <i class="bi bi-arrow-right" style="font-size:.7rem;color:#9ca3af;"></i>
                        @endif
                        <span class="nota-new {{ $cls }}">
                            {{ $nuevo !== null ? number_format($nuevo,1) : '—' }}
                        </span>
                        @if($diff !== null && $diff != 0)
                            <span style="font-size:.7rem;color:{{ $diff > 0 ? '#15803d' : '#dc2626' }};">
                                ({{ $diff > 0 ? '+' : '' }}{{ number_format($diff,1) }})
                            </span>
                        @endif
                    </div>
                </td>
                <td style="font-size:.75rem;color:#9ca3af;font-family:monospace;">{{ $a->ip ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-4 text-muted">Sin registros de auditoría.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3 d-flex justify-content-center">{{ $audits->links() }}</div>
@endsection
