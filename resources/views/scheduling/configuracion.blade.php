@extends('layouts.admin')
@section('page-title', 'Configuración de Horarios')

@section('content')

@foreach(['success','error'] as $t)
@if(session($t))
<div class="alert alert-{{ $t === 'error' ? 'danger' : $t }} alert-dismissible fade show" style="border-radius:12px;">
    {{ session($t) }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@endforeach

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('scheduling.horarios.index') }}" class="text-muted text-decoration-none" style="font-size:.82rem;">
        <i class="bi bi-arrow-left"></i> Horarios
    </a>
    <h5 class="mb-0 fw-bold">Configuración del Sistema</h5>
</div>

<div class="row g-4">

    {{-- ── FRANJAS ─────────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius:16px 16px 0 0;">
                <strong style="font-size:.9rem;"><i class="bi bi-clock me-2" style="color:#6366f1;"></i>Franjas Horarias</strong>
                <button class="btn btn-sm btn-primary" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalFranja">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8faff;"><tr><th class="ps-4">N°</th><th>Hora</th><th>Nombre</th><th class="text-center">Recreo</th><th></th></tr></thead>
                    <tbody>
                    @forelse($franjas as $f)
                    <tr>
                        <td class="ps-4">{{ $f->numero }}</td>
                        <td>{{ $f->hora_inicio }} – {{ $f->hora_fin }}</td>
                        <td>{{ $f->nombre ?? '—' }}</td>
                        <td class="text-center">
                            @if($f->es_recreo)<i class="bi bi-check-circle-fill text-success"></i>@else<span class="text-muted">—</span>@endif
                        </td>
                        <td>
                            <form action="{{ route('scheduling.franjas.destroy', $f) }}" method="POST" onsubmit="return confirm('¿Eliminar franja?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs text-danger" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.8rem;">Sin franjas configuradas</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── AULAS ───────────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius:16px 16px 0 0;">
                <strong style="font-size:.9rem;"><i class="bi bi-door-open me-2" style="color:#0891b2;"></i>Aulas</strong>
                <button class="btn btn-sm btn-primary" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalAula">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8faff;"><tr><th class="ps-4">Nombre</th><th>Cap.</th><th>Tipo</th><th></th></tr></thead>
                    <tbody>
                    @forelse($aulas as $a)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $a->nombre }}</td>
                        <td>{{ $a->capacidad }}</td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary" style="border-radius:20px;font-size:.7rem;">{{ $a->tipo }}</span></td>
                        <td>
                            <form action="{{ route('scheduling.aulas.destroy', $a) }}" method="POST" onsubmit="return confirm('¿Eliminar aula?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs text-danger" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3" style="font-size:.8rem;">Sin aulas registradas</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── CURSOS ──────────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius:16px 16px 0 0;">
                <strong style="font-size:.9rem;"><i class="bi bi-grid me-2" style="color:#f59e0b;"></i>Cursos</strong>
                <button class="btn btn-sm btn-primary" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalCurso">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8faff;"><tr><th class="ps-4">Nombre</th><th>Grado</th><th>Sección</th><th>Cap.</th><th></th></tr></thead>
                    <tbody>
                    @forelse($cursos as $c)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $c->nombre }}</td>
                        <td>{{ $c->grado }}</td>
                        <td>{{ $c->seccion ?? '—' }}</td>
                        <td>{{ $c->capacidad }}</td>
                        <td>
                            <form action="{{ route('scheduling.cursos.destroy', $c) }}" method="POST" onsubmit="return confirm('¿Eliminar curso?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs text-danger" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.8rem;">Sin cursos registrados</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── MATERIAS ────────────────────────────────────────────────── --}}
    <div class="col-lg-6">
        <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius:16px 16px 0 0;">
                <strong style="font-size:.9rem;"><i class="bi bi-book me-2" style="color:#10b981;"></i>Materias</strong>
                <button class="btn btn-sm btn-primary" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalMateria">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8faff;"><tr><th class="ps-4">Nombre</th><th class="text-center">Hrs/semana</th><th class="text-center">Color</th><th></th></tr></thead>
                    <tbody>
                    @forelse($materias as $m)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $m->nombre }}</td>
                        <td class="text-center">{{ $m->horas_semana }}</td>
                        <td class="text-center">
                            <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $m->color }};border:1px solid rgba(0,0,0,.1);"></span>
                        </td>
                        <td>
                            <form action="{{ route('scheduling.materias.destroy', $m) }}" method="POST" onsubmit="return confirm('¿Eliminar materia?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs text-danger" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3" style="font-size:.8rem;">Sin materias registradas</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── PROFESORES ──────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius:16px 16px 0 0;">
                <strong style="font-size:.9rem;"><i class="bi bi-person-badge me-2" style="color:#8b5cf6;"></i>Profesores</strong>
                <button class="btn btn-sm btn-primary" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalProfesor">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8faff;"><tr><th class="ps-4">Nombre</th><th>Email</th><th>Especialidad</th><th></th></tr></thead>
                    <tbody>
                    @forelse($profesores as $p)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $p->nombre_completo }}</td>
                        <td style="color:#64748b;">{{ $p->email ?? '—' }}</td>
                        <td>{{ $p->especialidad ?? '—' }}</td>
                        <td>
                            <form action="{{ route('scheduling.profesores.destroy', $p) }}" method="POST" onsubmit="return confirm('¿Eliminar profesor?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs text-danger" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3" style="font-size:.8rem;">Sin profesores registrados</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── ASIGNACIONES ────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card border-0" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius:16px 16px 0 0;">
                <div>
                    <strong style="font-size:.9rem;"><i class="bi bi-diagram-3 me-2" style="color:#ef4444;"></i>Asignaciones</strong>
                    <div style="font-size:.75rem;color:#64748b;margin-top:2px;">Qué materia enseña cada profesor en cada curso y cuántas horas/semana</div>
                </div>
                <button class="btn btn-sm btn-primary" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalAsignacion">
                    <i class="bi bi-plus"></i> Nueva
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.82rem;">
                    <thead style="background:#f8faff;"><tr><th class="ps-4">Materia</th><th>Profesor</th><th>Curso</th><th class="text-center">Hrs/sem</th><th></th></tr></thead>
                    <tbody>
                    @forelse($asignaciones as $a)
                    <tr>
                        <td class="ps-4">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $a->materia->color }};margin-right:6px;"></span>
                            <strong>{{ $a->materia->nombre }}</strong>
                        </td>
                        <td>{{ $a->profesor->nombre_completo }}</td>
                        <td>{{ $a->curso->nombre }}</td>
                        <td class="text-center">{{ $a->horas_semana }}</td>
                        <td>
                            <form action="{{ route('scheduling.asignaciones.destroy', $a) }}" method="POST" onsubmit="return confirm('¿Eliminar asignación?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs text-danger" style="font-size:.72rem;"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.8rem;">Sin asignaciones. Agrega al menos una para poder generar el horario.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>{{-- /row --}}

{{-- ══ MODALES ═══════════════════════════════════════════════════════ --}}

{{-- Modal Franja --}}
<div class="modal fade" id="modalFranja" tabindex="-1">
    <div class="modal-dialog"><form action="{{ route('scheduling.franjas.store') }}" method="POST">@csrf
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Nueva Franja</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-4"><label class="form-label fw-semibold" style="font-size:.82rem;">N°</label><input type="number" name="numero" class="form-control form-control-sm" required min="1"></div>
                    <div class="col-4"><label class="form-label fw-semibold" style="font-size:.82rem;">Inicio</label><input type="time" name="hora_inicio" class="form-control form-control-sm" required></div>
                    <div class="col-4"><label class="form-label fw-semibold" style="font-size:.82rem;">Fin</label><input type="time" name="hora_fin" class="form-control form-control-sm" required></div>
                    <div class="col-8"><label class="form-label fw-semibold" style="font-size:.82rem;">Nombre (opcional)</label><input type="text" name="nombre" class="form-control form-control-sm" placeholder="1ra hora, Recreo…"></div>
                    <div class="col-4 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="es_recreo" class="form-check-input" value="1" id="chkRecreo"><label class="form-check-label" for="chkRecreo" style="font-size:.82rem;">Es recreo</label></div></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
        </div>
    </form></div>
</div>

{{-- Modal Aula --}}
<div class="modal fade" id="modalAula" tabindex="-1">
    <div class="modal-dialog"><form action="{{ route('scheduling.aulas.store') }}" method="POST">@csrf
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Nueva Aula</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-8"><label class="form-label fw-semibold" style="font-size:.82rem;">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required></div>
                    <div class="col-4"><label class="form-label fw-semibold" style="font-size:.82rem;">Capacidad</label><input type="number" name="capacidad" class="form-control form-control-sm" required min="1" value="30"></div>
                    <div class="col-12"><label class="form-label fw-semibold" style="font-size:.82rem;">Tipo</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="aula">Aula</option><option value="laboratorio">Laboratorio</option><option value="taller">Taller</option><option value="gimnasio">Gimnasio</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
        </div>
    </form></div>
</div>

{{-- Modal Curso --}}
<div class="modal fade" id="modalCurso" tabindex="-1">
    <div class="modal-dialog"><form action="{{ route('scheduling.cursos.store') }}" method="POST">@csrf
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Nuevo Curso</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required placeholder="1ro A"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Grado</label><input type="text" name="grado" class="form-control form-control-sm" required placeholder="Primer Año"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Sección</label><input type="text" name="seccion" class="form-control form-control-sm" placeholder="A"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Capacidad</label><input type="number" name="capacidad" class="form-control form-control-sm" value="30" min="1"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
        </div>
    </form></div>
</div>

{{-- Modal Materia --}}
<div class="modal fade" id="modalMateria" tabindex="-1">
    <div class="modal-dialog"><form action="{{ route('scheduling.materias.store') }}" method="POST">@csrf
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Nueva Materia</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-8"><label class="form-label fw-semibold" style="font-size:.82rem;">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required></div>
                    <div class="col-4"><label class="form-label fw-semibold" style="font-size:.82rem;">Hrs/semana</label><input type="number" name="horas_semana" class="form-control form-control-sm" required min="1" max="10" value="4"></div>
                    <div class="col-4"><label class="form-label fw-semibold" style="font-size:.82rem;">Color</label><input type="color" name="color" class="form-control form-control-sm form-control-color" value="#3b82f6" style="height:34px;"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
        </div>
    </form></div>
</div>

{{-- Modal Profesor --}}
<div class="modal fade" id="modalProfesor" tabindex="-1">
    <div class="modal-dialog"><form action="{{ route('scheduling.profesores.store') }}" method="POST">@csrf
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Nuevo Profesor</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Apellidos</label><input type="text" name="apellidos" class="form-control form-control-sm" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Email</label><input type="email" name="email" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:.82rem;">Especialidad</label><input type="text" name="especialidad" class="form-control form-control-sm"></div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
        </div>
    </form></div>
</div>

{{-- Modal Asignación --}}
<div class="modal fade" id="modalAsignacion" tabindex="-1">
    <div class="modal-dialog"><form action="{{ route('scheduling.asignaciones.store') }}" method="POST">@csrf
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Nueva Asignación</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Materia</label>
                        <select name="materia_id" class="form-select form-select-sm" required>
                            <option value="">Seleccionar…</option>
                            @foreach($materias as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Profesor</label>
                        <select name="profesor_id" class="form-select form-select-sm" required>
                            <option value="">Seleccionar…</option>
                            @foreach($profesores as $p)<option value="{{ $p->id }}">{{ $p->nombre_completo }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Curso</label>
                        <select name="curso_id" class="form-select form-select-sm" required>
                            <option value="">Seleccionar…</option>
                            @foreach($cursos as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">Hrs/semana</label>
                        <input type="number" name="horas_semana" class="form-control form-control-sm" required min="1" max="10" value="4">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary btn-sm">Guardar</button></div>
        </div>
    </form></div>
</div>

@endsection
