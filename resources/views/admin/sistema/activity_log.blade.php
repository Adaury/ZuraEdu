@extends('layouts.admin')
@section('page-title', 'Log de Actividad')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-clipboard-data me-2"></i>Log de Actividad
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            Auditoría de acciones realizadas en el sistema
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.sistema.actividad.pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.sistema.actividad.excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Acción</label>
                <input type="text" name="accion" value="{{ request('accion') }}"
                       class="form-control form-control-sm" placeholder="ej: login, estudiante…" style="width:180px;">
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Usuario</label>
                <select name="user_id" class="form-select form-select-sm" style="width:180px;">
                    <option value="">Todos</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-funnel me-1"></i>Filtrar
            </button>
            <a href="{{ route('admin.sistema.actividad') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Limpiar
            </a>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.81rem;">
                <thead>
                    <tr style="background:var(--primary);color:#fff;">
                        <th class="ps-3 py-2">Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Modelo</th>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                @php
                    $color = str_contains($log->accion, 'eliminado') ? '#fee2e2'
                           : (str_contains($log->accion, 'creado') ? '#dcfce7'
                           : (str_contains($log->accion, 'login') ? '#dbeafe' : '#fff'));
                @endphp
                <tr style="background:{{ $color }}">
                    <td class="ps-3 text-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/y H:i') }}</td>
                    <td>{{ $log->user?->name ?? ('ID '.$log->user_id) }}</td>
                    <td><code style="font-size:.73rem;">{{ $log->accion }}</code></td>
                    <td style="color:#6b7280;font-size:.75rem;">{{ class_basename($log->modelo ?? '') }}</td>
                    <td style="color:#6b7280;">{{ $log->modelo_id }}</td>
                    <td style="max-width:320px;white-space:normal;font-size:.78rem;">{{ $log->descripcion }}</td>
                    <td style="color:#9ca3af;font-size:.73rem;">{{ $log->ip }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Sin registros para los filtros aplicados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>
@endsection
