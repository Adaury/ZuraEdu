@extends('layouts.admin')
@section('title', 'Solicitudes de Estudiantes')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
    <div>
        <h1 class="h4 fw-bold mb-0">Solicitudes de Estudiantes</h1>
        <p class="text-muted small mb-0">Peticiones enviadas directamente por los estudiantes</p>
    </div>
    <a href="{{ route('admin.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people"></i> Ver solicitudes de representantes
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Pendientes',  $stats['pendientes'], 'warning', 'clock-fill'],
        ['En proceso',  $stats['en_proceso'], 'primary', 'arrow-repeat'],
        ['Recibidas hoy',$stats['total_hoy'], 'info',    'calendar-check-fill'],
    ] as [$lbl, $val, $color, $icon])
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-{{ $color }} bg-opacity-10">
                    <i class="bi bi-{{ $icon }} text-{{ $color }} fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4 lh-1">{{ $val }}</div>
                    <div class="text-muted small">{{ $lbl }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filtros --}}
<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex gap-2 flex-wrap">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar estudiante o asunto..."
               class="form-control form-control-sm" style="max-width:260px;">
        <select name="estado" class="form-select form-select-sm" style="max-width:150px;">
            <option value="">Todos los estados</option>
            @foreach($estados as $k => $v)
            <option value="{{ $k }}" {{ request('estado') === $k ? 'selected' : '' }}>{{ $v['label'] }}</option>
            @endforeach
        </select>
        <select name="tipo" class="form-select form-select-sm" style="max-width:200px;">
            <option value="">Todos los tipos</option>
            @foreach($tipos as $k => $v)
            <option value="{{ $k }}" {{ request('tipo') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filtrar</button>
        <a href="{{ route('admin.solicitudes-est.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Estudiante</th>
                    <th>Tipo</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitudes as $sol)
                @php $ec = $estados[$sol->estado] ?? $estados['pendiente']; @endphp
                <tr>
                    <td>
                        <div class="fw-semibold small">{{ $sol->estudiante?->nombre_completo ?? '—' }}</div>
                    </td>
                    <td><span class="badge bg-light text-dark small">{{ $tipos[$sol->tipo] ?? $sol->tipo }}</span></td>
                    <td class="small" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $sol->asunto }}</td>
                    <td>
                        <span class="badge" style="background:{{ $ec['bg'] }};color:{{ $ec['color'] }};">{{ $ec['label'] }}</span>
                    </td>
                    <td class="small text-muted">{{ $sol->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('admin.solicitudes-est.show', $sol) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay solicitudes con los filtros aplicados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($solicitudes->hasPages())
    <div class="card-footer bg-white">{{ $solicitudes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
