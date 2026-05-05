@extends('layouts.admin')
@section('page-title', 'Observaciones de Docentes')

@section('content')
<div class="container-fluid py-3">

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-chat-square-text text-warning me-2"></i>Observaciones de Docentes
        </h4>
        @if($schoolYear)
        <small class="text-muted">{{ $schoolYear->nombre }}</small>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.observaciones.pdf', request()->query()) }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        <a href="{{ route('admin.observaciones.excel', request()->query()) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
    </div>
</div>

{{-- Tarjetas resumen por tipo --}}
<div class="row g-2 mb-3">
    @foreach($tipos as $key => $tipo)
    @php $count = $totalesTipo[$key] ?? 0; @endphp
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid {{ $tipo['color'] }} !important;">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi {{ $tipo['icon'] }}" style="color:{{ $tipo['color'] }};font-size:1.4rem;"></i>
                <div>
                    <div class="fw-bold fs-5">{{ $count }}</div>
                    <div class="small text-muted">{{ $tipo['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $key => $t)
                    <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>
                        {{ $t['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            @if(!$isDocente && $docentes->isNotEmpty())
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Docente</label>
                <select name="docente_id" class="form-select form-select-sm">
                    <option value="">Todos los docentes</option>
                    @foreach($docentes as $doc)
                    <option value="{{ $doc->id }}" {{ request('docente_id') == $doc->id ? 'selected' : '' }}>
                        {{ $doc->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Grupo</label>
                <select name="grupo_id" class="form-select form-select-sm">
                    <option value="">Todos los grupos</option>
                    @foreach($grupos as $gr)
                    <option value="{{ $gr->id }}" {{ request('grupo_id') == $gr->id ? 'selected' : '' }}>
                        {{ $gr->nombre_completo }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm mb-1">Visibilidad</label>
                <select name="privada" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="0" {{ request('privada') === '0' ? 'selected' : '' }}>Públicas</option>
                    <option value="1" {{ request('privada') === '1' ? 'selected' : '' }}>Privadas</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm mb-1">Buscar estudiante/texto</label>
                <input type="text" name="q" class="form-control form-control-sm"
                    placeholder="Nombre o texto..." value="{{ request('q') }}">
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('admin.observaciones.index') }}" class="btn btn-outline-secondary btn-sm">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($observaciones->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-chat-square-x" style="font-size:2.5rem;"></i>
            <div class="mt-2 fw-semibold">No hay observaciones registradas</div>
            @if(request()->hasAny(['tipo','docente_id','grupo_id','privada','q']))
            <div class="mt-1 small">Intenta con otros filtros.</div>
            @endif
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:110px;">Tipo</th>
                        <th>Estudiante</th>
                        <th>Grupo / Materia</th>
                        @if(!$isDocente)<th>Docente</th>@endif
                        <th style="min-width:200px;">Observación</th>
                        <th style="width:80px;">Visib.</th>
                        <th style="width:90px;">Fecha</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($observaciones as $obs)
                @php $ti = $obs->tipo_info; @endphp
                <tr>
                    <td>
                        <span class="badge" style="background:{{ $ti['color'] }}20;color:{{ $ti['color'] }};font-weight:700;font-size:.75rem;">
                            <i class="bi {{ $ti['icon'] }} me-1"></i>{{ $ti['label'] }}
                        </span>
                    </td>
                    <td class="fw-semibold">
                        {{ $obs->estudiante?->nombre_completo ?? '—' }}
                    </td>
                    <td class="text-muted small">
                        {{ $obs->asignacion?->grupo?->nombre_completo ?? '—' }}<br>
                        <span style="color:#6366f1;">{{ $obs->asignacion?->asignatura?->nombre ?? '—' }}</span>
                    </td>
                    @if(!$isDocente)
                    <td class="text-muted small">{{ $obs->docente?->nombre_completo ?? '—' }}</td>
                    @endif
                    <td>
                        <div style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                             title="{{ $obs->texto }}">
                            {{ $obs->texto }}
                        </div>
                    </td>
                    <td class="text-center">
                        <form method="POST" action="{{ route('admin.observaciones.toggle-privada', $obs) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm {{ $obs->privada ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $obs->privada ? 'Privada — clic para hacer pública' : 'Pública — clic para privatizar' }}"
                                    style="padding:.15rem .4rem;font-size:.72rem;">
                                <i class="bi bi-{{ $obs->privada ? 'eye-slash' : 'eye' }}"></i>
                            </button>
                        </form>
                    </td>
                    <td class="text-muted small">
                        {{ $obs->created_at->format('d/m/Y') }}<br>
                        <span class="text-muted" style="font-size:.68rem;">{{ $obs->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.observaciones.destroy', $obs) }}"
                              onsubmit="return confirm('¿Eliminar esta observación?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar"
                                    style="padding:.15rem .4rem;font-size:.72rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($observaciones->hasPages())
        <div class="p-3">
            {{ $observaciones->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

</div>
@endsection
