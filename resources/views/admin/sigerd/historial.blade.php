@extends('layouts.admin')
@section('title', 'Historial de Exportaciones SIGERD')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-clock-history me-2"></i>Historial de Exportaciones</h1>
        <a href="{{ route('admin.sigerd.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="nomina_matricula" {{ request("tipo")=="nomina_matricula" ? "selected" : "" }}>Nomina Matricula</option>
                        <option value="calificaciones" {{ request("tipo")=="calificaciones" ? "selected" : "" }}>Calificaciones</option>
                        <option value="docentes" {{ request("tipo")=="docentes" ? "selected" : "" }}>Docentes</option>
                        <option value="asistencia" {{ request("tipo")=="asistencia" ? "selected" : "" }}>Asistencia</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Desde</label>
                    <input type="date" name="desde" class="form-control form-control-sm" value="{{ request('desde') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Hasta</label>
                    <input type="date" name="hasta" class="form-control form-control-sm" value="{{ request('hasta') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Tipo</th><th>Grupo</th><th>Periodo</th><th>Formato</th><th>Registros</th><th>Usuario</th><th>Fecha</th><th>Notas</th></tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                <tr>
                    <td><span class="badge bg-secondary">{{ $log->tipo }}</span></td>
                    <td>{{ $log->grupo?->nombre_completo ?? 'Todos' }}</td>
                    <td>{{ $log->periodo?->nombre ?? 'N/A' }}</td>
                    <td><span class="badge bg-info text-dark">{{ strtoupper($log->formato) }}</span></td>
                    <td>{{ $log->total_registros }}</td>
                    <td>{{ $log->user?->name ?? 'N/A' }}</td>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td><small>{{ Str::limit($log->notas, 40) }}</small></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay exportaciones registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer"><div class="d-flex justify-content-center">{{ $logs->links() }}</div></div>
        @endif
    </div>
</div>
@endsection
