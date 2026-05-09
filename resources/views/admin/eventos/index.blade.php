@extends('layouts.admin')
@section('page-title', 'Eventos Extracurriculares')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-calendar-event-fill me-2"></i>Eventos Extracurriculares
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Gestión de eventos académicos, deportivos, culturales y sociales</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.eventos.lista-pdf', request()->query()) }}"
           class="btn btn-outline-danger" style="border-radius:8px;">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.eventos.lista-excel', request()->query()) }}"
           class="btn btn-outline-success" style="border-radius:8px;">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.eventos.create') }}" class="btn btn-primary" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Evento
        </a>
    </div>
</div>

{{-- Alertas de sesión --}}
@foreach(['success','error','warning'] as $type)
    @if(session($type))
        @php $alertMap = ['success'=>'success','error'=>'danger','warning'=>'warning']; @endphp
        <div class="alert alert-{{ $alertMap[$type] }} mb-3" style="border-radius:10px;">{{ session($type) }}</div>
    @endif
@endforeach

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.eventos.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Buscar</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Nombre del evento..."
                       value="{{ request('q') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="academico"  @selected(request('tipo')=='academico')>Académico</option>
                    <option value="deportivo"  @selected(request('tipo')=='deportivo')>Deportivo</option>
                    <option value="cultural"   @selected(request('tipo')=='cultural')>Cultural</option>
                    <option value="social"     @selected(request('tipo')=='social')>Social</option>
                    <option value="otro"       @selected(request('tipo')=='otro')>Otro</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm"
                       value="{{ request('fecha_desde') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                       value="{{ request('fecha_hasta') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">Estado</label>
                <select name="activo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('activo')==='1')>Activos</option>
                    <option value="0" @selected(request('activo')==='0')>Inactivos</option>
                </select>
            </div>
            <div class="col-sm-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm" title="Filtrar">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('admin.eventos.index') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar">
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
                        <th class="ps-3 py-2">Evento</th>
                        <th>Tipo</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Lugar</th>
                        <th class="text-center">Inscritos</th>
                        <th class="text-center">Cupo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($eventos as $ev)
                @php
                    $tipoColors = [
                        'academico' => ['bg'=>'#1d4ed8','label'=>'Académico'],
                        'deportivo' => ['bg'=>'#15803d','label'=>'Deportivo'],
                        'cultural'  => ['bg'=>'#7c3aed','label'=>'Cultural'],
                        'social'    => ['bg'=>'#b45309','label'=>'Social'],
                        'otro'      => ['bg'=>'#4b5563','label'=>'Otro'],
                    ];
                    $tc = $tipoColors[$ev->tipo] ?? ['bg'=>'#4b5563','label'=>ucfirst($ev->tipo)];
                @endphp
                <tr>
                    <td class="ps-3 fw-semibold py-2">
                        <a href="{{ route('admin.eventos.show', $ev) }}" class="text-decoration-none text-dark">
                            {{ $ev->nombre }}
                        </a>
                        @if($ev->descripcion)
                            <div class="text-muted" style="font-size:.75rem;font-weight:400;">
                                {{ \Illuminate\Support\Str::limit($ev->descripcion, 60) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge rounded-pill" style="background:{{ $tc['bg'] }};color:#fff;font-size:.7rem;">
                            {{ $tc['label'] }}
                        </span>
                    </td>
                    <td>{{ $ev->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $ev->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $ev->lugar ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-info text-dark" style="font-size:.72rem;">
                            {{ $ev->inscripciones_count }}
                        </span>
                    </td>
                    <td class="text-center" style="font-size:.78rem;">
                        @if($ev->cupo_maximo)
                            @php $disp = max(0, $ev->cupo_maximo - $ev->inscripciones_count); @endphp
                            <span class="{{ $disp === 0 ? 'text-danger fw-bold' : 'text-success fw-semibold' }}">
                                {{ $disp }} / {{ $ev->cupo_maximo }}
                            </span>
                        @else
                            <span class="text-muted">Sin límite</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($ev->activo)
                            <span class="badge bg-success" style="font-size:.7rem;">Activo</span>
                        @else
                            <span class="badge bg-secondary" style="font-size:.7rem;">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            {{-- Ver inscritos --}}
                            <a href="{{ route('admin.eventos.show', $ev) }}"
                               class="btn btn-xs btn-outline-info"
                               style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            {{-- Editar --}}
                            <a href="{{ route('admin.eventos.edit', $ev) }}"
                               class="btn btn-xs btn-outline-primary"
                               style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            {{-- Toggle activo --}}
                            <form method="POST" action="{{ route('admin.eventos.toggle', $ev) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="btn btn-xs {{ $ev->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;"
                                    title="{{ $ev->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="bi bi-{{ $ev->activo ? 'pause-circle' : 'play-circle' }}"></i>
                                </button>
                            </form>
                            {{-- Eliminar --}}
                            <form method="POST" action="{{ route('admin.eventos.destroy', $ev) }}" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este evento? Se perderán todas las inscripciones.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="btn btn-xs btn-outline-danger"
                                    style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;" title="Eliminar">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
                        No se encontraron eventos con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $eventos->links() }}</div>
@endsection
