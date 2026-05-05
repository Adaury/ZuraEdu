@extends('layouts.admin')
@section('page-title', 'Detalle del Evento')

@section('content')

{{-- Encabezado --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.eventos.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--primary)">
                <i class="bi bi-calendar-event-fill me-2"></i>{{ $evento->nombre }}
            </h4>
            <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">
                Detalle del evento e inscripciones
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.eventos.inscritos-pdf', $evento) }}" target="_blank"
           class="btn btn-danger btn-sm" style="border-radius:7px;">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF Inscritos
        </a>
        <a href="{{ route('admin.eventos.edit', $evento) }}"
           class="btn btn-outline-primary btn-sm" style="border-radius:7px;">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </div>
</div>

{{-- Alertas --}}
@foreach(['success','error','warning'] as $type)
    @if(session($type))
        @php $alertMap = ['success'=>'success','error'=>'danger','warning'=>'warning']; @endphp
        <div class="alert alert-{{ $alertMap[$type] }} mb-3" style="border-radius:10px;">{{ session($type) }}</div>
    @endif
@endforeach

@php
$tipoColors = [
    'academico' => ['bg'=>'#1d4ed8','label'=>'Académico'],
    'deportivo' => ['bg'=>'#15803d','label'=>'Deportivo'],
    'cultural'  => ['bg'=>'#7c3aed','label'=>'Cultural'],
    'social'    => ['bg'=>'#b45309','label'=>'Social'],
    'otro'      => ['bg'=>'#4b5563','label'=>'Otro'],
];
$tc = $tipoColors[$evento->tipo] ?? ['bg'=>'#4b5563','label'=>ucfirst($evento->tipo)];
$cuposDisp = $evento->cupo_maximo ? max(0, $evento->cupo_maximo - $evento->inscripciones_count) : null;
@endphp

<div class="row g-3 mb-4">

    {{-- Datos del evento --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="color:var(--primary);">
                    <i class="bi bi-info-circle me-2"></i>Información del Evento
                </h6>

                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted" style="font-size:.82rem;">Tipo</span>
                    <span class="badge rounded-pill" style="background:{{ $tc['bg'] }};color:#fff;font-size:.72rem;">
                        {{ $tc['label'] }}
                    </span>
                </div>
                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:.82rem;">Estado</span>
                    <span class="badge {{ $evento->activo ? 'bg-success' : 'bg-secondary' }}" style="font-size:.72rem;">
                        {{ $evento->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted" style="font-size:.82rem;">Fecha inicio</span>
                    <span class="fw-semibold" style="font-size:.83rem;">{{ $evento->fecha_inicio->format('d/m/Y') }}</span>
                </div>
                @if($evento->fecha_fin)
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted" style="font-size:.82rem;">Fecha fin</span>
                    <span class="fw-semibold" style="font-size:.83rem;">{{ $evento->fecha_fin->format('d/m/Y') }}</span>
                </div>
                @endif
                @if($evento->lugar)
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted" style="font-size:.82rem;">Lugar</span>
                    <span style="font-size:.83rem;">{{ $evento->lugar }}</span>
                </div>
                @endif
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted" style="font-size:.82rem;">Inscritos</span>
                    <span class="badge bg-info text-dark" style="font-size:.72rem;">{{ $evento->inscripciones_count }}</span>
                </div>
                @if($evento->cupo_maximo)
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted" style="font-size:.82rem;">Cupo disponible</span>
                    <span class="{{ $cuposDisp === 0 ? 'text-danger fw-bold' : 'text-success fw-semibold' }}" style="font-size:.83rem;">
                        {{ $cuposDisp }} / {{ $evento->cupo_maximo }}
                    </span>
                </div>
                @endif
                @if($evento->descripcion)
                <hr class="my-2">
                <p style="font-size:.82rem;color:#475569;line-height:1.5;">{{ $evento->descripcion }}</p>
                @endif

                {{-- Toggle activo --}}
                <hr class="my-2">
                <form method="POST" action="{{ route('admin.eventos.toggle', $evento) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                        class="btn btn-sm w-100 {{ $evento->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                        style="border-radius:7px;">
                        <i class="bi bi-{{ $evento->activo ? 'pause-circle' : 'play-circle' }} me-1"></i>
                        {{ $evento->activo ? 'Desactivar Evento' : 'Activar Evento' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Inscribir estudiantes --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-0 pt-3 pb-1">
                <h6 class="fw-bold mb-0" style="color:var(--primary);">
                    <i class="bi bi-person-plus-fill me-2"></i>Inscribir Estudiantes
                </h6>
            </div>
            <div class="card-body pt-2">
                <form method="POST" action="{{ route('admin.eventos.inscribir', $evento) }}">
                    @csrf

                    {{-- Inscripción masiva por grupo --}}
                    <div class="row g-2 mb-3">
                        <div class="col-sm-8">
                            <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">
                                <i class="bi bi-people me-1"></i>Inscribir grupo completo
                            </label>
                            <select name="grupo_id" class="form-select form-select-sm">
                                <option value="">— Seleccionar grupo —</option>
                                @foreach($grupos as $g)
                                    <option value="{{ $g->id }}">
                                        {{ $g->grado->nombre ?? '' }} {{ $g->seccion->nombre ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary w-100" style="border-radius:7px;">
                                <i class="bi bi-people-fill me-1"></i>Inscribir Grupo
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Búsqueda individual --}}
                <form method="GET" action="{{ route('admin.eventos.show', $evento) }}" class="mb-2">
                    <div class="input-group input-group-sm">
                        <input type="text" name="buscar" class="form-control" style="border-radius:7px 0 0 7px;"
                               placeholder="Buscar estudiante por nombre o matrícula..."
                               value="{{ request('buscar') }}">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-search"></i>
                        </button>
                        @if(request('buscar'))
                        <a href="{{ route('admin.eventos.show', $evento) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                        @endif
                    </div>
                </form>

                @if($estudiantesDisponibles->isNotEmpty())
                <form method="POST" action="{{ route('admin.eventos.inscribir', $evento) }}">
                    @csrf
                    <div class="table-responsive" style="max-height:220px;overflow-y:auto;">
                        <table class="table table-sm table-hover mb-2" style="font-size:.8rem;">
                            <thead style="position:sticky;top:0;background:#f8fafc;">
                                <tr>
                                    <th style="width:32px;">
                                        <input type="checkbox" id="chkAll" class="form-check-input"
                                               onchange="document.querySelectorAll('.chk-est').forEach(c=>c.checked=this.checked)">
                                    </th>
                                    <th>Nombre</th>
                                    <th>Matrícula</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($estudiantesDisponibles as $est)
                            <tr>
                                <td>
                                    <input type="checkbox" name="estudiante_ids[]" value="{{ $est->id }}"
                                           class="form-check-input chk-est">
                                </td>
                                <td>{{ $est->nombre_completo }}</td>
                                <td class="text-muted">{{ $est->numero_matricula ?? '—' }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success" style="border-radius:7px;">
                        <i class="bi bi-person-check me-1"></i>Inscribir Seleccionados
                    </button>
                </form>
                @elseif(request('buscar'))
                    <p class="text-muted" style="font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        No se encontraron estudiantes disponibles para "{{ request('buscar') }}".
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Lista de inscritos --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-list-check me-2"></i>Lista de Inscritos
            <span class="badge bg-info text-dark ms-2" style="font-size:.72rem;">{{ $inscripciones->count() }}</span>
        </h6>
        <a href="{{ route('admin.eventos.inscritos-pdf', $evento) }}" target="_blank"
           class="btn btn-sm btn-outline-danger" style="border-radius:7px;">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
    </div>
    <div class="card-body p-0">
        @if($inscripciones->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-slash" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
                Aún no hay estudiantes inscritos en este evento.
            </div>
        @else
        <form method="POST" action="{{ route('admin.eventos.asistencia', $evento) }}">
            @csrf @method('PATCH')
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                    <thead>
                        <tr style="background:var(--primary);color:#fff;">
                            <th class="ps-3 py-2">#</th>
                            <th>Nombre</th>
                            <th>Matrícula</th>
                            <th>Grupo</th>
                            <th>F. Inscripción</th>
                            <th class="text-center">Asistió</th>
                            <th class="text-center">Quitar</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($inscripciones as $i => $insc)
                    @php
                        $est = $insc->estudiante;
                        $matricula = $est?->matriculas->first();
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $est?->nombre_completo ?? '—' }}</td>
                        <td class="text-muted">{{ $est?->numero_matricula ?? '—' }}</td>
                        <td style="font-size:.78rem;">
                            @if($matricula?->grupo)
                                {{ $matricula->grupo->grado->nombre ?? '' }}
                                {{ $matricula->grupo->seccion->nombre ?? '' }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $insc->fecha_inscripcion?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-center">
                            <input type="checkbox"
                                   name="asistencias[{{ $insc->id }}]"
                                   value="1"
                                   class="form-check-input"
                                   @checked($insc->asistio)>
                        </td>
                        <td class="text-center">
                            <form method="POST"
                                  action="{{ route('admin.eventos.desinscribir', [$evento, $est]) }}"
                                  onsubmit="return confirm('¿Quitar a {{ addslashes($est?->nombre_completo ?? '') }} del evento?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="btn btn-xs btn-outline-danger"
                                    style="font-size:.7rem;padding:.15rem .4rem;border-radius:5px;" title="Desinscribir">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <button type="submit" class="btn btn-sm btn-primary" style="border-radius:7px;">
                    <i class="bi bi-save me-1"></i>Guardar Asistencia
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

@endsection
