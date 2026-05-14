@extends('layouts.admin')
@section('page-title', 'Solicitudes de Docentes')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-send-fill me-2 text-primary"></i>Solicitudes de Docentes</h4>
        <p class="text-muted small mb-0">Permisos, licencias y gestiones enviadas por el personal docente</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people me-1"></i>Solicitudes de Representantes
        </a>
        <a href="{{ route('admin.solicitudes-est.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-mortarboard me-1"></i>Solicitudes de Estudiantes
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Pendientes',   $stats['pendientes'], 'warning', 'clock-fill'],
        ['En proceso',   $stats['en_proceso'], 'primary', 'arrow-repeat'],
        ['Recibidas hoy',$stats['total_hoy'],  'info',    'calendar-check-fill'],
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
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar docente o asunto..."
               class="form-control form-control-sm" style="max-width:260px;">
        <select name="estado" class="form-select form-select-sm" style="max-width:150px;">
            <option value="">Todos los estados</option>
            @foreach($estados as $k => $v)
            <option value="{{ $k }}" {{ request('estado') === $k ? 'selected' : '' }}>{{ $v['label'] }}</option>
            @endforeach
        </select>
        <select name="tipo" class="form-select form-select-sm" style="max-width:220px;">
            <option value="">Todos los tipos</option>
            @foreach($tipos as $k => $v)
            <option value="{{ $k }}" {{ request('tipo') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        @if(request()->anyFilled(['q','estado','tipo']))
        <a href="{{ route('admin.solicitudes-docente.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        @endif
    </div>
</form>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($solicitudes->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="mt-2 mb-0">No hay solicitudes que coincidan con los filtros</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Docente</th>
                        <th>Tipo</th>
                        <th>Asunto</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th>Enviada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($solicitudes as $sol)
                    @php $cfg = $sol->estado_config; @endphp
                    <tr>
                        <td class="text-muted">{{ $sol->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $sol->docente?->nombre_completo ?? '—' }}</div>
                            <small class="text-muted">{{ $sol->docente?->cedula }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:.72rem;">
                                {{ $sol->tipo_label }}
                            </span>
                        </td>
                        <td style="max-width:220px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $sol->asunto }}
                            </div>
                        </td>
                        <td>
                            @if($sol->fecha_inicio)
                            <span style="font-size:.78rem;">
                                {{ $sol->fecha_inicio->format('d/m/Y') }}
                                @if($sol->fecha_fin && $sol->fecha_fin != $sol->fecha_inicio)
                                – {{ $sol->fecha_fin->format('d/m/Y') }}
                                @endif
                            </span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill"
                                  style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};border:1px solid {{ $cfg['color'] }}40;font-size:.72rem;">
                                {{ $cfg['label'] }}
                            </span>
                        </td>
                        <td class="text-muted" style="font-size:.78rem;">{{ $sol->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('admin.solicitudes-docente.show', $sol) }}"
                               class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2 border-top">
            {{ $solicitudes->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
