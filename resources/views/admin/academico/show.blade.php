@extends('layouts.admin')

@section('page-title', 'Materias — ' . $grupo->nombre_completo)

@push('styles')
<style>
.ac-show-header { background:linear-gradient(135deg,#1e3a6e 0%,#2f5eb3 100%); color:#fff; border-radius:16px; padding:1.4rem 1.6rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.ac-show-title { font-size:2rem; font-weight:900; line-height:1; }
.ac-show-sub { font-size:.8rem; opacity:.75; margin-top:.3rem; }
.ac-stat-chips { display:flex; gap:10px; flex-wrap:wrap; }
.ac-chip { background:rgba(255,255,255,.18); border-radius:8px; padding:.3rem .8rem; font-size:.78rem; font-weight:700; white-space:nowrap; }
.materias-table { width:100%; border-collapse:collapse; }
.materias-table thead th { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:#6b7280; padding:.6rem .9rem; border-bottom:2px solid #e5e7eb; white-space:nowrap; }
.materias-table tbody tr { border-bottom:1px solid #f3f4f6; transition:background .15s; }
.materias-table tbody tr:hover { background:#f8faff; }
.materias-table td { padding:.65rem .9rem; vertical-align:middle; font-size:.875rem; }
.badge-area { font-size:.65rem; font-weight:800; padding:.2rem .5rem; border-radius:5px; text-transform:uppercase; letter-spacing:.05em; }
.badge-area.academica { background:#dbeafe; color:#1e40af; }
.badge-area.tecnica { background:#fef3c7; color:#92400e; }
.badge-tipo { font-size:.65rem; font-weight:700; padding:.2rem .5rem; border-radius:5px; background:#f3f4f6; color:#4b5563; }
.td-acciones { white-space:nowrap; display:flex; gap:5px; align-items:center; }
.btn-ic { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:7px; border:1.5px solid; cursor:pointer; transition:background .15s; }
.btn-ic.edit { border-color:#c7d4e8; color:#1e3a6e; background:#fff; }
.btn-ic.edit:hover { background:#eef2ff; }
.btn-ic.del { border-color:#fecaca; color:#dc2626; background:#fff; }
.btn-ic.del:hover { background:#fee2e2; }
.btn-ic.tog { border-color:#d1fae5; color:#047857; background:#fff; }
.btn-ic.tog-off { border-color:#fde68a; color:#92400e; background:#fff; }
.btn-ic.tog:hover, .btn-ic.tog-off:hover { background:#f0fdf4; }
.add-materia-card { background:#fff; border:1.5px dashed #c7d4e8; border-radius:14px; padding:1.25rem 1.4rem; margin-top:1.2rem; }
.add-materia-card h6 { font-size:.8rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:#6b7280; margin-bottom:1rem; }
.docente-pill { font-size:.78rem; color:#4b5563; display:flex; align-items:center; gap:4px; }
.materias-section { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; }
.materias-section-hdr { padding:.85rem 1.2rem; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
.materias-section-hdr h5 { font-size:.9rem; font-weight:800; color:#1e3a6e; margin:0; }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Breadcrumb --}}
    <nav class="mb-3" style="font-size:.82rem;">
        <a href="{{ route('admin.academico.index') }}" class="text-decoration-none text-primary fw-semibold">
            <i class="bi bi-calendar3 me-1"></i>Cursos
        </a>
        <span class="text-muted mx-1">/</span>
        <span class="text-muted">{{ $grupo->nombre_completo }}</span>
    </nav>

    {{-- Header del curso --}}
    <div class="ac-show-header">
        <div style="flex:1">
            <div class="ac-show-title">{{ $grupo->nombre_completo }}</div>
            <div class="ac-show-sub">
                {{ $grupo->schoolYear?->nombre }}
                @if($grupo->aula) &mdash; Aula {{ $grupo->aula }} @endif
                @if($grupo->capacidad) &mdash; Cap. {{ $grupo->capacidad }} estudiantes @endif
            </div>
        </div>
        <div class="ac-stat-chips">
            <span class="ac-chip"><i class="bi bi-book me-1"></i>{{ $asignaciones->count() }} materias</span>
            <span class="ac-chip"><i class="bi bi-person-badge me-1"></i>{{ $asignaciones->whereNotNull('docente')->count() }} con docente</span>
            <span class="ac-chip {{ $grupo->activo ? '' : 'bg-warning text-dark' }}">
                <i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i>{{ $grupo->activo ? 'Activo' : 'Inactivo' }}
            </span>
        </div>

        {{-- Editar / eliminar curso --}}
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditCurso">
                <i class="bi bi-pencil me-1"></i>Editar
            </button>
            <form method="POST" action="{{ route('admin.academico.cursos.destroy', $grupo) }}"
                  onsubmit="return confirm('¿Eliminar este curso y todas sus materias?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabla de materias --}}
    <div class="materias-section mb-3">
        <div class="materias-section-hdr">
            <h5><i class="bi bi-book-fill me-2" style="color:#3b82f6"></i>Materias del Curso</h5>
            <span class="badge bg-primary-subtle text-primary fw-bold">{{ $asignaciones->count() }} total</span>
        </div>

        @if($asignaciones->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-book" style="font-size:2rem;opacity:.3"></i>
                <p class="mt-1 mb-0 small">Sin materias asignadas.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="materias-table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Docente</th>
                        <th>Área</th>
                        <th>Evaluación</th>
                        <th>Hrs/Sem</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($asignaciones as $asig)
                    <tr class="{{ $asig->activo ? '' : 'opacity-50' }}">
                        <td>
                            <span class="fw-700" style="color:#1e3a6e;font-weight:700;">
                                {{ $asig->asignatura?->nombre ?? '—' }}
                            </span>
                            @if($asig->asignatura?->es_basica)
                                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.6rem">básica</span>
                            @endif
                        </td>
                        <td>
                            @if($asig->docente)
                                <span class="docente-pill">
                                    <i class="bi bi-person-circle" style="color:#3b82f6"></i>
                                    {{ $asig->docente->apellidos }}, {{ Str::limit($asig->docente->nombres ?? '', 12) }}
                                </span>
                            @else
                                <span class="text-muted small">Sin asignar</span>
                            @endif
                        </td>
                        <td><span class="badge-area {{ $asig->area }}">{{ ucfirst($asig->area) }}</span></td>
                        <td><span class="badge-tipo">{{ $tiposEval[$asig->tipo_evaluacion] ?? $asig->tipo_evaluacion }}</span></td>
                        <td class="text-center">{{ $asig->horas_semana ?? '—' }}</td>
                        <td>
                            @if($asig->activo)
                                <span class="badge bg-success-subtle text-success fw-bold" style="font-size:.65rem">Activa</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning fw-bold" style="font-size:.65rem">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="td-acciones">
                                {{-- Editar --}}
                                <button class="btn-ic edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditMateria{{ $asig->id }}"
                                    title="Editar">
                                    <i class="bi bi-pencil" style="font-size:.75rem"></i>
                                </button>

                                {{-- Toggle activo --}}
                                <form method="POST" action="{{ route('admin.academico.materias.toggle', $asig) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-ic {{ $asig->activo ? 'tog' : 'tog-off' }}" title="{{ $asig->activo ? 'Desactivar' : 'Activar' }}">
                                        <i class="bi bi-{{ $asig->activo ? 'toggle-on' : 'toggle-off' }}" style="font-size:.85rem"></i>
                                    </button>
                                </form>

                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('admin.academico.materias.destroy', $asig) }}"
                                      onsubmit="return confirm('¿Eliminar esta materia?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-ic del" title="Eliminar">
                                        <i class="bi bi-trash" style="font-size:.75rem"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Agregar materia --}}
    @if($asignaturasDisponibles->isNotEmpty())
    <div class="add-materia-card">
        <h6><i class="bi bi-plus-circle me-2"></i>Agregar Materia</h6>
        <form method="POST" action="{{ route('admin.academico.materias.store', $grupo) }}" class="row g-2">
            @csrf
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" style="font-size:.78rem">Asignatura <span class="text-danger">*</span></label>
                <select name="asignatura_id" class="form-select form-select-sm" required>
                    <option value="">Seleccionar…</option>
                    @foreach($asignaturasDisponibles as $asig)
                        <option value="{{ $asig->id }}">{{ $asig->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold" style="font-size:.78rem">Docente</label>
                <select name="docente_id" class="form-select form-select-sm">
                    <option value="">Sin asignar</option>
                    @foreach($docentes as $doc)
                        <option value="{{ $doc->id }}">{{ $doc->apellidos }}, {{ Str::limit($doc->nombres ?? '', 15) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold" style="font-size:.78rem">Área <span class="text-danger">*</span></label>
                <select name="area" class="form-select form-select-sm" required>
                    <option value="academica">Académica</option>
                    <option value="tecnica">Técnica</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold" style="font-size:.78rem">Evaluación <span class="text-danger">*</span></label>
                <select name="tipo_evaluacion" class="form-select form-select-sm" required>
                    @foreach($tiposEval as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label fw-semibold" style="font-size:.78rem">Hrs/Sem</label>
                <input type="number" name="horas_semana" class="form-control form-control-sm" min="1" max="40" placeholder="4">
            </div>
            <div class="col-8 col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm fw-bold w-100">
                    <i class="bi bi-plus-lg"></i> Agregar
                </button>
            </div>
        </form>
    </div>
    @else
        <p class="text-muted small text-center mt-2">
            <i class="bi bi-check-circle-fill text-success me-1"></i>
            Todas las asignaturas disponibles ya están asignadas a este curso.
        </p>
    @endif

</div>

{{-- Modales editar materia --}}
@foreach($asignaciones as $asig)
<div class="modal fade" id="modalEditMateria{{ $asig->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.academico.materias.update', $asig) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="font-size:.95rem">
                        <i class="bi bi-pencil me-2"></i>{{ $asig->asignatura?->nombre }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Docente</label>
                        <select name="docente_id" class="form-select">
                            <option value="">Sin asignar</option>
                            @foreach($docentes as $doc)
                                <option value="{{ $doc->id }}" @selected($asig->docente_id === $doc->id)>
                                    {{ $doc->apellidos }}, {{ $doc->nombres }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Área</label>
                            <select name="area" class="form-select" required>
                                <option value="academica" @selected($asig->area === 'academica')>Académica</option>
                                <option value="tecnica"   @selected($asig->area === 'tecnica')>Técnica</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hrs/Semana</label>
                            <input type="number" name="horas_semana" class="form-control" min="1" max="40" value="{{ $asig->horas_semana }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Tipo de Evaluación</label>
                        <select name="tipo_evaluacion" class="form-select" required>
                            @foreach($tiposEval as $val => $lbl)
                                <option value="{{ $val }}" @selected($asig->tipo_evaluacion === $val)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Modal editar curso --}}
<div class="modal fade" id="modalEditCurso" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.academico.cursos.update', $grupo) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar Curso — {{ $grupo->nombre_completo }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Aula</label>
                            <input type="text" name="aula" class="form-control" value="{{ $grupo->aula }}" placeholder="Ej: Aula 3B">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Capacidad</label>
                            <input type="number" name="capacidad" class="form-control" min="1" max="60" value="{{ $grupo->capacidad }}" placeholder="35">
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" class="form-check-input" id="ckActivo" @checked($grupo->activo)>
                        <label class="form-check-label fw-semibold" for="ckActivo">Curso activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
